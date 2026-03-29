<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_orders_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `orders` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `user_id` INT NOT NULL,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `currency_id` INT NOT NULL DEFAULT 1,
            `status` ENUM('pending', 'paid', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
            `stripe_session_id` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_orders_stripe_session_id` (`stripe_session_id`),
            KEY `idx_orders_status` (`status`),
            KEY `idx_orders_deleted_at` (`deleted_at`),
            KEY `idx_orders_currency_id` (`currency_id`),
            CONSTRAINT `orders_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
            CONSTRAINT `orders_currency_id_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('orders', TRUE);
    }
}
