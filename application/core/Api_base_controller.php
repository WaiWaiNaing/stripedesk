<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class Api_base_controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json', 'UTF-8');
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
}
