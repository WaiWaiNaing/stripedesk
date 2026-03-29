<?php

namespace Stripedesk\Dto;

final class Order_summary_dto
{
    private $id;
    private $user_id;
    private $status;
    private $total_amount;
    private $currency_id;
    private $stripe_session_id;
    private $created_at;

    public function __construct($id, $user_id, $status, $total_amount, $currency_id, $stripe_session_id, $created_at)
    {
        $this->id = (int) $id;
        $this->user_id = (int) $user_id;
        $this->status = (string) $status;
        $this->total_amount = (string) $total_amount;
        $this->currency_id = (int) $currency_id;
        $this->stripe_session_id = $stripe_session_id !== null ? (string) $stripe_session_id : null;
        $this->created_at = $created_at !== null ? (string) $created_at : null;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency_id' => $this->currency_id,
            'stripe_session_id' => $this->stripe_session_id,
            'created_at' => $this->created_at,
        );
    }
}
