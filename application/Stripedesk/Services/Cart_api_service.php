<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Cart_detail_dto;
use Stripedesk\Dto\Cart_line_dto;

final class Cart_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('cart_model');
        $this->ci->load->model('cart_item_model');
        $this->ci->load->model('product_model');
    }

    public function create_for_actor($user, array $payload)
    {
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
        if (empty($items))
        {
            return array(false, 'items is required');
        }

        $expires_days = isset($payload['expires_days']) ? (int) $payload['expires_days'] : 7;
        if ($expires_days < 1) $expires_days = 7;
        if ($expires_days > 30) $expires_days = 30;

        $cart_currency_id = null;
        $cart_total_amount = 0.0;
        $prepared_items = array(); // each: product, quantity, unit_price

        foreach ($items as $it)
        {
            $product_id = isset($it['product_id']) ? (int) $it['product_id'] : 0;
            $quantity = isset($it['quantity']) ? (int) $it['quantity'] : 1;
            if ($product_id < 1)
            {
                return array(false, 'product_id is required');
            }
            if ($quantity < 1) $quantity = 1;
            if ($quantity > 25) $quantity = 25;

            $product = $this->ci->product_model->find($product_id);
            if (! $product || $product->deleted_at !== null)
            {
                return array(false, 'product not found: ' . (string) $product_id);
            }

            if ($cart_currency_id === null)
            {
                $cart_currency_id = (int) $product->currency_id;
            }
            elseif ((int) $product->currency_id !== (int) $cart_currency_id)
            {
                return array(false, 'all cart items must use the same currency');
            }

            $unit_price = (float) $product->price;
            $cart_total_amount += $unit_price * (int) $quantity;

            $prepared_items[] = array(
                'product_id' => (int) $product_id,
                'quantity' => (int) $quantity,
                'unit_price' => (string) $product->price,
            );
        }

        $expires_at = date('Y-m-d H:i:s', time() + ($expires_days * 86400));

        $cart_id = $this->ci->cart_model->create(array(
            'user_id' => (int) $user->id,
            'status' => 'active',
            'total_amount' => number_format($cart_total_amount, 2, '.', ''),
            'expires_at' => (string) $expires_at,
            'created_by' => (int) $user->id,
            'updated_by' => (int) $user->id,
        ));

        if (! $cart_id)
        {
            return array(false, 'failed to create cart');
        }

        foreach ($prepared_items as $pi)
        {
            $ok = $this->ci->cart_item_model->create(array(
                'cart_id' => (int) $cart_id,
                'product_id' => (int) $pi['product_id'],
                'quantity' => (int) $pi['quantity'],
                'price' => (string) $pi['unit_price'],
            ));

            if (! $ok)
            {
                return array(false, 'failed to create cart item');
            }
        }

        $cart = $this->ci->cart_model->get_by_id_for_access($user, (int) $cart_id);
        if (! $cart)
        {
            return array(false, 'cart not found after creation');
        }

        $line_rows = $this->ci->cart_item_model->list_with_product_names_for_cart((int) $cart_id);
        $lines = array();
        foreach ($line_rows as $lr)
        {
            $lines[] = new Cart_line_dto(
                $lr->product_id,
                $lr->product_name,
                $lr->quantity,
                $lr->price
            );
        }

        return array(true, new Cart_detail_dto($cart, $lines));
    }

    public function detail_for_actor($user, $cart_id)
    {
        $cart = $this->ci->cart_model->get_by_id_for_access($user, (int) $cart_id);
        if (! $cart)
        {
            return null;
        }

        $line_rows = $this->ci->cart_item_model->list_with_product_names_for_cart((int) $cart_id);
        $lines = array();
        foreach ($line_rows as $lr)
        {
            $lines[] = new Cart_line_dto(
                $lr->product_id,
                $lr->product_name,
                $lr->quantity,
                $lr->price
            );
        }

        return new Cart_detail_dto($cart, $lines);
    }
}

