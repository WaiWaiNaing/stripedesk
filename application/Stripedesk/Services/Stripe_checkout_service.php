<?php

namespace Stripedesk\Services;

final class Stripe_checkout_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->config->load('stripe', true);
        $this->ci->load->model('product_model');
        $this->ci->load->model('currency_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
        $this->ci->load->model('stripe_log_model');
    }

    public function create_checkout_session($actor_user, array $payload)
    {
        $secret = $this->ci->config->item('stripe_secret_key', 'stripe');
        if ($secret === null || $secret === '')
        {
            return array(false, 'Stripe secret key not configured');
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 25) $quantity = 25;
        if ($product_id < 1)
        {
            return array(false, 'product_id is required');
        }

        $product = $this->ci->product_model->find($product_id);
        if ( ! $product || $product->deleted_at !== null)
        {
            return array(false, 'product not found');
        }

        $currency_row = $this->ci->currency_model->find((int) $product->currency_id);
        $currency_code = $currency_row ? strtolower((string) $currency_row->code) : 'usd';

        $total = (float) $product->price * (int) $quantity;
        $order_id = $this->ci->order_model->create(array(
            'user_id' => (int) $actor_user->id,
            'total_amount' => number_format($total, 2, '.', ''),
            'currency_id' => (int) $product->currency_id,
            'status' => 'pending',
            'created_by' => (int) $actor_user->id,
            'updated_by' => (int) $actor_user->id,
        ));
        if ( ! $order_id)
        {
            return array(false, 'failed to create order');
        }

        $this->ci->order_item_model->create(array(
            'order_id' => (int) $order_id,
            'product_id' => (int) $product->id,
            'quantity' => (int) $quantity,
            'unit_price' => (string) $product->price,
            'created_by' => (int) $actor_user->id,
            'updated_by' => (int) $actor_user->id,
        ));

        \Stripe\Stripe::setApiKey($secret);

        $success_url = $this->ci->config->item('stripe_success_url', 'stripe');
        $cancel_url = $this->ci->config->item('stripe_cancel_url', 'stripe');

        $minor = 2;
        if ($currency_row && isset($currency_row->minor_unit))
        {
            $minor = (int) $currency_row->minor_unit;
        }
        $unit_amount = (int) round(((float) $product->price) * pow(10, $minor));

        $session = \Stripe\Checkout\Session::create(array(
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => array(
                'order_id' => (string) (int) $order_id,
                'user_id' => (string) (int) $actor_user->id,
                'product_id' => (string) (int) $product->id,
                'quantity' => (string) (int) $quantity,
            ),
            'line_items' => array(
                array(
                    'quantity' => (int) $quantity,
                    'price_data' => array(
                        'currency' => $currency_code,
                        'unit_amount' => $unit_amount,
                        'product_data' => array(
                            'name' => (string) $product->name,
                            'description' => $product->description ? (string) $product->description : null,
                        ),
                    ),
                ),
            ),
        ));

        $this->ci->order_model->update_row((int) $order_id, array(
            'stripe_session_id' => (string) $session->id,
            'updated_by' => (int) $actor_user->id,
        ));

        $this->ci->stripe_log_model->log_event('checkout.session.created', array(
            'order_id' => (int) $order_id,
            'session_id' => (string) $session->id,
        ));

        return array(true, array(
            'order_id' => (int) $order_id,
            'checkout_session_id' => (string) $session->id,
            'checkout_url' => (string) $session->url,
        ));
    }
}

