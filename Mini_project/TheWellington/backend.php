<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php'; // use DB users table

$action = $_POST['action'] ?? '';

// Helper: safe JSON error response
function json_error($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Ensure DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    json_error('Database connection failed.');
}

// 1. HANDLE LOGIN
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        json_error('Username is required.');
    }

    if ($password === '') {
        json_error('Password is required.');
    }

    // Check users table - verify username and password, detect role from DB
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) {
        json_error('Database error preparing statement.');
    }
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        $stmt->close();
        json_error('Database error executing statement.');
    }
    $stmt->bind_result($dbUserId, $dbUser, $dbPassword, $dbRole);
    $found = false;
    while ($stmt->fetch()) {
        $found = true;
        // Verify password (check if it's hashed or plaintext)
        $passwordMatch = false;
        if ($dbPassword && password_verify($password, $dbPassword)) {
            // Password is hashed and matches
            $passwordMatch = true;
        } elseif ($dbPassword === $password) {
            // Password stored as plaintext (for backward compatibility)
            $passwordMatch = true;
        }
        
        if ($passwordMatch) {
            $_SESSION['user'] = $dbRole;
            $_SESSION['username'] = $dbUser;
            $_SESSION['user_id'] = $dbUserId;
            echo json_encode(['status' => 'success', 'role' => $dbRole]);
        } else {
            json_error('Invalid password.');
        }
    }
    $stmt->close();
    if (!$found) {
        json_error('User not found. Please check your username.');
    }
    exit;
}

// 2. HANDLE LOGOUT
if ($action === 'logout') {
    // Clear all session variables
    $_SESSION = array();
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    // Destroy the session
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

// 3. HANDLE RESERVATION + ORDER SUBMISSION
if ($action === 'reserve') {
    // Use PDO for reservation processing
    $dsn = 'mysql:host=localhost;dbname=thewellingtondb1;charset=utf8mb4';
    $pdoUser = 'root';
    $pdoPass = '';
    try {
        $pdo = new PDO($dsn, $pdoUser, $pdoPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        json_error('Database connection failed.');
    }
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    
    // Parse guests - handle "2 People" format or just number
    $guestsInput = $_POST['guests'] ?? '2';
    $guests = intval(preg_replace('/[^0-9]/', '', $guestsInput)); // Extract number from "2 People" or just use number
    if ($guests < 1) $guests = 2; // Default to 2 if invalid
    
    // Parse table - handle "Table 1" format or just number/ID
    $tableInput = $_POST['table'] ?? '';
    $tableId = 0;
    if (!empty($tableInput)) {
        // Extract number from "Table 1" or use as-is if it's already a number
        $tableNum = preg_replace('/[^0-9]/', '', $tableInput);
        if (!empty($tableNum)) {
            // Convert to database format (T1, T2, etc.) or try as-is
            $tableNumber = 'T' . $tableNum;
            // Look up table_id by table_number (T1, T2, etc.) or by table_id
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_table WHERE table_number = ? OR table_number = ? OR table_id = ? LIMIT 1");
            $stmt->execute([$tableNumber, $tableNum, $tableNum]);
            $tableRow = $stmt->fetch();
            if ($tableRow) {
                $tableId = $tableRow['table_id'];
            }
        }
    }
    
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $orderItems = json_decode($_POST['order_items'] ?? '[]', true);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time)) {
        json_error('Please fill in all required fields.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get or create user
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $stmt = $pdo->prepare("SELECT user_id, phone FROM Users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                $userId = $existingUser['user_id'];
                // Update phone number if user didn't have one before
                if (empty($existingUser['phone']) && !empty($phone)) {
                    $stmt = $pdo->prepare("UPDATE Users SET phone = ? WHERE user_id = ?");
                    $stmt->execute([$phone, $userId]);
                }
            } else {
                $username = strtolower(str_replace(' ', '', $name)) . rand(1000, 9999);
                $stmt = $pdo->prepare("
                    INSERT INTO Users (username, password, full_name, email, phone, role) 
                    VALUES (?, ?, ?, ?, ?, 'customer')
                ");
                $stmt->execute([$username, '', $name, $email, $phone]);
                $userId = $pdo->lastInsertId();
            }
        } else {
            // Update phone number if logged-in user provides phone during reservation
            if (!empty($phone)) {
                $stmt = $pdo->prepare("UPDATE Users SET phone = ? WHERE user_id = ? AND (phone IS NULL OR phone = '')");
                $stmt->execute([$phone, $userId]);
            }
        }
        
        // Check table availability
        if ($tableId > 0) {
            // Verify table_id exists in Reservations table
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM Reservations 
                    WHERE table_id = ? AND reservation_date = ? AND reservation_time = ?
                ");
                $stmt->execute([$tableId, $date, $time]);
                $result = $stmt->fetch();
                if ($result && $result['count'] > 0) {
                    $pdo->rollBack();
                    json_error('This table is no longer available. Please select another table.');
                }
            } catch (PDOException $e) {
                // If table_id column doesn't exist, provide helpful error
                if (strpos($e->getMessage(), 'table_id') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                    $pdo->rollBack();
                    json_error('Database error: Reservations table structure is incorrect.');
                }
                throw $e;
            }
        } else {
            // Auto-assign available table - use LEFT JOIN instead of subquery
            $stmt = $pdo->prepare("
                SELECT rt.table_id 
                FROM restaurant_table rt
                LEFT JOIN Reservations r ON rt.table_id = r.table_id 
                    AND r.reservation_date = ? 
                    AND r.reservation_time = ?
                WHERE rt.capacity >= ? 
                    AND r.reservation_id IS NULL
                ORDER BY rt.capacity ASC 
                LIMIT 1
            ");
            $stmt->execute([$date, $time, $guests]);
            $availableTable = $stmt->fetch();
            if ($availableTable) {
                $tableId = $availableTable['table_id'];
            } else {
                $pdo->rollBack();
                json_error('No tables available for this time slot.');
            }
        }
        
        // Insert reservation into database
        // Date format: YYYY-MM-DD, Time format: HH:MM:SS
        // Build INSERT statement based on new schema
        $stmt = $pdo->prepare("
            INSERT INTO Reservations (user_id, full_name, email, phone, reservation_date, reservation_time, party_size, table_id, special_requests) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $name, $email, $phone, $date, $time, $guests, $tableId, $specialRequests]);
        $reservationId = $pdo->lastInsertId();
        
        // Verify reservation was saved
        if (!$reservationId) {
            $pdo->rollBack();
            json_error('Failed to save reservation. Please try again.');
        }
        
        // Insert order items (if reservation_orders table exists)
        if (!empty($orderItems) && is_array($orderItems)) {
            foreach ($orderItems as $item) {
                try {
                    $stmt = $pdo->prepare("SELECT item_id FROM Menu WHERE name = ? LIMIT 1");
                    $stmt->execute([$item['name']]);
                    $menuItem = $stmt->fetch();
                    if ($menuItem) {
                        $stmt = $pdo->prepare("
                            INSERT INTO reservation_orders (reservation_id, item_id, quantity) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$reservationId, $menuItem['item_id'], $item['qty']]);
                    } else {
                        // Log warning but don't fail the reservation
                        error_log("Menu item not found: " . $item['name']);
                    }
                } catch (PDOException $e) {
                    // Log error but don't fail the reservation
                    error_log("Error saving order item: " . $e->getMessage());
                }
            }
        }
        
        // Handle membership points - ALWAYS award 10 points for booking
        $pointsAwarded = 0;
        if ($userId) {
            // Check if membership exists for this user
            $stmt = $pdo->prepare("
                SELECT membership_id, points FROM Memberships 
                WHERE user_id = ? LIMIT 1
            ");
            $stmt->execute([$userId]);
            $membership = $stmt->fetch();
            
            if ($membership) {
                // Update existing membership - add 10 points
                $newPoints = (int)$membership['points'] + 10;
                $stmt = $pdo->prepare("
                    UPDATE Memberships 
                    SET points = ? 
                    WHERE membership_id = ?
                ");
                $stmt->execute([$newPoints, $membership['membership_id']]);
                $pointsAwarded = 10;
            } else {
                // Create new membership with 10 points
                $stmt = $pdo->prepare("
                    INSERT INTO Memberships (user_id, member_name, points) 
                    VALUES (?, ?, 10)
                ");
                $stmt->execute([$userId, $name]);
                $pointsAwarded = 10;
            }
        }
        
        $pdo->commit();
        
        $message = 'Reservation confirmed! We look forward to seeing you.';
        if ($pointsAwarded > 0) {
            $message .= " You've earned {$pointsAwarded} points!";
        }
        
        echo json_encode(['status' => 'success', 'message' => $message, 'points_awarded' => $pointsAwarded]);
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_error('Error processing reservation: ' . $e->getMessage());
    }
}

// 4. GET ORDERS (For Staff Dashboard)
if ($action === 'get_orders') {
    if (isset($_SESSION['user']) && $_SESSION['user'] === 'staff') {
        $data = file_get_contents($dbFile);
        echo $data;
    } else {
        echo json_encode(['error' => 'Unauthorized']);
    }
    exit;
}
?>