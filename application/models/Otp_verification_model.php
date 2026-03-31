<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email verification OTPs (self-registration and admin-created accounts).
 * Password-reset OTPs remain on password_resets.
 */
class Otp_verification_model extends MY_Model
{
    protected $table = 'otp_verifications';

    protected $allowed_fields = array(
        'user_id',
        'email',
        'intent',
        'otp_hash',
        'otp_expires_at',
        'verified_at',
        'attempts',
    );

    /**
     * Latest non-deleted row for email + intent (registration | account_activation).
     *
     * @param string $intent registration|account_activation
     */
    public function latest_active_for_email_intent($email, $intent)
    {
        $this->db->where('email', (string) $email);
        $this->db->where('intent', (string) $intent);
        $this->db->where('deleted_at', null);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }
}
