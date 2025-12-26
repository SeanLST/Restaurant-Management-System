<?php
/**
 * Direct Table Creation Script
 * This will create all tables directly without needing to import SQL file
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
    <title>Create Tables Directly</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Creating Database Tables...</h1>";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<div class='box'><p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p></div>";
    exit;
}

// Create database if it doesn't exist
if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "<div class='box'><p class='error'>✗ Failed to create database: " . $conn->error . "</p></div>";
    $conn->close();
    exit;
}

// Select database
$conn->select_db($dbname);

echo "<div class='box'><p class='success'>✓ Connected to database '$dbname'</p></div>";

// Read SQL file
$sqlFile = __DIR__ . '/databased/setup_thewellington1.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='box'><p class='error'>✗ SQL file not found: $sqlFile</p></div>";
    $conn->close();
    exit;
}

$sql = file_get_contents($sqlFile);

// Remove CREATE DATABASE and USE statements (we're already in the database)
$sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
$sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql);

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "<div class='box'><h2>Executing SQL Statements...</h2>";

foreach ($statements as $index => $statement) {
    // Skip empty statements, comments, and ALTER statements that might fail
    if (empty($statement) || 
        strpos($statement, '--') === 0 || 
        strpos($statement, '/*') === 0 ||
        trim($statement) === '') {
        continue;
    }
    
    // Skip ALTER TABLE statements for now (they depend on tables existing)
    if (stripos($statement, 'ALTER TABLE') === 0) {
        continue;
    }
    
    // Execute statement
    if ($conn->query($statement)) {
        $successCount++;
        // Show which table was created
        if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
            echo "<p class='success'>✓ Created table: " . $matches[1] . "</p>";
        }
    } else {
        $errorCount++;
        $errorMsg = $conn->error;
        // Ignore "table already exists" errors
        if (stripos($errorMsg, 'already exists') === false) {
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $errorMsg
            ];
            echo "<p class='error'>✗ Error: " . htmlspecialchars($errorMsg) . "</p>";
        } else {
            // Table already exists - that's OK
            if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "<p class='success'>ℹ Table already exists: " . $matches[1] . "</p>";
            }
        }
    }
}

echo "</div>";

// Now handle ALTER TABLE statements (foreign keys that depend on tables)
echo "<div class='box'><h2>Setting up Foreign Keys...</h2>";

$alterStatements = [];
$sql = file_get_contents($sqlFile);
preg_match_all('/ALTER TABLE.*?;/is', $sql, $matches);
foreach ($matches[0] as $alter) {
    $alter = trim($alter);
    if (!empty($alter)) {
        if ($conn->query($alter)) {
            echo "<p class='success'>✓ Foreign key constraint added</p>";
        } else {
            // Ignore "duplicate key" errors
            if (stripos($conn->error, 'Duplicate key') === false) {
                echo "<p class='error'>⚠ " . htmlspecialchars($conn->error) . "</p>";
            }
        }
    }
}

echo "</div>";

// Verify tables were created
echo "<div class='box'><h2>Verification</h2>";

$requiredTables = ['accounts', 'memberships', 'staffs', 'restaurant_tables', 'menu', 'table_availability', 'reservations', 'payment_transactions', 'bills', 'bill_items', 'kitchen'];
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

if ($allExist) {
    echo "<div class='box' style='background: #d4edda; border-left: 4px solid #28a745;'>";
    echo "<h2 class='success'>✓ SUCCESS! All Tables Created</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='account.php'>Go to Registration Page</a></li>";
    echo "<li><a href='database_diagnostic.php'>Run Diagnostic</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='box' style='background: #f8d7da; border-left: 4px solid #dc3545;'>";
    echo "<h2 class='error'>✗ Some Tables Are Missing</h2>";
    echo "<p>Please try importing the SQL file manually via phpMyAdmin:</p>";
    echo "<ol>";
    echo "<li>Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
    echo "<li>Select database 'thewellington1'</li>";
    echo "<li>Click 'Import' tab</li>";
    echo "<li>Upload: databased/setup_thewellington1.sql</li>";
    echo "</ol>";
    echo "</div>";
}

$conn->close();

echo "</body>
</html>";
?>

