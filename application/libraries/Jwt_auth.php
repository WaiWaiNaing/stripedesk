<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Jwt_auth
{
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('jwt', true);
    }

    public function issue_for_user($user)
    {
        return $this->issue_access_token_for_user($user);
    }

    public function issue_access_token_for_user($user)
    {
        return $this->encode_token_for_user($user, $this->get_ttl_seconds(), 'access');
    }

    public function issue_refresh_token_for_user($user)
    {
        return $this->encode_token_for_user($user, $this->get_refresh_ttl_seconds(), 'refresh');
    }

    private function encode_token_for_user($user, $ttl, $token_type)
    {
        $secret = $this->ci->config->item('jwt_secret', 'jwt');
        $issuer = $this->ci->config->item('jwt_issuer', 'jwt');
        $now = time();
        $payload = array(
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + max(60, (int) $ttl),
            'sub' => (string) (int) $user->id,
            'role' => (string) $user->role,
            'email' => (string) $user->email,
            'token_type' => (string) $token_type,
        );

        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }

    public function get_ttl_seconds()
    {
        return (int) $this->ci->config->item('jwt_ttl', 'jwt');
    }

    public function get_refresh_ttl_seconds()
    {
        return (int) $this->ci->config->item('jwt_refresh_ttl', 'jwt');
    }

    public function get_authenticated_user()
    {
        $raw = $this->ci->input->get_request_header('Authorization', true);
        if ($raw === false || $raw === null || $raw === '')
        {
            $raw = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        }

        $token = '';
        if (is_string($raw) && stripos($raw, 'Bearer ') === 0)
        {
            $token = trim(substr($raw, 7));
        }

        if ($token === '')
        {
            $cookie_name = function_exists('sd_env') ? (string) sd_env('AUTH_ACCESS_COOKIE_NAME', sd_env('AUTH_COOKIE_NAME', 'stripedesk_access_token')) : 'stripedesk_access_token';
            if (isset($_COOKIE[$cookie_name]) && is_string($_COOKIE[$cookie_name]))
            {
                $token = trim((string) $_COOKIE[$cookie_name]);
            }
        }

        if ($token === '')
        {
            return null;
        }
        $secret = $this->ci->config->item('jwt_secret', 'jwt');
        try
        {
            $decoded = \Firebase\JWT\JWT::decode($token, $secret, array('HS256'));
        }
        catch (Exception $e)
        {
            return null;
        }
        $token_type = isset($decoded->token_type) ? (string) $decoded->token_type : 'access';
        if ($token_type !== 'access')
        {
            return null;
        }
        $sub = isset($decoded->sub) ? (int) $decoded->sub : 0;
        if ($sub < 1)
        {
            return null;
        }
        $this->ci->load->model('user_model');
        $user = $this->ci->user_model->find($sub);
        if ( ! $user || $user->deleted_at !== null)
        {
            return null;
        }

        return $user;
    }
    public function get_user_from_refresh_token($token)
    {
        $token = is_string($token) ? trim($token) : '';
        if ($token === '')
        {
            return null;
        }
        $secret = $this->ci->config->item('jwt_secret', 'jwt');
        try
        {
            $decoded = \Firebase\JWT\JWT::decode($token, $secret, array('HS256'));
        }
        catch (Exception $e)
        {
            return null;
        }
        $token_type = isset($decoded->token_type) ? (string) $decoded->token_type : '';
        if ($token_type !== 'refresh')
        {
            return null;
        }
        $sub = isset($decoded->sub) ? (int) $decoded->sub : 0;
        if ($sub < 1)
        {
            return null;
        }
        $this->ci->load->model('user_model');
        $user = $this->ci->user_model->find($sub);
        if ( ! $user || $user->deleted_at !== null)
        {
            return null;
        }

        return $user;
    }
}
