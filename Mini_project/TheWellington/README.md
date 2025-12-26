# The Wellington Restaurant Management System

A comprehensive restaurant management system built with PHP, MySQL, and JavaScript for handling reservations, orders, payments, and staff management.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Usage Guide](#usage-guide)
- [Configuration](#configuration)

## 🎯 Overview

The Wellington is a full-featured restaurant management system that enables restaurants to manage reservations, process orders, handle payments, track kitchen orders, and manage staff and customer memberships. The system supports multiple user roles with role-based access control.

## ✨ Features

### Customer Features
- **Account Registration & Login** - Secure customer account creation and authentication
- **Table Reservations** - Book tables with date, time, and party size selection
- **Pre-ordering** - Order food items in advance during reservation
- **Payment Processing** - Multiple payment methods (Touch 'n Go, Bank Transfer, Cash, Credit Card)
- **Order History** - View past reservations and order history
- **Loyalty Points** - Earn and track membership points

### Staff Features
- **Staff Dashboard** - Overview of daily operations, reservations, and revenue
- **Reservation Management** - View, update, and manage customer reservations
- **Payment Verification** - Verify and process customer payments
- **Kitchen Order Management** - Track and complete kitchen orders
- **Table Management** - Monitor table availability and status
- **Member Management** - View and manage customer memberships
- **Menu Management** - Add, update, and delete menu items
- **Reports & Statistics** - View revenue, bookings, and sales analytics

### Role-Specific Interfaces
- **Cashier Interface** - Point of Sale (POS) system for processing orders and payments
- **Waiter Interface** - View and manage ready orders, mark orders as served
- **Kitchen Interface** - View pending orders, mark orders as complete

## 🛠 Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Server**: Apache (XAMPP)
- **Icons**: Font Awesome 6.4.0
- **Architecture**: MVC-like structure with separate action handlers

## 📦 Installation

### Prerequisites
- XAMPP (or similar PHP/MySQL stack)
- Web browser (Chrome, Firefox, Edge, Safari)

### Step 1: Clone/Download Project
```bash
# If using git
git clone <repository-url>
cd TheWellington

# Or download and extract to your web server directory
```

### Step 2: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to a directory (e.g., `C:\xampp`)
3. Start Apache and MySQL from XAMPP Control Panel

### Step 3: Place Project Files
Copy the `TheWellington` folder to your XAMPP htdocs directory:
- Windows: `C:\xampp\htdocs\TheWellington`
- Mac/Linux: `/Applications/XAMPP/htdocs/TheWellington` or `/var/www/html/TheWellington`

### Step 4: Database Configuration
The database connection is configured in `db_connect.php`. Default settings:
- **Host**: `localhost`
- **Username**: `root`
- **Password**: `` (empty)
- **Database**: `thewellington1`

If your MySQL setup differs, edit `db_connect.php`:
```php
$servername = "localhost";
$username = "root";
$password = "";  // Update if you have a password
$dbname = "thewellington1";
```

## 🗄 Database Setup

### Option 1: Using phpMyAdmin (Recommended)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a database (optional - will auto-create)
3. Select the `thewellington1` database (or create it)
4. Click "Import" tab
5. Choose file: `databased/setup_thewellington1.sql`
6. Click "Go" to import

### Option 2: Using MySQL Command Line
```bash
# Navigate to project directory
cd C:\xampp\htdocs\TheWellington\databased

# Run SQL file (adjust path as needed)
mysql -u root -p < setup_thewellington1.sql
```

### Database Schema
The database includes the following tables:
- `accounts` - User authentication (customers and staff)
- `memberships` - Customer membership profiles
- `staffs` - Staff member profiles
- `restaurant_tables` - Restaurant table information
- `table_availability` - Table booking availability
- `reservations` - Customer reservations
- `menu` - Menu items
- `bills` - Order bills
- `bill_items` - Individual items in bills
- `payment_transactions` - Payment records
- `kitchen` - Kitchen order tracking

## 📁 Project Structure

```
TheWellington/
├── databased/
│   ├── setup_thewellington1.sql      # Database setup script
│   └── SETUP_INSTRUCTIONS.md         # Detailed setup guide
├── login/
│   ├── login_process.php             # Login handler
│   └── register_process.php          # Registration handler
├── reservation/
│   ├── reservation.php               # Main reservation page
│   ├── reservation_process.php       # Reservation handler
│   ├── check_availability.php        # Table availability checker
│   └── get_booked_tables.php         # Get booked tables API
├── customer/
│   ├── view_history.php              # Customer order history
│   └── customer_history.php          # History API endpoint
├── staff/
│   ├── staff.php                     # Staff dashboard
│   ├── staff_actions.php             # Staff API endpoints
│   ├── cashier.php                   # Cashier POS interface
│   ├── cashier_actions.php           # Cashier API endpoints
│   ├── waiter.php                    # Waiter interface
│   └── waiter_actions.php            # Waiter API endpoints
├── db_connect.php                    # Database connection
├── logout.php                        # Logout handler
├── index.php                         # Home page
├── script.js                         # Main JavaScript file
├── style.css                         # Main stylesheet
└── README.md                         # This file
```

## 👥 User Roles

### Customer
- Can create account and login
- Make table reservations
- Pre-order food items
- View order history
- Track loyalty points

### Staff Roles

#### Manager
- Full access to all features
- Staff and member management
- View all reports and statistics

#### Cashier
- Access to POS system
- Process orders and payments
- Verify payment transactions
- Create bills for walk-in customers

#### Waiter
- View kitchen orders ready to serve
- Mark orders as served
- Monitor table status

#### Chef/Kitchen
- View all pending orders
- Mark orders as complete when ready
- Track order preparation status

## 📖 Usage Guide

### For Customers

1. **Registration**
   - Go to home page
   - Click "Sign Up" or "Register"
   - Fill in personal details
   - Choose "Customer" role
   - Create account

2. **Making a Reservation**
   - Login to your account
   - Click "Reservation"
   - Select date, time, and party size
   - Choose a table (or select "Auto-assigned")
   - Optionally add pre-orders
   - Complete payment
   - Confirm reservation

3. **Viewing History**
   - Login to account
   - Navigate to "History" or "My Orders"
   - View past reservations and orders

### For Staff

1. **Login**
   - Go to login page
   - Enter staff credentials
   - System automatically redirects based on role:
     - Cashier → Cashier POS interface
     - Waiter → Waiter interface
     - Other staff → Staff dashboard

2. **Staff Dashboard**
   - View today's reservations
   - Monitor table availability
   - Process payments
   - View reports and statistics
   - Manage members and staff

3. **Cashier Interface**
   - Process walk-in orders
   - Verify online payments
   - Create bills
   - Handle payments

4. **Waiter Interface**
   - View orders ready to serve
   - See preparing orders
   - Mark orders as served when delivered

5. **Kitchen Interface** (via Staff Dashboard)
   - View all pending orders
   - Mark orders as complete when ready
   - Track order preparation status

## ⚙️ Configuration

### Database Connection
Edit `db_connect.php` if your MySQL credentials differ:
```php
$servername = "localhost";
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "thewellington1";
```

### Session Configuration
Sessions are managed automatically. To customize session settings, edit PHP configuration or add session settings in each PHP file:
```php
session_start();
ini_set('session.gc_maxlifetime', 3600); // 1 hour
```

### File Paths
The system uses relative paths. If you deploy to a subdirectory, update paths in:
- `script.js` - Update fetch URLs if needed
- `logout.php` - Update redirect paths
- Navigation links in PHP files

## 🔒 Security Notes

- **Password Storage**: Currently uses plain text (for development). **IMPORTANT**: Implement password hashing for production:
  ```php
  password_hash($password, PASSWORD_DEFAULT);
  password_verify($password, $hash);
  ```

- **SQL Injection**: Prepared statements are used throughout. Maintain this practice.

- **XSS Protection**: User input is escaped using `htmlspecialchars()` in most places.

- **Session Security**: Implement session timeout and secure session cookies for production.

- **File Permissions**: Ensure proper file permissions on server.

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `db_connect.php`
- Ensure database exists (will auto-create if configured)

### Page Not Found (404)
- Check file paths are correct
- Verify .htaccess if using URL rewriting
- Ensure Apache mod_rewrite is enabled

### Session Issues
- Check PHP session directory is writable
- Verify cookies are enabled in browser
- Clear browser cookies and try again

### Permission Errors
- Check file permissions (644 for files, 755 for directories)
- Ensure web server user has read access

## 📝 Notes

- The system auto-creates the database if it doesn't exist (configured in `db_connect.php`)
- Default staff account must be created manually or via registration
- Payment verification sends orders to kitchen automatically
- Orders are tracked from creation to serving
- The system supports both online reservations and walk-in orders

## 🚀 Future Enhancements

Potential improvements:
- [ ] Implement password hashing for production
- [ ] Add email notifications for reservations
- [ ] SMS notifications for order ready status
- [ ] Mobile app for waiters
- [ ] Advanced reporting and analytics
- [ ] Inventory management
- [ ] Employee scheduling
- [ ] Online menu with images
- [ ] Customer reviews and ratings

## 📄 License

[Specify your license here]

## 👨‍💻 Developer

Developed for The Wellington Restaurant Management System

## 📞 Support

For issues or questions, please contact the development team or create an issue in the repository.

---

**Version**: 1.0  
**Last Updated**: 2024  
**Database Version**: thewellington1

