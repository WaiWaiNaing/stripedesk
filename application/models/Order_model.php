<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends MY_Model
{
    protected $table = 'orders';

    protected $allowed_fields = array(
        'user_id',
        'total_amount',
        'currency_id',
        'status',
        'stripe_session_id',
    );

    public function get_by_user($user_id, $limit = null, $offset = 0)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        if ($limit !== null)
        {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get($this->table)->result();
    }

    public function get_by_stripe_session_id($session_id)
    {
        return $this->db->get_where($this->table, array('stripe_session_id' => $session_id))->row();
    }

    public function get_with_items($order_id)
    {
        $order = $this->find($order_id);
        if ( ! $order)
        {
            return array(null, array());
        }
        $this->load->model('order_item_model');
        $items = $this->order_item_model->get_by_order_id($order_id);
        return array($order, $items);
    }
}
