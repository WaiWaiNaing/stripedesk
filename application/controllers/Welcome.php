<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
    public function index()
    {
        $this->output->set_content_type('text/plain', 'UTF-8');
        $this->output->set_output('StripeDesk API: /api/v1/...');
    }
}
