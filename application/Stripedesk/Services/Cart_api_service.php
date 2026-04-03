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

        if ($this->ci->cart_model->find_active_cart_for_user((int) $user->id))
        {
            return array(false, 'cart_already_exists');
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

        $lines = $this->lines_for_cart((int) $cart_id);
        return array(true, new Cart_detail_dto($cart, $lines));
    }

    /**
     * Add or merge a line on an active cart. Body: product_id (required), quantity (optional, default 1).
     *
     * @return array{0:bool,1:Cart_detail_dto|string}
     */
    public function add_item_for_actor($user, $cart_id, array $payload)
    {
        $cart_id = (int) $cart_id;
        $cart = $this->ci->cart_model->get_by_id_for_access($user, $cart_id);
        if (! $cart)
        {
            return array(false, 'cart not found');
        }
        if ((string) $cart->status !== 'active')
        {
            return array(false, 'cart must be active to modify');
        }
        if ($cart->expires_at !== null && strtotime((string) $cart->expires_at) < time())
        {
            return array(false, 'cart expired');
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
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

        $new_currency_id = (int) $product->currency_id;
        $existing_lines = $this->ci->cart_item_model->list_with_product_names_for_cart($cart_id);
        if ( ! empty($existing_lines))
        {
            $existing_currency = (int) $existing_lines[0]->product_currency_id;
            if ($new_currency_id !== $existing_currency)
            {
                return array(false, 'all cart items must use the same currency');
            }
        }

        $unit_price = (string) $product->price;
        $existing = $this->ci->cart_item_model->find_by_cart_and_product($cart_id, $product_id);

        if ($existing)
        {
            $merged = (int) $existing->quantity + $quantity;
            if ($merged > 25) $merged = 25;
            $ok = $this->ci->cart_item_model->update_row((int) $existing->id, array(
                'quantity' => $merged,
                'price' => $unit_price,
            ));
            if (! $ok)
            {
                return array(false, 'failed to update cart item');
            }
        }
        else
        {
            $ok = $this->ci->cart_item_model->create(array(
                'cart_id' => $cart_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $unit_price,
            ));
            if (! $ok)
            {
                return array(false, 'failed to create cart item');
            }
        }

        $this->ci->cart_model->recalculate_total_amount($cart_id, (int) $user->id);

        $cart = $this->ci->cart_model->get_by_id_for_access($user, $cart_id);
        if (! $cart)
        {
            return array(false, 'cart not found');
        }

        return array(true, new Cart_detail_dto($cart, $this->lines_for_cart($cart_id)));
    }

    /**
     * Remove a line by product_id. Body: product_id (required).
     *
     * @return array{0:bool,1:Cart_detail_dto|string}
     */
    public function remove_item_for_actor($user, $cart_id, array $payload)
    {
        $cart_id = (int) $cart_id;
        $cart = $this->ci->cart_model->get_by_id_for_access($user, $cart_id);
        if (! $cart)
        {
            return array(false, 'cart not found');
        }
        if ((string) $cart->status !== 'active')
        {
            return array(false, 'cart must be active to modify');
        }
        if ($cart->expires_at !== null && strtotime((string) $cart->expires_at) < time())
        {
            return array(false, 'cart expired');
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        if ($product_id < 1)
        {
            return array(false, 'product_id is required');
        }

        if ( ! $this->ci->cart_item_model->delete_by_cart_and_product($cart_id, $product_id))
        {
            return array(false, 'cart line not found');
        }

        $this->ci->cart_model->recalculate_total_amount($cart_id, (int) $user->id);

        $cart = $this->ci->cart_model->get_by_id_for_access($user, $cart_id);
        if (! $cart)
        {
            return array(false, 'cart not found');
        }

        return array(true, new Cart_detail_dto($cart, $this->lines_for_cart($cart_id)));
    }

    /**
     * @return array{0:bool,1:Cart_detail_dto|string}
     */
    public function remove_item_for_user_id($actor_user, $target_user_id, array $payload)
    {
        if (! $this->can_access_user_cart($actor_user, $target_user_id))
        {
            return array(false, 'forbidden');
        }

        $cart = $this->ci->cart_model->find_latest_active_cart_for_user((int) $target_user_id);
        if (! $cart)
        {
            return array(false, 'no active cart');
        }
        if (! $this->ci->cart_model->get_by_id_for_access($actor_user, (int) $cart->id))
        {
            return array(false, 'cart not found');
        }

        return $this->remove_item_for_actor($actor_user, (int) $cart->id, $payload);
    }

    public function detail_for_actor($user, $cart_id)
    {
        $cart = $this->ci->cart_model->get_by_id_for_access($user, (int) $cart_id);
        if (! $cart)
        {
            return null;
        }

        return new Cart_detail_dto($cart, $this->lines_for_cart((int) $cart_id));
    }

    public function can_access_user_cart($actor_user, $target_user_id)
    {
        if (isset($actor_user->role) && $actor_user->role === 'admin')
        {
            return true;
        }
        return (int) $actor_user->id === (int) $target_user_id;
    }

    /**
     * Active cart for a user id (path param). Null if none. Caller must enforce can_access_user_cart.
     */
    public function detail_for_user_id($actor_user, $target_user_id)
    {
        $cart = $this->ci->cart_model->find_latest_active_cart_for_user((int) $target_user_id);
        if (! $cart)
        {
            return null;
        }
        if (! $this->ci->cart_model->get_by_id_for_access($actor_user, (int) $cart->id))
        {
            return null;
        }

        return new Cart_detail_dto($cart, $this->lines_for_cart((int) $cart->id));
    }

    /**
     * Resolves cart by user id; creates an empty active cart if none exists, then adds the line.
     *
     * @return array{0:bool,1:Cart_detail_dto|string}
     */
    public function add_item_for_user_id($actor_user, $target_user_id, array $payload)
    {
        if (! $this->can_access_user_cart($actor_user, $target_user_id))
        {
            return array(false, 'forbidden');
        }

        $cart = $this->ci->cart_model->find_latest_active_cart_for_user((int) $target_user_id);
        if (! $cart)
        {
            $stale = $this->ci->cart_model->find_active_cart_for_user((int) $target_user_id);
            if ($stale)
            {
                $expires_at = date('Y-m-d H:i:s', time() + (7 * 86400));
                $this->ci->cart_model->update_row((int) $stale->id, array(
                    'expires_at' => (string) $expires_at,
                    'updated_by' => (int) $actor_user->id,
                ));
                $cart = $this->ci->cart_model->get_by_id_for_access($actor_user, (int) $stale->id);
            }
        }
        if (! $cart)
        {
            $cart_id = $this->create_empty_cart_for_user($actor_user, (int) $target_user_id);
            if (! $cart_id)
            {
                return array(false, 'failed to create cart');
            }
            $cart = $this->ci->cart_model->get_by_id_for_access($actor_user, (int) $cart_id);
            if (! $cart)
            {
                return array(false, 'cart not found');
            }
        }

        return $this->add_item_for_actor($actor_user, (int) $cart->id, $payload);
    }

    private function create_empty_cart_for_user($actor_user, $owner_user_id)
    {
        $expires_at = date('Y-m-d H:i:s', time() + (7 * 86400));
        return $this->ci->cart_model->create(array(
            'user_id' => (int) $owner_user_id,
            'status' => 'active',
            'total_amount' => '0.00',
            'expires_at' => (string) $expires_at,
            'created_by' => (int) $actor_user->id,
            'updated_by' => (int) $actor_user->id,
        ));
    }

    private function lines_for_cart($cart_id)
    {
        $line_rows = $this->ci->cart_item_model->list_with_product_names_for_cart((int) $cart_id);
        $lines = array();
        foreach ($line_rows as $lr)
        {
            $lines[] = new Cart_line_dto(
                $lr->product_id,
                $lr->product_name,
                $lr->quantity,
                $lr->price,
                isset($lr->product_description) ? $lr->product_description : null,
                isset($lr->product_currency_id) ? (int) $lr->product_currency_id : null
            );
        }
        return $lines;
    }
}
