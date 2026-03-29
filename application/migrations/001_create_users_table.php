<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_users_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `users` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            `api_token` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `users_email_unique` (`email`),
            KEY `idx_users_deleted_at` (`deleted_at`),
            KEY `idx_users_api_token` (`api_token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('users', TRUE);
    }
}
