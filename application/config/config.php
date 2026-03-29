<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'env.php';

$base_url = sd_env('APP_BASE_URL');
if ($base_url === null || $base_url === '')
{
    $base_url = isset($_SERVER['HTTP_HOST'])
        ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
        : 'http://localhost:8081';
}
$base_url = rtrim((string) $base_url, '/') . '/';

$config['base_url'] = $base_url;
$config['index_page'] = sd_env('CI_INDEX_PAGE', '');
$config['uri_protocol'] = sd_env('CI_URI_PROTOCOL', 'REQUEST_URI');
$config['url_suffix'] = sd_env('CI_URL_SUFFIX', '');
$config['language'] = sd_env('APP_LANGUAGE', 'english');
$config['charset'] = sd_env('APP_CHARSET', 'UTF-8');
$config['enable_hooks'] = sd_env_bool('CI_ENABLE_HOOKS', false);
$config['subclass_prefix'] = sd_env('CI_SUBCLASS_PREFIX', 'MY_');
$config['composer_autoload'] = sd_env_bool('CI_COMPOSER_AUTOLOAD', false);
$config['permitted_uri_chars'] = sd_env('CI_PERMITTED_URI_CHARS', 'a-z 0-9~%.:_\-');
$config['enable_query_strings'] = sd_env_bool('CI_ENABLE_QUERY_STRINGS', false);
$config['controller_trigger'] = sd_env('CI_CONTROLLER_TRIGGER', 'c');
$config['function_trigger'] = sd_env('CI_FUNCTION_TRIGGER', 'm');
$config['directory_trigger'] = sd_env('CI_DIRECTORY_TRIGGER', 'd');
$config['allow_get_array'] = sd_env_bool('CI_ALLOW_GET_ARRAY', true);
$config['standardize_newlines'] = sd_env_bool('CI_STANDARDIZE_NEWLINES', false);
$config['global_xss_filtering'] = sd_env_bool('CI_GLOBAL_XSS_FILTERING', false);
$config['csrf_protection'] = sd_env_bool('CI_CSRF_PROTECTION', false);
$config['csrf_token_name'] = sd_env('CI_CSRF_TOKEN_NAME', 'csrf_test_name');
$config['csrf_cookie_name'] = sd_env('CI_CSRF_COOKIE_NAME', 'csrf_cookie_name');
$config['csrf_expire'] = sd_env_int('CI_CSRF_EXPIRE', 7200);
$config['csrf_regenerate'] = sd_env_bool('CI_CSRF_REGENERATE', true);
$config['csrf_exclude_uris'] = array();
$config['compress_output'] = sd_env_bool('CI_COMPRESS_OUTPUT', false);
$config['time_reference'] = sd_env('CI_TIME_REFERENCE', 'local');
$config['rewrite_short_tags'] = sd_env_bool('CI_REWRITE_SHORT_TAGS', false);
$config['proxy_ips'] = sd_env('CI_PROXY_IPS', '');

$config['encryption_key'] = sd_env('CI_ENCRYPTION_KEY', 'stripedesk_dev_change_me');

$config['log_threshold'] = sd_env_int('CI_LOG_THRESHOLD', 0);
$config['log_path'] = sd_env('CI_LOG_PATH', '');
$config['log_file_extension'] = sd_env('CI_LOG_FILE_EXT', '');
$config['log_file_permissions'] = sd_env_int('CI_LOG_FILE_PERMISSIONS', 0644);
$config['log_date_format'] = sd_env('CI_LOG_DATE_FORMAT', 'Y-m-d H:i:s');
