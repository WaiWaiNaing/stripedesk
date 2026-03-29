<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_invoices_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `invoices` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `order_id` INT NOT NULL,
            `invoice_number` VARCHAR(50) NOT NULL,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `status` ENUM('unpaid', 'paid', 'void') NOT NULL DEFAULT 'unpaid',
            `due_date` DATE NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `invoices_order_id_unique` (`order_id`),
            UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
            KEY `idx_invoices_status` (`status`),
            KEY `idx_invoices_deleted_at` (`deleted_at`),
            CONSTRAINT `invoices_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('invoices', TRUE);
    }
}
