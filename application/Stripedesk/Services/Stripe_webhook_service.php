<?php

namespace Stripedesk\Services;

final class Stripe_webhook_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->config->load('stripe', true);
        $this->ci->load->model('order_model');
        $this->ci->load->model('invoice_model');
        $this->ci->load->model('receipt_model');
        $this->ci->load->model('stripe_log_model');
    }

    public function handle_raw_webhook($payload, $sig_header)
    {
        $secret = $this->ci->config->item('stripe_webhook_secret', 'stripe');

        try
        {
            if ($secret)
            {
                $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
            }
            else
            {
                $event = json_decode($payload);
                if ( ! $event)
                {
                    return array(false, 400, 'invalid_json');
                }
            }
        }
        catch (\Exception $e)
        {
            return array(false, 400, 'invalid_signature');
        }

        $type = isset($event->type) ? (string) $event->type : 'unknown';
        $this->ci->stripe_log_model->log_event($type, $event);

        if ($type === 'checkout.session.completed')
        {
            $session = $event->data->object;
            $metadata = isset($session->metadata) ? $session->metadata : null;
            $order_id = $metadata && isset($metadata->order_id) ? (int) $metadata->order_id : 0;
            if ($order_id < 1)
            {
                return array(true, 200, 'no_order_id');
            }

            $order = $this->ci->order_model->find($order_id);
            if ( ! $order)
            {
                return array(true, 200, 'order_not_found');
            }

            $this->ci->order_model->update_row($order_id, array(
                'status' => 'paid',
            ));

            $invoice = $this->ci->invoice_model->get_by_order_id($order_id);
            if ( ! $invoice)
            {
                $invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $order_id, 5, '0', STR_PAD_LEFT);
                $invoice_id = $this->ci->invoice_model->create(array(
                    'order_id' => $order_id,
                    'invoice_number' => $invoice_number,
                    'total_amount' => (string) $order->total_amount,
                    'status' => 'paid',
                ));
                $invoice = $invoice_id ? $this->ci->invoice_model->find($invoice_id) : null;
            }
            else
            {
                $this->ci->invoice_model->update_row((int) $invoice->id, array('status' => 'paid'));
            }

            if ($invoice)
            {
                $receipt = $this->ci->receipt_model->get_by_invoice_id((int) $invoice->id);
                if ( ! $receipt)
                {
                    $receipt_number = 'RCP-' . date('Y') . '-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);
                    $payment_intent = isset($session->payment_intent) ? (string) $session->payment_intent : null;
                    $amount_total = isset($session->amount_total) ? ((int) $session->amount_total) : null;
                    $amount_paid = $amount_total !== null ? number_format(((float) $amount_total) / 100, 2, '.', '') : (string) $order->total_amount;
                    $this->ci->receipt_model->create(array(
                        'invoice_id' => (int) $invoice->id,
                        'receipt_number' => $receipt_number,
                        'stripe_payment_intent' => $payment_intent,
                        'amount_paid' => $amount_paid,
                        'paid_at' => date('Y-m-d H:i:s'),
                    ));
                }
            }
        }
        elseif ($type === 'checkout.session.expired')
        {
            $session = $event->data->object;
            $metadata = isset($session->metadata) ? $session->metadata : null;
            $order_id = $metadata && isset($metadata->order_id) ? (int) $metadata->order_id : 0;
            if ($order_id > 0)
            {
                $this->ci->order_model->update_row($order_id, array('status' => 'cancelled'));
            }
        }

        return array(true, 200, 'ok');
    }
}

