CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
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
