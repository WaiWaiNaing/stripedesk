<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipts extends Api_base_controller
{
    public function show($id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Receipt_api_service($this);
        $result = $svc->detail_for_actor($user, $id);
        if ($result === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Receipt not found'));
            return;
        }
        if ($result === false)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'You do not have access to this receipt'));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array()));
    }
}
