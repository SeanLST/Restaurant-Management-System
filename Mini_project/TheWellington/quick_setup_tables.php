<?php
/**
 * Quick Table Setup - Creates all tables directly
 * Just open this file in your browser to create all tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellington1";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Table Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        h1 { color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #8b6f47; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>🔧 Quick Table Setup</h1>";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<div class='box'><p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p></div>";
    exit;
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($dbname);

echo "<div class='box'><p class='success'>✓ Connected to database '$dbname'</p></div>";

// Array of table creation SQL statements
$tables = [
    'accounts' => "CREATE TABLE IF NOT EXISTS accounts (
        account_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'staff') NOT NULL DEFAULT 'customer',
        account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        register_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'memberships' => "CREATE TABLE IF NOT EXISTS memberships (
        member_id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL UNIQUE,
        member_name VARCHAR(250) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        points INT DEFAULT 0 NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
        INDEX idx_member_phone (phone),
        INDEX idx_member_email (email),
        INDEX idx_member_points (points DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'staffs' => "CREATE TABLE IF NOT EXISTS staffs (
        staff_id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL UNIQUE,
        staff_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        role VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
        INDEX idx_staff_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'restaurant_tables' => "CREATE TABLE IF NOT EXISTS restaurant_tables (
        table_id INT AUTO_INCREMENT PRIMARY KEY,
        table_number INT NOT NULL UNIQUE,
        capacity INT NOT NULL,
        section VARCHAR(50) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_table_number (table_number),
        INDEX idx_capacity (capacity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'menu' => "CREATE TABLE IF NOT EXISTS menu (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        price DECIMAL(10, 2) NOT NULL,
        category VARCHAR(50) NOT NULL,
        available TINYINT(1) DEFAULT 1,
        image_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_available (available),
        CONSTRAINT chk_positive_price CHECK (price >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'table_availability' => "CREATE TABLE IF NOT EXISTS table_availability (
        availability_id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT NOT NULL,
        reservation_date DATE NOT NULL,
        reservation_time TIME NOT NULL,
        status ENUM('available', 'reserved', 'occupied', 'maintenance') DEFAULT 'available',
        reservation_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE CASCADE,
        INDEX idx_availability_table (table_id),
        INDEX idx_availability_datetime (reservation_date, reservation_time),
        INDEX idx_availability_status (status),
        INDEX idx_availability_reservation (reservation_id),
        UNIQUE KEY unique_table_datetime (table_id, reservation_date, reservation_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'reservations' => "CREATE TABLE IF NOT EXISTS reservations (
        reservation_id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        availability_id INT NOT NULL,
        party_size INT NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
        special_requests TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES memberships(member_id) ON DELETE CASCADE,
        FOREIGN KEY (availability_id) REFERENCES table_availability(availability_id) ON DELETE CASCADE,
        INDEX idx_reservation_member (member_id),
        INDEX idx_reservation_availability (availability_id),
        INDEX idx_reservation_status (status),
        CONSTRAINT chk_party_size CHECK (party_size BETWEEN 1 AND 50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'payment_transactions' => "CREATE TABLE IF NOT EXISTS payment_transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        payment_method ENUM('touch_n_go','bank_transfer','cash','credit_card','debit_card','ewallet') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('pending', 'verified', 'failed', 'refunded') DEFAULT 'pending',
        transaction_reference VARCHAR(100) UNIQUE NULL,
        bill_id INT NULL,
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (verified_by) REFERENCES staffs(staff_id) ON DELETE SET NULL,
        INDEX idx_payment_bill (bill_id),
        INDEX idx_payment_status (payment_status),
        INDEX idx_payment_date (created_at DESC),
        CONSTRAINT chk_positive_amount CHECK (amount > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'bills' => "CREATE TABLE IF NOT EXISTS bills (
        bill_id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        reservation_id INT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (staff_id) REFERENCES staffs(staff_id) ON DELETE CASCADE,
        FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL,
        INDEX idx_bill_staff (staff_id),
        INDEX idx_bill_reservation (reservation_id),
        INDEX idx_bill_status (payment_status),
        INDEX idx_bill_date (created_at DESC),
        CONSTRAINT chk_positive_total CHECK (total_amount >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'bill_items' => "CREATE TABLE IF NOT EXISTS bill_items (
        bill_item_id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE,
        INDEX idx_bill_item_bill (bill_id),
        INDEX idx_bill_item_menu (item_id),
        CONSTRAINT chk_positive_quantity CHECK (quantity > 0),
        CONSTRAINT chk_positive_unit_price CHECK (unit_price >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'kitchen' => "CREATE TABLE IF NOT EXISTS kitchen (
        kitchen_id INT AUTO_INCREMENT PRIMARY KEY,
        bill_item_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity INT NOT NULL,
        status ENUM('preparing', 'ready', 'completed', 'served') DEFAULT 'preparing',
        confirm_order TINYINT(1) DEFAULT 0,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bill_item_id) REFERENCES bill_items(bill_item_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE,
        INDEX idx_kitchen_status (status),
        INDEX idx_kitchen_item (item_id),
        INDEX idx_kitchen_bill_item (bill_item_id),
        CONSTRAINT chk_positive_kitchen_quantity CHECK (quantity > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

echo "<div class='box'><h2>Creating Tables...</h2>";

$created = 0;
$errors = 0;
$skipped = 0;

foreach ($tables as $tableName => $sql) {
    // Check if table already exists
    $check = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($check && $check->num_rows > 0) {
        echo "<p class='info'>ℹ Table '$tableName' already exists - skipped</p>";
        $skipped++;
        continue;
    }
    
    // Create table
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Created table: $tableName</p>";
        $created++;
    } else {
        echo "<p class='error'>✗ Failed to create table '$tableName': " . $conn->error . "</p>";
        $errors++;
    }
}

echo "</div>";

// Add foreign key constraints that depend on other tables
echo "<div class='box'><h2>Setting up Foreign Keys...</h2>";

$alterStatements = [
    "ALTER TABLE table_availability ADD CONSTRAINT fk_availability_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE SET NULL",
    "ALTER TABLE payment_transactions ADD CONSTRAINT fk_payment_bill FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE"
];

foreach ($alterStatements as $alter) {
    // Check if constraint already exists
    $tableName = preg_match('/ALTER TABLE (\w+)/i', $alter, $matches) ? $matches[1] : '';
    if ($tableName) {
        $check = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME LIKE 'fk_%'");
        if ($check && $check->num_rows > 0) {
            echo "<p class='info'>ℹ Foreign key already exists for $tableName - skipped</p>";
            continue;
        }
    }
    
    if ($conn->query($alter)) {
        echo "<p class='success'>✓ Added foreign key constraint</p>";
    } else {
        // Ignore "Duplicate key" errors
        if (stripos($conn->error, 'Duplicate key') === false && stripos($conn->error, 'already exists') === false) {
            echo "<p class='error'>⚠ " . htmlspecialchars($conn->error) . "</p>";
        }
    }
}

echo "</div>";

// Verify
echo "<div class='box'><h2>Verification</h2>";

$requiredTables = array_keys($tables);
$allExist = true;

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' is MISSING</p>";
        $allExist = false;
    }
}

echo "</div>";

if ($allExist) {
    echo "<div class='box' style='background: #d4edda; border-left: 4px solid #28a745;'>";
    echo "<h2 class='success'>✓ SUCCESS! All Tables Created</h2>";
    echo "<p>Summary: Created $created, Skipped $skipped, Errors $errors</p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='account.php' class='btn'>Go to Registration Page</a></li>";
    echo "<li><a href='database_diagnostic.php' class='btn'>Run Diagnostic</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='box' style='background: #f8d7da; border-left: 4px solid #dc3545;'>";
    echo "<h2 class='error'>✗ Some Tables Are Missing</h2>";
    echo "<p>Please try again or use phpMyAdmin to import setup_thewellington1.sql</p>";
    echo "</div>";
}

$conn->close();

echo "</body>
</html>";
?>

