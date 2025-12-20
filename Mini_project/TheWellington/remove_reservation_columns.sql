-- ==========================================
-- REMOVE full_name, email, phone COLUMNS FROM RESERVATIONS TABLE
-- Since users are logged in, we only need user_id (links to Users table)
-- Run this in phpMyAdmin SQL tab
-- ==========================================

USE thewellingtondb;

-- Remove full_name column if it exists
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'full_name') > 0,
    'ALTER TABLE Reservations DROP COLUMN full_name',
    'SELECT "full_name column does not exist" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove email column if it exists
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'email') > 0,
    'ALTER TABLE Reservations DROP COLUMN email',
    'SELECT "email column does not exist" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove phone column if it exists
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'phone') > 0,
    'ALTER TABLE Reservations DROP COLUMN phone',
    'SELECT "phone column does not exist" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove special_requests column if it exists
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'special_requests') > 0,
    'ALTER TABLE Reservations DROP COLUMN special_requests',
    'SELECT "special_requests column does not exist" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure party_size column exists (rename guests to party_size if needed)
SET @has_party_size = (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'party_size');
     
SET @has_guests = (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE TABLE_SCHEMA = 'thewellingtondb' 
     AND TABLE_NAME = 'Reservations' 
     AND COLUMN_NAME = 'guests');

-- If guests exists but party_size doesn't, rename it
SET @sql = IF(
    @has_guests > 0 AND @has_party_size = 0,
    'ALTER TABLE Reservations CHANGE COLUMN guests party_size INT NOT NULL',
    IF(@has_party_size > 0,
        'SELECT "party_size column already exists" AS message',
        'ALTER TABLE Reservations ADD COLUMN party_size INT NOT NULL AFTER reservation_time'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show the final structure
DESCRIBE Reservations;

