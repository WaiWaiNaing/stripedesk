<?php

namespace Stripedesk\Services;

use Stripedesk\Validation\Password_validator;

final class Admin_user_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('user_model');
    }

    public function create_user(array $payload, $admin_user_id)
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($name === '' || $email === '' || $password === '')
        {
            return array(false, 'name, email, password are required');
        }
        $pw_err = Password_validator::validate($password);
        if ($pw_err !== null)
        {
            return array(false, $pw_err);
        }
        if ($this->ci->user_model->get_by_email($email))
        {
            return array(false, 'email already exists');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id = $this->ci->user_model->create(array(
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'role' => 'admin',
            'created_by' => (int) $admin_user_id,
            'updated_by' => (int) $admin_user_id,
        ));

        if ( ! $id)
        {
            return array(false, 'failed to create user');
        }

        $auth_pw = new Auth_password_service($this->ci);
        list($ok, $otp_meta) = $auth_pw->issue_email_verification_otp((int) $id, $email, 'account_activation');
        if ( ! $ok)
        {
            return array(false, (string) $otp_meta);
        }

        $user = $this->ci->user_model->find($id);
        $data = array(
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => (string) $user->role,
            'created_at' => (string) $user->created_at,
            'requires_verification' => true,
        );
        if (is_array($otp_meta))
        {
            if (isset($otp_meta['otp']))
            {
                $data['otp'] = $otp_meta['otp'];
            }
        }

        return array(true, $data);
    }

    /**
     * Soft-delete a user (admin only). Cannot delete self or the last active admin.
     *
     * @param int $target_id
     * @param int $admin_user_id
     * @return array{0:bool,1:mixed}
     */
    public function soft_delete_user($target_id, $admin_user_id)
    {
        $target_id = (int) $target_id;
        $admin_user_id = (int) $admin_user_id;
        if ($target_id < 1)
        {
            return array(false, 'invalid user id');
        }
        if ($target_id === $admin_user_id)
        {
            return array(false, 'cannot delete your own account');
        }

        $row = $this->ci->user_model->find($target_id);
        if ( ! $row)
        {
            return array(false, 'user not found');
        }
        if ($row->deleted_at !== null && $row->deleted_at !== '')
        {
            return array(false, 'user already deleted');
        }

        if (isset($row->role) && (string) $row->role === 'admin')
        {
            $this->ci->db->where('deleted_at', null);
            $this->ci->db->where('role', 'admin');
            $admin_count = (int) $this->ci->db->count_all_results('users');
            if ($admin_count <= 1)
            {
                return array(false, 'cannot delete the last admin');
            }
        }

        if ( ! $this->ci->user_model->soft_deactivate($target_id))
        {
            return array(false, 'failed to delete user');
        }

        return array(true, array('id' => $target_id, 'deleted' => true));
    }
}
