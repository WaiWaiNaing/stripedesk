<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only jobs (e.g. php public/index.php cron stripe_reconcile [token]).
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ( ! is_cli())
        {
            show_404();
        }
    }

    /**
     * Reconcile pending Stripe Checkout orders against the Stripe API (~every 15 min via cron).
     * Optional: set CRON_STRIPE_RECONCILE_TOKEN in .env and pass the same value as 3rd CLI argument.
     */
    public function stripe_reconcile()
    {
        $expected = function_exists('sd_env') ? trim((string) sd_env('CRON_STRIPE_RECONCILE_TOKEN', '')) : '';
        if ($expected !== '')
        {
            $token = isset($_SERVER['argv'][3]) ? trim((string) $_SERVER['argv'][3]) : '';
            if ($token !== $expected)
            {
                echo "unauthorized\n";
                return;
            }
        }

        $svc = new \Stripedesk\Services\Checkout_reconcile_service($this);
        $n = $svc->reconcile_all_pending_from_stripe();
        echo 'fulfilled_pending_orders=' . (int) $n . PHP_EOL;
    }
}
