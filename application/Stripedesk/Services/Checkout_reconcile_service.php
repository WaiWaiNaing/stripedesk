<?php

namespace Stripedesk\Services;

/**
 * After Stripe Checkout redirect: confirm payment if webhook is slow.
 * Cron: reconcile pending orders against Stripe API.
 */
final class Checkout_reconcile_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->config->load('stripe', true);
        $this->ci->load->model('order_model');
        $this->ci->load->model('invoice_model');
        $this->ci->load->model('stripe_log_model');
    }

    /**
     * @param object $user Authenticated user row
     *
     * @return array{0:bool,1:array|string}
     */
    public function reconcile_session_for_user($user, $session_id)
    {
        $session_id = trim((string) $session_id);
        if ($session_id === '')
        {
            return array(false, 'session_id is required');
        }

        $order = $this->ci->order_model->get_by_stripe_session_id($session_id);
        if ( ! $order)
        {
            return array(false, 'order_not_found');
        }
        if ((int) $order->user_id !== (int) $user->id)
        {
            return array(false, 'forbidden');
        }

        if ((string) $order->status === 'paid')
        {
            return array(true, $this->build_paid_response($user, (int) $order->id));
        }

        $secret = $this->ci->config->item('stripe_secret_key', 'stripe');
        if ($secret === null || $secret === '')
        {
            return array(false, 'stripe_not_configured');
        }

        try
        {
            \Stripe\Stripe::setApiKey($secret);
            $session = \Stripe\Checkout\Session::retrieve($session_id);
            $payment_status = isset($session->payment_status) ? (string) $session->payment_status : '';
            $status = isset($session->status) ? (string) $session->status : '';

            if ($payment_status === 'paid' && $status === 'complete')
            {
                $reason = Checkout_session_fulfillment_service::apply($this->ci, $session);
                $this->ci->stripe_log_model->log_event('checkout.session.reconciled_api', array(
                    'session_id' => $session_id,
                    'order_id' => (int) $order->id,
                    'fulfill_reason' => $reason,
                ));
                $order = $this->ci->order_model->find((int) $order->id);

                return array(true, $this->build_paid_response($user, (int) $order->id));
            }

            return array(true, array(
                'state' => 'pending',
                'order_id' => (int) $order->id,
                'payment_status' => $payment_status,
                'session_status' => $status,
            ));
        }
        catch (\Exception $e)
        {
            return array(false, 'stripe_error: ' . $e->getMessage());
        }
    }

    /**
     * Batch job: pending orders with a Checkout session — poll Stripe and fulfill if paid.
     *
     * @return int Number of orders fulfilled
     */
    public function reconcile_all_pending_from_stripe()
    {
        $secret = $this->ci->config->item('stripe_secret_key', 'stripe');
        if ($secret === null || $secret === '')
        {
            return 0;
        }

        \Stripe\Stripe::setApiKey($secret);

        $orders = $this->ci->order_model->list_pending_with_stripe_session(300);
        $fulfilled = 0;

        foreach ($orders as $order)
        {
            $sid = isset($order->stripe_session_id) ? trim((string) $order->stripe_session_id) : '';
            if ($sid === '')
            {
                continue;
            }
            try
            {
                $session = \Stripe\Checkout\Session::retrieve($sid);
                $payment_status = isset($session->payment_status) ? (string) $session->payment_status : '';
                $status = isset($session->status) ? (string) $session->status : '';
                if ($payment_status === 'paid' && $status === 'complete')
                {
                    $reason = Checkout_session_fulfillment_service::apply($this->ci, $session);
                    $this->ci->stripe_log_model->log_event('checkout.session.reconciled_cron', array(
                        'session_id' => $sid,
                        'order_id' => (int) $order->id,
                        'fulfill_reason' => $reason,
                    ));
                    if ($reason === 'ok' || $reason === 'skipped_paid')
                    {
                        $fulfilled++;
                    }
                }
            }
            catch (\Exception $e)
            {
                $this->ci->stripe_log_model->log_event('checkout.reconcile_cron.error', array(
                    'order_id' => (int) $order->id,
                    'session_id' => $sid,
                    'message' => $e->getMessage(),
                ));
            }
        }

        return $fulfilled;
    }

    private function build_paid_response($user, $order_id)
    {
        $invoice = $this->ci->invoice_model->get_by_order_id($order_id);
        $invoice_id = $invoice ? (int) $invoice->id : 0;
        $out = array(
            'state' => 'paid',
            'order_id' => $order_id,
            'invoice_id' => $invoice_id > 0 ? $invoice_id : null,
        );
        if ($invoice_id > 0)
        {
            $inv_svc = new Invoice_api_service($this->ci);
            $detail = $inv_svc->detail_for_actor($user, $invoice_id);
            if ($detail instanceof \Stripedesk\Dto\Invoice_detail_dto)
            {
                $out['invoice'] = $detail->to_array();
            }
        }

        return $out;
    }
}
