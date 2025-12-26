<?php
/**
 * Verify Cashier Setup
 * Checks if cashier users are properly configured in the database
 */
session_start();
require 'db_connect.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cashier Setup Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #8b6f47;
            color: white;
        }
        .cashier-row {
            background-color: #d4edda;
        }
        .no-staff-row {
            background-color: #f8d7da;
        }
        .info-box {
            background-color: #e7f3ff;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
        }
        .action-btn {
            background-color: #8b6f47;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 5px;
        }
        .action-btn:hover {
            background-color: #6b5438;
        }
    </style>
</head>
<body>
    <h1>Cashier Setup Verification</h1>
    
    <?php
    // Get all staff users and their Staff table records
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.role as user_role,
            s.staff_id,
            s.role as staff_role
        FROM Users u
        LEFT JOIN Staff s ON u.user_id = s.account_id
        WHERE u.role = 'staff'
        ORDER BY u.user_id
    ");
    
    if (!$stmt) {
        echo "<p class='error'>Database error: " . $conn->error . "</p>";
        exit;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='info-box'>";
    echo "<h3>Database Connection</h3>";
    echo "<p><strong>Database:</strong> thewellingtondb1</p>";
    echo "<p class='success'>✓ Connected successfully</p>";
    echo "</div>";
    
    if ($result->num_rows > 0) {
        echo "<h2>Staff Users</h2>";
        echo "<table>";
        echo "<tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Staff ID</th>
                <th>Staff Role</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>";
        
        $hasCashier = false;
        $noStaffRecord = [];
        
        while ($row = $result->fetch_assoc()) {
            $hasStaffRecord = !empty($row['staff_id']);
            $isCashier = ($hasStaffRecord && strtolower($row['staff_role']) === 'cashier');
            $rowClass = $isCashier ? 'cashier-row' : ($hasStaffRecord ? '' : 'no-staff-row');
            
            if ($isCashier) {
                $hasCashier = true;
            }
            
            if (!$hasStaffRecord) {
                $noStaffRecord[] = $row['user_id'];
            }
            
            echo "<tr class='$rowClass'>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ($row['staff_id'] ? htmlspecialchars($row['staff_id']) : '<span class="error">None</span>') . "</td>";
            echo "<td><strong>" . ($row['staff_role'] ? htmlspecialchars($row['staff_role']) : '<span class="error">No Staff Record</span>') . "</strong></td>";
            
            // Status
            if ($isCashier) {
                echo "<td><span class='success'>✓ Cashier</span></td>";
            } elseif ($hasStaffRecord) {
                echo "<td><span class='warning'>Other Staff Role</span></td>";
            } else {
                echo "<td><span class='error'>✗ No Staff Record</span></td>";
            }
            
            // Actions
            echo "<td>";
            if (!$hasStaffRecord) {
                echo "<a href='?create_staff=" . $row['user_id'] . "&role=cashier' class='action-btn'>Create as Cashier</a>";
            } elseif (!$isCashier) {
                echo "<a href='?update_role=" . $row['user_id'] . "&role=cashier' class='action-btn'>Set as Cashier</a>";
            } else {
                echo "<span class='success'>✓ Configured</span>";
            }
            echo "</td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Summary
        echo "<div class='info-box'>";
        echo "<h3>Summary</h3>";
        if ($hasCashier) {
            echo "<p class='success'>✓ At least one cashier user is configured</p>";
        } else {
            echo "<p class='error'>✗ No cashier users found. Please create or update a staff user to have role 'cashier'</p>";
        }
        
        if (count($noStaffRecord) > 0) {
            echo "<p class='warning'>⚠ " . count($noStaffRecord) . " staff user(s) do not have Staff table records</p>";
        }
        echo "</div>";
        
    } else {
        echo "<p class='warning'>No staff users found in the Users table.</p>";
    }
    
    // Handle actions
    if (isset($_GET['create_staff'])) {
        $userId = intval($_GET['create_staff']);
        $role = $_GET['role'] ?? 'cashier';
        
        $stmt = $conn->prepare("SELECT full_name FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $stmt = $conn->prepare("INSERT INTO Staff (staff_name, account_id, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $user['full_name'], $userId, $role);
            if ($stmt->execute()) {
                echo "<div class='info-box'><p class='success'>✓ Staff record created successfully! <a href='verify_cashier_setup.php'>Refresh page</a></p></div>";
            } else {
                echo "<div class='info-box'><p class='error'>✗ Error creating staff record: " . $stmt->error . "</p></div>";
            }
            $stmt->close();
        }
    }
    
    if (isset($_GET['update_role'])) {
        $userId = intval($_GET['update_role']);
        $role = $_GET['role'] ?? 'cashier';
        
        $stmt = $conn->prepare("UPDATE Staff SET role = ? WHERE account_id = ?");
        $stmt->bind_param("si", $role, $userId);
        if ($stmt->execute()) {
            echo "<div class='info-box'><p class='success'>✓ Staff role updated successfully! <a href='verify_cashier_setup.php'>Refresh page</a></p></div>";
        } else {
            echo "<div class='info-box'><p class='error'>✗ Error updating role: " . $stmt->error . "</p></div>";
        }
        $stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    ?>
    
    <div class="info-box">
        <h3>How to Test Cashier Login</h3>
        <ol>
            <li>Make sure at least one user has role 'cashier' in the Staff table (use actions above)</li>
            <li>Go to <a href="account.php">account.php</a> and login with that user's username and password</li>
            <li>You should be automatically redirected to <strong>cashier.php</strong></li>
            <li>If you're redirected to staff.php instead, check the browser console (F12) for error messages</li>
        </ol>
    </div>
</body>
</html>

