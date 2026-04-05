<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stripe_log_model extends MY_Model
{
    protected $table = 'stripe_logs';

    protected $allowed_fields = array(
        'event_id',
        'event_type',
        'payload',
        'processed',
    );

    /**
     * Log a non-webhook event (OTP, reconcile note, etc.): no Stripe event id, marked processed immediately.
     *
     * @param mixed $payload
     *
     * @return int|false insert id
     */
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
            'processed' => 1,
        ));
    }

    /**
     * Reserve processing for a Stripe webhook event (idempotency by event_id).
     *
     * @param string      $event_id   evt_… from Stripe; empty skips deduplication
     * @param string      $event_type
     * @param object|array $event_obj full event for payload
     *
     * @return array{action:string,log_id:?int} action = skip_processed|proceed
     */
    public function reserve_webhook_event($event_id, $event_type, $event_obj)
    {
        $event_id = trim((string) $event_id);
        $payload = is_object($event_obj) || is_array($event_obj) ? json_encode($event_obj) : (string) $event_obj;

        if ($event_id === '')
        {
            $log_id = $this->create(array(
                'event_type' => $event_type,
                'payload' => $payload,
                'processed' => 0,
            ));
            return array(
                'action' => 'proceed',
                'log_id' => $log_id ? (int) $log_id : null,
            );
        }

        $row = $this->db->get_where($this->table, array('event_id' => $event_id))->row();
        if ($row)
        {
            if ((int) $row->processed === 1)
            {
                return array('action' => 'skip_processed', 'log_id' => null);
            }
            return array('action' => 'proceed', 'log_id' => (int) $row->id);
        }

        $log_id = $this->create(array(
            'event_id' => $event_id,
            'event_type' => $event_type,
            'payload' => $payload,
            'processed' => 0,
        ));

        if ($log_id)
        {
            return array('action' => 'proceed', 'log_id' => (int) $log_id);
        }

        $row = $this->db->get_where($this->table, array('event_id' => $event_id))->row();
        if ($row)
        {
            if ((int) $row->processed === 1)
            {
                return array('action' => 'skip_processed', 'log_id' => null);
            }
            return array('action' => 'proceed', 'log_id' => (int) $row->id);
        }

        return array('action' => 'proceed', 'log_id' => null);
    }

    public function mark_processed($log_id)
    {
        if ($log_id < 1)
        {
            return false;
        }
        return $this->update_row((int) $log_id, array('processed' => 1));
    }

    public function recent($limit = 100)
    {
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get($this->table)->result();
    }
}
