-- ==========================================
-- Database Setup Script for thewellingtondb1
-- Complete database structure matching the new schema
-- Run this in phpMyAdmin or MySQL command line
-- ==========================================

USE thewellingtondb1;

-- ==========================================
-- 1. CREATE USERS TABLE
-- Stores both Staff and Customers
-- ==========================================
CREATE TABLE IF NOT EXISTS Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role VARCHAR(30) NOT NULL,
    register_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================
-- 2. CREATE STAFF TABLE
-- Staff members linked to Users
-- ==========================================
CREATE TABLE IF NOT EXISTS Staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_name VARCHAR(100) NOT NULL,
    account_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    FOREIGN KEY (account_id) REFERENCES Users(user_id)
) ENGINE=InnoDB;

-- ==========================================
-- 3. CREATE TABLE_AVAILABILITY TABLE
-- Table availability tracking
-- ==========================================
CREATE TABLE IF NOT EXISTS Table_Availability (
    availability_id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'Available',
    FOREIGN KEY (table_id) REFERENCES restaurant_table(table_id)
) ENGINE=InnoDB;

-- ==========================================
-- 4. CREATE MEMBERSHIPS TABLE
-- Customer loyalty points system
-- ==========================================
CREATE TABLE IF NOT EXISTS Memberships (
    membership_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    member_name VARCHAR(250) NOT NULL,
    points INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
) ENGINE=InnoDB;

-- ==========================================
-- 5. CREATE RESTAURANT TABLE
-- Physical tables in the restaurant
-- ==========================================
CREATE TABLE IF NOT EXISTS restaurant_table (
    table_id INT PRIMARY KEY AUTO_INCREMENT,
    table_number INT NOT NULL UNIQUE,
    capacity INT NOT NULL,
    status VARCHAR(20) DEFAULT 'Available'
) ENGINE=InnoDB;

-- ==========================================
-- 6. CREATE RESERVATIONS TABLE
-- Customer reservations (MUST be before payment_transactions)
-- ==========================================
CREATE TABLE IF NOT EXISTS Reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INT NOT NULL,
    table_id INT NOT NULL,
    special_requests VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (table_id) REFERENCES restaurant_table(table_id)
) ENGINE=InnoDB;

-- ==========================================
-- 7. CREATE MENU TABLE
-- Menu items available for ordering
-- ==========================================
CREATE TABLE IF NOT EXISTS Menu (
    item_id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(8,2) NOT NULL,
    category VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- ==========================================
-- 8. CREATE PAYMENT_TRANSACTIONS TABLE
-- Payment transaction tracking
-- Updated with verified_at column
-- ==========================================
CREATE TABLE IF NOT EXISTS payment_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    payment_method ENUM('touch_n_go', 'bank_transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','verified','failed','refunded') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id),
    FOREIGN KEY (verified_by) REFERENCES Users(user_id)
) ENGINE=InnoDB;

-- ==========================================
-- 9. CREATE BILLS TABLE
-- Bill and payment tracking
-- Updated with payment_status column
-- ==========================================
CREATE TABLE IF NOT EXISTS Bills (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    table_id INT NOT NULL,
    transaction_id INT NULL,
    payment_method ENUM('touch_n_go','bank_transfer','cash','online') NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
    FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id),
    FOREIGN KEY (table_id) REFERENCES restaurant_table(table_id),
    FOREIGN KEY (transaction_id) REFERENCES payment_transactions(transaction_id)
) ENGINE=InnoDB;

-- ==========================================
-- 10. CREATE BILL_ITEMS TABLE
-- Individual items in each bill
-- ==========================================
CREATE TABLE IF NOT EXISTS Bill_Items (
    bill_item_id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    item_id VARCHAR(10) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES Bills(bill_id),
    FOREIGN KEY (item_id) REFERENCES Menu(item_id)
) ENGINE=InnoDB;

-- ==========================================
-- 11. CREATE KITCHEN TABLE
-- Kitchen order management
-- Updated with reservation_id, bill_item_id, reservation_time, confirm_order, and status
-- ==========================================
CREATE TABLE IF NOT EXISTS Kitchen (
    kitchen_id INT(11) NOT NULL AUTO_INCREMENT,
    bill_item_id INT(11) DEFAULT NULL,
    reservation_id INT(11) DEFAULT NULL,
    table_id INT(11) NOT NULL,
    item_id VARCHAR(10) NOT NULL,
    quantity INT(11) NOT NULL,
    reservation_time TIME NOT NULL,
    confirm_order TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'preparing',
    PRIMARY KEY (kitchen_id),
    KEY table_id (table_id),
    KEY item_id (item_id),
    KEY idx_kitchen_reservation (reservation_id),
    KEY fk_kitchen_bill_item (bill_item_id),
    CONSTRAINT idx_kitchen_reservation
        FOREIGN KEY (reservation_id)
        REFERENCES Reservations (reservation_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_kitchen_bill_item
        FOREIGN KEY (bill_item_id)
        REFERENCES Bill_Items (bill_item_id)
        ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES restaurant_table(table_id),
    FOREIGN KEY (item_id) REFERENCES Menu(item_id)
) ENGINE=InnoDB;

-- ==========================================
-- INSERT SAMPLE DATA
-- ==========================================

-- Insert Staff Users
INSERT INTO Users (username, password, full_name, email, phone, role)
VALUES
('waiter1', 'pass123', 'Ali Rahman', 'ali@rest.com', '0121111111', 'staff'),
('waiter2', 'pass123', 'Siti Aminah', 'siti@rest.com', '0122222222', 'staff'),
('chef1',   'pass123', 'Ahmad Zaki', 'zaki@rest.com', '0123333333', 'staff'),
('chef2',   'pass123', 'Nur Huda', 'huda@rest.com', '0124444444', 'staff'),
('manager1','pass123', 'Daniel Lim', 'daniel@rest.com', '0125555555', 'staff'),
('manager2','pass123', 'Sarah Tan', 'sarah@rest.com', '0126666666', 'staff'),
('waiter3', 'pass123', 'Firdaus', 'firdaus@rest.com', '0127777777', 'staff'),
('chef3',   'pass123', 'Aisyah', 'aisyah@rest.com', '0128888888', 'staff');

-- Insert Staff Records
INSERT INTO Staff (staff_name, account_id, role)
VALUES
('Ali Rahman',   1, 'waiter'),
('Siti Aminah',  2, 'waiter'),
('Ahmad Zaki',   3, 'chef'),
('Nur Huda',     4, 'chef'),
('Daniel Lim',   5, 'manager'),
('Sarah Tan',    6, 'manager'),
('Firdaus',      7, 'waiter'),
('Aisyah',       8, 'chef');

-- Insert Restaurant Tables
INSERT IGNORE INTO restaurant_table (table_number, capacity) VALUES
(1, 2),
(2, 2),
(3, 4),
(4, 4),
(5, 6),
(6, 6),
(7, 4),
(8, 4);

-- Insert Menu Items
INSERT INTO Menu (item_id, name, description, price, category) VALUES
-- Wellingtons (W)
('W1', 'Beef Wellington', 'Classic filet steak coated in pâté and duxelles, wrapped in puff pastry.', 55.00, 'Main'),
('W2', 'Chicken Wellington', 'Oven-baked chicken breast with spinach and cheese filling.', 32.00, 'Main'),
('W3', 'Salmon Wellington', 'Fresh salmon fillet with dill cream wrapped in pastry.', 48.00, 'Main'),
('W4', 'Mini Mushroom Wellington', 'Vegetarian option with sauteed mushroom duxelles.', 25.00, 'Main'),

-- Other Mains (M)
('M1', 'Grilled Ribeye Steak', '240g ribeye with herb butter.', 58.00, 'Main'),
('M2', 'Herb-Roasted Chicken', 'Slow-roasted chicken thigh with rosemary and thyme.', 28.00, 'Main'),
('M3', 'Pan-Seared Seabass', 'Served with lemon butter sauce.', 35.00, 'Main'),

-- Pasta (P)
('P1', 'Spaghetti Carbonara', 'Classic carbonara with smoked beef bacon.', 22.00, 'Pasta'),
('P2', 'Spaghetti Aglio Olio', 'Garlic, chili, olive oil with prawns.', 24.00, 'Pasta'),

-- Starters (S)
('S1', 'Classic Caesar Salad', 'Romaine, parmesan, croutons.', 16.00, 'Starter'),
('S2', 'Wild Mushroom Soup', 'Creamy soup with truffle oil.', 14.00, 'Starter'),
('S3', 'Bruschetta', 'Tomato, basil and balsamic glaze.', 12.00, 'Starter'),
('S4', 'Smoked Salmon Bites', 'Dill cream, pickled shallot.', 18.00, 'Starter'),
('S5', 'Garlic Herb Bread Basket', 'House-made bread with garlic butter.', 10.00, 'Starter'),

-- Sides (SD)
('SD1', 'Truffle Fries', 'Hand cut fries tossed in truffle oil.', 12.00, 'Side'),
('SD2', 'Creamy Mashed Potatoes', 'Buttery Yukon Gold potatoes whipped with cream and chives.', 12.00, 'Side'),
('SD3', 'Grilled Asparagus', 'Fresh asparagus spears grilled with lemon zest and parmesan.', 18.00, 'Side'),
('SD4', 'Three-Cheese Mac & Cheese', 'Macaroni baked in a rich cheddar, parmesan, and gruyère sauce.', 16.00, 'Side'),
('SD5', 'Sautéed Wild Mushrooms', 'Button and shiitake mushrooms cooked in garlic herb butter.', 14.00, 'Side'),
('SD6', 'Creamed Spinach', 'Fresh spinach simmered in a rich garlic cream sauce.', 14.00, 'Side'),

-- Desserts (D)
('D1', 'Crème Brûlée', 'Vanilla bean custard with caramelized sugar.', 16.00, 'Dessert'),
('D2', 'Molten Chocolate Cake', 'Warm chocolate fondant with vanilla gelato.', 18.00, 'Dessert'),
('D3', 'Tiramisu Wellington Style', 'Coffee-soaked ladyfingers, mascarpone cream.', 20.00, 'Dessert'),

-- Beverages (B)
('B1', 'Peppermint Tea', 'Refreshing mint herbal infusion, naturally caffeine-free.', 8.00, 'Beverages'),
('B2', 'Green Tea', 'Premium Japanese sencha green tea.', 9.00, 'Beverages'),
('B3', 'Americano', 'Espresso with hot water, smooth and bold.', 9.00, 'Beverages'),
('B4', 'Espresso', 'Rich, full-bodied Italian coffee shot.', 7.00, 'Beverages'),
('B5', 'Watermelon Juice', 'Refreshing and hydrating watermelon juice.', 13.00, 'Beverages'),
('B6', 'Green Detox Juice', 'Spinach, celery, cucumber, apple, and lemon.', 16.00, 'Beverages'),
('B7', 'Berry Blast Juice', 'Mixed berries with apple and honey.', 16.00, 'Beverages');
