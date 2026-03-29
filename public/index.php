<?php
require_once __DIR__ . '/bootstrap_env.php';

define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

switch (ENVIRONMENT)
{
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        break;
    case 'testing':
    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>='))
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
        else
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Invalid environment.';
        exit(1);
}

$system_path = dirname(__DIR__) . '/vendor/codeigniter/framework/system';
$application_folder = dirname(__DIR__) . '/application';
$view_folder = '';

if (realpath($system_path) === false)
{
    header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
    echo 'Run composer install (CodeIgniter system not found).';
    exit(1);
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', $system_path . DIRECTORY_SEPARATOR);
define('FCPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('SYSDIR', basename(BASEPATH));
define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);

if ( ! empty($view_folder))
{
    if ( ! is_dir($view_folder))
    {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your view folder path does not appear to be set correctly.';
        exit(3);
    }
    define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);
}
else
{
    define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
}

$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_readable($composer_autoload))
{
    require_once $composer_autoload;
}

require_once BASEPATH . 'core/CodeIgniter.php';
