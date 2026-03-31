<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Api_base_controller
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

        if ($method === 'GET')
        {
            $limit = (int) $this->input->get('limit');
            if ($limit < 1) $limit = 200;
            if ($limit > 500) $limit = 500;

            $this->load->model('user_model');
            $rows = $this->user_model->list_all_active($limit);
            $data = array();
            foreach ($rows as $r)
            {
                $data[] = array(
                    'id' => (int) $r->id,
                    'name' => (string) $r->name,
                    'email' => (string) $r->email,
                    'role' => (string) $r->role,
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

            $svc = new \Stripedesk\Services\Admin_user_service($this);
            list($ok, $result) = $svc->create_user($body, $user->id);
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

    public function delete($id)
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
        if ( ! $this->require_method('DELETE'))
        {
            return;
        }

        $svc = new \Stripedesk\Services\Admin_user_service($this);
        list($ok, $result) = $svc->soft_delete_user((int) $id, (int) $user->id);
        if ( ! $ok)
        {
            $code = ((string) $result === 'user not found') ? 404 : 400;
            $this->emit(\Stripedesk\Api\Api_error_response::create($code, 'validation_error', (string) $result));
            return;
        }
        $this->emit(\Stripedesk\Api\Api_success_response::with_data($result));
    }
}

