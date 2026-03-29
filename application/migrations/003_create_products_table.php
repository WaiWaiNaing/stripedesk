<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_products_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `products` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `name` VARCHAR(200) NOT NULL,
            `description` TEXT NULL,
            `price` DECIMAL(10, 2) NOT NULL,
            `currency_id` INT NOT NULL DEFAULT 1,
            `stripe_price_id` VARCHAR(100) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_products_deleted_at` (`deleted_at`),
            KEY `idx_products_stripe_price_id` (`stripe_price_id`),
            KEY `idx_products_currency_id` (`currency_id`),
            CONSTRAINT `products_currency_id_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('products', TRUE);
    }
}
