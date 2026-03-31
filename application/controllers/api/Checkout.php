<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Checkout extends Api_base_controller
{
    public function session()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if (isset($user->role) && $user->role === 'admin')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Admins cannot purchase products'));
            return;
        }
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }

        $svc = new \Stripedesk\Services\Stripe_checkout_service($this);
        list($ok, $result) = $svc->create_checkout_session($user, $body);
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'checkout_failed', (string) $result));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result, 201));
    }
}

