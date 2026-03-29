<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Invoice_detail_dto;
use Stripedesk\Dto\Invoice_order_item_line_dto;
use Stripedesk\Dto\Invoice_summary_dto;
use Stripedesk\Dto\Order_summary_dto;

final class Invoice_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('invoice_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
    }

    public function list_summaries_for_actor($user)
    {
        $is_admin = isset($user->role) && $user->role === 'admin';
        $rows = $this->ci->invoice_model->list_with_access_scope((int) $user->id, $is_admin);
        $dtos = array();
        foreach ($rows as $row)
        {
            $dtos[] = $this->row_to_summary_dto($row);
        }

        return $dtos;
    }

    public function detail_for_actor($user, $invoice_id)
    {
        $row = $this->ci->invoice_model->find_with_order_for_access((int) $invoice_id);
        if ( ! $row)
        {
            return null;
        }
        $is_admin = isset($user->role) && $user->role === 'admin';
        if ( ! $is_admin && (int) $row->order_user_id !== (int) $user->id)
        {
            return false;
        }
        $order = $this->ci->order_model->find((int) $row->order_id);
        if ( ! $order)
        {
            return null;
        }
        $line_rows = $this->ci->order_item_model->list_with_product_names_for_order((int) $order->id);
        $lines = array();
        foreach ($line_rows as $lr)
        {
            $lines[] = new Invoice_order_item_line_dto(
                $lr->id,
                $lr->product_id,
                $lr->product_name,
                $lr->quantity,
                $lr->unit_price
            );
        }

        $invoice_dto = $this->row_to_summary_dto($row);
        $order_dto = new Order_summary_dto(
            $order->id,
            $order->user_id,
            $order->status,
            $order->total_amount,
            $order->currency_id,
            $order->stripe_session_id,
            $order->created_at
        );

        return new Invoice_detail_dto($invoice_dto, $order_dto, $lines);
    }

    private function row_to_summary_dto($row)
    {
        return new Invoice_summary_dto(
            $row->id,
            $row->order_id,
            $row->invoice_number,
            $row->total_amount,
            $row->status,
            $row->due_date,
            $row->created_at
        );
    }
}
