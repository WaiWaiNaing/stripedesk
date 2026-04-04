<?php

namespace Stripedesk\Services;

/**
 * Idempotent fulfillment for Stripe Checkout (webhook + API reconcile + cron).
 * Mirrors checkout.session.completed handling.
 */
final class Checkout_session_fulfillment_service
{
    /**
     * @param object $session Stripe Checkout\Session or decoded webhook object
     *
     * @return string Reason code for logs: ok|skipped_paid|no_order_id|order_not_found
     */
    public static function apply($ci, $session)
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

        $ci->order_model->update_row($order_id, array(
            'status' => 'paid',
        ));

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

        $invoice = $ci->invoice_model->get_by_order_id($order_id);
        if ( ! $invoice)
        {
            $invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $order_id, 5, '0', STR_PAD_LEFT);
            $invoice_id = $ci->invoice_model->create(array(
                'order_id' => $order_id,
                'invoice_number' => $invoice_number,
                'total_amount' => (string) $order->total_amount,
                'status' => 'paid',
            ));
            $invoice = $invoice_id ? $ci->invoice_model->find($invoice_id) : null;
        }
        else
        {
            $ci->invoice_model->update_row((int) $invoice->id, array('status' => 'paid'));
        }

        if ($invoice)
        {
            $receipt = $ci->receipt_model->get_by_invoice_id((int) $invoice->id);
            if ( ! $receipt)
            {
                $receipt_number = 'RCP-' . date('Y') . '-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);
                $payment_intent = isset($session->payment_intent) ? (string) $session->payment_intent : null;
                $amount_total = isset($session->amount_total) ? ((int) $session->amount_total) : null;
                $amount_paid = $amount_total !== null ? number_format(((float) $amount_total) / 100, 2, '.', '') : (string) $order->total_amount;
                $ci->receipt_model->create(array(
                    'invoice_id' => (int) $invoice->id,
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
