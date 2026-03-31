<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stripe_logs extends Api_base_controller
{
    public function index()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if ( ! $this->require_admin($user))
        {
            return;
        }
        $limit = (int) $this->input->get('limit');
        if ($limit < 1) $limit = 100;
        if ($limit > 500) $limit = 500;

        $this->load->model('stripe_log_model');
        $rows = $this->stripe_log_model->recent($limit);
        $data = array();
        foreach ($rows as $r)
        {
            $data[] = array(
                'id' => (int) $r->id,
                'event_type' => (string) $r->event_type,
                'created_at' => (string) $r->created_at,
                'payload' => $r->payload,
            );
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
    }
}

