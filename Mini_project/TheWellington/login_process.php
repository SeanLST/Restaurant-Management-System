<?php
session_start();
header('Content-Type: application/json');

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
    
    // Get credentials
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password)) {
        json_error('Please enter both username and password.');
    }
    
    // Query user from database (auto-detect role)
    $stmt = $conn->prepare("SELECT user_id, username, password, full_name, email, role FROM Users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        json_error('Database error.');
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        json_error('Invalid username or password.');
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password (plain text comparison)
    if ($password !== $user['password']) {
        json_error('Invalid username or password.');
    }
    
    // Set session variables based on user's role from database
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user'] = $user['role']; // 'customer' or 'staff'
    
    // Success response with role
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $user['role'],
        'username' => $user['username'],
        'full_name' => $user['full_name']
    ]);
    
} else {
    json_error('Invalid request method.');
}
?>


