<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_receipts_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `receipts` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `invoice_id` INT NOT NULL,
            `receipt_number` VARCHAR(50) NOT NULL,
            `stripe_payment_intent` VARCHAR(255) NULL,
            `amount_paid` DECIMAL(10, 2) NOT NULL,
            `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `receipts_invoice_id_unique` (`invoice_id`),
            UNIQUE KEY `receipts_receipt_number_unique` (`receipt_number`),
            KEY `idx_receipts_stripe_payment_intent` (`stripe_payment_intent`),
            KEY `idx_receipts_deleted_at` (`deleted_at`),
            CONSTRAINT `receipts_invoice_id_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->dbforge->drop_table('receipts', TRUE);
    }
}
