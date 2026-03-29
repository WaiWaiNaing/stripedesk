<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Currency_model extends MY_Model
{
    protected $table = 'currencies';

    protected $allowed_fields = array(
        'code',
        'numeric_code',
        'name',
        'minor_unit',
        'symbol',
        'is_default',
    );

    public function get_by_code($code)
    {
        $code = strtoupper(trim((string) $code));
        if (strlen($code) !== 3)
        {
            return null;
        }
        return $this->db->get_where($this->table, array('code' => $code, 'deleted_at' => null))->row();
    }

    public function get_default()
    {
        $this->db->where('is_default', 1);
        $this->db->where('deleted_at', null);
        return $this->db->get($this->table)->row();
    }

    public function all_active($order_by_code = true)
    {
        $this->db->where('deleted_at', null);
        if ($order_by_code)
        {
            $this->db->order_by('code', 'ASC');
        }
        return $this->db->get($this->table)->result();
    }
}
