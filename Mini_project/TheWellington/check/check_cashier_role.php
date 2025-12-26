<?php
/**
 * Quick script to check and fix cashier role in Staff table
 * Run this once to verify your cashier user has the correct role
 */
session_start();
require 'db_connect.php';

// Get all staff users
$stmt = $conn->prepare("
    SELECT u.user_id, u.username, u.full_name, s.role as staff_role
    FROM Users u
    LEFT JOIN Staff s ON u.user_id = s.account_id
    WHERE u.role = 'staff'
");
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Staff Users and Their Roles</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>User ID</th><th>Username</th><th>Full Name</th><th>Staff Role</th><th>Action</th></tr>";

while ($row = $result->fetch_assoc()) {
    $hasStaffRecord = !empty($row['staff_role']);
    $role = $row['staff_role'] ?? 'NO STAFF RECORD';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($role) . "</td>";
    
    if (!$hasStaffRecord) {
        echo "<td><a href='?fix=" . $row['user_id'] . "'>Create Staff Record</a></td>";
    } else {
        echo "<td><a href='?set_cashier=" . $row['user_id'] . "'>Set as Cashier</a></td>";
    }
    echo "</tr>";
}
echo "</table>";

// Handle fixing missing staff records
if (isset($_GET['fix'])) {
    $userId = intval($_GET['fix']);
    $stmt = $conn->prepare("SELECT full_name FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Create staff record with default role
        $stmt = $conn->prepare("INSERT INTO Staff (staff_name, account_id, role) VALUES (?, ?, 'cashier')");
        $stmt->bind_param("si", $user['full_name'], $userId);
        $stmt->execute();
        echo "<p style='color:green'>Staff record created for user ID $userId with role 'cashier'. <a href='?'>Refresh</a></p>";
    }
}

// Handle setting role to cashier
if (isset($_GET['set_cashier'])) {
    $userId = intval($_GET['set_cashier']);
    
    // Check if staff record exists
    $stmt = $conn->prepare("SELECT staff_id FROM Staff WHERE account_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE Staff SET role = 'cashier' WHERE account_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo "<p style='color:green'>Staff role updated to 'cashier' for user ID $userId. <a href='?'>Refresh</a></p>";
    } else {
        // Create new record
        $stmt = $conn->prepare("SELECT full_name FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $stmt = $conn->prepare("INSERT INTO Staff (staff_name, account_id, role) VALUES (?, ?, 'cashier')");
            $stmt->bind_param("si", $user['full_name'], $userId);
            $stmt->execute();
            echo "<p style='color:green'>Staff record created with role 'cashier' for user ID $userId. <a href='?'>Refresh</a></p>";
        }
    }
}

$stmt->close();
$conn->close();
?>

