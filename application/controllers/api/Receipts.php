<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipts extends Api_base_controller
{
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

        $svc = new \Stripedesk\Services\Receipt_api_service($this);
        $dtos = $svc->list_summaries_for_order_user_id($user, (int) $user_id);
        if ($dtos === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Cannot list receipts for this user'));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($this->dtos_to_arrays($dtos)));
    }

    public function pdf($id)
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

        $svc = new \Stripedesk\Services\Receipt_api_service($this);
        $result = $svc->pdf_binary_for_actor($user, (int) $id);
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

        $this->send_pdf_download((int) $id, $result['binary'], (string) $result['receipt_number']);
    }

    public function show($id)
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

    /**
     * @param object[] $dtos Objects exposing to_array()
     *
     * @return array<int, array>
     */
    private function dtos_to_arrays(array $dtos)
    {
        $data = array();
        foreach ($dtos as $dto)
        {
            $data[] = $dto->to_array();
        }

        return $data;
    }

    private function send_pdf_download($receipt_id, $binary, $receipt_number)
    {
        $safe_name = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $receipt_number);
        if ($safe_name === '')
        {
            $safe_name = 'receipt-' . (int) $receipt_id;
        }

        $this->output->set_content_type('application/pdf');
        $this->output->set_header('Content-Disposition: attachment; filename="' . $safe_name . '.pdf"');
        $this->output->set_output($binary);
        $this->output->_display();
        exit;
    }
}
