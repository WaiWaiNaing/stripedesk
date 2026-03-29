<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends MY_Model
{
    protected $table = 'products';

    protected $allowed_fields = array(
        'name',
        'description',
        'price',
        'currency_id',
        'stripe_price_id',
    );

    public function get_active_catalog($limit = null, $offset = 0)
    {
        $this->db->where('deleted_at', null);
        $this->db->order_by('name', 'ASC');
        if ($limit !== null)
        {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get($this->table)->result();
    }

    public function get_by_stripe_price_id($stripe_price_id)
    {
        $this->db->where('stripe_price_id', $stripe_price_id);
        $this->db->where('deleted_at', null);
        return $this->db->get($this->table)->row();
    }

    public function get_active_catalog_with_currency()
    {
        $this->db->select('products.*, currencies.code AS currency_code');
        $this->db->from($this->table);
        $this->db->join('currencies', 'currencies.id = products.currency_id');
        $this->db->where('products.deleted_at', null);
        $this->db->where('currencies.deleted_at', null);
        $this->db->order_by('products.name', 'ASC');
        return $this->db->get()->result();
    }
}
