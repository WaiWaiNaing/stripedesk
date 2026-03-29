<?php

namespace Stripedesk\Api;

final class Api_success_response extends Api_response
{
    private $status;
    private $data;

    private function __construct($http_status_code, array $data)
    {
        $this->status = (int) $http_status_code;
        $this->data = $data;
    }

    public static function with_data(array $data, $http_status_code = 200)
    {
        return new self($http_status_code, $data);
    }

    public function http_status_code()
    {
        return $this->status;
    }

    public function body()
    {
        return array(
            'success' => true,
            'data' => $this->data,
        );
    }
}
