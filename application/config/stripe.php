<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['stripe_secret_key'] = sd_env('STRIPE_SECRET_KEY', '');
$config['stripe_webhook_secret'] = sd_env('STRIPE_WEBHOOK_SECRET', '');
$config['stripe_success_url'] = sd_env('STRIPE_SUCCESS_URL', 'http://localhost:5173/purchase/success?session_id={CHECKOUT_SESSION_ID}');
$config['stripe_cancel_url'] = sd_env('STRIPE_CANCEL_URL', 'http://localhost:5173/purchase/cancel');

