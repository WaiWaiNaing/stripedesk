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

    public function can_access_user_invoices($actor_user, $target_user_id)
    {
        if (isset($actor_user->role) && $actor_user->role === 'admin')
        {
            return true;
        }
        return (int) $actor_user->id === (int) $target_user_id;
    }

    public function list_summaries_for_order_user_id($actor_user, $target_user_id)
    {
        if ( ! $this->can_access_user_invoices($actor_user, $target_user_id))
        {
            return null;
        }
        $rows = $this->ci->invoice_model->list_for_order_user_id((int) $target_user_id);
        $dtos = array();
        foreach ($rows as $row)
        {
            $dtos[] = $this->row_to_summary_dto($row);
        }

        return $dtos;
    }

    /**
     * Owner (or admin) initiates payment for a pending invoice. Stripe is not wired yet; response reserves fields for checkout URL / session.
     *
     * @return array{0:bool,1:array|string}
     */
    public function initiate_payment_for_actor($actor_user, $invoice_id)
    {
        $detail = $this->detail_for_actor($actor_user, (int) $invoice_id);
        if ($detail === null)
        {
            return array(false, 'not_found');
        }
        if ($detail === false)
        {
            return array(false, 'forbidden');
        }
        $arr = $detail->to_array();
        $inv = $arr['invoice'];
        if ( ! isset($inv['status']) || (string) $inv['status'] !== 'pending')
        {
            return array(false, 'invoice_not_pending');
        }

        return array(true, array(
            'invoice' => $arr['invoice'],
            'order' => $arr['order'],
            'lines' => $arr['lines'],
            'payment' => array(
                'stripe_checkout_url' => null,
                'stripe_checkout_session_id' => null,
                'note' => 'Stripe Checkout will be started from this endpoint when integrated; completion will move the invoice to paid (e.g. via webhook).',
            ),
        ));
    }

    /**
     * Update invoice status (admin only). Allowed values: pending, unpaid, paid, void.
     *
     * @return \Stripedesk\Dto\Invoice_detail_dto|null|false null=not found, false=forbidden
     */
    public function update_status_for_actor($actor_user, $invoice_id, $new_status)
    {
        if ( ! isset($actor_user->role) || $actor_user->role !== 'admin')
        {
            return false;
        }

        $row = $this->ci->invoice_model->find_with_order_for_access((int) $invoice_id);
        if ( ! $row)
        {
            return null;
        }

        $this->ci->invoice_model->update_row((int) $invoice_id, array(
            'status' => (string) $new_status,
            'updated_by' => (int) $actor_user->id,
        ));

        return $this->detail_for_actor($actor_user, (int) $invoice_id);
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
