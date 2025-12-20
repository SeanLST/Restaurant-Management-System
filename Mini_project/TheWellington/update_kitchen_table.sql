-- ========================================================================
-- UPDATE KITCHEN TABLE - Complete Schema Update
-- This script updates the Kitchen table to match the required schema
-- ========================================================================

-- STEP 1: Drop existing Kitchen table if you want to recreate it completely
-- (Uncomment only if you want to start fresh - this will DELETE all data)
-- DROP TABLE IF EXISTS Kitchen;

-- STEP 2: Check and add missing columns
-- Add bill_item_id if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "Kitchen";
SET @columnname = "bill_item_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column bill_item_id already exists' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(11) DEFAULT NULL AFTER kitchen_id;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add reservation_id if it doesn't exist
SET @columnname = "reservation_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column reservation_id already exists' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(11) DEFAULT NULL AFTER bill_item_id;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add reservation_time if it doesn't exist
SET @columnname = "reservation_time";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column reservation_time already exists' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " TIME NOT NULL AFTER quantity;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add confirm_order if it doesn't exist
SET @columnname = "confirm_order";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column confirm_order already exists' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " TINYINT(1) DEFAULT 0 AFTER reservation_time;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add/Update status column if it doesn't exist or is wrong type
SET @columnname = "status";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column status already exists' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(20) DEFAULT 'preparing' AFTER confirm_order;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- STEP 3: Add indexes if they don't exist
-- Index on reservation_id
CREATE INDEX IF NOT EXISTS idx_kitchen_reservation ON Kitchen(reservation_id);

-- Index on bill_item_id (if column exists)
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = 'bill_item_id')
  ) > 0,
  "CREATE INDEX IF NOT EXISTS fk_kitchen_bill_item ON Kitchen(bill_item_id);",
  "SELECT 'bill_item_id column does not exist, skipping index' AS result;"
));
PREPARE createIndexIfExists FROM @preparedStatement;
EXECUTE createIndexIfExists;
DEALLOCATE PREPARE createIndexIfExists;

-- STEP 4: Add foreign key constraints
-- Drop existing foreign key if it exists (to avoid errors)
SET @fk_name = 'idx_kitchen_reservation';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @fk_name)
  ) > 0,
  CONCAT("ALTER TABLE ", @tablename, " DROP FOREIGN KEY ", @fk_name, ";"),
  "SELECT 'Foreign key idx_kitchen_reservation does not exist' AS result;"
));
PREPARE dropFK FROM @preparedStatement;
EXECUTE dropFK;
DEALLOCATE PREPARE dropFK;

-- Add foreign key for reservation_id
ALTER TABLE Kitchen 
ADD CONSTRAINT idx_kitchen_reservation
    FOREIGN KEY (reservation_id)
    REFERENCES Reservations (reservation_id)
    ON DELETE CASCADE;

-- Drop existing bill_item_id foreign key if it exists
SET @fk_name = 'fk_kitchen_bill_item';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @fk_name)
  ) > 0,
  CONCAT("ALTER TABLE ", @tablename, " DROP FOREIGN KEY ", @fk_name, ";"),
  "SELECT 'Foreign key fk_kitchen_bill_item does not exist' AS result;"
));
PREPARE dropFK FROM @preparedStatement;
EXECUTE dropFK;
DEALLOCATE PREPARE dropFK;

-- Add foreign key for bill_item_id (check if table is Bill_Items or Bill_Item)
-- First check which table exists
SET @table_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'Bill_Items');
SET @preparedStatement = (SELECT IF(
  @table_check > 0,
  "ALTER TABLE Kitchen ADD CONSTRAINT fk_kitchen_bill_item FOREIGN KEY (bill_item_id) REFERENCES Bill_Items (bill_item_id) ON DELETE CASCADE;",
  "SELECT 'Bill_Items table does not exist, checking Bill_Item...' AS result;"
));
PREPARE addFK FROM @preparedStatement;
EXECUTE addFK;
DEALLOCATE PREPARE addFK;

-- If Bill_Items doesn't exist, try Bill_Item
SET @table_check2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'Bill_Item');
SET @preparedStatement = (SELECT IF(
  @table_check2 > 0,
  "ALTER TABLE Kitchen ADD CONSTRAINT fk_kitchen_bill_item FOREIGN KEY (bill_item_id) REFERENCES Bill_Item (bill_item_id) ON DELETE CASCADE;",
  "SELECT 'Neither Bill_Items nor Bill_Item table found' AS result;"
));
PREPARE addFK2 FROM @preparedStatement;
EXECUTE addFK2;
DEALLOCATE PREPARE addFK2;

-- STEP 5: Update existing records (optional - populate reservation_time from Reservations)
UPDATE Kitchen k
INNER JOIN Reservations r ON k.reservation_id = r.reservation_id
SET k.reservation_time = r.reservation_time
WHERE k.reservation_time IS NULL OR k.reservation_time = '00:00:00';

-- STEP 6: Verify the table structure
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

SELECT 'Kitchen table update completed!' AS result;

