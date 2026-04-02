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

    /**
     * POST /api/v1/checkout/invoice — create order + invoice from cart (no Stripe).
     */
    public function invoice()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if (isset($user->role) && $user->role === 'admin')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Admins cannot checkout carts'));
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

        $cart_id = isset($body['cart_id']) ? (int) $body['cart_id'] : 0;
        $svc = new \Stripedesk\Services\Checkout_invoice_service($this);
        list($ok, $result) = $svc->create_invoice_from_cart($user, $cart_id);
        if ( ! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'cart not found')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', $msg));
                return;
            }
            if ($msg === 'not_cart_owner')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'You must be the owner of this cart'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'checkout_invoice_failed', $msg));
            return;
        }

        if ($result instanceof \Stripedesk\Dto\Invoice_detail_dto)
        {
            $this->emit(\Stripedesk\Api\Api_success_response::with_data($result->to_array(), 201));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data(array('invoice_id' => (int) $result), 201));
    }
}

