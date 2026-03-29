<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('sd_env'))
{
    function sd_env($key, $default = null)
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '')
        {
            return $_ENV[$key];
        }
        $v = getenv($key);
        if ($v !== false && $v !== '')
        {
            return $v;
        }

        return $default;
    }
}

if ( ! function_exists('sd_env_bool'))
{
    function sd_env_bool($key, $default = false)
    {
        $v = sd_env($key, null);
        if ($v === null || $v === '')
        {
            return $default;
        }

        return in_array(strtolower((string) $v), array('1', 'true', 'yes', 'on'), true);
    }
}

if ( ! function_exists('sd_env_int'))
{
    function sd_env_int($key, $default = 0)
    {
        $v = sd_env($key, null);
        if ($v === null || $v === '')
        {
            return $default;
        }

        return (int) $v;
    }
}

if ( ! function_exists('sd_env_first'))
{
    function sd_env_first(array $keys, $default = null)
    {
        foreach ($keys as $key)
        {
            $v = sd_env($key, null);
            if ($v !== null && $v !== '')
            {
                return $v;
            }
        }

        return $default;
    }
}

if ( ! function_exists('sd_env_int_first'))
{
    function sd_env_int_first(array $keys, $default = 0)
    {
        foreach ($keys as $key)
        {
            $v = sd_env($key, null);
            if ($v !== null && $v !== '')
            {
                return (int) $v;
            }
        }

        return (int) $default;
    }
}
