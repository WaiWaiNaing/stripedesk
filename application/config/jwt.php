<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['jwt_secret'] = sd_env('JWT_SECRET', 'change_me_in_production_use_long_random_secret');
$config['jwt_ttl'] = sd_env_int('JWT_TTL', 86400);
$config['jwt_issuer'] = sd_env('JWT_ISSUER', 'stripedesk');
