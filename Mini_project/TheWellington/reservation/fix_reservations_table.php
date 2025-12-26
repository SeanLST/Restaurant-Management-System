<?php
/**
 * Fix Reservations Table Structure
 * This script checks and fixes the reservations table structure
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellingtondb1";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Checking Reservations Table Structure</h2>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reservations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ Reservations table does NOT exist. Creating it now...</p>";
        
        // Create the table
        $pdo->exec("
            CREATE TABLE reservations (
                reservation_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                table_id INT,
                reservation_date DATE NOT NULL,
                reservation_time TIME NOT NULL,
                guests INT NOT NULL,
                special_requests TEXT,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Reservations table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✅ Reservations table exists.</p>";
        
        // Check columns
        $stmt = $pdo->query("DESCRIBE reservations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        echo "<h3>Current Columns:</h3><ul>";
        foreach ($columnNames as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
        
        // Check for required columns
        $requiredColumns = ['reservation_id', 'user_id', 'table_id', 'reservation_date', 'reservation_time', 'guests', 'status'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $reqCol) {
            if (!in_array($reqCol, $columnNames)) {
                $missingColumns[] = $reqCol;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
            
            // Add missing columns
            foreach ($missingColumns as $col) {
                try {
                    switch ($col) {
                        case 'table_id':
                            // Check if there's existing data
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
                            $result = $stmt->fetch();
                            $hasData = $result['count'] > 0;
                            
                            if ($hasData) {
                                echo "<p style='color: orange;'>⚠️ Table has existing data. Adding table_id as nullable...</p>";
                            }
                            
                            $pdo->exec("ALTER TABLE reservations ADD COLUMN table_id INT NULL AFTER user_id");
                            
                            // Try to add foreign key, but don't fail if it already exists
                            try {
                                $pdo->exec("ALTER TABLE reservations ADD FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL");
                            } catch (PDOException $fkError) {
                                if (strpos($fkError->getMessage(), 'Duplicate foreign key') === false) {
                                    echo "<p style='color: orange;'>⚠️ Could not add foreign key (may already exist): " . $fkError->getMessage() . "</p>";
                                }
                            }
                            echo "<p style='color: green;'>✅ Added column: $col</p>";
                            break;
                        case 'reservation_date':
                            $pdo->exec("ALTER TABLE reservations ADD COLUMN reservation_date DATE NOT NULL AFTER table_id");
                            echo "<p style='color: green;'>✅ Added column: $col</p>";
                            break;
                        case 'reservation_time':
                            $pdo->exec("ALTER TABLE reservations ADD COLUMN reservation_time TIME NOT NULL AFTER reservation_date");
                            echo "<p style='color: green;'>✅ Added column: $col</p>";
                            break;
                        case 'guests':
                            $pdo->exec("ALTER TABLE reservations ADD COLUMN guests INT NOT NULL AFTER reservation_time");
                            echo "<p style='color: green;'>✅ Added column: $col</p>";
                            break;
                        case 'status':
                            $pdo->exec("ALTER TABLE reservations ADD COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending' AFTER guests");
                            echo "<p style='color: green;'>✅ Added column: $col</p>";
                            break;
                    }
                } catch (PDOException $e) {
                    echo "<p style='color: orange;'>⚠️ Could not add column $col: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>✅ All required columns exist!</p>";
        }
        
        // Check for special_requests column (optional but nice to have)
        if (!in_array('special_requests', $columnNames)) {
            try {
                $pdo->exec("ALTER TABLE reservations ADD COLUMN special_requests TEXT AFTER guests");
                echo "<p style='color: green;'>✅ Added optional column: special_requests</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Could not add special_requests: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Final verification
    echo "<h3>Final Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE reservations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check reservation_orders table
    echo "<h2>Checking Reservation Orders Table Structure</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'reservation_orders'");
    $ordersTableExists = $stmt->rowCount() > 0;
    
    if (!$ordersTableExists) {
        echo "<p style='color: red;'>❌ Reservation_orders table does NOT exist. Creating it now...</p>";
        $pdo->exec("
            CREATE TABLE reservation_orders (
                order_id INT AUTO_INCREMENT PRIMARY KEY,
                reservation_id INT,
                item_id INT,
                quantity INT DEFAULT 1,
                FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES menu(item_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ Reservation_orders table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✅ Reservation_orders table exists.</p>";
        $stmt = $pdo->query("DESCRIBE reservation_orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        echo "<h3>Reservation Orders Columns:</h3><ul>";
        foreach ($columnNames as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    }
    
    echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ Database structure check complete!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure:</p>";
    echo "<ul>";
    echo "<li>The database 'thewellingtondb1' exists</li>";
    echo "<li>MySQL/XAMPP is running</li>";
    echo "<li>You have proper database credentials</li>";
    echo "</ul>";
}
?>

