<?php
session_start();
header('Content-Type: application/json'); // Return JSON to JavaScript

require 'db_connect.php'; // Include the connection

// Helper: safe JSON error response
function json_error($message) {
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

// Check if DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    json_error('Database connection failed.');
}

// Check if data is received via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get data from the JavaScript request
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = trim($_POST['role'] ?? 'customer');

    // 2. Validation
    if (empty($username) || empty($password)) {
        json_error("Username and password are required.");
    }

    if (strlen($password) < 8) {
        json_error("Password must be at least 8 characters.");
    }

    // Combine first_name and last_name into full_name (to match database schema)
    $full_name = trim($first_name . ' ' . $last_name);
    if (empty($full_name)) {
        json_error("First name and last name are required.");
    }

    // 3. Check if Username or Email already exists
    $checkQuery = "SELECT user_id FROM Users WHERE username = ?";
    if (!empty($email)) {
        $checkQuery .= " OR email = ?";
    }
    
    $stmt = $conn->prepare($checkQuery);
    if (!$stmt) {
        json_error('Database error preparing statement.');
    }
    
    if (!empty($email)) {
        $stmt->bind_param("ss", $username, $email);
    } else {
        $stmt->bind_param("s", $username);
    }
    
    if (!$stmt->execute()) {
        $stmt->close();
        json_error('Database error checking existing users.');
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        json_error("Username or Email already exists.");
    }
    $stmt->close();

    // 4. Save password as plain text (no hashing)
    // Password will be saved exactly as the user typed it

    // 5. Insert into Database
    // Use Users table with full_name (matching new schema)
    $sql = "INSERT INTO Users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        json_error('Database error preparing insert statement.');
    }
    
    $stmt->bind_param("ssssss", $username, $password, $full_name, $email, $phone, $role);
    
    if ($stmt->execute()) {
        $newUserId = $conn->insert_id;
        
        // 6. Create membership record for customer users (all customers automatically become members)
        if ($role === 'customer') {
            // Always create membership for new customers
            // Check if membership already exists (shouldn't, but safety check)
            $checkMembership = $conn->prepare("SELECT membership_id FROM Memberships WHERE user_id = ? LIMIT 1");
            $checkMembership->bind_param("i", $newUserId);
            $checkMembership->execute();
            $existingMembership = $checkMembership->get_result();
            
            if ($existingMembership->num_rows == 0) {
                // Create new membership record with 0 initial points
                // Points will be awarded when they make reservations (10 points per reservation)
                $membershipSql = "INSERT INTO Memberships (user_id, member_name, points) VALUES (?, ?, 0)";
                $membershipStmt = $conn->prepare($membershipSql);
                if ($membershipStmt) {
                    $membershipStmt->bind_param("is", $newUserId, $full_name);
                    if ($membershipStmt->execute()) {
                        // Membership created successfully
                        $membershipStmt->close();
                    } else {
                        // Log error - membership creation failed
                        error_log("Failed to create membership for user_id: " . $newUserId . " - " . $membershipStmt->error);
                        $membershipStmt->close();
                        // Continue with registration even if membership creation fails
                    }
                } else {
                    error_log("Failed to prepare membership statement for user_id: " . $newUserId . " - " . $conn->error);
                }
            } else {
                // Membership already exists (shouldn't happen for new registrations)
                error_log("Membership already exists for new user_id: " . $newUserId);
            }
            $checkMembership->close();
        }
        
        // 7. Create staff record for staff users
        if ($role === 'staff') {
            // Check if staff record already exists for this user
            $checkStaff = $conn->prepare("SELECT staff_id FROM Staff WHERE account_id = ? LIMIT 1");
            $checkStaff->bind_param("i", $newUserId);
            $checkStaff->execute();
            $existingStaff = $checkStaff->get_result();
            
            if ($existingStaff->num_rows == 0) {
                // Create new staff record
                // Using 'staff' as the default role in Staff table, can be customized later
                $staffSql = "INSERT INTO Staff (staff_name, account_id, role) VALUES (?, ?, ?)";
                $staffStmt = $conn->prepare($staffSql);
                if ($staffStmt) {
                    $staffStmt->bind_param("sis", $full_name, $newUserId, $role);
                    $staffStmt->execute();
                    $staffStmt->close();
                }
            }
            $checkStaff->close();
        }
        
        echo json_encode(["status" => "success", "message" => "Registration successful! Your account has been created. You can now login."]);
    } else {
        json_error("Database error: " . $conn->error);
    }
    $stmt->close();
} else {
    json_error("Invalid request method.");
}
?>

