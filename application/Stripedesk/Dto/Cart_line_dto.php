<?php
namespace Stripedesk\Dto;

final class Cart_line_dto
{
    private $product_id;
    private $product_name;
    private $quantity;
    private $price; // snapshot unit price

    public function __construct($product_id, $product_name, $quantity, $price)
    {
        $this->product_id = (int) $product_id;
        $this->product_name = (string) $product_name;
        $this->quantity = (int) $quantity;
        $this->price = (string) $price;
    }

    public function to_array()
    {
        return array(
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'price' => $this->price,
        );
    }
}

