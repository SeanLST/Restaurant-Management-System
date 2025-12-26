<?php
/**
 * Registration Process - UPDATED FOR NEW SCHEMA (thewellington1)
 * Database: thewellington1
 * Tables: accounts, memberships, staffs
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json'); // Return JSON to JavaScript

// Check if db_connect.php exists
if (!file_exists('../db_connect.php')) {
    echo json_encode(["status" => "error", "message" => "Database connection file not found."]);
    exit;
}

require '../db_connect.php'; // Include the connection

// Helper: safe JSON error response
function json_error($message) {
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

// Check if DB connection is valid
if (!isset($conn)) {
    json_error('Database connection variable not set. Please check db_connect.php');
}

if ($conn->connect_error) {
    error_log("Database connection error: " . $conn->connect_error);
    json_error('Database connection failed: ' . $conn->connect_error);
}

// Check if required tables exist
$tables = ['accounts', 'memberships'];
$missingTables = [];
foreach ($tables as $table) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$checkTable || $checkTable->num_rows == 0) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    $missingList = implode(', ', $missingTables);
    error_log("Register Error: Missing tables: " . $missingList);
    json_error("Required table(s) '$missingList' do not exist. Please run setup_thewellington1.sql in phpMyAdmin or visit setup_tables.php");
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

    // 3. Check if Username already exists in accounts table
    $checkStmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? LIMIT 1");
    if (!$checkStmt) {
        json_error('Database error preparing statement.');
    }
    
    $checkStmt->bind_param("s", $username);
    if (!$checkStmt->execute()) {
        $checkStmt->close();
        json_error('Database error checking existing username.');
    }
    
    $result = $checkStmt->get_result();
    if ($result->num_rows > 0) {
        $checkStmt->close();
        json_error("Username already exists.");
    }
    $checkStmt->close();
    
    // Check if email already exists in memberships or staffs table
    if (!empty($email)) {
        if ($role === 'customer') {
            $emailCheckStmt = $conn->prepare("SELECT member_id FROM memberships WHERE email = ? LIMIT 1");
        } else {
            $emailCheckStmt = $conn->prepare("SELECT staff_id FROM staffs WHERE email = ? LIMIT 1");
        }
        
        if ($emailCheckStmt) {
            $emailCheckStmt->bind_param("s", $email);
            $emailCheckStmt->execute();
            $emailResult = $emailCheckStmt->get_result();
            if ($emailResult->num_rows > 0) {
                $emailCheckStmt->close();
                json_error("Email already exists.");
            }
            $emailCheckStmt->close();
        }
    }

    // 4. Start transaction for atomicity
    $conn->begin_transaction();
    
    try {
        // 5. Insert into accounts table first
        $accountSql = "INSERT INTO accounts (username, password, role, account_status) VALUES (?, ?, ?, 'active')";
        $stmt = $conn->prepare($accountSql);
        if (!$stmt) {
            error_log("Register Error: Failed to prepare account insert - " . $conn->error);
            throw new Exception('Database error preparing account insert statement: ' . $conn->error);
        }
        
        $stmt->bind_param("sss", $username, $password, $role);
        
        if (!$stmt->execute()) {
            error_log("Register Error: Failed to execute account insert - " . $stmt->error);
            throw new Exception("Database error inserting account: " . $stmt->error);
        }
        
        $newAccountId = $conn->insert_id;
        if (!$newAccountId) {
            error_log("Register Error: No account_id returned after insert");
            throw new Exception("Failed to create account. Please try again.");
        }
        $stmt->close();
        
        // 6. Create profile record based on role
        if ($role === 'customer') {
            // Create membership record for customer users
            $membershipSql = "INSERT INTO memberships (account_id, member_name, email, phone, points) VALUES (?, ?, ?, ?, 0)";
            $membershipStmt = $conn->prepare($membershipSql);
            if (!$membershipStmt) {
                throw new Exception('Database error preparing membership insert statement.');
            }
            
            $membershipStmt->bind_param("isss", $newAccountId, $full_name, $email, $phone);
            if (!$membershipStmt->execute()) {
                error_log("Register Error: Failed to execute membership insert - " . $membershipStmt->error);
                throw new Exception("Failed to create membership: " . $membershipStmt->error);
            }
            $membershipStmt->close();
            
        } else if ($role === 'staff') {
            // Create staff record for staff users
            // Default staff role is 'waiter' - can be changed by admin later
            $staffRole = 'waiter'; // Default role
            $staffSql = "INSERT INTO staffs (account_id, staff_name, email, phone, role) VALUES (?, ?, ?, ?, ?)";
            $staffStmt = $conn->prepare($staffSql);
            if (!$staffStmt) {
                throw new Exception('Database error preparing staff insert statement.');
            }
            
            $staffStmt->bind_param("issss", $newAccountId, $full_name, $email, $phone, $staffRole);
            if (!$staffStmt->execute()) {
                error_log("Register Error: Failed to execute staff insert - " . $staffStmt->error);
                throw new Exception("Failed to create staff record: " . $staffStmt->error);
            }
            $staffStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Registration successful! Your account has been created. You can now login."]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollback();
        }
        $errorMsg = $e->getMessage();
        error_log("Register Error: Transaction rolled back - " . $errorMsg);
        error_log("Register Error: Stack trace - " . $e->getTraceAsString());
        json_error($errorMsg);
    } catch (Error $e) {
        // Catch PHP 7+ Error exceptions
        if (isset($conn)) {
            $conn->rollback();
        }
        $errorMsg = "Fatal error: " . $e->getMessage();
        error_log("Register Fatal Error: " . $errorMsg);
        json_error($errorMsg);
    }
} else {
    json_error("Invalid request method. Expected POST, got " . $_SERVER["REQUEST_METHOD"]);
}
?>
