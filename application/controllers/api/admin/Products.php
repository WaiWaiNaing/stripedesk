<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Api_base_controller
{
    public function index()
    {
        $method = strtoupper((string) $this->input->server('REQUEST_METHOD'));

        if ($method === 'GET')
        {
            $include_deleted = false;
            $user = $this->authenticated_user();
            if ($user !== null && isset($user->role) && (string) $user->role === 'admin')
            {
                $include_deleted = $this->input->get('include_deleted') ? true : false;
            }
            $this->load->model('product_model');
            $rows = $this->product_model->list_all_with_currency($include_deleted);
            $data = array();
            foreach ($rows as $r)
            {
                $data[] = array(
                    'id' => (int) $r->id,
                    'name' => (string) $r->name,
                    'description' => $r->description !== null ? (string) $r->description : null,
                    'price' => (string) $r->price,
                    'currency_code' => (string) $r->currency_code,
                    'stripe_price_id' => $r->stripe_price_id !== null ? (string) $r->stripe_price_id : null,
                    'deleted_at' => $r->deleted_at,
                    'created_at' => (string) $r->created_at,
                );
            }
            $this->emit(\Stripedesk\Api\Api_success_response::with_data($data));
            return;
        }

        if ($method === 'POST')
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
            $body = $this->json_body();
            if ($body === null)
            {
                return;
            }

            $svc = new \Stripedesk\Services\Admin_product_service($this);
            list($ok, $result) = $svc->create_product($body, $user->id);
            if ( ! $ok)
            {
                $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'validation_error', (string) $result));
                return;
            }
            $this->emit(\Stripedesk\Api\Api_success_response::with_data($result, 201));
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

        $svc = new \Stripedesk\Services\Admin_product_service($this);
        list($ok, $result) = $svc->update_product((int) $id, $body, $user->id);
        if ( ! $ok)
        {
            $this->emit(\Stripedesk\Api\Api_error_response::create(400, 'update_failed', (string) $result));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result));
    }
}

