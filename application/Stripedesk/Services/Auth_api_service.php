<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Login_token_dto;
use Stripedesk\Dto\User_profile_dto;
use Stripedesk\Support\Email_verification;

final class Auth_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('user_model');
        $this->ci->load->library('jwt_auth');
    }

    /**
     * @return array{status:string, dto?:Login_token_dto, verification_intent?:string}
     */
    public function login_with_credentials($email, $password)
    {
        $email = is_string($email) ? trim($email) : '';
        $password = is_string($password) ? $password : '';
        if ($email === '' || $password === '')
        {
            return array('status' => 'invalid_credentials');
        }
        $user = $this->ci->user_model->get_by_email($email);
        if ( ! $user || ! password_verify($password, $user->password))
        {
            return array('status' => 'invalid_credentials');
        }
        if ( ! $this->user_email_is_verified($user))
        {
            $intent = ((string) $user->role === 'admin') ? 'account_activation' : 'registration';

            return array(
                'status' => 'email_not_verified',
                'verification_intent' => $intent,
            );
        }

        $token = $this->ci->jwt_auth->issue_for_user($user);
        $ttl = $this->ci->jwt_auth->get_ttl_seconds();

        return array(
            'status' => 'success',
            'dto' => new Login_token_dto($token, 'Bearer', $ttl),
        );
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
