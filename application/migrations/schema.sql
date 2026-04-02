CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    api_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY users_email_unique (email),
    KEY idx_users_deleted_at (deleted_at),
    KEY idx_users_api_token (api_token)
);

CREATE TABLE currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code CHAR(3) NOT NULL,
    numeric_code CHAR(3) NULL,
    name VARCHAR(80) NOT NULL,
    minor_unit TINYINT UNSIGNED NOT NULL DEFAULT 2,
    symbol VARCHAR(10) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY currencies_code_unique (code),
    KEY idx_currencies_deleted_at (deleted_at),
    KEY idx_currencies_is_default (is_default)
);

INSERT INTO currencies (id, code, numeric_code, name, minor_unit, symbol, is_default) VALUES (1, 'USD', '840', 'US Dollar', 2, '$', 1);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    currency_id INT NOT NULL DEFAULT 1,
    stripe_price_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_products_deleted_at (deleted_at),
    KEY idx_products_stripe_price_id (stripe_price_id),
    KEY idx_products_currency_id (currency_id),
    CONSTRAINT products_currency_id_fk FOREIGN KEY (currency_id) REFERENCES currencies (id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency_id INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'paid', 'cancelled', 'failed') DEFAULT 'pending',
    stripe_session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_orders_stripe_session_id (stripe_session_id),
    KEY idx_orders_status (status),
    KEY idx_orders_deleted_at (deleted_at),
    KEY idx_orders_currency_id (currency_id),
    CONSTRAINT orders_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT orders_currency_id_fk FOREIGN KEY (currency_id) REFERENCES currencies (id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_order_items_deleted_at (deleted_at),
    CONSTRAINT order_items_order_id_fk FOREIGN KEY (order_id) REFERENCES orders (id),
    CONSTRAINT order_items_product_id_fk FOREIGN KEY (product_id) REFERENCES products (id)
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'paid', 'void') DEFAULT 'unpaid',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY invoices_order_id_unique (order_id),
    UNIQUE KEY invoices_invoice_number_unique (invoice_number),
    KEY idx_invoices_status (status),
    KEY idx_invoices_deleted_at (deleted_at),
    CONSTRAINT invoices_order_id_fk FOREIGN KEY (order_id) REFERENCES orders (id)
);

CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    receipt_number VARCHAR(50) NOT NULL,
    stripe_payment_intent VARCHAR(255) NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY receipts_invoice_id_unique (invoice_id),
    UNIQUE KEY receipts_receipt_number_unique (receipt_number),
    KEY idx_receipts_stripe_payment_intent (stripe_payment_intent),
    KEY idx_receipts_deleted_at (deleted_at),
    CONSTRAINT receipts_invoice_id_fk FOREIGN KEY (invoice_id) REFERENCES invoices (id)
);

CREATE TABLE stripe_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100),
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_stripe_logs_created_at (created_at),
    KEY idx_stripe_logs_event_type (event_type),
    KEY idx_stripe_logs_deleted_at (deleted_at)
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    intent VARCHAR(32) NOT NULL DEFAULT 'password_reset',
    otp_hash VARCHAR(255) NOT NULL,
    otp_expires_at TIMESTAMP NULL DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    reset_token_hash VARCHAR(255) NULL,
    reset_expires_at TIMESTAMP NULL DEFAULT NULL,
    attempts INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_password_resets_email (email),
    KEY idx_password_resets_deleted_at (deleted_at),
    KEY idx_password_resets_otp_expires_at (otp_expires_at)
);

CREATE TABLE otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    intent VARCHAR(32) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    otp_expires_at TIMESTAMP NULL DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    attempts INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_otp_verifications_user_id (user_id),
    KEY idx_otp_verifications_email_intent (email, intent),
    KEY idx_otp_verifications_deleted_at (deleted_at),
    KEY idx_otp_verifications_otp_expires_at (otp_expires_at),
    CONSTRAINT otp_verifications_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE carts (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('active','checking_out','converted','expired') NOT NULL DEFAULT 'active',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_carts_user (user_id),
    KEY idx_carts_status (status),
    KEY idx_carts_deleted_at (deleted_at),
    CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
    KEY idx_cart_items_cart (cart_id),
    KEY idx_cart_items_product (product_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id)
);
