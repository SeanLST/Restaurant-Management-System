<?php
/**
 * Test Login Script
 * This will help identify login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Debug Test</h2>";
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
    try {
        require 'db_connect.php';
        echo "<p class='success'>✓ db_connect.php loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error loading db_connect.php: " . $e->getMessage() . "</p>";
        exit;
    }
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
        echo "<p class='info'>Check if:</p>";
        echo "<ul>";
        echo "<li>MySQL/XAMPP is running</li>";
        echo "<li>Database 'thewellington1' exists</li>";
        echo "<li>Username and password in db_connect.php are correct</li>";
        echo "</ul>";
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
    $currentDb = $row['db'];
    if ($currentDb === 'thewellington1') {
        echo "<p class='success'>✓ Current database: " . $currentDb . "</p>";
    } else {
        echo "<p class='error'>✗ Wrong database selected. Current: " . ($currentDb ?: 'none') . ", Expected: thewellington1</p>";
    }
} else {
    echo "<p class='error'>✗ Could not check database</p>";
}
echo "</div>";

// Test 4: Check if accounts table exists
echo "<div class='test-section'><h3>Test 4: Accounts Table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'accounts'");
if ($result && $result->num_rows > 0) {
    echo "<p class='success'>✓ Table 'accounts' exists</p>";
    
    // Check table structure
    $result = $conn->query("DESCRIBE accounts");
    if ($result) {
        echo "<p class='info'>Table structure:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>✗ Table 'accounts' does NOT exist</p>";
    echo "<p class='error'><strong>Action required:</strong> Please run setup_thewellington1.sql to create the tables.</p>";
}
echo "</div>";

// Test 5: Check if there are any accounts
echo "<div class='test-section'><h3>Test 5: Existing Accounts</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM accounts");
if ($result) {
    $row = $result->fetch_assoc();
    $count = $row['count'];
    if ($count > 0) {
        echo "<p class='success'>✓ Found $count account(s) in database</p>";
        
        // Show sample accounts (without passwords)
        $result = $conn->query("SELECT account_id, username, role, account_status FROM accounts LIMIT 5");
        if ($result && $result->num_rows > 0) {
            echo "<p class='info'>Sample accounts:</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['account_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                echo "<td>" . htmlspecialchars($row['account_status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='info'>ℹ No accounts found. You need to register first.</p>";
    }
} else {
    echo "<p class='error'>✗ Could not query accounts table</p>";
}
echo "</div>";

// Test 6: Test SQL prepared statement
echo "<div class='test-section'><h3>Test 6: SQL Statement Preparation</h3>";
$testUsername = 'test_user_' . time();
$stmt = $conn->prepare("SELECT account_id, username, password, role, account_status FROM accounts WHERE username = ? LIMIT 1");
if ($stmt) {
    echo "<p class='success'>✓ Login SELECT statement can be prepared</p>";
    $stmt->close();
} else {
    echo "<p class='error'>✗ Login SELECT failed: " . $conn->error . "</p>";
}
echo "</div>";

// Test 7: Check login_process.php file
echo "<div class='test-section'><h3>Test 7: Login PHP File</h3>";
if (file_exists('login/login_process.php')) {
    echo "<p class='success'>✓ login/login_process.php exists</p>";
    
    // Check for syntax errors
    $output = [];
    $return_var = 0;
    exec('php -l login/login_process.php 2>&1', $output, $return_var);
    if ($return_var === 0) {
        echo "<p class='success'>✓ No syntax errors in login_process.php</p>";
    } else {
        echo "<p class='error'>✗ Syntax errors found:</p>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ login/login_process.php NOT FOUND</p>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>Next Steps</h3>";
echo "<p>If all tests passed, try:</p>";
echo "<ol>";
echo "<li>Open browser console (F12) when logging in</li>";
echo "<li>Check Network tab to see the actual response from login_process.php</li>";
echo "<li>Check PHP error logs (usually in XAMPP/logs/php_error_log)</li>";
echo "<li>Make sure you have at least one account registered</li>";
echo "</ol>";
echo "</div>";

if (isset($conn)) {
    $conn->close();
}
?>

