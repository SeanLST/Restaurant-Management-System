-- ==========================================
-- DIRECT FIX FOR RESERVATIONS TABLE
-- Run this in phpMyAdmin SQL tab
-- ==========================================

USE thewellingtondb1;

-- Check and add table_id column if missing
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'thewellingtondb1' 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'table_id';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER user_id',
    'SELECT "Column table_id already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for table_id if it doesn't exist
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'thewellingtondb1'
AND TABLE_NAME = 'reservations'
AND COLUMN_NAME = 'table_id'
AND REFERENCED_TABLE_NAME IS NOT NULL;

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE reservations ADD FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add special_requests column if missing
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'thewellingtondb1' 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'special_requests';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE reservations ADD COLUMN special_requests TEXT AFTER guests',
    'SELECT "Column special_requests already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure reservation_orders table exists
CREATE TABLE IF NOT EXISTS reservation_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    item_id INT,
    quantity INT DEFAULT 1,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show final structure
DESCRIBE reservations;
DESCRIBE reservation_orders;

