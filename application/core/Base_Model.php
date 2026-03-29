<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Model extends CI_Model
{
    protected $table = '';
    protected $primary_key = 'id';
    protected $allowed_fields = array();

    public function __construct()
    {
        parent::__construct();
        if ($this->table === '')
        {
            show_error('Model ' . get_class($this) . ' must define $table.');
        }
    }

    public function find($id)
    {
        return $this->db->get_where($this->table, array($this->primary_key => $id))->row();
    }

    public function all($limit = null, $offset = 0)
    {
        if ($limit !== null)
        {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get($this->table)->result();
    }

    public function create(array $data)
    {
        $row = $this->filter_allowed($data);
        if (empty($row))
        {
            return false;
        }
        if ($this->db->insert($this->table, $row))
        {
            return (int) $this->db->insert_id();
        }
        return false;
    }

    public function update_row($id, array $data)
    {
        $row = $this->filter_allowed($data);
        if (empty($row))
        {
            return false;
        }
        $this->db->where($this->primary_key, $id);
        return $this->db->update($this->table, $row);
    }

    protected function filter_allowed(array $data)
    {
        if (empty($this->allowed_fields))
        {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->allowed_fields));
    }
}

require_once APPPATH . 'core/Auditable_model.php';
