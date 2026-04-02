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

    /**
     * Any cart with status active (not deleted), including expired; at most one per user is expected.
     */
    public function find_active_cart_for_user($user_id)
    {
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('status', 'active');
        $this->db->where('deleted_at', null);
        $this->db->order_by('updated_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }

    /**
     * Latest active, non-expired cart for a user (by updated_at, then id).
     */
    public function find_latest_active_cart_for_user($user_id)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('status', 'active');
        $this->db->where('deleted_at', null);
        $this->db->where('expires_at >=', $now);
        $this->db->order_by('updated_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
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

    /**
     * Set carts.total_amount from SUM(cart_items.price * cart_items.quantity).
     */
    public function recalculate_total_amount($cart_id, $updated_by = null)
    {
        $this->db->select('SUM(cart_items.price * cart_items.quantity) AS cart_total', false);
        $this->db->from('cart_items');
        $this->db->where('cart_items.cart_id', (int) $cart_id);
        $row = $this->db->get()->row();
        $total = 0.0;
        if ($row && $row->cart_total !== null && $row->cart_total !== '')
        {
            $total = (float) $row->cart_total;
        }
        $data = array(
            'total_amount' => number_format($total, 2, '.', ''),
        );
        if ($updated_by !== null)
        {
            $data['updated_by'] = (int) $updated_by;
        }
        return $this->update_row((int) $cart_id, $data);
    }
}

