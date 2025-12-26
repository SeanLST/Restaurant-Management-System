<?php
/**
 * Quick Database Creation Script
 * This will create the thewellington1 database if it doesn't exist
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";

echo "<h2>Database Setup Helper</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; padding: 10px; background: #d4edda; border-left: 4px solid #28a745; margin: 10px 0; }
    .error { color: red; font-weight: bold; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; margin: 10px 0; }
    .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-left: 4px solid #17a2b8; margin: 10px 0; }
    .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
</style>";

// Step 1: Connect to MySQL
echo "<div class='step'><h3>Step 1: Connecting to MySQL Server</h3>";
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<div class='error'>✗ Connection failed: " . $conn->connect_error . "</div>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP/WAMP MySQL is running</li>";
    echo "<li>Username and password are correct</li>";
    echo "</ul>";
    exit;
} else {
    echo "<div class='success'>✓ Connected to MySQL server</div>";
}
echo "</div>";

// Step 2: Create database
$dbname = "thewellington1";
echo "<div class='step'><h3>Step 2: Creating Database '$dbname'</h3>";

// Check if database exists
$result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
if ($result && $result->num_rows > 0) {
    echo "<div class='info'>ℹ Database '$dbname' already exists</div>";
} else {
    // Create database
    if ($conn->query("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        echo "<div class='success'>✓ Database '$dbname' created successfully</div>";
    } else {
        echo "<div class='error'>✗ Failed to create database: " . $conn->error . "</div>";
        $conn->close();
        exit;
    }
}
echo "</div>";

// Step 3: Select database
echo "<div class='step'><h3>Step 3: Selecting Database</h3>";
if ($conn->select_db($dbname)) {
    echo "<div class='success'>✓ Database '$dbname' selected</div>";
} else {
    echo "<div class='error'>✗ Failed to select database: " . $conn->error . "</div>";
    $conn->close();
    exit;
}
echo "</div>";

// Step 4: Check if tables exist
echo "<div class='step'><h3>Step 4: Checking Tables</h3>";
$requiredTables = ['accounts', 'memberships', 'staffs', 'restaurant_tables', 'menu', 'table_availability', 'reservations', 'payment_transactions', 'bills', 'bill_items', 'kitchen'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✓ Table '$table' exists</div>";
    } else {
        echo "<div class='error'>✗ Table '$table' does NOT exist</div>";
        $missingTables[] = $table;
    }
}
echo "</div>";

// Step 5: Instructions
if (empty($missingTables)) {
    echo "<div class='step'>";
    echo "<h3>✓ Setup Complete!</h3>";
    echo "<p>All tables exist. Your database is ready to use.</p>";
    echo "<p><a href='account.php'>Go to Login Page</a></p>";
    echo "</div>";
} else {
    echo "<div class='step'>";
    echo "<h3>⚠ Tables Missing</h3>";
    echo "<p>The following tables are missing: " . implode(', ', $missingTables) . "</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Select the '$dbname' database from the left sidebar</li>";
    echo "<li>Click the 'Import' tab at the top</li>";
    echo "<li>Click 'Choose File' and select: <code>databased/setup_thewellington1.sql</code></li>";
    echo "<li>Click 'Go' to import</li>";
    echo "</ol>";
    echo "<p>Or use command line:</p>";
    echo "<pre>mysql -u root -p $dbname < databased/setup_thewellington1.sql</pre>";
    echo "</div>";
}

$conn->close();
?>

