<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Product_catalog_item_dto;

final class Product_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('product_model');
    }

    public function list_active_catalog()
    {
        $rows = $this->ci->product_model->get_active_catalog_with_currency();
        $dtos = array();
        foreach ($rows as $row)
        {
            $dtos[] = new Product_catalog_item_dto(
                $row->id,
                $row->name,
                $row->description,
                $row->price,
                $row->currency_code,
                $row->stripe_price_id
            );
        }

        return $dtos;
    }
}
