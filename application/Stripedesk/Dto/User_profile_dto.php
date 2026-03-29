<?php

namespace Stripedesk\Dto;

final class User_profile_dto
{
    private $id;
    private $name;
    private $email;
    private $role;
    private $created_at;

    public function __construct($id, $name, $email, $role, $created_at)
    {
        $this->id = (int) $id;
        $this->name = (string) $name;
        $this->email = (string) $email;
        $this->role = (string) $role;
        $this->created_at = $created_at !== null ? (string) $created_at : null;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at,
        );
    }
}
