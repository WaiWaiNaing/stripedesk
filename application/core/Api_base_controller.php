<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class Api_base_controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->apply_cors();
        $this->output->set_content_type('application/json', 'UTF-8');
    }
    protected function apply_cors()
    {
        if ( ! function_exists('sd_env'))
        {
            return;
        }

        $request_origin = (string) $this->input->server('HTTP_ORIGIN');
        if ($request_origin === '')
        {
            return;
        }

        $allowed_origin = $this->resolve_allowed_origin($request_origin);
        if ($allowed_origin === null)
        {
            return;
        }

        $this->output->set_header('Vary: Origin');
        $this->output->set_header('Access-Control-Allow-Origin: ' . $allowed_origin);
        $this->output->set_header('Access-Control-Allow-Credentials: true');
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $this->output->set_header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'OPTIONS')
        {
            $this->output->set_status_header(204);
            $this->output->_display();
            exit;
        }
    }

    protected function resolve_allowed_origin($request_origin)
    {
        $configured = trim((string) sd_env('CORS_ALLOW_ORIGIN', ''));
        if ($configured !== '')
        {
            if ($configured === '*')
            {
                return $request_origin;
            }

            $origins = preg_split('/\s*,\s*/', $configured);
            if (is_array($origins))
            {
                foreach ($origins as $origin)
                {
                    if ($origin !== '' && $origin === $request_origin)
                    {
                        return $request_origin;
                    }
                }
            }

            return null;
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development')
        {
            if (preg_match('#\Ahttps?://(localhost|127\.0\.0\.1)(:\d+)?\z#', $request_origin))
            {
                return $request_origin;
            }
        }

        return null;
    }

    protected function emit(\Stripedesk\Api\Api_response $response)
    {
        $response->emit($this->output);
    }

    protected function set_auth_cookies($access_token, $access_ttl_seconds, $refresh_token, $refresh_ttl_seconds)
    {
        if ($access_token === '' || $refresh_token === '')
        {
            return;
        }
        $access_cookie_name = function_exists('sd_env') ? (string) sd_env('AUTH_ACCESS_COOKIE_NAME', sd_env('AUTH_COOKIE_NAME', 'stripedesk_access_token')) : 'stripedesk_access_token';
        $refresh_cookie_name = function_exists('sd_env') ? (string) sd_env('AUTH_REFRESH_COOKIE_NAME', 'stripedesk_refresh_token') : 'stripedesk_refresh_token';
        $same_site = function_exists('sd_env') ? strtolower((string) sd_env('AUTH_COOKIE_SAMESITE', 'None')) : 'none';
        if ($same_site !== 'none' && $same_site !== 'strict' && $same_site !== 'lax')
        {
            $same_site = 'none';
        }
        $same_site_header = ucfirst($same_site);
        $secure = function_exists('sd_env_bool') ? sd_env_bool('AUTH_COOKIE_SECURE', true) : true;

        $this->send_auth_cookie($access_cookie_name, $access_token, $access_ttl_seconds, $same_site, $same_site_header, $secure);
        $this->send_auth_cookie($refresh_cookie_name, $refresh_token, $refresh_ttl_seconds, $same_site, $same_site_header, $secure);
    }

    protected function clear_auth_cookie()
    {
        $access_cookie_name = function_exists('sd_env') ? (string) sd_env('AUTH_ACCESS_COOKIE_NAME', sd_env('AUTH_COOKIE_NAME', 'stripedesk_access_token')) : 'stripedesk_access_token';
        $refresh_cookie_name = function_exists('sd_env') ? (string) sd_env('AUTH_REFRESH_COOKIE_NAME', 'stripedesk_refresh_token') : 'stripedesk_refresh_token';
        $same_site = function_exists('sd_env') ? strtolower((string) sd_env('AUTH_COOKIE_SAMESITE', 'None')) : 'none';
        if ($same_site !== 'none' && $same_site !== 'strict' && $same_site !== 'lax')
        {
            $same_site = 'none';
        }
        $same_site_header = ucfirst($same_site);
        $secure = function_exists('sd_env_bool') ? sd_env_bool('AUTH_COOKIE_SECURE', true) : true;
        $this->send_expired_cookie($access_cookie_name, $same_site, $same_site_header, $secure);
        $this->send_expired_cookie($refresh_cookie_name, $same_site, $same_site_header, $secure);
    }

    protected function authenticated_user()
    {
        $this->load->library('jwt_auth');
        $user = $this->jwt_auth->get_authenticated_user();
        if ( ! $user)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(401, 'unauthorized', 'Invalid or missing bearer token'));
            return null;
        }

        return $user;
    }

    protected function require_admin($user)
    {
        if ( ! $user || ! isset($user->role) || $user->role !== 'admin')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(403, 'forbidden', 'Admin access required'));
            return false;
        }
        return true;
    }

    protected function require_method($method)
    {
        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== strtoupper((string) $method))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(405, 'method_not_allowed', 'Use ' . strtoupper((string) $method)));
            return false;
        }
        return true;
    }

    protected function json_body()
    {
        $raw = $this->input->raw_input_stream;
        $body = json_decode($raw, true);
        if ( ! is_array($body))
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'invalid_json', 'JSON body required'));
            return null;
        }
        return $body;
    }

    private function send_auth_cookie($cookie_name, $token, $ttl_seconds, $same_site, $same_site_header, $secure)
    {
        $ttl = max(60, (int) $ttl_seconds);
        $expires = gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT';
        $parts = array(
            rawurlencode((string) $cookie_name) . '=' . rawurlencode((string) $token),
            'Path=/',
            'Expires=' . $expires,
            'Max-Age=' . $ttl,
            'HttpOnly',
            'SameSite=' . $same_site_header,
        );
        if ($secure || $same_site === 'none')
        {
            $parts[] = 'Secure';
        }
        header('Set-Cookie: ' . implode('; ', $parts), false);
    }

    private function send_expired_cookie($cookie_name, $same_site, $same_site_header, $secure)
    {
        $parts = array(
            rawurlencode((string) $cookie_name) . '=',
            'Path=/',
            'Expires=Thu, 01 Jan 1970 00:00:00 GMT',
            'Max-Age=0',
            'HttpOnly',
            'SameSite=' . $same_site_header,
        );
        if ($secure || $same_site === 'none')
        {
            $parts[] = 'Secure';
        }
        header('Set-Cookie: ' . implode('; ', $parts), false);
    }
}
