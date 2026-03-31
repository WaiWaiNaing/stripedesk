<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_otp_verifications_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `otp_verifications` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `user_id` INT NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `intent` VARCHAR(32) NOT NULL,
            `otp_hash` VARCHAR(255) NOT NULL,
            `otp_expires_at` TIMESTAMP NULL DEFAULT NULL,
            `verified_at` TIMESTAMP NULL DEFAULT NULL,
            `attempts` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_otp_verifications_user_id` (`user_id`),
            KEY `idx_otp_verifications_email_intent` (`email`, `intent`),
            KEY `idx_otp_verifications_deleted_at` (`deleted_at`),
            KEY `idx_otp_verifications_otp_expires_at` (`otp_expires_at`),
            CONSTRAINT `otp_verifications_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('otp_verifications', TRUE);
    }
}
