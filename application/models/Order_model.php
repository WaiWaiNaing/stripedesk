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
        'stripe_payment_intent',
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

    /**
     * Pending orders that started Stripe Checkout (for cron reconciliation).
     *
     * @param int $limit
     * @return array
     */
    public function list_pending_with_stripe_session($limit = 300)
    {
        $this->db->where('status', 'pending');
        $this->db->where('stripe_session_id IS NOT NULL', null, false);
        $this->db->where('stripe_session_id !=', '');
        $this->db->order_by('id', 'ASC');
        $this->db->limit((int) $limit);
        return $this->db->get($this->table)->result();
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
