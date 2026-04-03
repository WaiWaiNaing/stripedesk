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
            if ($result === 'cart_already_exists')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(409, 'cart_already_exists', 'User already has an active cart'));
                return;
            }
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

    public function add_item($id)
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
        list($ok, $result) = $svc->add_item_for_actor($user, (int) $id, $body);
        if (! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'cart not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart not found'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'cart_item_add_failed', $msg));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 200));
    }

    public function remove_item($id)
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
        list($ok, $result) = $svc->remove_item_for_actor($user, (int) $id, $body);
        if (! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'cart not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart not found'));
                return;
            }
            if ($msg === 'cart line not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart line not found'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'cart_item_remove_failed', $msg));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 200));
    }

    public function show_for_user($user_id)
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
        if ( ! $svc->can_access_user_cart($user, (int) $user_id))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Cannot access cart for this user'));
            return;
        }

        $result = $svc->detail_for_user_id($user, (int) $user_id);
        if ($result === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'No active cart for this user'));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array()));
    }

    public function add_item_for_user($user_id)
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
        list($ok, $result) = $svc->add_item_for_user_id($user, (int) $user_id, $body);
        if (! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'forbidden')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Cannot modify cart for this user'));
                return;
            }
            if ($msg === 'cart not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart not found'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'cart_item_add_failed', $msg));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 200));
    }

    public function remove_item_for_user($user_id)
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
        list($ok, $result) = $svc->remove_item_for_user_id($user, (int) $user_id, $body);
        if (! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'forbidden')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Cannot modify cart for this user'));
                return;
            }
            if ($msg === 'no active cart')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'No active cart for this user'));
                return;
            }
            if ($msg === 'cart not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart not found'));
                return;
            }
            if ($msg === 'cart line not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'Cart line not found'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'cart_item_remove_failed', $msg));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 200));
    }
}

