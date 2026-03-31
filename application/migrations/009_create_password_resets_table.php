<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_password_resets_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `password_resets` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `email` VARCHAR(150) NOT NULL,
            `otp_hash` VARCHAR(255) NOT NULL,
            `otp_expires_at` TIMESTAMP NULL DEFAULT NULL,
            `verified_at` TIMESTAMP NULL DEFAULT NULL,
            `reset_token_hash` VARCHAR(255) NULL,
            `reset_expires_at` TIMESTAMP NULL DEFAULT NULL,
            `attempts` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_password_resets_email` (`email`),
            KEY `idx_password_resets_deleted_at` (`deleted_at`),
            KEY `idx_password_resets_otp_expires_at` (`otp_expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('password_resets', TRUE);
    }
}

