<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Invoice_summary_dto;
use Stripedesk\Dto\Receipt_detail_dto;

final class Receipt_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('receipt_model');
        $this->ci->load->model('invoice_model');
    }

    public function detail_for_actor($user, $receipt_id)
    {
        $receipt = $this->ci->receipt_model->find_active((int) $receipt_id);
        if ( ! $receipt)
        {
            return null;
        }
        $invoice_row = $this->ci->invoice_model->find_with_order_for_access((int) $receipt->invoice_id);
        if ( ! $invoice_row)
        {
            return null;
        }
        $is_admin = isset($user->role) && $user->role === 'admin';
        if ( ! $is_admin && (int) $invoice_row->order_user_id !== (int) $user->id)
        {
            return false;
        }
        $invoice_dto = new Invoice_summary_dto(
            $invoice_row->id,
            $invoice_row->order_id,
            $invoice_row->invoice_number,
            $invoice_row->total_amount,
            $invoice_row->status,
            $invoice_row->due_date,
            $invoice_row->created_at
        );

        return new Receipt_detail_dto(
            $receipt->id,
            $receipt->invoice_id,
            $receipt->receipt_number,
            $receipt->stripe_payment_intent,
            $receipt->amount_paid,
            $receipt->paid_at,
            $receipt->created_at,
            $invoice_dto
        );
    }
}
