-- ========================================================================
-- UPDATE KITCHEN TABLE - Simple Direct Script
-- Run this in phpMyAdmin or MySQL command line
-- ========================================================================

-- Add columns (will error if they already exist, which is fine)
ALTER TABLE Kitchen ADD COLUMN bill_item_id INT(11) DEFAULT NULL AFTER kitchen_id;
ALTER TABLE Kitchen ADD COLUMN reservation_id INT(11) DEFAULT NULL AFTER bill_item_id;
ALTER TABLE Kitchen ADD COLUMN reservation_time TIME NOT NULL DEFAULT '00:00:00' AFTER quantity;
ALTER TABLE Kitchen ADD COLUMN confirm_order TINYINT(1) DEFAULT 0 AFTER reservation_time;
ALTER TABLE Kitchen ADD COLUMN status VARCHAR(20) DEFAULT 'preparing' AFTER confirm_order;

-- Modify status if it already exists
ALTER TABLE Kitchen MODIFY COLUMN status VARCHAR(20) DEFAULT 'preparing';

-- Add indexes
CREATE INDEX idx_kitchen_reservation ON Kitchen(reservation_id);
CREATE INDEX fk_kitchen_bill_item ON Kitchen(bill_item_id);

-- Drop existing foreign keys (ignore errors if they don't exist)
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE Kitchen DROP FOREIGN KEY idx_kitchen_reservation;
ALTER TABLE Kitchen DROP FOREIGN KEY fk_kitchen_bill_item;
SET FOREIGN_KEY_CHECKS = 1;

-- Add foreign key for reservation_id
ALTER TABLE Kitchen 
ADD CONSTRAINT idx_kitchen_reservation
    FOREIGN KEY (reservation_id)
    REFERENCES Reservations (reservation_id)
    ON DELETE CASCADE;

-- Add foreign key for bill_item_id (use Bill_Items if it exists, otherwise Bill_Item)
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

-- Show table structure
DESCRIBE Kitchen;

SELECT 'Database update completed!' AS result;

