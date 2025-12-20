-- ==========================================
-- ADD ALL MISSING COLUMNS TO RESERVATIONS TABLE
-- Run this in phpMyAdmin SQL tab
-- Ignore errors that say "Duplicate column name" - that means it already exists
-- ==========================================

USE thewellingtondb;

-- Add table_id if missing (ignore error if exists)
ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER user_id;

-- Add reservation_date if missing (ignore error if exists)
ALTER TABLE reservations ADD COLUMN reservation_date DATE NOT NULL AFTER table_id;

-- Add reservation_time if missing (ignore error if exists)
ALTER TABLE reservations ADD COLUMN reservation_time TIME NOT NULL AFTER reservation_date;

-- Add guests if missing (THIS IS THE ONE CAUSING YOUR ERROR - ignore error if exists)
ALTER TABLE reservations ADD COLUMN guests INT NOT NULL AFTER reservation_time;

-- Add status if missing (ignore error if exists)
ALTER TABLE reservations ADD COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending' AFTER guests;

-- Add special_requests if missing (ignore error if exists)
ALTER TABLE reservations ADD COLUMN special_requests TEXT AFTER guests;

-- Add foreign key for table_id (ignore error if exists)
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = 'thewellingtondb'
     AND TABLE_NAME = 'reservations'
     AND COLUMN_NAME = 'table_id'
     AND REFERENCED_TABLE_NAME IS NOT NULL) = 0,
    'ALTER TABLE reservations ADD FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show the final structure
DESCRIBE reservations;

