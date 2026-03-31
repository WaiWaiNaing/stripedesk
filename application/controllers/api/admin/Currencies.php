<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Currencies extends Api_base_controller
{
    public function index()
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if ( ! $this->require_admin($user))
        {
            return;
        }

        $method = strtoupper((string) $this->input->server('REQUEST_METHOD'));
        $this->load->model('currency_model');

        if ($method === 'GET')
        {
            $rows = $this->currency_model->all_active(true);
            $data = array();
            foreach ($rows as $r)
            {
                $data[] = array(
                    'id' => (int) $r->id,
                    'code' => (string) $r->code,
                    'numeric_code' => $r->numeric_code !== null ? (string) $r->numeric_code : null,
                    'name' => (string) $r->name,
                    'minor_unit' => (int) $r->minor_unit,
                    'symbol' => $r->symbol !== null ? (string) $r->symbol : null,
                    'is_default' => (int) $r->is_default === 1,
                    'created_at' => (string) $r->created_at,
                );
            }
            $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
            return;
        }

        if ($method === 'POST')
        {
            $body = $this->json_body();
            if ($body === null)
            {
                return;
            }

            $code = isset($body['code']) ? strtoupper(trim((string) $body['code'])) : '';
            $name = isset($body['name']) ? trim((string) $body['name']) : '';
            $numeric_code = isset($body['numeric_code']) ? trim((string) $body['numeric_code']) : null;
            $minor_unit = isset($body['minor_unit']) ? (int) $body['minor_unit'] : 2;
            $symbol = isset($body['symbol']) ? (string) $body['symbol'] : null;
            $is_default = isset($body['is_default']) ? ((bool) $body['is_default']) : false;

            if ($code === '' || strlen($code) !== 3 || $name === '')
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'validation_error', 'code (3 letters) and name are required'));
                return;
            }
            if ($minor_unit < 0) $minor_unit = 0;
            if ($minor_unit > 6) $minor_unit = 6;

            if ($this->currency_model->get_by_code($code))
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'validation_error', 'currency code already exists'));
                return;
            }

            if ($is_default)
            {
                $this->db->set('is_default', 0);
                $this->db->where('deleted_at', null);
                $this->db->update('currencies');
            }

            $id = $this->currency_model->create(array(
                'code' => $code,
                'numeric_code' => $numeric_code,
                'name' => $name,
                'minor_unit' => $minor_unit,
                'symbol' => $symbol,
                'is_default' => $is_default ? 1 : 0,
                'created_by' => (int) $user->id,
                'updated_by' => (int) $user->id,
            ));

            if ( ! $id)
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'create_failed', 'failed to create currency'));
                return;
            }

            $this->emit(\Stripedesk\Api\Api_success_response::with_data(array('id' => (int) $id), 201));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_error_response::create(405, 'method_not_allowed', 'Use GET or POST'));
    }

    public function update($id)
    {
        $user = $this->authenticated_user();
        if ($user === null)
        {
            return;
        }
        if ( ! $this->require_admin($user))
        {
            return;
        }
        if ( ! $this->require_method('PUT'))
        {
            return;
        }
        $body = $this->json_body();
        if ($body === null)
        {
            return;
        }

        $this->load->model('currency_model');
        $currency = $this->currency_model->find((int) $id);
        if ( ! $currency || $currency->deleted_at !== null)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(404, 'not_found', 'currency not found'));
            return;
        }

        // Code is immutable in this API (use create + migrate products if needed).
        $name = isset($body['name']) ? trim((string) $body['name']) : (string) $currency->name;
        $numeric_code = array_key_exists('numeric_code', $body) ? trim((string) $body['numeric_code']) : $currency->numeric_code;
        $minor_unit = isset($body['minor_unit']) ? (int) $body['minor_unit'] : (int) $currency->minor_unit;
        $symbol = array_key_exists('symbol', $body) ? (string) $body['symbol'] : $currency->symbol;
        $is_default = array_key_exists('is_default', $body) ? ((bool) $body['is_default']) : ((int) $currency->is_default === 1);

        if ($name === '')
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'validation_error', 'name is required'));
            return;
        }
        if ($minor_unit < 0) $minor_unit = 0;
        if ($minor_unit > 6) $minor_unit = 6;

        if ($is_default)
        {
            $this->db->set('is_default', 0);
            $this->db->where('deleted_at', null);
            $this->db->update('currencies');
        }

        $ok = $this->currency_model->update_row((int) $id, array(
            'name' => $name,
            'numeric_code' => $numeric_code !== '' ? $numeric_code : null,
            'minor_unit' => $minor_unit,
            'symbol' => $symbol !== '' ? $symbol : null,
            'is_default' => $is_default ? 1 : 0,
            'updated_by' => (int) $user->id,
        ));
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'update_failed', 'failed to update currency'));
            return;
        }

        $this->emit(\Stripedesk\Api\Api_success_response::with_data(array('id' => (int) $id)));
    }
}

