<?php

namespace Stripedesk\Dto;

final class Receipt_detail_dto
{
    private $id;
    private $invoice_id;
    private $receipt_number;
    private $stripe_payment_intent;
    private $amount_paid;
    private $paid_at;
    private $created_at;
    private $invoice;

    public function __construct(
        $id,
        $invoice_id,
        $receipt_number,
        $stripe_payment_intent,
        $amount_paid,
        $paid_at,
        $created_at,
        Invoice_summary_dto $invoice
    ) {
        $this->id = (int) $id;
        $this->invoice_id = (int) $invoice_id;
        $this->receipt_number = (string) $receipt_number;
        $this->stripe_payment_intent = $stripe_payment_intent !== null ? (string) $stripe_payment_intent : null;
        $this->amount_paid = (string) $amount_paid;
        $this->paid_at = $paid_at !== null ? (string) $paid_at : null;
        $this->created_at = $created_at !== null ? (string) $created_at : null;
        $this->invoice = $invoice;
    }

    public function to_array()
    {
        return array(
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'receipt_number' => $this->receipt_number,
            'stripe_payment_intent' => $this->stripe_payment_intent,
            'amount_paid' => $this->amount_paid,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'invoice' => $this->invoice->to_array(),
        );
    }
}
