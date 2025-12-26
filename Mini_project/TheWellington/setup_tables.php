<?php
/**
 * Table Setup Helper
 * This script will help you create all required tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Database Tables</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .box { 
            background: white; 
            padding: 20px; 
            margin: 10px 0; 
            border-radius: 5px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { 
            color: #28a745; 
            font-weight: bold; 
        }
        .error { 
            color: #dc3545; 
            font-weight: bold; 
        }
        .warning { 
            color: #ffc107; 
            font-weight: bold; 
        }
        .info { 
            color: #17a2b8; 
        }
        h1 { 
            color: #333; 
        }
        h2 { 
            color: #555; 
            border-bottom: 2px solid #8b6f47;
            padding-bottom: 5px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #8b6f47;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #6b5435;
        }
    </style>
</head>
<body>
    <h1>🔧 Setup Database Tables</h1>";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellington1";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<div class='box'><p class='error'>✗ Cannot connect to MySQL: " . $conn->connect_error . "</p></div>";
    exit;
}

// Select database
if (!$conn->select_db($dbname)) {
    echo "<div class='box'><p class='error'>✗ Database '$dbname' does not exist. Please create it first.</p></div>";
    echo "<div class='box'><p>Run this in phpMyAdmin SQL tab: <code>CREATE DATABASE thewellington1;</code></p></div>";
    $conn->close();
    exit;
}

echo "<div class='box'><p class='success'>✓ Connected to database '$dbname'</p></div>";

// Check which tables exist
$result = $conn->query("SHOW TABLES");
$existingTables = [];
if ($result) {
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
}

$requiredTables = ['accounts', 'memberships', 'staffs', 'restaurant_tables', 'menu', 'table_availability', 'reservations', 'payment_transactions', 'bills', 'bill_items', 'kitchen'];
$missingTables = array_diff($requiredTables, $existingTables);

if (empty($missingTables)) {
    echo "<div class='box'>
        <h2>✓ All Tables Already Exist!</h2>
        <p class='success'>Your database is fully set up. You can now:</p>
        <ul>
            <li><a href='account.php' class='btn'>Go to Registration</a></li>
            <li><a href='database_diagnostic.php' class='btn'>Run Diagnostic</a></li>
        </ul>
    </div>";
} else {
    echo "<div class='box'>
        <h2>Missing Tables</h2>
        <p class='warning'>The following tables are missing:</p>
        <ul>";
    foreach ($missingTables as $table) {
        echo "<li><code>$table</code></li>";
    }
    echo "</ul>
    </div>";

    // Check if SQL file exists
    $sqlFile = __DIR__ . '/databased/setup_thewellington1.sql';
    if (file_exists($sqlFile)) {
        echo "<div class='box'>
            <h2>Option 1: Import via phpMyAdmin (Recommended)</h2>
            <ol>
                <li>Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
                <li>Select database '<code>$dbname</code>' from left sidebar</li>
                <li>Click the <strong>Import</strong> tab at the top</li>
                <li>Click <strong>Choose File</strong> and select: <code>databased/setup_thewellington1.sql</code></li>
                <li>Click <strong>Go</strong> button</li>
                <li>Wait for success message</li>
                <li>Refresh this page to verify</li>
            </ol>
        </div>";

        echo "<div class='box'>
            <h2>Option 2: Manual SQL Execution</h2>
            <ol>
                <li>Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
                <li>Select database '<code>$dbname</code>' from left sidebar</li>
                <li>Click the <strong>SQL</strong> tab at the top</li>
                <li>Open the file: <code>databased/setup_thewellington1.sql</code> in a text editor</li>
                <li>Copy ALL the SQL code</li>
                <li>Paste it into the SQL tab in phpMyAdmin</li>
                <li>Click <strong>Go</strong></li>
            </ol>
        </div>";

        // Try to read and execute SQL file automatically (if safe)
        if (isset($_GET['auto']) && $_GET['auto'] === 'yes') {
            echo "<div class='box'>
                <h2>Option 3: Automatic Setup (Attempting...)</h2>";
            
            $sql = file_get_contents($sqlFile);
            
            // Remove CREATE DATABASE and USE statements (we're already in the database)
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue; // Skip empty statements and comments
                }
                
                if ($conn->query($statement)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    if ($errorCount <= 5) { // Show first 5 errors
                        echo "<p class='error'>Error: " . htmlspecialchars($conn->error) . "</p>";
                        echo "<p class='info'>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
                    }
                }
            }
            
            if ($errorCount == 0) {
                echo "<p class='success'>✓ All SQL statements executed successfully!</p>";
                echo "<p><a href='setup_tables.php' class='btn'>Refresh to Verify</a></p>";
            } else {
                echo "<p class='warning'>⚠ Some errors occurred. Please use Option 1 (phpMyAdmin Import) instead.</p>";
            }
            
            echo "</div>";
        } else {
            echo "<div class='box'>
                <h2>Option 3: Automatic Setup</h2>
                <p class='warning'>⚠ This will attempt to run the SQL file automatically.</p>
                <p>If the above options don't work, you can try:</p>
                <p><a href='?auto=yes' class='btn'>Try Automatic Setup</a></p>
                <p class='info'>Note: This may fail if there are permission issues. Use Option 1 if this doesn't work.</p>
            </div>";
        }
    } else {
        echo "<div class='box'>
            <p class='error'>✗ SQL file not found at: <code>$sqlFile</code></p>
            <p>Please make sure the file exists.</p>
        </div>";
    }
}

$conn->close();

echo "</body>
</html>";
?>

