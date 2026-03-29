<?php

namespace Stripedesk\Dto;

final class Login_token_dto
{
    private $access_token;
    private $token_type;
    private $expires_in;

    public function __construct($access_token, $token_type, $expires_in)
    {
        $this->access_token = (string) $access_token;
        $this->token_type = (string) $token_type;
        $this->expires_in = (int) $expires_in;
    }

    public function to_array()
    {
        return array(
            'access_token' => $this->access_token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_in,
        );
    }
}
