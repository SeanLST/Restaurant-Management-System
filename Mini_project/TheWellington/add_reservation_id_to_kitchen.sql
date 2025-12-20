-- ========================================================================
-- CRITICAL FIX: Add reservation_id to Kitchen table
-- Problem: Kitchen orders showing for all time slots for the same table
-- Solution: Link kitchen orders directly to the specific reservation
-- ========================================================================

-- STEP 1: Add reservation_id column to Kitchen table
ALTER TABLE Kitchen ADD COLUMN reservation_id INT NULL AFTER kitchen_id;

-- STEP 2: Add foreign key constraint
ALTER TABLE Kitchen ADD FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id) ON DELETE CASCADE;

-- STEP 3: (Optional) Update existing records if needed
-- This will try to match existing kitchen orders to reservations based on table_id
-- Note: This is a best-effort update and may not be 100% accurate
UPDATE Kitchen k
INNER JOIN Reservations r ON k.table_id = r.table_id 
    AND r.reservation_date = CURDATE()
    AND r.status = 'confirmed'
SET k.reservation_id = r.reservation_id
WHERE k.reservation_id IS NULL;

