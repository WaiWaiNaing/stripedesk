<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_currencies_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE `currencies` (
            `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            `code` CHAR(3) NOT NULL,
            `numeric_code` CHAR(3) NULL,
            `name` VARCHAR(80) NOT NULL,
            `minor_unit` TINYINT UNSIGNED NOT NULL DEFAULT 2,
            `symbol` VARCHAR(10) NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY `currencies_code_unique` (`code`),
            KEY `idx_currencies_deleted_at` (`deleted_at`),
            KEY `idx_currencies_is_default` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("INSERT INTO `currencies` (`id`, `code`, `numeric_code`, `name`, `minor_unit`, `symbol`, `is_default`) VALUES (1, 'USD', '840', 'US Dollar', 2, '$', 1)");
    }

    public function down()
    {
        $this->dbforge->drop_table('currencies', TRUE);
    }
}
