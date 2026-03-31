<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Run database migrations from CLI only:
 *   docker compose exec web php /var/www/html/public/index.php migrate
 */
class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ( ! is_cli())
        {
            show_error('Migrations are only available from the command line.', 403);
        }
    }

    public function index()
    {
        $this->load->library('migration');
        if ($this->migration->current() === FALSE)
        {
            show_error($this->migration->error_string());
            return;
        }
        $this->config->load('migration');
        $target = $this->config->item('migration_version');
        echo 'Migrations OK. Config target version: ' . $target . PHP_EOL;
    }
}
