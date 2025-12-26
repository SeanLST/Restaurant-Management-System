# Database Migration Guide: thewellington1

## Overview
The database has been migrated from the old schema to a new normalized schema. This document outlines what has been updated and what still needs attention.

## ✅ Completed Updates

### 1. Database Connection
- **File**: `db_connect.php`
- **Change**: Updated database name from `thewellingtondb1` to `thewellington1`

### 2. SQL Setup File
- **File**: `databased/setup_thewellington1.sql`
- **Contents**: Complete schema with all 11 tables:
  - accounts
  - memberships
  - staffs
  - restaurant_tables
  - menu
  - table_availability
  - reservations
  - payment_transactions
  - bills
  - bill_items
  - kitchen

### 3. Login Process
- **File**: `login/login_process.php`
- **Changes**:
  - Updated to query `accounts` table instead of `Users`
  - Fetches profile from `memberships` (customers) or `staffs` (staff)
  - Sets proper session variables including `account_id`, `member_id`, `staff_id`
  - Updates `last_login` timestamp

### 4. Registration Process
- **File**: `login/register_process.php`
- **Changes**:
  - Creates account in `accounts` table first
  - Then creates profile in `memberships` (customers) or `staffs` (staff)
  - Uses transactions for data integrity
  - Proper email uniqueness checking

## 🔄 Schema Changes Summary

### Old Schema → New Schema

| Old Table | New Table(s) | Key Changes |
|-----------|--------------|-------------|
| `Users` | `accounts` + `memberships`/`staffs` | Separated authentication from profiles |
| `Users` | `accounts` | Authentication only (username, password, role, status) |
| `Users` | `memberships` | Customer profiles (linked to accounts via account_id) |
| `Users` | `staffs` | Staff profiles (linked to accounts via account_id) |
| `Reservations` | `reservations` + `table_availability` | Separated table scheduling from reservations |
| - | `payment_transactions` | New table for payment processing |
| - | `bills` + `bill_items` | New tables for billing system |
| - | `kitchen` | Updated structure with bill_item_id |

### Key Differences

1. **Authentication vs Profiles**: 
   - Old: Single `Users` table with all user data
   - New: `accounts` for login, `memberships`/`staffs` for profiles

2. **Table Availability**:
   - Old: Reservations directly linked to tables
   - New: `table_availability` table tracks time slots, then linked to `reservations`

3. **Session Variables**:
   - Old: `user_id` from Users table
   - New: `account_id` (for authentication), `member_id` or `staff_id` (for profiles)

## ⚠️ Files That Need Updating

The following files still reference the old schema and need to be updated:

### High Priority (Core Functionality)
1. **backend.php** - Reservation processing
   - Update queries from `Users` to `accounts`/`memberships`
   - Update reservation creation to use `table_availability`

2. **reservation/reservation_process.php**
   - Update to use new reservation flow with `table_availability`
   - Update user lookup to use `accounts`/`memberships`

3. **customer/customer_history.php**
   - Update to query `memberships` instead of `Users`
   - Update reservation queries

### Medium Priority (Staff Features)
4. **staff/staff_actions.php**
   - Multiple queries need updating
   - Update user management functions
   - Update reservation queries

5. **staff/staff.php**
   - Dashboard queries
   - User list queries

6. **staff/cashier_actions.php**
   - Update bill creation logic
   - Update reservation queries

7. **staff/waiter_actions.php**
   - Update table management

### Lower Priority (Utilities)
8. **check/check_availability.php**
9. **check/check_cashier_role.php**
10. **staff/verify_cashier_setup.php**
11. **test_db_connection.php**

## 📝 Common Update Patterns

### Pattern 1: User Lookup
**Old:**
```php
$stmt = $conn->prepare("SELECT user_id, full_name, email FROM Users WHERE username = ?");
```

**New:**
```php
// Get account first
$stmt = $conn->prepare("SELECT account_id, role FROM accounts WHERE username = ?");
// Then get profile
if ($role === 'customer') {
    $stmt = $conn->prepare("SELECT member_id, member_name, email FROM memberships WHERE account_id = ?");
} else {
    $stmt = $conn->prepare("SELECT staff_id, staff_name, email FROM staffs WHERE account_id = ?");
}
```

### Pattern 2: Session Variables
**Old:**
```php
$_SESSION['user_id'] // From Users table
```

**New:**
```php
$_SESSION['account_id'] // From accounts table
$_SESSION['member_id']  // From memberships (if customer)
$_SESSION['staff_id']   // From staffs (if staff)
```

### Pattern 3: Reservation Creation
**Old:**
```php
INSERT INTO Reservations (user_id, table_id, reservation_date, reservation_time, ...)
```

**New:**
```php
// 1. Create table_availability record
INSERT INTO table_availability (table_id, reservation_date, reservation_time, status)
// 2. Create reservation
INSERT INTO reservations (member_id, availability_id, party_size, ...)
// 3. Update table_availability with reservation_id
UPDATE table_availability SET reservation_id = ? WHERE availability_id = ?
```

## 🚀 Next Steps

1. **Run the SQL Setup**:
   ```bash
   mysql -u root < TheWellington/databased/setup_thewellington1.sql
   ```
   Or import via phpMyAdmin.

2. **Test Login/Registration**:
   - Test customer registration and login
   - Test staff registration (if applicable)

3. **Update Remaining Files**:
   - Start with high priority files
   - Test each file after updating
   - Update session variable references throughout

4. **Data Migration** (if needed):
   - If you have existing data, create migration scripts
   - Map old `Users` records to new `accounts` + `memberships`/`staffs`

## 📌 Notes

- The new schema uses lowercase table names
- Foreign key constraints are properly set up with CASCADE/SET NULL
- All tables use UTF8MB4 character set
- The `accounts` table tracks `account_status` (active, suspended, deleted)
- Staff roles are stored in `staffs.role` (waiter, cashier, chef, etc.)

