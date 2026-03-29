<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends Api_base_controller
{
    public function index()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Invoice_api_service($this);
        $dtos = $svc->list_summaries_for_actor($user);
        $data = array();
        foreach ($dtos as $dto)
        {
            $data[] = $dto->to_array();
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
    }

    public function show($id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Invoice_api_service($this);
        $result = $svc->detail_for_actor($user, $id);
        if ($result === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Invoice not found'));
            return;
        }
        if ($result === false)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'You do not have access to this invoice'));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array()));
    }
}
