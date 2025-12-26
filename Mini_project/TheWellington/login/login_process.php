<?php
/**
 * Login Process - UPDATED FOR NEW SCHEMA (thewellington1)
 * Database: thewellington1
 * Tables: accounts, memberships, staffs
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any errors
ob_start();

session_start();
header('Content-Type: application/json');

// Helper: safe JSON error response
function json_error($message) {
    ob_clean(); // Clear any output before sending JSON
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

// Check if db_connect.php exists
if (!file_exists('../db_connect.php')) {
    json_error('Database connection file not found.');
}

// Try to include db_connect.php
try {
    require '../db_connect.php';
} catch (Exception $e) {
    error_log("Login Error: Failed to include db_connect.php - " . $e->getMessage());
    json_error('Database configuration error.');
}

// Check if DB connection is valid
if (!isset($conn) || $conn === null) {
    error_log("Login Error: Connection variable not set");
    json_error('Database connection variable not set. Please check database configuration.');
}

if ($conn->connect_error) {
    error_log("Login Error: Database connection failed - " . $conn->connect_error);
    json_error('Database connection failed: ' . $conn->connect_error);
}

// Check if accounts table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'accounts'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    error_log("Login Error: Accounts table does not exist");
    json_error('Database tables not set up. Please run the database setup script.');
}

// Check if data is received via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get credentials
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password)) {
        json_error('Please enter both username and password.');
    }
    
    // Query account from database (NEW SCHEMA: accounts table)
    $stmt = $conn->prepare("SELECT account_id, username, password, role, account_status FROM accounts WHERE username = ? LIMIT 1");
    if (!$stmt) {
        error_log("Login Error: Failed to prepare statement - " . $conn->error);
        json_error('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("Login Error: Failed to execute statement - " . $stmt->error);
        $stmt->close();
        json_error('Database query error.');
    }
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        json_error('Invalid username or password.');
    }
    
    $account = $result->fetch_assoc();
    $stmt->close();
    
    // Check if account is active
    if ($account['account_status'] !== 'active') {
        json_error('Account is suspended or deleted. Please contact administrator.');
    }
    
    // Verify password (plain text comparison for now - you can add password_hash later)
    if ($password !== $account['password']) {
        json_error('Invalid username or password.');
    }
    
    // Update last_login timestamp
    $updateStmt = $conn->prepare("UPDATE accounts SET last_login = CURRENT_TIMESTAMP WHERE account_id = ?");
    if ($updateStmt) {
        $updateStmt->bind_param("i", $account['account_id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Get profile information based on role
    $fullName = '';
    $email = '';
    $phone = '';
    $staffRole = null; // Will be set if staff user
    $memberId = null;
    $staffId = null;
    
    if ($account['role'] === 'customer') {
        // Get customer profile from memberships table
        $stmt = $conn->prepare("SELECT member_id, member_name, email, phone FROM memberships WHERE account_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $account['account_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $profile = $result->fetch_assoc();
                $fullName = $profile['member_name'];
                $email = $profile['email'] ?? '';
                $phone = $profile['phone'] ?? '';
                $memberId = $profile['member_id'];
            }
            $stmt->close();
        }
    } else if ($account['role'] === 'staff') {
        // Get staff profile from staffs table
        $stmt = $conn->prepare("SELECT staff_id, staff_name, email, phone, role FROM staffs WHERE account_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $account['account_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $profile = $result->fetch_assoc();
                $fullName = $profile['staff_name'];
                $email = $profile['email'] ?? '';
                $phone = $profile['phone'] ?? '';
                $staffRole = $profile['role'] ?? 'staff'; // cashier, waiter, chef, etc. - default to 'staff' if not set
                $staffId = $profile['staff_id'];
            } else {
                // Staff profile not found - log error but continue
                error_log("Login Warning: Staff profile not found for account_id: " . $account['account_id']);
                $staffRole = 'staff'; // Default role
            }
            $stmt->close();
        } else {
            error_log("Login Error: Failed to prepare staff profile query - " . $conn->error);
            $staffRole = 'staff'; // Default role if query fails
        }
    }
    
    // Set session variables
    $_SESSION['account_id'] = $account['account_id'];
    $_SESSION['user_id'] = $account['account_id']; // For backward compatibility
    $_SESSION['username'] = $account['username'];
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['user'] = $account['role']; // 'customer' or 'staff'
    
    if ($account['role'] === 'customer') {
        $_SESSION['member_id'] = $memberId;
    } else if ($account['role'] === 'staff') {
        $_SESSION['staff_id'] = $staffId;
        $_SESSION['staff_role'] = $staffRole;
    }
    
    // Success response with role
    ob_clean(); // Clear any output before sending JSON
    $response = [
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $account['role'],
        'username' => $account['username'],
        'full_name' => $fullName
    ];
    
    // Always include staff_role if user is staff (will be set to 'staff' as default if not found)
    if ($account['role'] === 'staff') {
        $response['staff_role'] = $staffRole ?? 'staff'; // cashier, waiter, chef, manager, etc.
    }
    
    echo json_encode($response);
    
} else {
    json_error('Invalid request method. Expected POST, got ' . $_SERVER["REQUEST_METHOD"]);
}

// End output buffering
ob_end_flush();
?>
