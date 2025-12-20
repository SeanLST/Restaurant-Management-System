-- ========================================================================
-- UPDATE KITCHEN TABLE - MySQL Compatible Version
-- Run this script in phpMyAdmin or MySQL command line
-- ========================================================================

-- STEP 1: Add bill_item_id column (if it doesn't exist)
-- Check and add column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND COLUMN_NAME = 'bill_item_id');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE Kitchen ADD COLUMN bill_item_id INT(11) DEFAULT NULL AFTER kitchen_id',
    'SELECT "Column bill_item_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 2: Add reservation_id column (if it doesn't exist)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND COLUMN_NAME = 'reservation_id');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE Kitchen ADD COLUMN reservation_id INT(11) DEFAULT NULL AFTER bill_item_id',
    'SELECT "Column reservation_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 3: Add reservation_time column (if it doesn't exist)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND COLUMN_NAME = 'reservation_time');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE Kitchen ADD COLUMN reservation_time TIME NOT NULL DEFAULT "00:00:00" AFTER quantity',
    'SELECT "Column reservation_time already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 4: Add confirm_order column (if it doesn't exist)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND COLUMN_NAME = 'confirm_order');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE Kitchen ADD COLUMN confirm_order TINYINT(1) DEFAULT 0 AFTER reservation_time',
    'SELECT "Column confirm_order already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 5: Add/Update status column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND COLUMN_NAME = 'status');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE Kitchen ADD COLUMN status VARCHAR(20) DEFAULT "preparing" AFTER confirm_order',
    'ALTER TABLE Kitchen MODIFY COLUMN status VARCHAR(20) DEFAULT "preparing"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 6: Add indexes
CREATE INDEX IF NOT EXISTS idx_kitchen_reservation ON Kitchen(reservation_id);
CREATE INDEX IF NOT EXISTS fk_kitchen_bill_item ON Kitchen(bill_item_id);

-- STEP 7: Drop existing foreign keys if they exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND CONSTRAINT_NAME = 'idx_kitchen_reservation');
    
SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE Kitchen DROP FOREIGN KEY idx_kitchen_reservation',
    'SELECT "Foreign key idx_kitchen_reservation does not exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'Kitchen' 
    AND CONSTRAINT_NAME = 'fk_kitchen_bill_item');
    
SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE Kitchen DROP FOREIGN KEY fk_kitchen_bill_item',
    'SELECT "Foreign key fk_kitchen_bill_item does not exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 8: Add foreign key for reservation_id
ALTER TABLE Kitchen 
ADD CONSTRAINT idx_kitchen_reservation
    FOREIGN KEY (reservation_id)
    REFERENCES Reservations (reservation_id)
    ON DELETE CASCADE;

-- STEP 9: Add foreign key for bill_item_id
-- Check which table name exists (Bill_Items or Bill_Item)
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Bill_Items');
    
SET @sql = IF(@table_exists > 0,
    'ALTER TABLE Kitchen ADD CONSTRAINT fk_kitchen_bill_item FOREIGN KEY (bill_item_id) REFERENCES Bill_Items (bill_item_id) ON DELETE CASCADE',
    'ALTER TABLE Kitchen ADD CONSTRAINT fk_kitchen_bill_item FOREIGN KEY (bill_item_id) REFERENCES Bill_Item (bill_item_id) ON DELETE CASCADE');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 10: Update existing records with reservation_time from Reservations
UPDATE Kitchen k
INNER JOIN Reservations r ON k.reservation_id = r.reservation_id
SET k.reservation_time = r.reservation_time
WHERE k.reservation_time IS NULL OR k.reservation_time = '00:00:00';

-- STEP 11: Show final table structure
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Kitchen'
ORDER BY ORDINAL_POSITION;

SELECT 'Kitchen table update completed successfully!' AS result;

