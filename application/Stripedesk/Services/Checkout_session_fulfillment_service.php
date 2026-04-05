<?php

namespace Stripedesk\Services;

/**
 * Idempotent fulfillment for Stripe Checkout (webhook + API reconcile + cron).
 * Mirrors checkout.session.completed handling.
 */
final class Checkout_session_fulfillment_service
{
    /**
     * @param object $session Stripe Checkout\Session or decoded webhook object
     *
     * @return string Reason code for logs: ok|skipped_paid|no_order_id|order_not_found
     */
    public static function apply($ci, $session)
    {
        require_once APPPATH . 'controllers/Webhook.php';
        return \Webhook::_fulfill_order($ci, $session);
    }
}
