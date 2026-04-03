<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart_item_model extends MY_Model
{
    protected $table = 'cart_items';

    protected $allowed_fields = array(
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'updated_at',
    );

    public function get_by_cart_id($cart_id)
    {
        $this->db->where('cart_id', (int) $cart_id);
        $this->db->order_by('id', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function find_by_cart_and_product($cart_id, $product_id)
    {
        return $this->db->get_where($this->table, array(
            'cart_id' => (int) $cart_id,
            'product_id' => (int) $product_id,
        ))->row();
    }

    public function delete_by_cart_and_product($cart_id, $product_id)
    {
        $this->db->where('cart_id', (int) $cart_id);
        $this->db->where('product_id', (int) $product_id);
        $this->db->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function list_with_product_names_for_cart($cart_id)
    {
        $this->db->select('cart_items.*, products.name AS product_name, products.description AS product_description, products.currency_id AS product_currency_id');
        $this->db->from($this->table);
        $this->db->join('products', 'products.id = cart_items.product_id');
        $this->db->where('cart_items.cart_id', (int) $cart_id);
        $this->db->where('products.deleted_at', null);
        $this->db->order_by('cart_items.id', 'ASC');
        return $this->db->get()->result();
    }
}

