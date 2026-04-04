<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Api_base_controller
{
    public function index()
    {
        if ( ! $this->require_method('GET'))
        {
            return;
        }
        // Public catalog: browse without login; cart/checkout still require auth where enforced.
        $svc = new \Stripedesk\Services\Product_api_service($this);
        $dtos = $svc->list_active_catalog();
        $data = array();
        foreach ($dtos as $dto)
        {
            $data[] = $dto->to_array();
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
    }
}
