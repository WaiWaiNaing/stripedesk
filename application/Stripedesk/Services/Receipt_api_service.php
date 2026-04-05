<?php

namespace Stripedesk\Services;

use Stripedesk\Dto\Invoice_summary_dto;
use Stripedesk\Dto\Receipt_detail_dto;
use Stripedesk\Dto\Receipt_summary_dto;

/**
 * Receipt list/detail/PDF use-cases and authorization against invoice order ownership.
 */
final class Receipt_api_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('receipt_model');
        $this->ci->load->model('invoice_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
    }

    public function list_summaries_for_actor($user)
    {
        $is_admin = $this->is_admin($user);
        $rows = $this->ci->receipt_model->list_with_access_scope((int) $user->id, $is_admin);

        return $this->map_rows_to_summary_dtos($rows);
    }

    public function can_access_user_receipts($actor_user, $target_user_id)
    {
        if ($this->is_admin($actor_user))
        {
            return true;
        }

        return (int) $actor_user->id === (int) $target_user_id;
    }

    public function list_summaries_for_order_user_id($actor_user, $target_user_id)
    {
        if ( ! $this->can_access_user_receipts($actor_user, $target_user_id))
        {
            return null;
        }

        $rows = $this->ci->receipt_model->list_for_order_user_id((int) $target_user_id);

        return $this->map_rows_to_summary_dtos($rows);
    }

    /**
     * @return array{binary:string,receipt_number:string}|null|false null=not found, false=forbidden
     */
    public function pdf_binary_for_actor($user, $receipt_id)
    {
        $detail = $this->detail_for_actor($user, $receipt_id);
        if ($detail === null)
        {
            return null;
        }
        if ($detail === false)
        {
            return false;
        }

        $data = $detail->to_array();
        $invoice_id = (int) $data['invoice_id'];
        $invoice_row = $this->ci->invoice_model->find_with_order_for_access($invoice_id);
        if ( ! $invoice_row)
        {
            return null;
        }
        $order = $this->ci->order_model->find((int) $invoice_row->order_id);
        if ( ! $order)
        {
            return null;
        }
        $data['line_items'] = $this->line_items_for_order_id((int) $order->id);

        $pdf_service = new Receipt_pdf_service();
        $binary = $pdf_service->render_binary($data);

        return array(
            'binary' => $binary,
            'receipt_number' => (string) $data['receipt_number'],
        );
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

        if ( ! $this->actor_may_access_order($user, (int) $invoice_row->order_user_id))
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

    private function map_rows_to_summary_dtos(array $rows)
    {
        $dtos = array();
        foreach ($rows as $row)
        {
            $dtos[] = $this->row_to_receipt_summary_dto($row);
        }

        return $dtos;
    }

    private function row_to_receipt_summary_dto($row)
    {
        $invoice_dto = new Invoice_summary_dto(
            (int) $row->invoice_id,
            (int) $row->order_id,
            (string) $row->invoice_number,
            (string) $row->total_amount,
            (string) $row->status,
            isset($row->due_date) ? $row->due_date : null,
            isset($row->invoice_created_at) ? $row->invoice_created_at : null
        );

        return new Receipt_summary_dto(
            $row->id,
            $row->invoice_id,
            $row->receipt_number,
            $row->amount_paid,
            isset($row->paid_at) ? $row->paid_at : null,
            isset($row->created_at) ? $row->created_at : null,
            $invoice_dto
        );
    }

    /**
     * @return list<array{product_name:string,quantity:int,unit_price:string}>
     */
    private function line_items_for_order_id($order_id)
    {
        $line_rows = $this->ci->order_item_model->list_with_product_names_for_order((int) $order_id);
        $line_items = array();
        foreach ($line_rows as $lr)
        {
            $line_items[] = array(
                'product_name' => isset($lr->product_name) ? (string) $lr->product_name : '',
                'quantity' => isset($lr->quantity) ? (int) $lr->quantity : 1,
                'unit_price' => isset($lr->unit_price) ? (string) $lr->unit_price : '',
            );
        }

        return $line_items;
    }

    private function actor_may_access_order($actor, $order_user_id)
    {
        if ($this->is_admin($actor))
        {
            return true;
        }

        return (int) $actor->id === (int) $order_user_id;
    }

    private function is_admin($user)
    {
        return isset($user->role) && $user->role === 'admin';
    }
}
