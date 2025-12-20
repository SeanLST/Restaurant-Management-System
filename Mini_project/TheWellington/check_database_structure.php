<?php
/**
 * Check Database Structure
 * Verifies that all required tables and columns exist
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellingtondb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Database Structure Check</h2>";
    echo "<pre>";
    
    // Check if reservations table exists
    $result = $conn->query("SHOW TABLES LIKE 'reservations'");
    if ($result->num_rows == 0) {
        echo "❌ ERROR: 'reservations' table does not exist!\n";
        echo "   Please run setup_database.sql to create the table.\n\n";
    } else {
        echo "✓ 'reservations' table exists\n";
        
        // Check columns in reservations table
        $result = $conn->query("DESCRIBE reservations");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = ['reservation_id', 'user_id', 'table_id', 'reservation_date', 'reservation_time', 'guests', 'status'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $missingColumns[] = $col;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "❌ ERROR: Missing columns in 'reservations' table:\n";
            foreach ($missingColumns as $col) {
                echo "   - {$col}\n";
            }
            echo "\n";
            echo "To fix this, run the following SQL:\n";
            echo "ALTER TABLE reservations ";
            if (in_array('table_id', $missingColumns)) {
                echo "ADD COLUMN table_id INT AFTER user_id, ";
            }
            if (in_array('reservation_date', $missingColumns)) {
                echo "ADD COLUMN reservation_date DATE NOT NULL AFTER table_id, ";
            }
            if (in_array('reservation_time', $missingColumns)) {
                echo "ADD COLUMN reservation_time TIME NOT NULL AFTER reservation_date, ";
            }
            if (in_array('guests', $missingColumns)) {
                echo "ADD COLUMN guests INT NOT NULL AFTER reservation_time, ";
            }
            if (in_array('status', $missingColumns)) {
                echo "ADD COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending' AFTER guests";
            }
            echo ";\n\n";
        } else {
            echo "✓ All required columns exist in 'reservations' table\n";
            echo "\nColumns found:\n";
            foreach ($columns as $col) {
                echo "  - {$col}\n";
            }
        }
    }
    
    // Check restaurant_tables table
    $result = $conn->query("SHOW TABLES LIKE 'restaurant_tables'");
    if ($result->num_rows == 0) {
        echo "\n❌ ERROR: 'restaurant_tables' table does not exist!\n";
        echo "   Please run setup_database.sql to create the table.\n";
    } else {
        echo "\n✓ 'restaurant_tables' table exists\n";
    }
    
    // Check users table
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        echo "\n❌ ERROR: 'users' table does not exist!\n";
        echo "   Please run setup_database.sql to create the table.\n";
    } else {
        echo "\n✓ 'users' table exists\n";
    }
    
    echo "\n========================================\n";
    echo "Summary:\n";
    echo "If you see any errors above, please:\n";
    echo "1. Run setup_database.sql in phpMyAdmin\n";
    echo "2. Or use the ALTER TABLE statements shown above\n";
    echo "========================================\n";
    
    echo "</pre>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

