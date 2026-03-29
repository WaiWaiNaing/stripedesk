<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Login_token_dto;
use Stripedesk\Dto\User_profile_dto;

final class Auth_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('user_model');
        $this->ci->load->library('jwt_auth');
    }

    public function login_with_credentials($email, $password)
    {
        $email = is_string($email) ? trim($email) : '';
        $password = is_string($password) ? $password : '';
        if ($email === '' || $password === '')
        {
            return null;
        }
        $user = $this->ci->user_model->get_by_email($email);
        if ( ! $user || ! password_verify($password, $user->password))
        {
            return null;
        }
        $token = $this->ci->jwt_auth->issue_for_user($user);
        $ttl = $this->ci->jwt_auth->get_ttl_seconds();

        return new Login_token_dto($token, 'Bearer', $ttl);
    }

    public function profile_for_user_id($user_id)
    {
        $user = $this->ci->user_model->find((int) $user_id);
        if ( ! $user || $user->deleted_at !== null)
        {
            return null;
        }

        return new User_profile_dto(
            $user->id,
            $user->name,
            $user->email,
            $user->role,
            $user->created_at
        );
    }
}
