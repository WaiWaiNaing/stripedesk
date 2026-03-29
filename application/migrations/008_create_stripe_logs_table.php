<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_stripe_logs_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `stripe_logs` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `event_type` VARCHAR(100) NULL,
            `payload` JSON NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_stripe_logs_created_at` (`created_at`),
            KEY `idx_stripe_logs_event_type` (`event_type`),
            KEY `idx_stripe_logs_deleted_at` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('stripe_logs', TRUE);
    }
}
