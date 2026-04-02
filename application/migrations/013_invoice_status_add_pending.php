<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Invoice_status_add_pending extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `invoices` MODIFY COLUMN `status` ENUM('pending','unpaid','paid','void') NOT NULL DEFAULT 'unpaid'");
    }

    public function down()
    {
        $this->db->query("UPDATE `invoices` SET `status` = 'unpaid' WHERE `status` = 'pending'");
        $this->db->query("ALTER TABLE `invoices` MODIFY COLUMN `status` ENUM('unpaid','paid','void') NOT NULL DEFAULT 'unpaid'");
    }
}
