<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_carts_tables extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `carts` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `status` ENUM('active','checking_out','converted','expired') NOT NULL DEFAULT 'active',
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `expires_at` TIMESTAMP NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_carts_user` (`user_id`),
            KEY `idx_carts_status` (`status`),
            KEY `idx_carts_deleted_at` (`deleted_at`),
            CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`)
              REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE `cart_items` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `cart_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `price` DECIMAL(10,2) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_cart_items_cart_product` (`cart_id`, `product_id`),
            KEY `idx_cart_items_cart` (`cart_id`),
            KEY `idx_cart_items_product` (`product_id`),
            CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`)
              REFERENCES `carts` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`)
              REFERENCES `products` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('cart_items', TRUE);
        $this->dbforge->drop_table('carts', TRUE);
    }
}

