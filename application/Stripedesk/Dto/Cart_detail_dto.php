<?php
namespace Stripedesk\Dto;

final class Cart_detail_dto
{
    private $cart;
    private $items;

    public function __construct($cart, array $items)
    {
        $this->cart = $cart;
        $this->items = $items;
    }

    public function to_array()
    {
        $line_arrays = array();
        foreach ($this->items as $line)
        {
            if ($line instanceof Cart_line_dto)
            {
                $line_arrays[] = $line->to_array();
            }
        }

        return array(
            'cart' => array(
                'id' => (int) $this->cart->id,
                'user_id' => (int) $this->cart->user_id,
                'status' => (string) $this->cart->status,
                'total_amount' => (string) $this->cart->total_amount,
                'expires_at' => $this->cart->expires_at !== null ? (string) $this->cart->expires_at : null,
                'created_at' => $this->cart->created_at !== null ? (string) $this->cart->created_at : null,
                'updated_at' => $this->cart->updated_at !== null ? (string) $this->cart->updated_at : null,
            ),
            'lines' => $line_arrays,
        );
    }
}

