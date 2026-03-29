SET NAMES utf8mb4;

-- Run after CodeIgniter migrations. USD (id=1) is created by migration 002.
-- Default login password for all seeded users: Password123!
-- (bcrypt below; compatible with PHP password_verify / CodeIgniter)

INSERT INTO `currencies` (`code`, `numeric_code`, `name`, `minor_unit`, `symbol`, `is_default`, `created_by`, `updated_by`, `deleted_at`)
VALUES ('EUR', '978', 'Euro', 2, '€', 0, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `numeric_code` = VALUES(`numeric_code`),
    `minor_unit` = VALUES(`minor_unit`),
    `symbol` = VALUES(`symbol`);

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `api_token`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 'Admin User', 'admin@stripedesk.local', '$2y$10$IDaXEovG3trHnfQZtuvE5OiheBepA1Ib7szZyq3CW5vEbnyK9cljS', 'admin', NULL, NULL, NULL, NULL),
    (2, 'Jane Smith', 'jane@stripedesk.local', '$2y$10$IDaXEovG3trHnfQZtuvE5OiheBepA1Ib7szZyq3CW5vEbnyK9cljS', 'user', NULL, NULL, NULL, NULL),
    (3, 'Bob Johnson', 'bob@stripedesk.local', '$2y$10$IDaXEovG3trHnfQZtuvE5OiheBepA1Ib7szZyq3CW5vEbnyK9cljS', 'user', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `password` = VALUES(`password`),
    `role` = VALUES(`role`);

ALTER TABLE `users` AUTO_INCREMENT = 4;

INSERT INTO `products` (`id`, `name`, `description`, `price`, `currency_id`, `stripe_price_id`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 'Basic Plan', 'Core features for individuals.', 29.99, 1, NULL, 1, NULL, NULL),
    (2, 'Pro Plan', 'Advanced features and priority support.', 79.99, 1, NULL, 1, NULL, NULL),
    (3, 'Add-on Pack', 'Extra storage and integrations.', 9.99, 1, NULL, 1, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `price` = VALUES(`price`),
    `currency_id` = VALUES(`currency_id`),
    `stripe_price_id` = VALUES(`stripe_price_id`);

ALTER TABLE `products` AUTO_INCREMENT = 4;

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `currency_id`, `status`, `stripe_session_id`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 2, 79.99, 1, 'paid', 'cs_seed_demo_session_001', NULL, NULL, NULL),
    (2, 2, 29.99, 1, 'pending', NULL, NULL, NULL, NULL),
    (3, 3, 9.99, 1, 'cancelled', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `total_amount` = VALUES(`total_amount`),
    `status` = VALUES(`status`),
    `stripe_session_id` = VALUES(`stripe_session_id`);

ALTER TABLE `orders` AUTO_INCREMENT = 4;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 1, 2, 1, 79.99, NULL, NULL, NULL),
    (2, 2, 1, 1, 29.99, NULL, NULL, NULL),
    (3, 3, 3, 1, 9.99, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `quantity` = VALUES(`quantity`),
    `unit_price` = VALUES(`unit_price`);

ALTER TABLE `order_items` AUTO_INCREMENT = 4;

INSERT INTO `invoices` (`id`, `order_id`, `invoice_number`, `total_amount`, `status`, `due_date`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 1, 'INV-2026-00001', 79.99, 'paid', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `total_amount` = VALUES(`total_amount`),
    `status` = VALUES(`status`);

ALTER TABLE `invoices` AUTO_INCREMENT = 2;

INSERT INTO `receipts` (`id`, `invoice_id`, `receipt_number`, `stripe_payment_intent`, `amount_paid`, `paid_at`, `created_by`, `updated_by`, `deleted_at`)
VALUES
    (1, 1, 'RCP-2026-00001', 'pi_seed_demo_payment_001', 79.99, '2026-03-29 10:00:00', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
    `amount_paid` = VALUES(`amount_paid`),
    `stripe_payment_intent` = VALUES(`stripe_payment_intent`);

ALTER TABLE `receipts` AUTO_INCREMENT = 2;

INSERT INTO `stripe_logs` (`event_type`, `payload`, `created_by`, `updated_by`, `deleted_at`)
VALUES (
    'checkout.session.completed',
    JSON_OBJECT('seed', true, 'order_id', 1, 'note', 'demo row'),
    NULL,
    NULL,
    NULL
);
