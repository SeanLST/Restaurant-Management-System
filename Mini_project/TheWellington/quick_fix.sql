-- ==========================================
-- QUICK FIX FOR RESERVATIONS TABLE
-- Copy and paste this into phpMyAdmin SQL tab
-- This will add all missing columns safely
-- ==========================================

USE thewellingtondb;

-- Add table_id column if missing (ignore error if exists)
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'table_id') = 0,
    'ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER user_id',
    'SELECT "table_id column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reservation_date column if missing
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'reservation_date') = 0,
    'ALTER TABLE reservations ADD COLUMN reservation_date DATE NOT NULL AFTER table_id',
    'SELECT "reservation_date column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reservation_time column if missing
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'reservation_time') = 0,
    'ALTER TABLE reservations ADD COLUMN reservation_time TIME NOT NULL AFTER reservation_date',
    'SELECT "reservation_time column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add guests column if missing
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'guests') = 0,
    'ALTER TABLE reservations ADD COLUMN guests INT NOT NULL AFTER reservation_time',
    'SELECT "guests column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column if missing
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE reservations ADD COLUMN status ENUM(\'pending\', \'confirmed\', \'completed\', \'cancelled\') DEFAULT \'pending\' AFTER guests',
    'SELECT "status column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add special_requests column if missing
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'reservations' 
     AND COLUMN_NAME = 'special_requests') = 0,
    'ALTER TABLE reservations ADD COLUMN special_requests TEXT AFTER guests',
    'SELECT "special_requests column already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for table_id if missing
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'thewellingtondb'
    AND TABLE_NAME = 'reservations'
    AND COLUMN_NAME = 'table_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE reservations ADD FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create reservation_orders table if it doesn't exist
CREATE TABLE IF NOT EXISTS reservation_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    item_id INT,
    quantity INT DEFAULT 1,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify the structure
DESCRIBE reservations;

