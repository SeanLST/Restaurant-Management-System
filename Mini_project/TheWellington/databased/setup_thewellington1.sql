-- ========================================
-- THE WELLINGTON DATABASE SETUP
-- Database: thewellington1
-- ========================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS thewellington1;

USE thewellington1;

-- ========================================
-- 1️⃣ ACCOUNTS (users table)
-- ========================================
CREATE TABLE accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'staff') NOT NULL DEFAULT 'customer',
    account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    register_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='User authentication accounts';

-- ========================================
-- 2️⃣ MEMBERSHIPS (customer profiles)
-- ========================================
CREATE TABLE memberships (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL UNIQUE,
    member_name VARCHAR(250) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    points INT DEFAULT 0 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    
    INDEX idx_member_phone (phone),
    INDEX idx_member_email (email),
    INDEX idx_member_points (points DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Customer membership profiles';

-- ========================================
-- 3️⃣ STAFFS (staff profiles)
-- ========================================
CREATE TABLE staffs (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL UNIQUE,
    staff_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    
    INDEX idx_staff_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Staff member profiles';

-- ========================================
-- 4️⃣ RESTAURANT_TABLES
-- ========================================
CREATE TABLE restaurant_tables (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT NOT NULL UNIQUE,
    capacity INT NOT NULL,
    section VARCHAR(50) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_table_number (table_number),
    INDEX idx_capacity (capacity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Restaurant table inventory';

-- ========================================
-- 5️⃣ MENU
-- ========================================
CREATE TABLE menu (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    available TINYINT(1) DEFAULT 1,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_available (available),
    CONSTRAINT chk_positive_price CHECK (price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Menu items catalog';

-- ========================================
-- 6️⃣ TABLE_AVAILABILITY (no FK to reservation yet)
-- ========================================
CREATE TABLE table_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status ENUM('available', 'reserved', 'occupied', 'maintenance') DEFAULT 'available',
    reservation_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE CASCADE,
    
    INDEX idx_availability_table (table_id),
    INDEX idx_availability_datetime (reservation_date, reservation_time),
    INDEX idx_availability_status (status),
    INDEX idx_availability_reservation (reservation_id),
    UNIQUE KEY unique_table_datetime (table_id, reservation_date, reservation_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Table scheduling and availability';

-- ========================================
-- 7️⃣ RESERVATIONS
-- ========================================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    availability_id INT NOT NULL,
    party_size INT NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    special_requests TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (member_id) REFERENCES memberships(member_id) ON DELETE CASCADE,
    FOREIGN KEY (availability_id) REFERENCES table_availability(availability_id) ON DELETE CASCADE,
    
    INDEX idx_reservation_member (member_id),
    INDEX idx_reservation_availability (availability_id),
    INDEX idx_reservation_status (status),
    CONSTRAINT chk_party_size CHECK (party_size BETWEEN 1 AND 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Customer reservations';

-- Now add FK from table_availability to reservations
ALTER TABLE table_availability 
ADD CONSTRAINT fk_availability_reservation 
FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL;

-- ========================================
-- 8️⃣ PAYMENT_TRANSACTIONS (no FK to bills yet)
-- ========================================
CREATE TABLE payment_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_method ENUM('touch_n_go','bank_transfer','cash','credit_card','debit_card','ewallet') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
    transaction_reference VARCHAR(100) UNIQUE NULL,
    bill_id INT NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (verified_by) REFERENCES staffs(staff_id) ON DELETE SET NULL,
    
    INDEX idx_payment_bill (bill_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (created_at DESC),
    CONSTRAINT chk_positive_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Payment transaction records';

-- ========================================
-- 9️⃣ BILLS
-- ========================================
CREATE TABLE bills (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    reservation_id INT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES staffs(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL,
    
    INDEX idx_bill_staff (staff_id),
    INDEX idx_bill_reservation (reservation_id),
    INDEX idx_bill_status (payment_status),
    INDEX idx_bill_date (created_at DESC),
    CONSTRAINT chk_positive_total CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Customer bills';

-- Now add FK from payment_transactions to bills
ALTER TABLE payment_transactions 
ADD CONSTRAINT fk_payment_bill 
FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE;

-- ========================================
-- 🔟 BILL_ITEMS
-- ========================================
CREATE TABLE bill_items (
    bill_item_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    
    FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE,
    
    INDEX idx_bill_item_bill (bill_id),
    INDEX idx_bill_item_menu (item_id),
    CONSTRAINT chk_positive_quantity CHECK (quantity > 0),
    CONSTRAINT chk_positive_unit_price CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Individual items in each bill';

-- ========================================
-- 1️⃣1️⃣ KITCHEN
-- ========================================
CREATE TABLE kitchen (
    kitchen_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_item_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('preparing', 'ready', 'completed', 'served') DEFAULT 'preparing',
    confirm_order TINYINT(1) DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bill_item_id) REFERENCES bill_items(bill_item_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE,
    
    INDEX idx_kitchen_status (status),
    INDEX idx_kitchen_item (item_id),
    INDEX idx_kitchen_bill_item (bill_item_id),
    CONSTRAINT chk_positive_kitchen_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Kitchen order queue';
