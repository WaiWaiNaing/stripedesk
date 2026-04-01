<?php

namespace Stripedesk\Dto;

final class Login_token_dto
{
    private $access_token;
    private $refresh_token;
    private $token_type;
    private $expires_in;
    private $refresh_expires_in;

    public function __construct($access_token, $refresh_token, $token_type, $expires_in, $refresh_expires_in)
    {
        $this->access_token = (string) $access_token;
        $this->refresh_token = (string) $refresh_token;
        $this->token_type = (string) $token_type;
        $this->expires_in = (int) $expires_in;
        $this->refresh_expires_in = (int) $refresh_expires_in;
    }

    public function to_array()
    {
        return array(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_in,
            'refresh_expires_in' => $this->refresh_expires_in,
        );
    }
}
