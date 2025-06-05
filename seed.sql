-- Database schema and seed data for BazarTrack-API

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL
);

-- Wallets table
CREATE TABLE IF NOT EXISTS wallets (
    user_id INT PRIMARY KEY,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    assigned_to INT NULL,
    status VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) DEFAULT '',
    estimated_cost DECIMAL(10,2) NULL,
    actual_cost DECIMAL(10,2) NULL,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- History logs table
CREATE TABLE IF NOT EXISTS history_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    changed_by_user_id INT NOT NULL,
    timestamp DATETIME NOT NULL,
    data_snapshot JSON NULL
);

-- Seed users
INSERT INTO users (id, name, email, password, role) VALUES
    (1, 'Default Owner', 'owner@example.com', '$2y$10$bJCOemcxy.RQWdS9evmCeeH9yryaa4sraTvcWoHPjj0xxKSi7htW6', 'owner'),
    (2, 'Grace Morrison', 'grace.morrison@example.com', '$2y$10$r1cVdgtEhOvUkQqj5pmuoe8eCQxKDfWc.gcKXwxuV3QcUmzntVR5G', 'owner'),
    (3, 'Leo Martinez', 'leo.martinez@example.com', '$2y$10$xYI66DYuT65qtu/scWa5.u1IYQpckUs3z3.PHnfIUbsR9X740XAxq', 'assistant');

-- Seed wallets
INSERT INTO wallets (user_id, balance) VALUES
    (1, 100.00),
    (2, 100.00),
    (3, 50.00);

-- Seed orders
INSERT INTO orders (id, created_by, assigned_to, status, created_at, completed_at) VALUES
    (1, 2, 3, 'pending', '2025-01-01 10:00:00', NULL),
    (2, 2, NULL, 'completed', '2025-01-02 11:00:00', '2025-01-03 12:00:00');

-- Seed order items
INSERT INTO order_items (order_id, product_name, quantity, unit, estimated_cost, actual_cost, status) VALUES
    (1, 'Apples', 10, 'kg', 20.00, 18.50, 'ordered'),
    (1, 'Oranges', 5, 'kg', 12.00, 11.50, 'ordered'),
    (2, 'Bananas', 3, 'kg', 6.00, 5.50, 'delivered');

-- Seed transactions
INSERT INTO transactions (user_id, amount, type, created_at) VALUES
    (2, 25.00, 'credit', '2025-01-05 09:00:00'),
    (2, 10.00, 'debit', '2025-01-06 09:30:00'),
    (3, 50.00, 'credit', '2025-01-05 10:00:00');

-- Seed payments
INSERT INTO payments (user_id, amount, type, created_at) VALUES
    (2, 25.00, 'wallet', '2025-01-05 09:00:00'),
    (3, 50.00, 'wallet', '2025-01-05 10:00:00');

-- Seed history logs
INSERT INTO history_logs (entity_type, entity_id, action, changed_by_user_id, timestamp, data_snapshot) VALUES
    ('order', 1, 'created', 2, '2025-01-01 10:00:00', '{"status":"pending"}'),
    ('order', 2, 'completed', 2, '2025-01-03 12:00:00', '{"status":"completed"}');
