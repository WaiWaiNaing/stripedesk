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
        $this->ci->load->model('cart_model');
        $this->ci->load->model('cart_item_model');
        $this->ci->load->model('stripe_log_model');
    }

    public function create_checkout_session($actor_user, array $payload)
    {
        $secret = $this->ci->config->item('stripe_secret_key', 'stripe');
        if ($secret === null || $secret === '')
        {
            return array(false, 'Stripe secret key not configured');
        }

        $cart_id = isset($payload['cart_id']) ? (int) $payload['cart_id'] : 0;

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 25) $quantity = 25;

        $line_items = array();
        $order_id = 0;
        $currency_code = 'usd';
        $minor = 2;
        $total_quantity = 0;

        if ($cart_id > 0)
        {
            $cart = $this->ci->cart_model->get_by_id_for_access($actor_user, $cart_id);
            if (! $cart)
            {
                return array(false, 'cart not found');
            }
            if ((string) $cart->status !== 'active')
            {
                return array(false, 'cart must be active to checkout');
            }
            if ($cart->expires_at !== null && strtotime((string) $cart->expires_at) < time())
            {
                return array(false, 'cart expired');
            }

            $cart_items = $this->ci->cart_item_model->list_with_product_names_for_cart((int) $cart_id);
            if (empty($cart_items))
            {
                return array(false, 'cart has no items');
            }

            $currency_row = $this->ci->currency_model->find((int) $cart_items[0]->product_currency_id);
            $currency_code = $currency_row ? strtolower((string) $currency_row->code) : 'usd';
            $minor = 2;
            if ($currency_row && isset($currency_row->minor_unit))
            {
                $minor = (int) $currency_row->minor_unit;
            }

            // Mark the cart as in-progress before creating Stripe session.
            $this->ci->cart_model->update_status((int) $cart_id, 'checking_out', (int) $actor_user->id);

            $order_id = $this->ci->order_model->create(array(
                'user_id' => (int) $actor_user->id,
                'total_amount' => (string) $cart->total_amount,
                'currency_id' => $currency_row ? (int) $currency_row->id : 1,
                'status' => 'pending',
                'created_by' => (int) $actor_user->id,
                'updated_by' => (int) $actor_user->id,
            ));
            if (! $order_id)
            {
                return array(false, 'failed to create order');
            }

            $this->ci->load->model('order_item_model');
            foreach ($cart_items as $ci_item)
            {
                $total_quantity += (int) $ci_item->quantity;
                $unit_price = (string) $ci_item->price;
                $unit_amount = (int) round(((float) $unit_price) * pow(10, $minor));
                $line_items[] = array(
                    'quantity' => (int) $ci_item->quantity,
                    'price_data' => array(
                        'currency' => $currency_code,
                        'unit_amount' => $unit_amount,
                        'product_data' => array(
                            'name' => (string) $ci_item->product_name,
                            'description' => $ci_item->product_description ? (string) $ci_item->product_description : null,
                        ),
                    ),
                );

                $this->ci->order_item_model->create(array(
                    'order_id' => (int) $order_id,
                    'product_id' => (int) $ci_item->product_id,
                    'quantity' => (int) $ci_item->quantity,
                    'unit_price' => (string) $unit_price,
                    'created_by' => (int) $actor_user->id,
                    'updated_by' => (int) $actor_user->id,
                ));
            }
        }
        else
        {
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

            $minor = 2;
            if ($currency_row && isset($currency_row->minor_unit))
            {
                $minor = (int) $currency_row->minor_unit;
            }
            $unit_amount = (int) round(((float) $product->price) * pow(10, $minor));

            $line_items = array(
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
            );
            $total_quantity = (int) $quantity;
        }

        \Stripe\Stripe::setApiKey($secret);

        $success_url = $this->ci->config->item('stripe_success_url', 'stripe');
        $cancel_url = $this->ci->config->item('stripe_cancel_url', 'stripe');

        $metadata = array(
            'order_id' => (string) (int) $order_id,
            'user_id' => (string) (int) $actor_user->id,
            'quantity' => (string) (int) $total_quantity,
        );
        if ($product_id > 0)
        {
            $metadata['product_id'] = (string) (int) $product_id;
        }
        if ($cart_id > 0)
        {
            $metadata['cart_id'] = (string) (int) $cart_id;
        }

        $session = \Stripe\Checkout\Session::create(array(
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => $metadata,
            'line_items' => $line_items,
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

