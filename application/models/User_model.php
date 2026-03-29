<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends Auditable_model
{
    protected $table = 'users';

    protected $allowed_fields = array(
        'name',
        'email',
        'password',
        'role',
        'api_token',
    );

    public function get_by_email($email)
    {
        return $this->db->get_where($this->table, array('email' => $email))->row();
    }

    public function get_by_api_token($token)
    {
        $this->db->where('api_token', $token);
        $this->db->where('deleted_at', null);
        return $this->db->get($this->table)->row();
    }

    public function get_active($limit = null, $offset = 0)
    {
        $this->db->where('deleted_at', null);
        if ($limit !== null)
        {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get($this->table)->result();
    }
}
