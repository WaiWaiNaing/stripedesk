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
        $this->ci->config->load('stripe', true);
        $this->ci->load->model('invoice_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
        $this->ci->load->model('receipt_model');
        $this->ci->load->model('currency_model');
        $this->ci->load->model('stripe_log_model');
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
     * Owner (or admin) initiates payment for a pending invoice via Stripe Checkout.
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

        $secret = $this->ci->config->item('stripe_secret_key', 'stripe');
        if ($secret === null || $secret === '')
        {
            return array(false, 'stripe_not_configured');
        }

        $order = $arr['order'];
        $order_id = isset($order['id']) ? (int) $order['id'] : 0;
        if ($order_id < 1)
        {
            return array(false, 'order_not_found');
        }

        $order_row = $this->ci->order_model->find($order_id);
        if ( ! $order_row)
        {
            return array(false, 'order_not_found');
        }

        $line_rows = $this->ci->order_item_model->list_with_product_names_for_order($order_id);
        if (empty($line_rows))
        {
            return array(false, 'invoice_has_no_items');
        }

        $currency_code = 'usd';
        $minor = 2;
        if (isset($order_row->currency_id))
        {
            $currency_row = $this->ci->currency_model->find((int) $order_row->currency_id);
            if ($currency_row)
            {
                $currency_code = strtolower((string) $currency_row->code);
                if (isset($currency_row->minor_unit))
                {
                    $minor = (int) $currency_row->minor_unit;
                }
            }
        }

        $line_items = array();
        foreach ($line_rows as $line)
        {
            $qty = isset($line->quantity) ? (int) $line->quantity : 1;
            if ($qty < 1) $qty = 1;
            $unit_price = isset($line->unit_price) ? (float) $line->unit_price : 0.0;
            $unit_amount = (int) round($unit_price * pow(10, $minor));
            if ($unit_amount < 1) $unit_amount = 1;

            $line_items[] = array(
                'quantity' => $qty,
                'price_data' => array(
                    'currency' => $currency_code,
                    'unit_amount' => $unit_amount,
                    'product_data' => array(
                        'name' => isset($line->product_name) ? (string) $line->product_name : ('Product #' . (int) $line->product_id),
                    ),
                ),
            );
        }

        \Stripe\Stripe::setApiKey($secret);

        $session_id = '';
        $checkout_url = '';
        $reused_existing_session = false;

        if (isset($order_row->stripe_session_id) && trim((string) $order_row->stripe_session_id) !== '')
        {
            try
            {
                $existing = \Stripe\Checkout\Session::retrieve((string) $order_row->stripe_session_id);
                if ($existing && isset($existing->status) && (string) $existing->status === 'open')
                {
                    $session_id = (string) $existing->id;
                    $checkout_url = isset($existing->url) ? (string) $existing->url : '';
                    $reused_existing_session = true;
                }
            }
            catch (\Throwable $e)
            {
                // If stale/invalid, create a fresh session below.
            }
        }

        if ($session_id === '' || $checkout_url === '')
        {
            $success_url = trim((string) $this->ci->config->item('stripe_success_url', 'stripe'));
            $cancel_url = trim((string) $this->ci->config->item('stripe_cancel_url', 'stripe'));
            if ($success_url === '' || $cancel_url === '')
            {
                return array(false, 'stripe_checkout_urls_not_configured');
            }
            $metadata = array(
                'invoice_id' => (string) (int) $inv['id'],
                'order_id' => (string) $order_id,
                'user_id' => (string) (int) $actor_user->id,
            );

            try
            {
                $session = \Stripe\Checkout\Session::create(array(
                    'mode' => 'payment',
                    'success_url' => $success_url,
                    'cancel_url' => $cancel_url,
                    'metadata' => $metadata,
                    'line_items' => $line_items,
                ));
            }
            catch (\Throwable $e)
            {
                log_message('error', 'Stripe Checkout Session::create failed (invoice pay): ' . $e->getMessage());
                $hint = $e->getMessage();
                if ($e instanceof \Stripe\Exception\ApiErrorException)
                {
                    $code = method_exists($e, 'getStripeCode') ? (string) $e->getStripeCode() : '';
                    if ($code !== '')
                    {
                        $hint = $code . ': ' . $hint;
                    }
                }

                return array(false, 'stripe_checkout_failed: ' . $hint);
            }

            $session_id = (string) $session->id;
            $checkout_url = isset($session->url) ? (string) $session->url : '';

            if ( ! $this->ci->order_model->update_row($order_id, array(
                'stripe_session_id' => $session_id,
                'updated_by' => (int) $actor_user->id,
            )))
            {
                log_message('error', 'order update stripe_session_id failed order_id=' . $order_id);

                return array(false, 'failed_to_save_checkout_session');
            }
        }

        $this->ci->stripe_log_model->log_event_safe('invoice.payment.session.created', array(
            'invoice_id' => (int) $inv['id'],
            'order_id' => $order_id,
            'session_id' => $session_id,
            'reused_existing_session' => $reused_existing_session,
        ));

        return array(true, array(
            'invoice' => $arr['invoice'],
            'order' => $arr['order'],
            'lines' => $arr['lines'],
            'payment' => array(
                'stripe_checkout_url' => $checkout_url,
                'stripe_checkout_session_id' => $session_id,
                'status' => $reused_existing_session ? 'reused_open_session' : 'created',
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

        $receipt_stub = null;
        $rec = $this->ci->receipt_model->get_by_invoice_id((int) $row->id);
        if ($rec && ( ! isset($rec->deleted_at) || $rec->deleted_at === null))
        {
            $receipt_stub = array(
                'id' => (int) $rec->id,
                'receipt_number' => (string) $rec->receipt_number,
            );
        }

        return new Invoice_detail_dto($invoice_dto, $order_dto, $lines, $receipt_stub);
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
