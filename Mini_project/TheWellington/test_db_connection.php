<?php
// Test Database Connection
// Access this file via browser: http://localhost/TheWellington/test_db_connection.php

require_once 'db_connect.php';

echo "<h2>Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check if connection exists
if (isset($conn)) {
    echo "<p style='color: green;'>✓ Connection object created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Connection object not found</p>";
    exit;
}

// Test 2: Check connection status
if ($conn->connect_error) {
    echo "<p style='color: red;'>✗ Connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✓ Connected to MySQL server</p>";
}

// Test 3: Check if database exists
$dbname = "thewellingtondb";
$result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Database '$dbname' exists</p>";
} else {
    echo "<p style='color: red;'>✗ Database '$dbname' does not exist. Please create it in phpMyAdmin.</p>";
}

// Test 4: Check if users table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if ($tableCheck->num_rows > 0) {
    echo "<p style='color: green;'>✓ Table 'users' exists</p>";
    
    // Test 5: Check table structure
    $columns = $conn->query("SHOW COLUMNS FROM users");
    echo "<h3>Users Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 6: Count users
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $userCount->fetch_assoc()['count'];
    echo "<p>Total users in database: <strong>$count</strong></p>";
    
    // Test 7: List users
    if ($count > 0) {
        echo "<h3>Users in Database:</h3>";
        $users = $conn->query("SELECT user_id, username, role FROM users LIMIT 10");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
        while ($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No users found in database. You may need to insert test users.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'users' does not exist. Please create it using your database.txt schema.</p>";
}

// Test 8: Test a simple query
try {
    $testQuery = $conn->query("SELECT 1 as test");
    if ($testQuery) {
        echo "<p style='color: green;'>✓ Test query executed successfully</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Connection Info:</strong></p>";
echo "<ul>";
echo "<li>Server: " . $conn->host_info . "</li>";
echo "<li>Database: thewellingtondb</li>";
echo "<li>Character Set: " . $conn->character_set_name() . "</li>";
echo "</ul>";

$conn->close();
?>

