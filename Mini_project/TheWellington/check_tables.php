<?php
/**
 * Quick Table Checker
 * This will show you exactly what's wrong
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Tables</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        h1 { color: #333; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Table Checker</h1>";

if (!isset($conn) || $conn->connect_error) {
    echo "<div class='box'><p class='error'>✗ Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</p></div>";
    exit;
}

echo "<div class='box'><p class='success'>✓ Connected to database</p></div>";

// Check current database
$result = $conn->query("SELECT DATABASE() as db");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<div class='box'><p><strong>Current Database:</strong> " . htmlspecialchars($row['db']) . "</p></div>";
}

// Check for tables
echo "<div class='box'><h2>Checking Tables...</h2>";

$requiredTables = ['accounts', 'memberships'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
    if ($checkTable && $checkTable->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' EXISTS</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' DOES NOT EXIST</p>";
        $missingTables[] = $table;
    }
}

echo "</div>";

if (empty($missingTables)) {
    echo "<div class='box' style='background: #d4edda;'>
        <h2 class='success'>✓ All Required Tables Exist!</h2>
        <p>Your database is ready. Try registering again.</p>
        <p><a href='account.php'>Go to Registration</a></p>
    </div>";
} else {
    echo "<div class='box' style='background: #f8d7da;'>
        <h2 class='error'>✗ Tables Missing: " . implode(', ', $missingTables) . "</h2>
        <p><strong>Quick Fix:</strong></p>
        <ol>
            <li>Open this link: <a href='quick_setup_tables.php' target='_blank'><strong>quick_setup_tables.php</strong></a></li>
            <li>Wait for it to finish creating tables</li>
            <li>Refresh this page to verify</li>
        </ol>
        <p><strong>Or use phpMyAdmin:</strong></p>
        <ol>
            <li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
            <li>Select database '<code>thewellington1</code>'</li>
            <li>Click 'Import' tab</li>
            <li>Upload: <code>databased/setup_thewellington1.sql</code></li>
            <li>Click 'Go'</li>
        </ol>
    </div>";
}

// Show all tables in database
echo "<div class='box'><h2>All Tables in Database</h2>";
$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " table(s):</p><ul>";
    while ($row = $result->fetch_array()) {
        echo "<li><code>" . htmlspecialchars($row[0]) . "</code></li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>No tables found in database!</p>";
}
echo "</div>";

$conn->close();

echo "</body>
</html>";
?>

