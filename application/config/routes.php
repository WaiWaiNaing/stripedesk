<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = false;

$route['api/v1/auth/login'] = 'api/auth/login';
$route['api/v1/auth/me'] = 'api/auth/me';
$route['api/v1/invoices'] = 'api/invoices/index';
$route['api/v1/invoices/(:num)'] = 'api/invoices/show/$1';
$route['api/v1/receipts/(:num)'] = 'api/receipts/show/$1';
$route['api/v1/products'] = 'api/products/index';
