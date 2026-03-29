<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_item_model extends Auditable_model
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
}
