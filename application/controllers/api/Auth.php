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
            $this->emit(\Stripedesk\Api\Api_success_response::with_data(array(
                'requires_verification' => true,
                'intent' => $intent,
            )));
            return;
        }
        if ( ! isset($result['status']) || $result['status'] !== 'success' || ! isset($result['dto']))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(401, 'invalid_credentials', 'Invalid email or password'));
            return;
        }
        $token_data = $result['dto']->to_array();
        $this->set_auth_cookie($token_data['access_token'], isset($token_data['expires_in']) ? (int) $token_data['expires_in'] : 86400);
        $this->emit(\Stripedesk\Api\Api_success_response::with_data(array(
            'requires_verification' => false,
        )));
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

    public function resend()
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
        list($ok, $result) = $svc->resend_otp($body);
        if ( ! $ok)
        {
            $msg = (string) $result;
            $status = 400;
            $code = 'resend_failed';
            if ($msg === 'this action is not available for administrator accounts')
            {
                $status = 403;
                $code = 'forbidden';
            }
            elseif ($msg === 'user not found')
            {
                $status = 404;
                $code = 'user_not_found';
            }
            elseif ($msg === 'email already verified')
            {
                $code = 'email_already_verified';
            }
            $this->emit(\Stripedesk\Api\Api_error_response::create($status, $code, $msg));
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
        if ($this->should_issue_auth_cookie_for_intent(isset($body['intent']) ? $body['intent'] : ''))
        {
            $this->set_auth_cookie(isset($result['access_token']) ? $result['access_token'] : '', isset($result['expires_in']) ? (int) $result['expires_in'] : 86400);
            unset($result['access_token']);
            unset($result['token_type']);
            unset($result['expires_in']);
            $result['requires_verification'] = false;
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
        if (isset($result['access_token']))
        {
            $this->set_auth_cookie($result['access_token'], isset($result['expires_in']) ? (int) $result['expires_in'] : 86400);
            unset($result['access_token']);
            unset($result['token_type']);
            unset($result['expires_in']);
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

    private function should_issue_auth_cookie_for_intent($intent)
    {
        return $intent === 'registration' || $intent === 'account_activation';
    }
}
