<?php
/**
 * Test Registration Script
 * This script tests if the database connection and registration process work correctly
 */

require 'db_connect.php';

echo "<h2>Registration Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background-color: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #8b6f47; color: white; }
</style>";

// Check database connection
echo "<div class='info'><h3>Step 1: Database Connection Test</h3>";
if (!isset($conn) || $conn->connect_error) {
    echo "<p class='error'>✗ Connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</p>";
    die();
} else {
    echo "<p class='success'>✓ Successfully connected to database: thewellington1</p>";
}
echo "</div>";

// Check if tables exist
echo "<div class='info'><h3>Step 2: Check Required Tables</h3>";
$tables = ['accounts', 'memberships', 'staffs'];
$allTablesExist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' does NOT exist</p>";
        $allTablesExist = false;
    }
}
echo "</div>";

if (!$allTablesExist) {
    echo "<div class='info'><p class='error'><strong>Please run the SQL setup file first:</strong> databased/setup_thewellington1.sql</p></div>";
    exit;
}

// Test account creation (dry run - check if we can prepare the statement)
echo "<div class='info'><h3>Step 3: Test SQL Prepared Statements</h3>";

$testUsername = 'test_user_' . time();
$testPassword = 'testpass123';
$testRole = 'customer';

// Test account insert
$stmt = $conn->prepare("INSERT INTO accounts (username, password, role, account_status) VALUES (?, ?, ?, 'active')");
if ($stmt) {
    echo "<p class='success'>✓ Accounts INSERT statement is valid</p>";
    $stmt->close();
} else {
    echo "<p class='error'>✗ Accounts INSERT statement failed: " . $conn->error . "</p>";
}

// Test membership insert
$testAccountId = 1; // Dummy ID for testing
$stmt = $conn->prepare("INSERT INTO memberships (account_id, member_name, email, phone, points) VALUES (?, ?, ?, ?, 0)");
if ($stmt) {
    echo "<p class='success'>✓ Memberships INSERT statement is valid</p>";
    $stmt->close();
} else {
    echo "<p class='error'>✗ Memberships INSERT statement failed: " . $conn->error . "</p>";
}

// Test username check
$stmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? LIMIT 1");
if ($stmt) {
    echo "<p class='success'>✓ Username check statement is valid</p>";
    $stmt->close();
} else {
    echo "<p class='error'>✗ Username check statement failed: " . $conn->error . "</p>";
}

echo "</div>";

// Show existing accounts
echo "<div class='info'><h3>Step 4: Current Accounts in Database</h3>";
$result = $conn->query("SELECT account_id, username, role, account_status, register_time FROM accounts ORDER BY register_time DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Account ID</th><th>Username</th><th>Role</th><th>Status</th><th>Registered</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['account_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['account_status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['register_time'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No accounts found in database.</p>";
}
echo "</div>";

// Show existing memberships
echo "<div class='info'><h3>Step 5: Current Memberships</h3>";
$result = $conn->query("SELECT m.member_id, m.member_name, m.email, m.phone, m.points, a.username 
                        FROM memberships m 
                        JOIN accounts a ON m.account_id = a.account_id 
                        ORDER BY m.created_at DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Member ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Points</th><th>Username</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['member_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['member_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['points']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No memberships found in database.</p>";
}
echo "</div>";

echo "<div class='info'><h3>Next Steps</h3>";
echo "<p>If all tests passed, try registering a new account through the website.</p>";
echo "<p>If you see errors, check:</p>";
echo "<ul>";
echo "<li>Database 'thewellington1' exists</li>";
echo "<li>All tables are created (run setup_thewellington1.sql)</li>";
echo "<li>Database connection credentials are correct in db_connect.php</li>";
echo "</ul>";
echo "</div>";

$conn->close();
?>

