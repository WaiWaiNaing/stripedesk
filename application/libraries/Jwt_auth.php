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
        $secret = $this->ci->config->item('jwt_secret', 'jwt');
        $ttl = (int) $this->ci->config->item('jwt_ttl', 'jwt');
        $issuer = $this->ci->config->item('jwt_issuer', 'jwt');
        $now = time();
        $payload = array(
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => (string) (int) $user->id,
            'role' => (string) $user->role,
            'email' => (string) $user->email,
        );

        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }

    public function get_ttl_seconds()
    {
        return (int) $this->ci->config->item('jwt_ttl', 'jwt');
    }

    public function get_authenticated_user()
    {
        $raw = $this->ci->input->get_request_header('Authorization', true);
        if ($raw === false || $raw === null || $raw === '')
        {
            $raw = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        }
        if ( ! is_string($raw) || stripos($raw, 'Bearer ') !== 0)
        {
            return null;
        }
        $token = trim(substr($raw, 7));
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
