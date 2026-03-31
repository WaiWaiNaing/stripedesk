<?php

namespace Stripedesk\Validation;

/**
 * Shared password policy for registration, reset, and admin-created accounts.
 * Matches frontend rules in frontend/src/utils/passwordRules.js.
 */
final class Password_validator
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 128;

    /**
     * @param mixed $password
     * @return string|null Error message, or null if valid.
     */
    public static function validate($password)
    {
        if ( ! is_string($password))
        {
            return 'password is invalid';
        }
        $len = strlen($password);
        if ($len < self::MIN_LENGTH)
        {
            return 'password must be at least '.self::MIN_LENGTH.' characters';
        }
        if ($len > self::MAX_LENGTH)
        {
            return 'password must be at most '.self::MAX_LENGTH.' characters';
        }
        if ( ! preg_match('/[A-Z]/', $password))
        {
            return 'password must contain an uppercase letter';
        }
        if ( ! preg_match('/[a-z]/', $password))
        {
            return 'password must contain a lowercase letter';
        }
        if ( ! preg_match('/[0-9]/', $password))
        {
            return 'password must contain a digit';
        }
        if ( ! preg_match('/[^A-Za-z0-9]/', $password))
        {
            return 'password must contain a special character';
        }

        return null;
    }
}
