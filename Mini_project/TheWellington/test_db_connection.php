<?php
/**
 * Database Connection Test Script
 * Tests connection and shows available databases
 */
$servername = "localhost";
$username = "root";
$password = "";

echo "<h2>Database Connection Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #8b6f47; color: white; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background-color: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
</style>";

// Test basic MySQL connection
echo "<div class='info'><h3>Step 1: Testing MySQL Connection</h3>";
try {
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        echo "<p class='error'>Connection failed: " . $conn->connect_error . "</p>";
        die();
    } else {
        echo "<p class='success'>✓ Successfully connected to MySQL server</p>";
    }
    
    // List all databases
    echo "</div><div class='info'><h3>Step 2: Available Databases</h3>";
    $result = $conn->query("SHOW DATABASES");
    
    if ($result && $result->num_rows > 0) {
        echo "<table><tr><th>Database Name</th><th>Action</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $dbName = $row['Database'];
            $isWellington = (stripos($dbName, 'wellington') !== false);
            $highlight = $isWellington ? "style='background-color: #fff3cd;'" : "";
            
            echo "<tr $highlight>";
            echo "<td><strong>" . htmlspecialchars($dbName) . "</strong></td>";
            echo "<td><a href='?test_db=" . urlencode($dbName) . "'>Test Connection</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test specific database if requested
    $testDb = $_GET['test_db'] ?? '';
    if ($testDb) {
        echo "</div><div class='info'><h3>Step 3: Testing Database: " . htmlspecialchars($testDb) . "</h3>";
        
        $testConn = new mysqli($servername, $username, $password, $testDb);
        
        if ($testConn->connect_error) {
            echo "<p class='error'>✗ Failed to connect to database: " . $testConn->connect_error . "</p>";
        } else {
            echo "<p class='success'>✓ Successfully connected to database: " . htmlspecialchars($testDb) . "</p>";
            
            // Show tables
            $tablesResult = $testConn->query("SHOW TABLES");
            if ($tablesResult && $tablesResult->num_rows > 0) {
                echo "<h4>Tables in this database:</h4><ul>";
                while ($table = $tablesResult->fetch_array()) {
                    echo "<li>" . htmlspecialchars($table[0]) . "</li>";
                }
                echo "</ul>";
            }
            
            // Check for Staff table
            $staffCheck = $testConn->query("SHOW TABLES LIKE 'Staff'");
            if ($staffCheck && $staffCheck->num_rows > 0) {
                echo "<h4>Staff Table Contents:</h4>";
                $staffResult = $testConn->query("SELECT s.*, u.username, u.full_name FROM Staff s LEFT JOIN Users u ON s.account_id = u.user_id LIMIT 20");
                if ($staffResult && $staffResult->num_rows > 0) {
                    echo "<table><tr><th>Staff ID</th><th>Staff Name</th><th>Account ID</th><th>Username</th><th>Role</th></tr>";
                    while ($staff = $staffResult->fetch_assoc()) {
                        $isCashier = (strtolower($staff['role']) === 'cashier');
                        $highlight = $isCashier ? "style='background-color: #d4edda;'" : "";
                        echo "<tr $highlight>";
                        echo "<td>" . htmlspecialchars($staff['staff_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($staff['staff_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($staff['account_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($staff['username'] ?? 'N/A') . "</td>";
                        echo "<td><strong>" . htmlspecialchars($staff['role']) . "</strong></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No staff records found.</p>";
                }
            } else {
                echo "<p class='error'>Staff table does not exist in this database.</p>";
            }
            
            $testConn->close();
        }
    }
    
    // Current configuration check
    echo "</div><div class='info'><h3>Step 4: Current Configuration</h3>";
    echo "<p><strong>Configured database name:</strong> thewellingtondb1</p>";
    echo "<p><strong>Server:</strong> $servername</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    
    // Test the configured database
    $configuredDb = "thewellingtondb1";
    $testConn = new mysqli($servername, $username, $password, $configuredDb);
    
    if ($testConn->connect_error) {
        echo "<p class='error'>✗ Configured database '$configuredDb' does not exist or connection failed.</p>";
        echo "<p>Please check if your database name is correct. You mentioned 'thewellingtondt1' - did you mean that?</p>";
    } else {
        echo "<p class='success'>✓ Configured database '$configuredDb' exists and is accessible.</p>";
    }
    $testConn->close();
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
