# Database Setup Instructions for thewellington1

## 📋 Prerequisites
- MySQL/MariaDB server running (XAMPP, WAMP, or standalone MySQL)
- Database access (default: username `root`, password empty)

## 🚀 Setup Methods

### Method 1: Using phpMyAdmin (Recommended for beginners)

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`
   - Or access via XAMPP Control Panel → Admin button next to MySQL

2. **Create Database**
   - Click "New" in the left sidebar
   - Database name: `thewellington1`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import SQL File**
   - Select the `thewellington1` database from the left sidebar
   - Click the "Import" tab at the top
   - Click "Choose File" and select: `TheWellington/databased/setup_thewellington1.sql`
   - Click "Go" button at the bottom
   - Wait for success message

4. **Verify Setup**
   - You should see 11 tables created:
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

### Method 2: Using MySQL Command Line

1. **Open Terminal/Command Prompt**
   - Windows: Open Command Prompt or PowerShell
   - Mac/Linux: Open Terminal

2. **Navigate to Project Directory**
   ```bash
   cd C:\Users\lsyx5\OneDrive\Desktop\Mini_project\TheWellington\databased
   ```

3. **Run SQL File**
   ```bash
   mysql -u root -p < setup_thewellington1.sql
   ```
   - If no password, use: `mysql -u root < setup_thewellington1.sql`
   - Enter password when prompted (or press Enter if no password)

### Method 3: Using MySQL Workbench

1. **Open MySQL Workbench**
2. **Connect to your MySQL server**
3. **Open SQL File**
   - File → Open SQL Script
   - Select `setup_thewellington1.sql`
4. **Execute**
   - Click the "Execute" button (lightning bolt icon)
   - Or press `Ctrl+Shift+Enter` (Windows) / `Cmd+Shift+Enter` (Mac)

## ✅ Verification Steps

After setup, verify everything works:

1. **Check Database Connection**
   - Visit: `http://localhost/TheWellington/test_registration.php`
   - Should show green checkmarks for all tables

2. **Test Registration**
   - Go to: `http://localhost/TheWellington/account.php`
   - Try creating a new account
   - Check if it saves to the database

3. **Verify in phpMyAdmin**
   - Go to `thewellington1` database
   - Check `accounts` table - should have your new account
   - Check `memberships` table - should have the profile linked to your account

## 🔧 Troubleshooting

### Error: "Database doesn't exist"
- Create the database first in phpMyAdmin
- Or modify the SQL file to remove `CREATE DATABASE` and `USE` statements, then select the database manually

### Error: "Table already exists"
- Drop existing database: `DROP DATABASE IF EXISTS thewellington1;`
- Then run the setup script again

### Error: "Access denied"
- Check your MySQL username and password
- Default XAMPP: username `root`, password is empty
- Update `db_connect.php` if using different credentials

### Error: "Foreign key constraint fails"
- Make sure you're running the SQL file in order (it's already in the correct order)
- Some databases require `SET FOREIGN_KEY_CHECKS=0;` at the start (not included as it's safer to keep checks enabled)

## 📝 Database Structure Overview

The database consists of 11 interconnected tables:

1. **accounts** - User authentication (username, password, role)
2. **memberships** - Customer profiles (linked to accounts)
3. **staffs** - Staff profiles (linked to accounts)
4. **restaurant_tables** - Physical tables in restaurant
5. **menu** - Menu items catalog
6. **table_availability** - Table time slots and availability
7. **reservations** - Customer reservations (links memberships to table_availability)
8. **payment_transactions** - Payment records
9. **bills** - Customer bills (links to reservations and staff)
10. **bill_items** - Items in each bill (links bills to menu)
11. **kitchen** - Kitchen order queue (links to bill_items)

## 🔐 Default Credentials

After setup, you'll need to:
1. Create accounts through the registration form on the website
2. Or manually insert test data using SQL

## 📞 Next Steps

After successful setup:
1. Test registration: Create a new customer account
2. Test login: Login with the new account
3. Update other PHP files to use the new schema (see MIGRATION_GUIDE.md)

