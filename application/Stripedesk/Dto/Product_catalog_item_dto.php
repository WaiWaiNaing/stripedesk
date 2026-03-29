<?php

namespace Stripedesk\Dto;

final class Product_catalog_item_dto
{
    private $id;
    private $name;
    private $description;
    private $price;
    private $currency_code;
    private $stripe_price_id;

    public function __construct($id, $name, $description, $price, $currency_code, $stripe_price_id)
    {
        $this->id = (int) $id;
        $this->name = (string) $name;
        $this->description = $description !== null ? (string) $description : null;
        $this->price = (string) $price;
        $this->currency_code = (string) $currency_code;
        $this->stripe_price_id = $stripe_price_id !== null ? (string) $stripe_price_id : null;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency_code' => $this->currency_code,
            'stripe_price_id' => $this->stripe_price_id,
        );
    }
}
