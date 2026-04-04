<?php

namespace Stripedesk\Services;

use Stripedesk\Mail\Otp_mailer;
use Stripedesk\Support\Email_verification;
use Stripedesk\Validation\Password_validator;

final class Auth_password_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('user_model');
        $this->ci->load->model('password_reset_model');
        $this->ci->load->model('otp_verification_model');
        $this->ci->load->model('stripe_log_model');
    }

    /**
     * Issue OTP for admin-created account (email not yet verified).
     *
     * @param int    $user_id
     * @param string $email
     * @param string $intent account_activation
     * @return array{0:bool,1:mixed}
     */
    public function issue_email_verification_otp($user_id, $email, $intent)
    {
        if ((int) $user_id < 1 || $email === '')
        {
            return array(false, 'invalid user');
        }
        if ($intent !== 'account_activation')
        {
            return array(false, 'invalid intent');
        }
        $otp_plain = $this->issue_otp_verification((int) $user_id, $email, $intent);
        $data = array('requires_verification' => true);
        if (function_exists('sd_env_bool') && sd_env_bool('OTP_DEV_RETURN_CODE', false))
        {
            $data['otp'] = $otp_plain;
        }

        return array(true, $data);
    }

    public function register(array $payload)
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($name === '' || $email === '' || $password === '')
        {
            return array(false, 'name, email, password are required');
        }
        $pw_err = Password_validator::validate($password);
        if ($pw_err !== null)
        {
            return array(false, $pw_err);
        }
        $existing = $this->ci->user_model->get_by_email($email);
        if ($existing)
        {
            return array(false, 'email already exists');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id = $this->ci->user_model->create(array(
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'role' => 'user',
        ));
        if ( ! $id)
        {
            return array(false, 'failed to create user');
        }

        $dev_otp = $this->issue_otp_verification((int) $id, $email, 'registration');

        $data = array(
            'id' => (int) $id,
            'email' => $email,
            'requires_verification' => true,
        );
        if (function_exists('sd_env_bool') && sd_env_bool('OTP_DEV_RETURN_CODE', false))
        {
            $data['otp'] = $dev_otp;
        }

        return array(true, $data);
    }

    public function forgot_password(array $payload)
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        if ($email === '')
        {
            return array(false, 'email is required');
        }

        $user = $this->ci->user_model->get_by_email($email);
        if ($user && isset($user->role) && (string) $user->role === 'admin')
        {
            return array(false, 'this action is not available for administrator accounts');
        }
        if ( ! $user)
        {
            return array(false, 'user not found');
        }

        $dev_otp = $this->issue_password_reset_otp_row($email);
        $data = array('status' => 'ok');
        if (function_exists('sd_env_bool') && sd_env_bool('OTP_DEV_RETURN_CODE', false))
        {
            $data['otp'] = $dev_otp;
        }

        return array(true, $data);
    }

    public function resend_otp(array $payload)
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $intent = isset($payload['intent']) ? trim((string) $payload['intent']) : '';
        if ($intent === 'reset_password')
        {
            $intent = 'password_reset';
        }
        $allowed = array('registration', 'account_activation', 'password_reset');

        if ($email === '' || $intent === '')
        {
            return array(false, 'email and intent are required');
        }
        if ( ! in_array($intent, $allowed, true))
        {
            return array(false, 'intent must be registration, account_activation, or reset_password');
        }

        $user = $this->ci->user_model->get_by_email($email);
        if ($intent === 'password_reset')
        {
            if ($user && isset($user->role) && (string) $user->role === 'admin')
            {
                return array(false, 'this action is not available for administrator accounts');
            }
            if ( ! $user)
            {
                return array(false, 'user not found');
            }

            $dev_otp = $this->issue_password_reset_otp_row($email);
            $data = array('status' => 'ok');
            if (function_exists('sd_env_bool') && sd_env_bool('OTP_DEV_RETURN_CODE', false))
            {
                $data['otp'] = $dev_otp;
            }

            return array(true, $data);
        }

        if ( ! $user)
        {
            return array(false, 'user not found');
        }
        if ($intent === 'registration' && (string) $user->role !== 'user')
        {
            return array(false, 'invalid resend intent');
        }
        if ($intent === 'account_activation' && (string) $user->role !== 'admin')
        {
            return array(false, 'invalid resend intent');
        }
        if (Email_verification::is_verified($user))
        {
            return array(false, 'email already verified');
        }

        $dev_otp = $this->issue_otp_verification((int) $user->id, $email, $intent);
        $data = array(
            'status' => 'ok',
            'requires_verification' => true,
            'intent' => $intent,
        );
        if (function_exists('sd_env_bool') && sd_env_bool('OTP_DEV_RETURN_CODE', false))
        {
            $data['otp'] = $dev_otp;
        }

        return array(true, $data);
    }

    public function verify_otp(array $payload)
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $otp = isset($payload['otp']) ? trim((string) $payload['otp']) : '';
        $intent = isset($payload['intent']) ? trim((string) $payload['intent']) : 'reset_password';
        if ($intent === 'reset_password')
        {
            $intent = 'password_reset';
        }
        if ($email === '' || $otp === '')
        {
            return array(false, 'email and otp are required');
        }
        $allowed = array('registration', 'account_activation', 'password_reset');
        if ( ! in_array($intent, $allowed, true))
        {
            return array(false, 'intent must be registration, account_activation, or reset_password');
        }

        if ($intent === 'registration' || $intent === 'account_activation')
        {
            return $this->verify_email_otp($email, $otp, $intent);
        }

        return $this->verify_password_reset_otp($email, $otp);
    }

    /**
     * @return array{0:bool,1:mixed}
     */
    private function verify_email_otp($email, $otp, $intent)
    {
        $row = $this->ci->otp_verification_model->latest_active_for_email_intent($email, $intent);
        if ( ! $row)
        {
            return array(false, 'invalid otp');
        }
        if ($row->otp_expires_at && strtotime((string) $row->otp_expires_at) < time())
        {
            return array(false, 'otp expired');
        }
        if ( ! password_verify($otp, (string) $row->otp_hash))
        {
            $this->ci->otp_verification_model->update_row((int) $row->id, array('attempts' => ((int) $row->attempts) + 1));
            return array(false, 'invalid otp');
        }

        $user = $this->ci->user_model->get_by_email($email);
        if ( ! $user || (int) $user->id !== (int) $row->user_id)
        {
            return array(false, 'invalid otp');
        }
        if ($intent === 'registration' && (string) $user->role !== 'user')
        {
            return array(false, 'invalid otp');
        }
        if ($intent === 'account_activation' && (string) $user->role !== 'admin')
        {
            return array(false, 'invalid otp');
        }
        if (Email_verification::is_verified($user))
        {
            return array(false, 'email already verified');
        }

        $this->ci->user_model->update_row((int) $user->id, array(
            'email_verified_at' => date('Y-m-d H:i:s'),
        ));
        $this->ci->otp_verification_model->update_row((int) $row->id, array(
            'deleted_at' => date('Y-m-d H:i:s'),
        ));

        $this->ci->load->library('jwt_auth');
        $user = $this->ci->user_model->find((int) $user->id);
        $access_token = $this->ci->jwt_auth->issue_access_token_for_user($user);
        $refresh_token = $this->ci->jwt_auth->issue_refresh_token_for_user($user);
        $ttl = $this->ci->jwt_auth->get_ttl_seconds();
        $refresh_ttl = $this->ci->jwt_auth->get_refresh_ttl_seconds();

        return array(true, array(
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'refresh_expires_in' => $refresh_ttl,
        ));
    }

    /**
     * @return array{0:bool,1:mixed}
     */
    private function verify_password_reset_otp($email, $otp)
    {
        $row = $this->ci->password_reset_model->latest_active_for_email_intent($email, 'password_reset');
        if ( ! $row)
        {
            return array(false, 'invalid otp');
        }
        if ($row->otp_expires_at && strtotime((string) $row->otp_expires_at) < time())
        {
            return array(false, 'otp expired');
        }
        if ( ! password_verify($otp, (string) $row->otp_hash))
        {
            $this->ci->password_reset_model->update_row((int) $row->id, array('attempts' => ((int) $row->attempts) + 1));
            return array(false, 'invalid otp');
        }

        $reset_token = bin2hex(random_bytes(24));
        $reset_hash = password_hash($reset_token, PASSWORD_BCRYPT);
        $reset_ttl = (int) (function_exists('sd_env_int') ? sd_env_int('RESET_TOKEN_TTL_SECONDS', 900) : 900);
        $reset_expires = date('Y-m-d H:i:s', time() + max(120, $reset_ttl));

        $this->ci->password_reset_model->update_row((int) $row->id, array(
            'verified_at' => date('Y-m-d H:i:s'),
            'reset_token_hash' => $reset_hash,
            'reset_expires_at' => $reset_expires,
        ));

        return array(true, array(
            'reset_token' => $reset_token,
            'reset_expires_at' => $reset_expires,
        ));
    }

    public function reset_password(array $payload)
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $token = isset($payload['reset_token']) ? trim((string) $payload['reset_token']) : '';
        $new_password = isset($payload['new_password']) ? (string) $payload['new_password'] : '';

        if ($email === '' || $token === '' || $new_password === '')
        {
            return array(false, 'email, reset_token, new_password are required');
        }
        $pw_err = Password_validator::validate($new_password);
        if ($pw_err !== null)
        {
            return array(false, $pw_err);
        }

        $row = $this->ci->password_reset_model->latest_active_password_reset_flow($email);
        if ( ! $row || ! $row->reset_token_hash)
        {
            return array(false, 'invalid reset token');
        }
        if ($row->reset_expires_at && strtotime((string) $row->reset_expires_at) < time())
        {
            return array(false, 'reset token expired');
        }
        if ( ! password_verify($token, (string) $row->reset_token_hash))
        {
            return array(false, 'invalid reset token');
        }

        $user = $this->ci->user_model->get_by_email($email);
        if ( ! $user)
        {
            return array(false, 'user not found');
        }
        if ((string) $user->role !== 'user')
        {
            return array(false, 'forbidden');
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $this->ci->user_model->update_row((int) $user->id, array(
            'password' => $hash,
        ));

        $this->ci->password_reset_model->update_row((int) $row->id, array(
            'deleted_at' => date('Y-m-d H:i:s'),
        ));

        $this->ci->load->library('jwt_auth');
        $user = $this->ci->user_model->find((int) $user->id);
        $access_token = $this->ci->jwt_auth->issue_access_token_for_user($user);
        $refresh_token = $this->ci->jwt_auth->issue_refresh_token_for_user($user);
        $ttl = $this->ci->jwt_auth->get_ttl_seconds();
        $refresh_ttl = $this->ci->jwt_auth->get_refresh_ttl_seconds();

        return array(true, array(
            'status' => 'ok',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'refresh_expires_in' => $refresh_ttl,
        ));
    }

    /**
     * @param int    $user_id
     * @param string $email
     * @param string $intent registration|account_activation
     * @return string OTP plaintext (for dev only)
     */
    private function issue_otp_verification($user_id, $email, $intent)
    {
        $otp = (string) random_int(100000, 999999);
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $ttl = (int) (function_exists('sd_env_int') ? sd_env_int('OTP_TTL_SECONDS', 600) : 600);
        $expires = date('Y-m-d H:i:s', time() + max(60, $ttl));

        $this->ci->otp_verification_model->create(array(
            'user_id' => (int) $user_id,
            'email' => $email,
            'intent' => $intent,
            'otp_hash' => $otp_hash,
            'otp_expires_at' => $expires,
            'attempts' => 0,
        ));

        $this->ci->stripe_log_model->log_event('auth.otp_verification.issued', array(
            'user_id' => (int) $user_id,
            'email' => $email,
            'intent' => $intent,
            'expires_at' => $expires,
            'dev_otp' => $otp,
        ));

        $this->send_otp_email($email, $otp, $intent);

        return $otp;
    }

    /**
     * Forgot-password OTP (still stored on password_resets).
     *
     * @return string OTP plaintext (for dev only)
     */
    private function issue_password_reset_otp_row($email)
    {
        $otp = (string) random_int(100000, 999999);
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $ttl = (int) (function_exists('sd_env_int') ? sd_env_int('OTP_TTL_SECONDS', 600) : 600);
        $expires = date('Y-m-d H:i:s', time() + max(60, $ttl));

        $this->ci->password_reset_model->create(array(
            'email' => $email,
            'intent' => 'password_reset',
            'otp_hash' => $otp_hash,
            'otp_expires_at' => $expires,
            'attempts' => 0,
        ));

        $this->ci->stripe_log_model->log_event('auth.otp.issued', array(
            'email' => $email,
            'intent' => 'password_reset',
            'expires_at' => $expires,
            'dev_otp' => $otp,
        ));

        $this->send_otp_email($email, $otp, 'password_reset');

        return $otp;
    }

    /**
     * @param string $intent registration|account_activation|password_reset
     */
    private function send_otp_email($email, $otp_plain, $intent)
    {
        $t0 = microtime(true);
        $transport = 'php_mail';
        if (function_exists('sd_env'))
        {
            $host = trim((string) sd_env('MAIL_HOST', ''));
            if ($host !== '')
            {
                $transport = 'smtp';
            }
        }

        $mailer = new Otp_mailer();
        $sent = $mailer->send($email, $otp_plain, $intent);
        $dt_ms = (int) round((microtime(true) - $t0) * 1000);
        $this->ci->stripe_log_model->log_event('auth.otp.email_dispatch', array(
            'email' => $email,
            'intent' => $intent,
            'sent' => $sent,
            'transport' => $transport,
            'duration_ms' => $dt_ms,
        ));
    }
}
