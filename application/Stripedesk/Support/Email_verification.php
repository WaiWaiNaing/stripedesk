<?php

namespace Stripedesk\Support;

final class Email_verification
{
    /**
     * @param object $user DB row
     */
    public static function is_verified($user)
    {
        if ( ! is_object($user))
        {
            return false;
        }
        if ( ! property_exists($user, 'email_verified_at'))
        {
            return true;
        }
        $v = $user->email_verified_at;
        if ($v === null || $v === '')
        {
            return false;
        }
        if (is_string($v) && strpos($v, '0000-00-00') === 0)
        {
            return false;
        }

        return true;
    }
}
