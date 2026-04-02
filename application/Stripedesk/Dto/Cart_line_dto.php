<?php
namespace Stripedesk\Dto;

final class Cart_line_dto
{
    private $product_id;
    private $product_name;
    private $quantity;
    private $price; // snapshot unit price
    private $product_description;
    private $currency_id;

    public function __construct($product_id, $product_name, $quantity, $price, $product_description = null, $currency_id = null)
    {
        $this->product_id = (int) $product_id;
        $this->product_name = (string) $product_name;
        $this->quantity = (int) $quantity;
        $this->price = (string) $price;
        $this->product_description = $product_description !== null ? (string) $product_description : null;
        $this->currency_id = $currency_id !== null ? (int) $currency_id : null;
    }

    public function currency_id()
    {
        return $this->currency_id;
    }

    public function to_array()
    {
        $out = array(
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'line_total' => number_format((float) $this->price * (int) $this->quantity, 2, '.', ''),
        );
        if ($this->product_description !== null && $this->product_description !== '')
        {
            $out['product_description'] = $this->product_description;
        }
        if ($this->currency_id !== null)
        {
            $out['currency_id'] = $this->currency_id;
        }
        return $out;
    }
}
