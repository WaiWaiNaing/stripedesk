<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auditable_model extends Base_Model
{
    protected $audit_fields = array(
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
        'deleted_at',
    );

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
