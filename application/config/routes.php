<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = false;

$route['api/v1/auth/login'] = 'api/auth/login';
$route['api/v1/auth/refresh'] = 'api/auth/refresh';
$route['api/v1/auth/me'] = 'api/auth/me';
$route['api/v1/auth/register'] = 'api/auth/register';
$route['api/v1/auth/forgot'] = 'api/auth/forgot';
$route['api/v1/auth/resend'] = 'api/auth/resend';
$route['api/v1/auth/verify-otp'] = 'api/auth/verify_otp';
$route['api/v1/auth/reset-password'] = 'api/auth/reset_password';
$route['api/v1/invoices'] = 'api/invoices/index';
$route['api/v1/invoices/(:num)'] = 'api/invoices/show/$1';
$route['api/v1/receipts/(:num)'] = 'api/receipts/show/$1';
$route['api/v1/products'] = 'api/products/index';

$route['api/v1/checkout/session'] = 'api/checkout/session';
$route['api/v1/users/(:num)/cart/items'] = 'api/carts/add_item_for_user/$1';
$route['api/v1/users/(:num)/cart'] = 'api/carts/show_for_user/$1';
$route['api/v1/carts'] = 'api/carts/create';
$route['api/v1/carts/(:num)/items'] = 'api/carts/add_item/$1';
$route['api/v1/carts/(:num)'] = 'api/carts/show/$1';
$route['api/v1/stripe/webhook'] = 'api/stripe/webhook';

$route['api/v1/admin/users'] = 'api/admin/users/index';
$route['api/v1/admin/users/(:num)'] = 'api/admin/users/delete/$1';
$route['api/v1/admin/products'] = 'api/admin/products/index';
$route['api/v1/admin/products/(:num)'] = 'api/admin/products/update/$1';
$route['api/v1/admin/currencies'] = 'api/admin/currencies/index';
$route['api/v1/admin/currencies/(:num)'] = 'api/admin/currencies/update/$1';
$route['api/v1/admin/stripe-logs'] = 'api/admin/stripe_logs/index';
