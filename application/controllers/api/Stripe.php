<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stripe extends Api_base_controller
{
    public function webhook()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $payload = $this->input->raw_input_stream;
        $sig = $this->input->get_request_header('Stripe-Signature', true);

        $this->config->load('stripe', true);
        $secret = $this->config->item('stripe_secret_key', 'stripe');
        if ($secret)
        {
            \Stripe\Stripe::setApiKey($secret);
        }

        $svc = new \Stripedesk\Services\Stripe_webhook_service($this);
        list($ok, $status, $msg) = $svc->handle_raw_webhook($payload, $sig);
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create((int) $status, 'webhook_error', (string) $msg));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data(array('status' => (string) $msg), 200));
    }
}

