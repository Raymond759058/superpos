-- SuperPOS Database Schema
-- Created for PHP 8+ / MySQL

CREATE DATABASE IF NOT EXISTS superpos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE superpos;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    sku VARCHAR(100),
    product_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    brand VARCHAR(100),
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'pcs',
    image VARCHAR(255),
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_no VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Cash','Credit Card','Debit Card','DuitNow QR','TNG eWallet','GrabPay','Boost') NOT NULL DEFAULT 'Cash',
    cash_received DECIMAL(10,2) DEFAULT 0.00,
    change_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('Completed','Cancelled','Refunded') DEFAULT 'Completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Transaction items table
CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Hold orders table
CREATE TABLE IF NOT EXISTS hold_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(100),
    cart_data LONGTEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Cash drawer logs table
CREATE TABLE IF NOT EXISTS cash_drawer_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_id INT,
    action_type VARCHAR(50) NOT NULL,
    method ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- System settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'SuperMart'),
('store_address', 'Jalan Padungan, 93100 Kuching, Sarawak'),
('store_phone', '+60 82-000000'),
('tax_rate', '6'),
('currency', 'RM'),
('receipt_footer', 'Thank you for shopping! Terima kasih.'),
('auto_drawer_open', '1'),
('allow_manual_drawer', '0'),
('cashier_discount', '0'),
('auto_print_receipt', '1'),
('printer_type', 'escpos'),
('admin_void_approval', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Default admin user (password: admin123)
INSERT INTO users (username, password, role, status) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Active'),
('cashier1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 'Active')
ON DUPLICATE KEY UPDATE username = username;
-- Default password for both: "password"

-- Sample products
INSERT INTO products (barcode, sku, product_name, category, brand, selling_price, cost_price, unit, status) VALUES
('8885001001', 'SKU001', 'Nescafé 3-in-1 Classic (12s)', 'Beverages', 'Nescafé', 12.90, 7.50, 'box', 'Active'),
('8885001002', 'SKU002', 'Milo Tin 400g', 'Beverages', 'Milo', 14.50, 8.20, 'tin', 'Active'),
('8885001003', 'SKU003', 'Maggi Ayam Noodles (5s)', 'Noodles', 'Maggi', 3.90, 1.80, 'pack', 'Active'),
('8885001004', 'SKU004', 'Gardenia White Bread 400g', 'Bakery', 'Gardenia', 4.50, 2.60, 'pcs', 'Active'),
('8885001005', 'SKU005', 'Spritzer Mineral Water 1.5L', 'Beverages', 'Spritzer', 2.90, 1.20, 'bottle', 'Active'),
('8885001006', 'SKU006', 'Sunlight Dish Soap 900ml', 'Household', 'Sunlight', 6.50, 3.80, 'bottle', 'Active'),
('8885001007', 'SKU007', 'Lipton Yellow Label Tea 100s', 'Beverages', 'Lipton', 11.90, 6.50, 'box', 'Active'),
('8885001008', 'SKU008', 'Colgate Toothpaste 225g', 'Personal Care', 'Colgate', 8.90, 5.20, 'tube', 'Active'),
('8885001009', 'SKU009', 'Twiggies Chocolate Cake', 'Snacks', 'Gardenia', 1.50, 0.80, 'pcs', 'Active'),
('8885001010', 'SKU010', 'Dutch Lady Full Cream Milk 1L', 'Dairy', 'Dutch Lady', 7.90, 4.80, 'carton', 'Active')
ON DUPLICATE KEY UPDATE product_name = product_name;
