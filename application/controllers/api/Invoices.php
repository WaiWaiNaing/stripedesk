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

    /**
     * POST /api/v1/invoices/{id}/pay — start payment (Stripe to be integrated).
     */
    public function pay($id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if ( ! $this->require_method('POST'))
        {
            return;
        }

        $raw = trim((string) $this->input->raw_input_stream);
        if ($raw !== '')
        {
            $body = json_decode($raw, true);
            if ( ! is_array($body))
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'invalid_json', 'JSON body must be an object if provided'));
                return;
            }
        }

        $svc = new \Stripedesk\Services\Invoice_api_service($this);
        list($ok, $result) = $svc->initiate_payment_for_actor($user, (int) $id);
        if ( ! $ok)
        {
            $code = (string) $result;
            if ($code === 'not_found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Invoice not found'));
                return;
            }
            if ($code === 'forbidden')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'You do not have access to this invoice'));
                return;
            }
            if ($code === 'invoice_not_pending')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(409, 'invoice_not_pending', 'Invoice must be in pending status to start payment'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'pay_failed', $code));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result, 200));
    }

    public function index_for_user($user_id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if ( ! $this->require_method('GET'))
        {
            return;
        }

        $svc = new \Stripedesk\Services\Invoice_api_service($this);
        $dtos = $svc->list_summaries_for_order_user_id($user, (int) $user_id);
        if ($dtos === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Cannot list invoices for this user'));
            return;
        }

        $data = array();
        foreach ($dtos as $dto)
        {
            $data[] = $dto->to_array();
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
    }

    public function update_status($id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }

        $m = strtoupper((string) $this->input->server('REQUEST_METHOD'));
        if ($m !== 'PATCH' && $m !== 'PUT')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(405, 'method_not_allowed', 'Use PATCH or PUT'));
            return;
        }

        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }

        $status = isset($body['status']) ? (string) $body['status'] : '';
        $allowed = array('pending', 'unpaid', 'paid', 'void');
        if ( ! in_array($status, $allowed, true))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'invalid_status', 'status must be pending, unpaid, paid, or void'));
            return;
        }

        $svc = new \Stripedesk\Services\Invoice_api_service($this);
        $result = $svc->update_status_for_actor($user, (int) $id, $status);
        if ($result === false)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Only admins can update invoice status'));
            return;
        }
        if ($result === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Invoice not found'));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array()));
    }
}
