<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_email_verification_and_otp_intent extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `users` ADD COLUMN `email_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `email`");
        $this->db->query("ALTER TABLE `password_resets` ADD COLUMN `intent` VARCHAR(32) NOT NULL DEFAULT 'password_reset' AFTER `email`");
        $this->db->query("UPDATE `users` SET `email_verified_at` = `created_at` WHERE `email_verified_at` IS NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `users` DROP COLUMN `email_verified_at`");
        $this->db->query("ALTER TABLE `password_resets` DROP COLUMN `intent`");
    }
}
