<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Api_base_controller
{
    public function login()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_api_service($this);
        $result = $svc->login_with_credentials(
            isset($body['email']) ? $body['email'] : '',
            isset($body['password']) ? $body['password'] : ''
        );
        if (isset($result['status']) && $result['status'] === 'email_not_verified')
        {
            $intent = isset($result['verification_intent']) ? (string) $result['verification_intent'] : 'registration';
            $msg = $intent === 'account_activation'
                ? 'Verify your email with the OTP sent to your inbox before signing in.'
                : 'Verify your email with the OTP code sent when you registered.';
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'email_not_verified', $msg, array('intent' => $intent)));
            return;
        }
        if ( ! isset($result['status']) || $result['status'] !== 'success' || ! isset($result['dto']))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(401, 'invalid_credentials', 'Invalid email or password'));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result['dto']->to_array()));
    }

    public function register()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_password_service($this);
        list($ok, $result) = $svc->register($body);
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'validation_error', (string) $result));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result, 201));
    }

    public function forgot()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_password_service($this);
        list($ok, $result) = $svc->forgot_password($body);
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', (string) $result));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result));
    }

    public function verify_otp()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_password_service($this);
        list($ok, $result) = $svc->verify_otp($body);
        if ( ! $ok)
        {
            $msg = (string) $result;
            $code = ($msg === 'email already verified') ? 'email_already_verified' : 'otp_invalid';
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, $code, $msg));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result));
    }

    public function reset_password()
    {
        if ( ! $this->require_method('POST'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_password_service($this);
        list($ok, $result) = $svc->reset_password($body);
        if ( ! $ok)
        {
            $msg = (string) $result;
            if ($msg === 'forbidden')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Forbidden'));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'reset_failed', $msg));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result));
    }

    public function me()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        $svc = new \Stripedesk\Services\Auth_api_service($this);
        $dto = $svc->profile_for_user_id($user->id);
        if ($dto === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'user_not_found', 'User not found'));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($dto->to_array()));
    }
}
