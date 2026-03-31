<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password_reset_model extends MY_Model
{
    protected $table = 'password_resets';

    protected $allowed_fields = array(
        'email',
        'intent',
        'otp_hash',
        'otp_expires_at',
        'verified_at',
        'reset_token_hash',
        'reset_expires_at',
        'attempts',
    );

    public function latest_active_for_email($email)
    {
        $this->db->where('email', (string) $email);
        $this->db->where('deleted_at', null);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }

    /**
     * @param string $email
     * @param string $intent registration|password_reset
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

    /**
     * Latest password-reset row that may carry a reset token (forgot-password flow).
     */
    public function latest_active_password_reset_flow($email)
    {
        $this->db->where('email', (string) $email);
        $this->db->where('intent', 'password_reset');
        $this->db->where('deleted_at', null);
        $this->db->where('reset_token_hash IS NOT NULL', null, false);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }
}

