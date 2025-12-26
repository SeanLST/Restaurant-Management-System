<?php
/**
 * SIMPLE DATABASE DIAGNOSTIC
 * Run this file in your browser to check what's wrong
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #8b6f47;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🔧 Wellington Database Diagnostic</h1>";

// TEST 1: PHP Configuration
echo "<div class='box'>
    <h2>Test 1: PHP Configuration</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>MySQLi Extension:</strong> ";
if (extension_loaded('mysqli')) {
    echo "<span class='success'>✓ Loaded</span>";
} else {
    echo "<span class='error'>✗ NOT Loaded</span>";
    echo "<p class='error'><strong>CRITICAL:</strong> MySQLi extension is not loaded. Please enable it in php.ini</p>";
}
echo "</p>";
echo "</div>";

// TEST 2: MySQL Connection (without selecting database)
echo "<div class='box'>
    <h2>Test 2: MySQL Server Connection</h2>";

$servername = "localhost";
$username = "root";
$password = "";

$testConn = new mysqli($servername, $username, $password);

if ($testConn->connect_error) {
    echo "<p class='error'>✗ FAILED: " . $testConn->connect_error . "</p>";
    echo "<p><strong>Possible solutions:</strong></p>
          <ul>
              <li>Make sure XAMPP MySQL/MariaDB is running</li>
              <li>Check if the username is 'root'</li>
              <li>Check if the password is empty (default XAMPP)</li>
          </ul>";
} else {
    echo "<p class='success'>✓ Connected to MySQL Server</p>";
    
    // TEST 3: List all databases
    echo "</div><div class='box'>
        <h2>Test 3: Available Databases</h2>";
    
    $result = $testConn->query("SHOW DATABASES");
    if ($result) {
        echo "<table>
                <tr><th>#</th><th>Database Name</th><th>Match</th></tr>";
        $count = 0;
        $foundWellington1 = false;
        while ($row = $result->fetch_assoc()) {
            $count++;
            $dbName = $row['Database'];
            $isTarget = ($dbName === 'thewellington1');
            if ($isTarget) $foundWellington1 = true;
            
            $highlight = $isTarget ? " style='background-color: #d4edda;'" : "";
            echo "<tr$highlight>
                    <td>$count</td>
                    <td><strong>$dbName</strong></td>
                    <td>" . ($isTarget ? "<span class='success'>✓ TARGET</span>" : "") . "</td>
                  </tr>";
        }
        echo "</table>";
        
        if ($foundWellington1) {
            echo "<p class='success'>✓ Database 'thewellington1' EXISTS</p>";
        } else {
            echo "<p class='error'>✗ Database 'thewellington1' NOT FOUND</p>";
            echo "<p class='warning'><strong>Action Required:</strong> Create database 'thewellington1' or run setup_thewellington1.sql</p>";
        }
    }
    
    // TEST 4: Try connecting to thewellington1
    echo "</div><div class='box'>
        <h2>Test 4: Connect to 'thewellington1'</h2>";
    
    $dbConn = new mysqli($servername, $username, $password, "thewellington1");
    
    if ($dbConn->connect_error) {
        echo "<p class='error'>✗ FAILED: " . $dbConn->connect_error . "</p>";
        
        if (strpos($dbConn->connect_error, 'Unknown database') !== false) {
            echo "<p class='error'><strong>Problem:</strong> Database 'thewellington1' does not exist!</p>";
            echo "<p><strong>Solution:</strong></p>
                  <ol>
                      <li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>
                      <li>Click 'SQL' tab</li>
                      <li>Run: <code>CREATE DATABASE thewellington1;</code></li>
                      <li>Or upload and run the setup_thewellington1.sql file</li>
                  </ol>";
        }
    } else {
        echo "<p class='success'>✓ Connected to 'thewellington1'</p>";
        
        // TEST 5: Check tables
        echo "</div><div class='box'>
            <h2>Test 5: Database Tables</h2>";
        
        $result = $dbConn->query("SHOW TABLES");
        if ($result && $result->num_rows > 0) {
            echo "<p class='success'>✓ Found " . $result->num_rows . " table(s)</p>";
            echo "<table>
                    <tr><th>#</th><th>Table Name</th><th>Status</th></tr>";
            
            $requiredTables = ['accounts', 'memberships', 'staffs', 'restaurant_tables', 'menu', 'table_availability', 'reservations', 'payment_transactions', 'bills', 'bill_items', 'kitchen'];
            $foundTables = [];
            $count = 0;
            
            while ($row = $result->fetch_array()) {
                $count++;
                $tableName = $row[0];
                $foundTables[] = $tableName;
                $isRequired = in_array($tableName, $requiredTables);
                $highlight = $isRequired ? " style='background-color: #d4edda;'" : "";
                
                echo "<tr$highlight>
                        <td>$count</td>
                        <td><strong>$tableName</strong></td>
                        <td>" . ($isRequired ? "<span class='success'>✓ Required</span>" : "<span class='info'>Optional</span>") . "</td>
                      </tr>";
            }
            echo "</table>";
            
            // Check if all required tables exist
            $missingTables = array_diff($requiredTables, $foundTables);
            if (empty($missingTables)) {
                echo "<p class='success'>✓ All required tables exist!</p>";
                
                // TEST 6: Check for sample data
                echo "</div><div class='box'>
                    <h2>Test 6: Sample Data</h2>";
                
                // Check accounts
                $result = $dbConn->query("SELECT COUNT(*) as count FROM accounts");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $accountCount = $row['count'];
                    echo "<p><strong>Accounts:</strong> ";
                    if ($accountCount > 0) {
                        echo "<span class='success'>$accountCount account(s)</span></p>";
                        
                        // Show sample accounts
                        $result = $dbConn->query("SELECT account_id, username, role, account_status FROM accounts LIMIT 5");
                        echo "<table>
                                <tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($row['account_id']) . "</td>
                                    <td><code>" . htmlspecialchars($row['username']) . "</code></td>
                                    <td>" . htmlspecialchars($row['role']) . "</td>
                                    <td>" . htmlspecialchars($row['account_status']) . "</td>
                                  </tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<span class='warning'>0 accounts - You need to register first</span></p>";
                    }
                }
                
            } else {
                echo "<p class='error'>✗ Missing tables: " . implode(', ', $missingTables) . "</p>";
                echo "<p class='warning'><strong>Action Required:</strong> Run setup_thewellington1.sql in phpMyAdmin</p>";
            }
            
        } else {
            echo "<p class='error'>✗ No tables found in database</p>";
            echo "<p class='warning'><strong>Action Required:</strong> Run setup_thewellington1.sql to create tables</p>";
        }
        
        $dbConn->close();
    }
    
    $testConn->close();
}

// SUMMARY
echo "</div><div class='box'>
    <h2>Summary & Next Steps</h2>";

if (isset($foundWellington1) && $foundWellington1 && isset($foundTables) && in_array('accounts', $foundTables)) {
    echo "<p class='success'><strong>✓ DATABASE IS SET UP CORRECTLY!</strong></p>";
    echo "<p>You should now be able to:</p>
          <ol>
              <li>Go to <a href='account.php'>Login Page</a> and register a new account</li>
              <li>Or try logging in if you already have an account</li>
              <li>If login still fails, check the browser console (F12) for errors</li>
          </ol>";
} else {
    echo "<p class='error'><strong>✗ DATABASE SETUP INCOMPLETE</strong></p>";
    echo "<p><strong>Required Actions:</strong></p>
          <ol>
              <li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>
              <li>Click 'SQL' tab at the top</li>
              <li>Copy the entire contents of <code>databased/setup_thewellington1.sql</code></li>
              <li>Paste and click 'Go'</li>
              <li>Or use the Import tab to upload the SQL file</li>
              <li>Refresh this page to verify</li>
          </ol>";
}

echo "</div>";

echo "</body>
</html>";
?>

