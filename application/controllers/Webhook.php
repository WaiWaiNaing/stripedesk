<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stripe checkout.session.completed fulfillment (idempotent).
 * Use Webhook::_fulfill_order($ci, $session) from services; HTTP /webhook is not supported.
 */
class Webhook extends CI_Controller
{
    public function index()
    {
        show_404();
    }

    /**
     * @param object $ci      CodeIgniter instance
     * @param object $session Stripe Checkout Session (webhook or API object)
     *
     * @return string ok|skipped_paid|no_order_id|order_not_found
     */
    public static function _fulfill_order($ci, $session)
    {
        $ci->load->model('order_model');
        $ci->load->model('cart_model');
        $ci->load->model('invoice_model');
        $ci->load->model('receipt_model');

        $metadata = isset($session->metadata) ? $session->metadata : null;
        $order_id = 0;
        $cart_id = 0;
        $meta_user_id = 0;
        if ($metadata)
        {
            if (isset($metadata->order_id))
            {
                $order_id = (int) $metadata->order_id;
            }
            if (isset($metadata->cart_id))
            {
                $cart_id = (int) $metadata->cart_id;
            }
            if (isset($metadata->user_id))
            {
                $meta_user_id = (int) $metadata->user_id;
            }
        }

        if ($order_id < 1)
        {
            return 'no_order_id';
        }

        $order = $ci->order_model->find($order_id);
        if ( ! $order)
        {
            return 'order_not_found';
        }

        if ((string) $order->status === 'paid')
        {
            return 'skipped_paid';
        }

        $payment_intent_id = null;
        if (isset($session->payment_intent) && $session->payment_intent !== null && $session->payment_intent !== '')
        {
            $pi = $session->payment_intent;
            if (is_object($pi) && isset($pi->id))
            {
                $payment_intent_id = (string) $pi->id;
            }
            else
            {
                $payment_intent_id = (string) $pi;
            }
            if ($payment_intent_id === '')
            {
                $payment_intent_id = null;
            }
        }

        $order_update = array('status' => 'paid');
        if ($payment_intent_id !== null)
        {
            $order_update['stripe_payment_intent'] = $payment_intent_id;
        }
        $ci->order_model->update_row($order_id, $order_update);

        if ($cart_id > 0)
        {
            $cart = $ci->cart_model->find($cart_id);
            if ($cart && ($meta_user_id < 1 || (int) $cart->user_id === (int) $meta_user_id))
            {
                $ci->cart_model->update_row($cart_id, array(
                    'status' => 'converted',
                    'updated_by' => $meta_user_id > 0 ? (int) $meta_user_id : null,
                ));
            }
        }

        $currency_id = (int) $order->currency_id > 0 ? (int) $order->currency_id : 1;

        $invoice = $ci->invoice_model->get_by_order_id($order_id);
        if ( ! $invoice)
        {
            $invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $order_id, 5, '0', STR_PAD_LEFT);
            $invoice_id = $ci->invoice_model->create(array(
                'order_id' => $order_id,
                'currency_id' => $currency_id,
                'invoice_number' => $invoice_number,
                'total_amount' => (string) $order->total_amount,
                'status' => 'paid',
            ));
            $invoice = $invoice_id ? $ci->invoice_model->find($invoice_id) : null;
        }
        else
        {
            $ci->invoice_model->update_row((int) $invoice->id, array(
                'status' => 'paid',
                'currency_id' => $currency_id,
            ));
        }

        if ($invoice)
        {
            $receipt = $ci->receipt_model->get_by_invoice_id((int) $invoice->id);
            if ( ! $receipt)
            {
                $receipt_number = 'RCP-' . date('Y') . '-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);
                $payment_intent = $payment_intent_id;
                $amount_total = isset($session->amount_total) ? ((int) $session->amount_total) : null;
                $amount_paid = $amount_total !== null ? number_format(((float) $amount_total) / 100, 2, '.', '') : (string) $order->total_amount;
                $ci->receipt_model->create(array(
                    'invoice_id' => (int) $invoice->id,
                    'currency_id' => $currency_id,
                    'receipt_number' => $receipt_number,
                    'stripe_payment_intent' => $payment_intent,
                    'amount_paid' => $amount_paid,
                    'paid_at' => date('Y-m-d H:i:s'),
                ));
            }
        }

        return 'ok';
    }
}
