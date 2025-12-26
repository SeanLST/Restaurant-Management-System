<?php
/**
 * Debug Registration Test Script
 * This will help identify registration issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Registration Debug Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: #666; }
    pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Test 1: Check db_connect.php
echo "<div class='test-section'><h3>Test 1: Database Connection File</h3>";
if (file_exists('db_connect.php')) {
    echo "<p class='success'>✓ db_connect.php exists</p>";
    require 'db_connect.php';
} else {
    echo "<p class='error'>✗ db_connect.php NOT FOUND</p>";
    exit;
}
echo "</div>";

// Test 2: Check database connection
echo "<div class='test-section'><h3>Test 2: Database Connection</h3>";
if (isset($conn)) {
    if ($conn->connect_error) {
        echo "<p class='error'>✗ Connection failed: " . $conn->connect_error . "</p>";
        exit;
    } else {
        echo "<p class='success'>✓ Connected to database</p>";
    }
} else {
    echo "<p class='error'>✗ Connection variable not set</p>";
    exit;
}
echo "</div>";

// Test 3: Check if database exists
echo "<div class='test-section'><h3>Test 3: Database Exists</h3>";
$result = $conn->query("SELECT DATABASE() as db");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p class='success'>✓ Current database: " . $row['db'] . "</p>";
} else {
    echo "<p class='error'>✗ Could not check database</p>";
}
echo "</div>";

// Test 4: Check if tables exist
echo "<div class='test-section'><h3>Test 4: Required Tables</h3>";
$requiredTables = ['accounts', 'memberships', 'staffs'];
$allExist = true;
foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' does NOT exist</p>";
        $allExist = false;
    }
}

if (!$allExist) {
    echo "<p class='error'><strong>Action required:</strong> Please run setup_thewellington1.sql to create the tables.</p>";
}
echo "</div>";

// Test 5: Check register_process.php path
echo "<div class='test-section'><h3>Test 5: Registration PHP File</h3>";
if (file_exists('login/register_process.php')) {
    echo "<p class='success'>✓ login/register_process.php exists</p>";
    
    // Check if it can be accessed
    $content = file_get_contents('login/register_process.php');
    if (strpos($content, 'register_process.php') !== false) {
        echo "<p class='success'>✓ File is readable</p>";
    }
} else {
    echo "<p class='error'>✗ login/register_process.php NOT FOUND</p>";
    echo "<p class='info'>Current directory: " . __DIR__ . "</p>";
}
echo "</div>";

// Test 6: Test SQL Prepared Statements
if ($allExist) {
    echo "<div class='test-section'><h3>Test 6: SQL Statement Preparation</h3>";
    
    // Test accounts insert
    $testUsername = 'test_' . time();
    $stmt = $conn->prepare("INSERT INTO accounts (username, password, role, account_status) VALUES (?, ?, ?, 'active')");
    if ($stmt) {
        echo "<p class='success'>✓ Accounts INSERT statement can be prepared</p>";
        $stmt->close();
    } else {
        echo "<p class='error'>✗ Accounts INSERT failed: " . $conn->error . "</p>";
    }
    
    // Test memberships insert
    $testAccountId = 99999; // Dummy ID
    $stmt = $conn->prepare("INSERT INTO memberships (account_id, member_name, email, phone, points) VALUES (?, ?, ?, ?, 0)");
    if ($stmt) {
        echo "<p class='success'>✓ Memberships INSERT statement can be prepared</p>";
        $stmt->close();
    } else {
        echo "<p class='error'>✗ Memberships INSERT failed: " . $conn->error . "</p>";
    }
    echo "</div>";
    
    // Test 7: Simulate registration (without actually inserting)
    echo "<div class='test-section'><h3>Test 7: Registration Process Simulation</h3>";
    echo "<p class='info'>Testing registration logic (dry run, no data saved):</p>";
    
    $testData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'username' => 'testuser_' . time(),
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'testpass123',
        'role' => 'customer'
    ];
    
    $full_name = trim($testData['first_name'] . ' ' . $testData['last_name']);
    
    // Check username
    $checkStmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $testData['username']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows == 0) {
            echo "<p class='success'>✓ Username check works (username available)</p>";
        } else {
            echo "<p class='info'>ℹ Username already exists (this is OK for testing)</p>";
        }
        $checkStmt->close();
    } else {
        echo "<p class='error'>✗ Username check failed: " . $conn->error . "</p>";
    }
    
    // Check email
    $emailCheckStmt = $conn->prepare("SELECT member_id FROM memberships WHERE email = ? LIMIT 1");
    if ($emailCheckStmt) {
        $emailCheckStmt->bind_param("s", $testData['email']);
        $emailCheckStmt->execute();
        $emailResult = $emailCheckStmt->get_result();
        if ($emailResult->num_rows == 0) {
            echo "<p class='success'>✓ Email check works (email available)</p>";
        } else {
            echo "<p class='info'>ℹ Email already exists (this is OK for testing)</p>";
        }
        $emailCheckStmt->close();
    } else {
        echo "<p class='error'>✗ Email check failed: " . $conn->error . "</p>";
    }
    echo "</div>";
}

// Test 8: Check PHP version and extensions
echo "<div class='test-section'><h3>Test 8: PHP Configuration</h3>";
echo "<p class='info'>PHP Version: " . PHP_VERSION . "</p>";
echo "<p class='info'>MySQLi extension: " . (extension_loaded('mysqli') ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Not loaded</span>') . "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>Next Steps</h3>";
echo "<p>If all tests passed, try:</p>";
echo "<ol>";
echo "<li>Open browser console (F12) when registering</li>";
echo "<li>Check for JavaScript errors</li>";
echo "<li>Check Network tab to see the actual response from register_process.php</li>";
echo "<li>Verify the database has been set up correctly</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>

