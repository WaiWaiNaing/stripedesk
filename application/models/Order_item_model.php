<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_item_model extends MY_Model
{
    protected $table = 'order_items';

    protected $allowed_fields = array(
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
    );

    public function get_by_order_id($order_id)
    {
        $this->db->where('order_id', $order_id);
        $this->db->order_by('id', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function get_by_product_id($product_id)
    {
        $this->db->where('product_id', $product_id);
        return $this->db->get($this->table)->result();
    }

    public function list_with_product_names_for_order($order_id)
    {
        $this->db->select('order_items.*, products.name AS product_name');
        $this->db->from($this->table);
        $this->db->join('products', 'products.id = order_items.product_id');
        $this->db->where('order_items.order_id', (int) $order_id);
        $this->db->where('order_items.deleted_at', null);
        $this->db->where('products.deleted_at', null);
        $this->db->order_by('order_items.id', 'ASC');
        return $this->db->get()->result();
    }
}
