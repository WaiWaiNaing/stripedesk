<?php

namespace Stripedesk\Api;

final class Api_error_response extends Api_response
{
    private $status;
    private $error_code;
    private $message;
    private $extra;

    private function __construct($http_status_code, $error_code, $message, array $extra = array())
    {
        $this->status = (int) $http_status_code;
        $this->error_code = (string) $error_code;
        $this->message = (string) $message;
        $this->extra = $extra;
    }

    public static function create($http_status_code, $error_code, $message, array $extra = array())
    {
        return new self($http_status_code, $error_code, $message, $extra);
    }

    public function http_status_code()
    {
        return $this->status;
    }

    public function body()
    {
        $error = array_merge(
            array(
                'code' => $this->error_code,
                'message' => $this->message,
            ),
            $this->extra
        );

        return array(
            'success' => false,
            'error' => $error,
        );
    }
}
