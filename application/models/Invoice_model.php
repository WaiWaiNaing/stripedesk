<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends MY_Model
{
    protected $table = 'invoices';

    protected $allowed_fields = array(
        'order_id',
        'invoice_number',
        'total_amount',
        'status',
        'due_date',
    );

    public function get_by_order_id($order_id)
    {
        return $this->db->get_where($this->table, array('order_id' => $order_id))->row();
    }

    public function get_by_invoice_number($invoice_number)
    {
        return $this->db->get_where($this->table, array('invoice_number' => $invoice_number))->row();
    }

    public function get_by_status($status, $limit = null, $offset = 0)
    {
        $this->db->where('status', $status);
        $this->db->order_by('id', 'DESC');
        if ($limit !== null)
        {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get($this->table)->result();
    }

    public function list_with_access_scope($user_id, $is_admin)
    {
        $this->db->select('invoices.*');
        $this->db->from($this->table);
        $this->db->join('orders', 'orders.id = invoices.order_id');
        $this->db->where('invoices.deleted_at', null);
        $this->db->where('orders.deleted_at', null);
        if ( ! $is_admin)
        {
            $this->db->where('orders.user_id', (int) $user_id);
        }
        $this->db->order_by('invoices.id', 'DESC');
        return $this->db->get()->result();
    }

    public function find_with_order_for_access($invoice_id)
    {
        $this->db->select('invoices.*, orders.user_id AS order_user_id');
        $this->db->from($this->table);
        $this->db->join('orders', 'orders.id = invoices.order_id');
        $this->db->where('invoices.id', (int) $invoice_id);
        $this->db->where('invoices.deleted_at', null);
        $this->db->where('orders.deleted_at', null);
        return $this->db->get()->row();
    }
}
