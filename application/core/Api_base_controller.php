<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class Api_base_controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->apply_cors();
        $this->output->set_content_type('application/json', 'UTF-8');
    }
    protected function apply_cors()
    {
        if ( ! function_exists('sd_env'))
        {
            return;
        }
        $origin = sd_env('CORS_ALLOW_ORIGIN');
        if ($origin === null || $origin === '')
        {
            return;
        }
        $this->output->set_header('Access-Control-Allow-Origin: ' . $origin);
        $this->output->set_header('Access-Control-Allow-Credentials: true');
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $this->output->set_header('Access-Control-Allow-Headers: Authorization, Content-Type');
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'OPTIONS')
        {
            $this->output->set_status_header(204);
            $this->output->_display();
            exit;
        }
    }

    protected function emit(\Stripedesk\Api\Api_response $response)
    {
        $response->emit($this->output);
    }

    protected function authenticated_user()
    {
        $this->load->library('jwt_auth');
        $user = $this->jwt_auth->get_authenticated_user();
        if ( ! $user)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(401, 'unauthorized', 'Invalid or missing bearer token'));
            return null;
        }

        return $user;
    }

    protected function require_admin($user)
    {
        if ( ! $user || ! isset($user->role) || $user->role !== 'admin')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Admin access required'));
            return false;
        }
        return true;
    }

    protected function require_method($method)
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== strtoupper((string) $method))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(405, 'method_not_allowed', 'Use ' . strtoupper((string) $method)));
            return false;
        }
        return true;
    }

    protected function json_body()
    {
        $raw = $this->input->raw_input_stream;
        $body = json_decode($raw, true);
        if ( ! is_array($body))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'invalid_json', 'JSON body required'));
            return null;
        }
        return $body;
    }
}
