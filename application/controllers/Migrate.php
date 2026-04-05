<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Run database migrations from CLI only:
 *   docker compose exec web php /var/www/html/public/index.php migrate
 *
 * "Table already exists" on 001: the DB already has tables, but `migrations.version`
 * is still 0 (new `migrations` table, restore without that row, etc.). CodeIgniter then
 * tries to run every migration from the start. Fix after confirming your schema matches
 * the migration files in application/migrations/ (see config migration_version):
 *
 *   mysql -u… -p… your_db -e "UPDATE migrations SET version = 14;"
 *
 * Use the same integer as $config['migration_version'] in application/config/migration.php.
 * Then re-run migrate; it should report OK without re-applying DDL.
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
