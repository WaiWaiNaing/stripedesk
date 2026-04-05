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
        $this->ci->load->model('cart_model');
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
        $event_id = isset($event->id) ? (string) $event->id : '';

        $reserved = $this->ci->stripe_log_model->reserve_webhook_event($event_id, $type, $event);
        if ($reserved['action'] === 'skip_processed')
        {
            return array(true, 200, 'duplicate_event');
        }
        $log_id = isset($reserved['log_id']) ? (int) $reserved['log_id'] : 0;

        try
        {
            if ($type === 'checkout.session.completed')
            {
                require_once APPPATH . 'controllers/Webhook.php';
                $session = $event->data->object;
                $reason = \Webhook::_fulfill_order($this->ci, $session);
                if ($reason === 'no_order_id')
                {
                    return array(true, 200, 'no_order_id');
                }
                if ($reason === 'order_not_found')
                {
                    return array(true, 200, 'order_not_found');
                }
            }
            elseif ($type === 'checkout.session.expired')
            {
                $session = $event->data->object;
                $metadata = isset($session->metadata) ? $session->metadata : null;
                $order_id = $metadata && isset($metadata->order_id) ? (int) $metadata->order_id : 0;
                $cart_id = $metadata && isset($metadata->cart_id) ? (int) $metadata->cart_id : 0;
                $meta_user_id = $metadata && isset($metadata->user_id) ? (int) $metadata->user_id : 0;
                if ($order_id > 0)
                {
                    $this->ci->order_model->update_row($order_id, array('status' => 'cancelled'));
                }
                if ($cart_id > 0)
                {
                    $cart = $this->ci->cart_model->find($cart_id);
                    if ($cart && ($meta_user_id < 1 || (int) $cart->user_id === (int) $meta_user_id))
                    {
                        $this->ci->cart_model->update_row($cart_id, array(
                            'status' => 'expired',
                            'updated_by' => $meta_user_id > 0 ? (int) $meta_user_id : null,
                        ));
                    }
                }
            }
        }
        finally
        {
            if ($log_id > 0)
            {
                $this->ci->stripe_log_model->mark_processed($log_id);
            }
        }

        return array(true, 200, 'ok');
    }
}
