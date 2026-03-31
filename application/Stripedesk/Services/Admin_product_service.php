<?php

namespace Stripedesk\Services;

final class Admin_product_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('product_model');
        $this->ci->load->model('currency_model');
    }

    public function create_product(array $payload, $admin_user_id)
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $description = isset($payload['description']) ? (string) $payload['description'] : null;
        $price = isset($payload['price']) ? (float) $payload['price'] : 0.0;
        $currency_code = isset($payload['currency_code']) ? (string) $payload['currency_code'] : 'USD';

        if ($name === '' || $price <= 0)
        {
            return array(false, 'name and positive price are required');
        }

        $currency = $this->ci->currency_model->get_by_code($currency_code);
        if ( ! $currency)
        {
            $currency = $this->ci->currency_model->get_default();
        }
        if ( ! $currency)
        {
            return array(false, 'currency not found');
        }

        $id = $this->ci->product_model->create(array(
            'name' => $name,
            'description' => $description,
            'price' => number_format($price, 2, '.', ''),
            'currency_id' => (int) $currency->id,
            'stripe_price_id' => isset($payload['stripe_price_id']) ? (string) $payload['stripe_price_id'] : null,
            'created_by' => (int) $admin_user_id,
            'updated_by' => (int) $admin_user_id,
        ));
        if ( ! $id)
        {
            return array(false, 'failed to create product');
        }

        return array(true, array('id' => (int) $id));
    }

    public function update_product($product_id, array $payload, $admin_user_id)
    {
        $product = $this->ci->product_model->find((int) $product_id);
        if ( ! $product || $product->deleted_at !== null)
        {
            return array(false, 'product not found');
        }

        $name = isset($payload['name']) ? trim((string) $payload['name']) : (string) $product->name;
        $description = array_key_exists('description', $payload) ? (string) $payload['description'] : $product->description;
        $price = isset($payload['price']) ? (float) $payload['price'] : (float) $product->price;
        $currency_code = isset($payload['currency_code']) ? (string) $payload['currency_code'] : null;
        $stripe_price_id = array_key_exists('stripe_price_id', $payload) ? $payload['stripe_price_id'] : $product->stripe_price_id;

        if ($name === '' || $price <= 0)
        {
            return array(false, 'name and positive price are required');
        }

        $update = array(
            'name' => $name,
            'description' => $description,
            'price' => number_format($price, 2, '.', ''),
            'updated_by' => (int) $admin_user_id,
        );

        if ($currency_code !== null && $currency_code !== '')
        {
            $currency = $this->ci->currency_model->get_by_code($currency_code);
            if ( ! $currency)
            {
                return array(false, 'currency not found');
            }
            $update['currency_id'] = (int) $currency->id;
        }

        $update['stripe_price_id'] = $stripe_price_id !== null && $stripe_price_id !== '' ? (string) $stripe_price_id : null;

        $ok = $this->ci->product_model->update_row((int) $product_id, $update);
        if ( ! $ok)
        {
            return array(false, 'failed to update product');
        }
        return array(true, array('id' => (int) $product_id));
    }
}

