<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Carts extends Api_base_controller
{
    public function create()
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

        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }

        $svc = new \Stripedesk\Services\Cart_api_service($this);
        list($ok, $result) = $svc->create_for_actor($user, $body);
        if (! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'cart_create_failed', (string) $result));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 201));
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

        $svc = new \Stripedesk\Services\Cart_api_service($this);
        $result = $svc->detail_for_actor($user, $id);
        if ($result === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart not found'));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array()));
    }
}

