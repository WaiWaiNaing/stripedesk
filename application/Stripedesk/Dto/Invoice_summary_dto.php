<?php

namespace Stripedesk\Dto;

final class Invoice_summary_dto
{
    private $id;
    private $order_id;
    private $invoice_number;
    private $total_amount;
    private $status;
    private $due_date;
    private $created_at;

    public function __construct($id, $order_id, $invoice_number, $total_amount, $status, $due_date, $created_at)
    {
        $this->id = (int) $id;
        $this->order_id = (int) $order_id;
        $this->invoice_number = (string) $invoice_number;
        $this->total_amount = (string) $total_amount;
        $this->status = (string) $status;
        $this->due_date = $due_date !== null ? (string) $due_date : null;
        $this->created_at = $created_at !== null ? (string) $created_at : null;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'order_id' => $this->order_id,
            'invoice_number' => $this->invoice_number,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
        );
    }
}
