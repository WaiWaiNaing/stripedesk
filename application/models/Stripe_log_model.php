<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stripe_log_model extends Auditable_model
{
    protected $table = 'stripe_logs';

    protected $allowed_fields = array(
        'event_type',
        'payload',
    );

    public function log_event($event_type, $payload)
    {
        $encoded = $payload;
        if (is_array($payload) || is_object($payload))
        {
            $encoded = json_encode($payload);
        }
        return $this->create(array(
            'event_type' => $event_type,
            'payload' => $encoded,
        ));
    }

    public function recent($limit = 100)
    {
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get($this->table)->result();
    }
}
