-- 1. USERS: Added audit fields and soft delete
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    api_token VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1, -- For soft delete
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL
);

-- 2. PRODUCTS: Added audit fields
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stripe_price_id VARCHAR(100) NULL, -- Useful for Stripe Checkout
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL
);

-- 3. ORDERS: Acts as the checkout session tracker
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'usd',
    status ENUM('pending', 'paid', 'cancelled', 'failed') DEFAULT 'pending',
    stripe_session_id VARCHAR(255) NULL, -- To track the Stripe Checkout session
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 4. ORDER_ITEMS: Better for scalability (even if they buy 1 item now, you're ready for many)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 5. INVOICES: The official billing record
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNIQUE NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL, -- e.g., INV-2023-001
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'paid', 'void') DEFAULT 'unpaid',
    due_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- 6. RECEIPTS: The proof of payment
CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNIQUE NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL, -- e.g., RCP-2023-001
    stripe_payment_intent VARCHAR(255) NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- 7. STRIPE_LOGS: For debugging Webhooks
CREATE TABLE stripe_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100),
    payload JSON, -- MySQL 8 JSON type is perfect here
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);