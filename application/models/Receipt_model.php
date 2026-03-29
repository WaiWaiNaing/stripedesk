<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt_model extends MY_Model
{
    protected $table = 'receipts';

    protected $allowed_fields = array(
        'invoice_id',
        'receipt_number',
        'stripe_payment_intent',
        'amount_paid',
        'paid_at',
    );

    public function get_by_invoice_id($invoice_id)
    {
        return $this->db->get_where($this->table, array('invoice_id' => $invoice_id))->row();
    }

    public function get_by_receipt_number($receipt_number)
    {
        return $this->db->get_where($this->table, array('receipt_number' => $receipt_number))->row();
    }

    public function get_by_payment_intent($payment_intent_id)
    {
        return $this->db->get_where($this->table, array('stripe_payment_intent' => $payment_intent_id))->row();
    }

    public function find_active($id)
    {
        $this->db->where($this->primary_key, (int) $id);
        $this->db->where('deleted_at', null);
        return $this->db->get($this->table)->row();
    }
}
