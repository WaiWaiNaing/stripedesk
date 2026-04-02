<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart_model extends MY_Model
{
    protected $table = 'carts';

    protected $allowed_fields = array(
        'user_id',
        'status',
        'total_amount',
        'expires_at',
        'created_by',
        'updated_by',
        'deleted_at',
    );

    public function get_by_id_for_access($user, $cart_id)
    {
        $this->db->where('id', (int) $cart_id);
        $this->db->where('deleted_at', null);
        if (isset($user->role) && $user->role !== 'admin')
        {
            $this->db->where('user_id', (int) $user->id);
        }
        return $this->db->get($this->table)->row();
    }

    public function update_status($cart_id, $status, $updated_by = null)
    {
        $data = array('status' => (string) $status);
        if ($updated_by !== null)
        {
            $data['updated_by'] = (int) $updated_by;
        }
        return $this->update_row((int) $cart_id, $data);
    }
}

