<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__ . DIRECTORY_SEPARATOR . 'env.php';

$active_group = sd_env_first(array('DB_ACTIVE_GROUP'), 'default');
if ($active_group === null || $active_group === '')
{
    $active_group = 'default';
}
$query_builder = sd_env_bool('DB_QUERY_BUILDER', true);

$db_debug_default = (defined('ENVIRONMENT') && ENVIRONMENT !== 'production');

$db['default'] = array(
    'dsn' => sd_env_first(array('DB_DSN'), ''),
    'hostname' => sd_env_first(array('DB_HOST', 'MYSQL_HOST'), ''),
    'username' => sd_env_first(array('DB_USERNAME', 'DB_USER', 'MYSQL_USER'), ''),
    'password' => sd_env_first(array('DB_PASSWORD', 'MYSQL_PASSWORD'), ''),
    'database' => sd_env_first(array('DB_DATABASE', 'DB_NAME', 'MYSQL_DATABASE'), ''),
    'dbdriver' => sd_env_first(array('DB_DRIVER'), 'mysqli'),
    'dbprefix' => sd_env_first(array('DB_PREFIX'), ''),
    'pconnect' => sd_env_bool('DB_PCONNECT', false),
    'db_debug' => sd_env_bool('CI_DB_DEBUG', $db_debug_default),
    'cache_on' => sd_env_bool('DB_CACHE_ON', false),
    'cachedir' => sd_env_first(array('DB_CACHEDIR'), ''),
    'char_set' => sd_env_first(array('DB_CHARSET', 'MYSQL_CHARSET'), ''),
    'dbcollat' => sd_env_first(array('DB_COLLATION', 'MYSQL_COLLATION'), ''),
    'swap_pre' => sd_env_first(array('DB_SWAP_PRE'), ''),
    'encrypt' => sd_env_bool('DB_ENCRYPT', false),
    'compress' => sd_env_bool('DB_COMPRESS', false),
    'stricton' => sd_env_bool('DB_STRICTON', false),
    'failover' => array(),
    'save_queries' => sd_env_bool('DB_SAVE_QUERIES', true),
);

$mysql_port = sd_env_int_first(array('DB_PORT', 'MYSQL_PORT'), 3306);
if ($mysql_port > 0 && isset($db['default']['hostname']))
{
    $db['default']['port'] = $mysql_port;
}
