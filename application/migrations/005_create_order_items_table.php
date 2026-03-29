<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_order_items_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `order_items` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(10, 2) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            KEY `idx_order_items_deleted_at` (`deleted_at`),
            CONSTRAINT `order_items_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
            CONSTRAINT `order_items_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('order_items', TRUE);
    }
}
