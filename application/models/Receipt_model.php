<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt_model extends MY_Model
{
    protected $table = 'receipts';

    protected $allowed_fields = array(
        'invoice_id',
        'currency_id',
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

    public function list_with_access_scope($user_id, $is_admin)
    {
        $this->db->select('
            receipts.id,
            receipts.invoice_id,
            receipts.receipt_number,
            receipts.stripe_payment_intent,
            receipts.amount_paid,
            receipts.paid_at,
            receipts.created_at,
            invoices.order_id,
            invoices.invoice_number,
            invoices.total_amount,
            invoices.status,
            invoices.due_date,
            invoices.created_at AS invoice_created_at
        ', false);
        $this->db->from($this->table);
        $this->db->join('invoices', 'invoices.id = receipts.invoice_id');
        $this->db->join('orders', 'orders.id = invoices.order_id');
        $this->db->where('receipts.deleted_at', null);
        $this->db->where('invoices.deleted_at', null);
        $this->db->where('orders.deleted_at', null);
        if ( ! $is_admin)
        {
            $this->db->where('orders.user_id', (int) $user_id);
        }
        $this->db->order_by('receipts.id', 'DESC');
        return $this->db->get()->result();
    }

    public function list_for_order_user_id($order_user_id)
    {
        $this->db->select('
            receipts.id,
            receipts.invoice_id,
            receipts.receipt_number,
            receipts.stripe_payment_intent,
            receipts.amount_paid,
            receipts.paid_at,
            receipts.created_at,
            invoices.order_id,
            invoices.invoice_number,
            invoices.total_amount,
            invoices.status,
            invoices.due_date,
            invoices.created_at AS invoice_created_at
        ', false);
        $this->db->from($this->table);
        $this->db->join('invoices', 'invoices.id = receipts.invoice_id');
        $this->db->join('orders', 'orders.id = invoices.order_id');
        $this->db->where('receipts.deleted_at', null);
        $this->db->where('invoices.deleted_at', null);
        $this->db->where('orders.deleted_at', null);
        $this->db->where('orders.user_id', (int) $order_user_id);
        $this->db->order_by('receipts.id', 'DESC');
        return $this->db->get()->result();
    }
}
