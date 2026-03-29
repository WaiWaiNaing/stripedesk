<?php

namespace Stripedesk\Api;

final class Api_error_response extends Api_response
{
    private $status;
    private $error_code;
    private $message;

    private function __construct($http_status_code, $error_code, $message)
    {
        $this->status = (int) $http_status_code;
        $this->error_code = (string) $error_code;
        $this->message = (string) $message;
    }

    public static function create($http_status_code, $error_code, $message)
    {
        return new self($http_status_code, $error_code, $message);
    }

    public function http_status_code()
    {
        return $this->status;
    }

    public function body()
    {
        return array(
            'success' => false,
            'error' => array(
                'code' => $this->error_code,
                'message' => $this->message,
            ),
        );
    }
}
