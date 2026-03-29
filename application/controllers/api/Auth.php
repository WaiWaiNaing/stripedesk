<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Api_base_controller
{
    public function login()
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(405, 'method_not_allowed', 'Use POST'));
            return;
        }
        $raw = $this->input->raw_input_stream;
        $body = json_decode($raw, true);
        if ( ! is_array($body))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'invalid_json', 'JSON body required'));
            return;
        }
        $svc = new \Stripedesk\Services\Auth_api_service($this);
        $dto = $svc->login_with_credentials(
            isset($body['email']) ? $body['email'] : '',
            isset($body['password']) ? $body['password'] : ''
        );
        if ($dto === null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(401, 'invalid_credentials', 'Invalid email or password'));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($dto->to_array()));
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
