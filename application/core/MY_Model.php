<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    protected $table = '';
    protected $primary_key = 'id';
    protected $allowed_fields = array();
    protected $audit_fields = array(
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'deleted_at',
    );

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

    public function soft_deactivate($id)
    {
        if ( ! in_array('deleted_at', $this->audit_fields, TRUE))
        {
            return false;
        }
        return $this->update_row($id, array('deleted_at' => date('Y-m-d H:i:s')));
    }

    public function restore_soft_delete($id)
    {
        if ( ! in_array('deleted_at', $this->audit_fields, TRUE))
        {
            return false;
        }
        $this->db->set('deleted_at', null);
        $this->db->where($this->primary_key, $id);
        return $this->db->update($this->table);
    }

    protected function filter_allowed(array $data)
    {
        $allowed = array_merge($this->allowed_fields, $this->audit_fields);
        if (empty($allowed))
        {
            return $data;
        }
        return array_intersect_key($data, array_flip($allowed));
    }
}
