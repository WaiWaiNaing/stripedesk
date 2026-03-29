<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Api_base_controller
{
    public function index()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
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
