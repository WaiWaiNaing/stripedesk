<?php

namespace Stripedesk\Api;

abstract class Api_response
{
    abstract public function http_status_code();

    abstract public function body();

    public function emit($output)
    {
        $output->set_status_header($this->http_status_code());
        $output->set_content_type('application/json', 'UTF-8');
        $output->set_output(json_encode($this->body()));
    }
}
