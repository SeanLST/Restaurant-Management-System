-- ========================================================================
-- SIMPLE UPDATE KITCHEN TABLE - Direct ALTER statements
-- Use this if the complex script above doesn't work
-- ========================================================================

-- Add bill_item_id column
ALTER TABLE Kitchen 
ADD COLUMN IF NOT EXISTS bill_item_id INT(11) DEFAULT NULL AFTER kitchen_id;

-- Add reservation_id column
ALTER TABLE Kitchen 
ADD COLUMN IF NOT EXISTS reservation_id INT(11) DEFAULT NULL AFTER bill_item_id;

-- Add reservation_time column
ALTER TABLE Kitchen 
ADD COLUMN IF NOT EXISTS reservation_time TIME NOT NULL DEFAULT '00:00:00' AFTER quantity;

-- Add confirm_order column
ALTER TABLE Kitchen 
ADD COLUMN IF NOT EXISTS confirm_order TINYINT(1) DEFAULT 0 AFTER reservation_time;

-- Add/Update status column
ALTER TABLE Kitchen 
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'preparing' AFTER confirm_order;

-- Update status column if it exists but is wrong type
ALTER TABLE Kitchen 
MODIFY COLUMN status VARCHAR(20) DEFAULT 'preparing';

-- Add indexes
CREATE INDEX IF NOT EXISTS idx_kitchen_reservation ON Kitchen(reservation_id);
CREATE INDEX IF NOT EXISTS fk_kitchen_bill_item ON Kitchen(bill_item_id);

-- Drop existing foreign keys if they exist (to avoid errors)
ALTER TABLE Kitchen DROP FOREIGN KEY IF EXISTS idx_kitchen_reservation;
ALTER TABLE Kitchen DROP FOREIGN KEY IF EXISTS fk_kitchen_bill_item;

-- Add foreign key for reservation_id
ALTER TABLE Kitchen 
ADD CONSTRAINT idx_kitchen_reservation
    FOREIGN KEY (reservation_id)
    REFERENCES Reservations (reservation_id)
    ON DELETE CASCADE;

-- Add foreign key for bill_item_id
-- Check if Bill_Items table exists (plural)
ALTER TABLE Kitchen 
ADD CONSTRAINT fk_kitchen_bill_item
    FOREIGN KEY (bill_item_id)
    REFERENCES Bill_Items (bill_item_id)
    ON DELETE CASCADE;

-- Update existing records with reservation_time from Reservations
UPDATE Kitchen k
INNER JOIN Reservations r ON k.reservation_id = r.reservation_id
SET k.reservation_time = r.reservation_time
WHERE k.reservation_time IS NULL OR k.reservation_time = '00:00:00';

SELECT 'Kitchen table updated successfully!' AS result;

