<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends Auditable_model
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
}
