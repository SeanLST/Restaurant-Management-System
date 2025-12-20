<?php
/**
 * Migration Script: Add all existing customer users to membership table
 * Run this once to backfill existing customers into the membership table
 * 
 * Usage: Open this file in your browser or run via command line: php migrate_customers_to_membership.php
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellingtondb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Migrating Customer Users to Membership Table</h2>";
    echo "<pre>";
    
    // Get all customer users who don't have a membership record yet
    $sql = "
        SELECT u.user_id, u.full_name, u.phone, u.email
        FROM users u
        WHERE u.role = 'customer'
        AND u.user_id NOT IN (
            SELECT COALESCE(user_id, 0) FROM membership WHERE user_id IS NOT NULL
        )
        AND u.phone IS NOT NULL 
        AND u.phone != ''
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Error fetching customers: " . $conn->error);
    }
    
    $migrated = 0;
    $skipped = 0;
    $errors = [];
    
    if ($result->num_rows > 0) {
        echo "Found " . $result->num_rows . " customer(s) to migrate.\n\n";
        
        while ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
            $memberName = $row['full_name'];
            $phone = trim($row['phone']);
            
            // Skip if phone is empty
            if (empty($phone)) {
                echo "Skipping user ID {$userId} ({$memberName}): No phone number\n";
                $skipped++;
                continue;
            }
            
            // Check if phone number already exists in membership (unique constraint)
            $checkPhone = $conn->prepare("SELECT member_id FROM membership WHERE phone_number = ? LIMIT 1");
            $checkPhone->bind_param("s", $phone);
            $checkPhone->execute();
            $phoneResult = $checkPhone->get_result();
            
            if ($phoneResult->num_rows > 0) {
                echo "Skipping user ID {$userId} ({$memberName}): Phone number {$phone} already exists in membership\n";
                $skipped++;
                $checkPhone->close();
                continue;
            }
            $checkPhone->close();
            
            // Insert into membership table
            $insertSql = "INSERT INTO membership (user_id, member_name, phone_number, point) VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($insertSql);
            
            if ($stmt) {
                $stmt->bind_param("iss", $userId, $memberName, $phone);
                
                if ($stmt->execute()) {
                    echo "✓ Migrated user ID {$userId} ({$memberName}) - Phone: {$phone}\n";
                    $migrated++;
                } else {
                    echo "✗ Error migrating user ID {$userId}: " . $stmt->error . "\n";
                    $errors[] = "User ID {$userId}: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "✗ Error preparing statement for user ID {$userId}: " . $conn->error . "\n";
                $errors[] = "User ID {$userId}: " . $conn->error;
            }
        }
    } else {
        echo "No customers found to migrate.\n";
        echo "All customers may already be in the membership table, or they don't have phone numbers.\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Migration Summary:\n";
    echo "========================================\n";
    echo "Successfully migrated: {$migrated}\n";
    echo "Skipped: {$skipped}\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nError Details:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }
    
    echo "</pre>";
    
    // Also handle customers without phone numbers (create membership with placeholder phone)
    echo "<h3>Customers Without Phone Numbers</h3>";
    echo "<pre>";
    
    $sqlNoPhone = "
        SELECT u.user_id, u.full_name, u.email
        FROM users u
        WHERE u.role = 'customer'
        AND (u.phone IS NULL OR u.phone = '')
        AND u.user_id NOT IN (
            SELECT COALESCE(user_id, 0) FROM membership WHERE user_id IS NOT NULL
        )
    ";
    
    $resultNoPhone = $conn->query($sqlNoPhone);
    
    if ($resultNoPhone && $resultNoPhone->num_rows > 0) {
        echo "Found " . $resultNoPhone->num_rows . " customer(s) without phone numbers.\n";
        echo "Note: These cannot be added to membership table as phone_number is required.\n";
        echo "They will be automatically added when they provide a phone number during registration or reservation.\n\n";
        
        while ($row = $resultNoPhone->fetch_assoc()) {
            echo "  - User ID {$row['user_id']}: {$row['full_name']} ({$row['email']})\n";
        }
    } else {
        echo "All customers have phone numbers or are already in membership table.\n";
    }
    
    echo "</pre>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

