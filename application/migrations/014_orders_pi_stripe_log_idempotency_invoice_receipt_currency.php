<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Orders_pi_stripe_log_idempotency_invoice_receipt_currency extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `orders` ADD COLUMN `stripe_payment_intent` VARCHAR(255) NULL DEFAULT NULL AFTER `stripe_session_id`, ADD KEY `idx_orders_stripe_payment_intent` (`stripe_payment_intent`)");

        $this->db->query("ALTER TABLE `stripe_logs` ADD COLUMN `event_id` VARCHAR(255) NULL DEFAULT NULL AFTER `id`, ADD COLUMN `processed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payload`, ADD UNIQUE KEY `uniq_stripe_logs_event_id` (`event_id`)");

        $this->db->query("ALTER TABLE `invoices` ADD COLUMN `currency_id` INT NOT NULL DEFAULT 1 AFTER `order_id`, ADD KEY `idx_invoices_currency_id` (`currency_id`), ADD CONSTRAINT `invoices_currency_id_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)");

        $this->db->query("ALTER TABLE `receipts` ADD COLUMN `currency_id` INT NOT NULL DEFAULT 1 AFTER `invoice_id`, ADD KEY `idx_receipts_currency_id` (`currency_id`), ADD CONSTRAINT `receipts_currency_id_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `receipts` DROP FOREIGN KEY `receipts_currency_id_fk`");
        $this->db->query("ALTER TABLE `receipts` DROP COLUMN `currency_id`");

        $this->db->query("ALTER TABLE `invoices` DROP FOREIGN KEY `invoices_currency_id_fk`");
        $this->db->query("ALTER TABLE `invoices` DROP COLUMN `currency_id`");

        $this->db->query("ALTER TABLE `stripe_logs` DROP INDEX `uniq_stripe_logs_event_id`");
        $this->db->query("ALTER TABLE `stripe_logs` DROP COLUMN `processed`");
        $this->db->query("ALTER TABLE `stripe_logs` DROP COLUMN `event_id`");

        $this->db->query("ALTER TABLE `orders` DROP INDEX `idx_orders_stripe_payment_intent`");
        $this->db->query("ALTER TABLE `orders` DROP COLUMN `stripe_payment_intent`");
    }
}
