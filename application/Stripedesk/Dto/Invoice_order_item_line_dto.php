<?php

namespace Stripedesk\Dto;

final class Invoice_order_item_line_dto
{
    private $id;
    private $product_id;
    private $product_name;
    private $quantity;
    private $unit_price;

    public function __construct($id, $product_id, $product_name, $quantity, $unit_price)
    {
        $this->id = (int) $id;
        $this->product_id = (int) $product_id;
        $this->product_name = (string) $product_name;
        $this->quantity = (int) $quantity;
        $this->unit_price = (string) $unit_price;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
        );
    }
}
