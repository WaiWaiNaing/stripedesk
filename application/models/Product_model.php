<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends Auditable_model
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
}
