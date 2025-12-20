<?php
// reservation_process.php - UPDATED FOR HISTORY & PAYMENT STATUS
// Handles reservation submission and membership points
// Ensures reservations appear in history with correct payment status
session_start();
header('Content-Type: application/json');

// Database connection function
if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $dsn = 'mysql:host=localhost;dbname=thewellingtondb;charset=utf8mb4';
        $user = 'root';
        $pass = '';
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (Exception $e) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }
}

// Helper function for JSON error response
function json_error($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Helper function for JSON success response
function json_success($message, $data = []) {
    $response = ['status' => 'success', 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get form data
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $date            = trim($_POST['date'] ?? '');
    $time            = trim($_POST['time'] ?? '');
    $guests          = intval($_POST['guests'] ?? 2);
    $tableInput      = trim($_POST['table'] ?? '');
    // Special requests removed - not stored in database
    $orderItems      = json_decode($_POST['order_items'] ?? '[]', true);
    $paymentMethod   = trim($_POST['payment_method'] ?? '');
    $preOrderTotal   = floatval($_POST['pre_order_total'] ?? 0);
    
    // If phone is missing but user is logged in, fetch from database
    if (empty($phone) && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT phone FROM Users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $userRow = $stmt->fetch();
            if ($userRow && !empty($userRow['phone'])) {
                $phone = $userRow['phone'];
            }
        } catch (Exception $e) {
            error_log('Error fetching phone from database: ' . $e->getMessage());
        }
    }
    
    // Validation
    if (empty($name) || empty($email) || empty($date) || empty($time)) {
        json_error('Please fill in all required fields (name, email, date, time).');
    }
    
    // Phone is optional if user is logged in (can be fetched from database)
    // But if creating new user, phone is required
    if (empty($phone) && !isset($_SESSION['user_id'])) {
        json_error('Phone number is required for new accounts.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }
    
    // Validate payment method
    if (!empty($paymentMethod) && !in_array($paymentMethod, ['touch_n_go', 'bank_transfer'])) {
        json_error('Invalid payment method selected.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get or create user
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        // Check if user exists by email
        $stmt = $pdo->prepare("SELECT user_id, username FROM Users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            $userId = (int)$existingUser['user_id'];
            $_SESSION['user_id'] = $userId;
            $_SESSION['user']    = 'customer';
            if (!isset($_SESSION['username']) && !empty($existingUser['username'])) {
                $_SESSION['username'] = $existingUser['username'];
            }
        } else {
            // Create new user account
            $username = strtolower(str_replace(' ', '', $name)) . rand(1000, 9999);
            $stmt = $pdo->prepare("\n                INSERT INTO Users (username, password, full_name, email, phone, role) \n                VALUES (?, ?, ?, ?, ?, 'customer')\n            ");
            $stmt->execute([$username, '', $name, $email, $phone]);
            $userId = (int)$pdo->lastInsertId();
            
            // Store in session
            $_SESSION['user_id']  = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user']     = 'customer';
            
            // Create membership for new customer
            try {
                $stmt = $pdo->prepare("\n                    INSERT INTO Memberships (user_id, member_name, points) \n                    VALUES (?, ?, 0)\n                ");
                $stmt->execute([$userId, $name]);
            } catch (Exception $e) {
                error_log('Failed to create membership for new user: ' . $e->getMessage());
            }
        }
    }
    
    // Parse table selection - handle "Table 1" format or just number
    $tableId = 0;
    if (!empty($tableInput) && $tableInput !== 'Auto-assigned') {
        $tableNumber = preg_replace('/[^0-9]/', '', $tableInput);
        if (!empty($tableNumber)) {
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_table WHERE table_number = ? LIMIT 1");
            $stmt->execute([intval($tableNumber)]);
            $tableRow = $stmt->fetch();
            if ($tableRow) {
                $tableId = (int)$tableRow['table_id'];
            }
        }
    }
    
    // Check if table is available
    if ($tableId > 0) {
        $stmt = $pdo->prepare("\n            SELECT COUNT(*) FROM Reservations \n            WHERE table_id = ? \n            AND reservation_date = ? \n            AND reservation_time = ?\n        ");
        $stmt->execute([$tableId, $date, $time]);
        $isTableBooked = $stmt->fetchColumn() > 0;
        
        if ($isTableBooked) {
            $pdo->rollBack();
            json_error('Sorry, this table is no longer available. Please select another table.');
        }
    } else {
        // Auto-assign available table
        $stmt = $pdo->prepare("\n            SELECT rt.table_id \n            FROM restaurant_table rt\n            WHERE rt.capacity >= ?\n            AND rt.table_id NOT IN (\n                SELECT table_id FROM Reservations \n                WHERE reservation_date = ? \n                AND reservation_time = ?\n            )\n            ORDER BY rt.capacity ASC\n            LIMIT 1\n        ");
        $stmt->execute([$guests, $date, $time]);
        $availableTable = $stmt->fetch();
        
        if ($availableTable) {
            $tableId = (int)$availableTable['table_id'];
        } else {
            $pdo->rollBack();
            json_error('No tables available for this time slot. Please select another time.');
        }
    }
    
    // Determine if this reservation has pre-payment
    $hasPayment    = (!empty($orderItems) && !empty($paymentMethod) && $preOrderTotal > 0);
    $paymentStatus = $hasPayment ? 'pending' : 'none';
    
    // Insert reservation - only use user_id (user is already logged in and verified)
    // Name, email, phone can be retrieved from Users table via user_id
    // Special requests removed - not needed for basic reservations
    // Try party_size first, fallback to guests if column doesn't exist
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Reservations 
            (user_id, reservation_date, reservation_time, party_size, table_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $date, $time, $guests, $tableId]);
    } catch (PDOException $e) {
        // If party_size column doesn't exist, try guests instead
        if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'party_size') !== false) {
            $stmt = $pdo->prepare("
                INSERT INTO Reservations 
                (user_id, reservation_date, reservation_time, guests, table_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $date, $time, $guests, $tableId]);
        } else {
            // Re-throw if it's a different error
            throw $e;
        }
    }
    $reservationId = (int)$pdo->lastInsertId();
    
    if (!$reservationId) {
        $pdo->rollBack();
        json_error('Failed to create reservation. Please try again.');
    }
    
    // Initialize variables
    $transactionId = null;
    $billId        = null;
    
    // Handle pre-orders with payment
    if ($hasPayment) {
        // Create payment transaction with 'pending' status
        $stmt = $pdo->prepare("\n            INSERT INTO payment_transactions \n            (reservation_id, payment_method, amount, payment_status) \n            VALUES (?, ?, ?, 'pending')\n        ");
        $stmt->execute([$reservationId, $paymentMethod, $preOrderTotal]);
        $transactionId = (int)$pdo->lastInsertId();
        
        if (!$transactionId) {
            $pdo->rollBack();
            json_error('Failed to create payment transaction.');
        }
        
        // Create Bill (Bills table: bill_id, reservation_id, table_id, transaction_id, payment_method, total_amount)
        $stmt = $pdo->prepare("\n            INSERT INTO Bills \n            (reservation_id, table_id, transaction_id, payment_method, total_amount) \n            VALUES (?, ?, ?, ?, ?)\n        ");
        $stmt->execute([$reservationId, $tableId, $transactionId, $paymentMethod, $preOrderTotal]);
        $billId = (int)$pdo->lastInsertId();
        
        if (!$billId) {
            $pdo->rollBack();
            json_error('Failed to create bill.');
        }
        
        // Add items to Bill_Items (bill_item_id, bill_id, item_id, quantity, unit_price)
        foreach ($orderItems as $item) {
            try {
                $stmt = $pdo->prepare("SELECT item_id, price FROM Menu WHERE name = ? LIMIT 1");
                $stmt->execute([$item['name']]);
                $menuItem = $stmt->fetch();
                
                if ($menuItem) {
                    $quantity  = intval($item['qty'] ?? 1);
                    $unitPrice = floatval($menuItem['price']);
                    
                    $stmt = $pdo->prepare("\n                        INSERT INTO Bill_Items \n                        (bill_id, item_id, quantity, unit_price) \n                        VALUES (?, ?, ?, ?)\n                    ");
                    $stmt->execute([$billId, $menuItem['item_id'], $quantity, $unitPrice]);
                }
            } catch (Exception $e) {
                error_log('Error saving bill item: ' . $e->getMessage());
                // Do not fail whole transaction for item-level error
            }
        }
        
        // NOTE: Kitchen orders will be created in staff_actions.php when payment is verified
    } elseif (!empty($orderItems) && is_array($orderItems) && empty($paymentMethod)) {
        // Walk-in style pre-orders without online payment - send directly to Kitchen
        foreach ($orderItems as $item) {
            try {
                $stmt = $pdo->prepare("SELECT item_id, price FROM Menu WHERE name = ? LIMIT 1");
                $stmt->execute([$item['name']]);
                $menuItem = $stmt->fetch();
                
                if ($menuItem) {
                    $itemId   = $menuItem['item_id'];
                    $quantity = intval($item['qty'] ?? 1);
                    
                    $stmt = $pdo->prepare("\n                        INSERT INTO Kitchen (table_id, item_id, quantity) \n                        VALUES (?, ?, ?)\n                    ");
                    $stmt->execute([$tableId, $itemId, $quantity]);
                }
            } catch (Exception $e) {
                error_log('Error saving order item to Kitchen: ' . $e->getMessage());
            }
        }
    }
    
    // Handle membership points - ALWAYS award 10 points for booking
    $pointsAwarded = 0;
    if ($userId) {
        $stmt = $pdo->prepare("\n            SELECT membership_id, points FROM Memberships \n            WHERE user_id = ? LIMIT 1\n        ");
        $stmt->execute([$userId]);
        $membership = $stmt->fetch();
        
        if ($membership) {
            $newPoints = (int)$membership['points'] + 10;
            $stmt = $pdo->prepare("\n                UPDATE Memberships \n                SET points = ? \n                WHERE membership_id = ?\n            ");
            $stmt->execute([$newPoints, $membership['membership_id']]);
            $pointsAwarded = 10;
        } else {
            $stmt = $pdo->prepare("\n                INSERT INTO Memberships (user_id, member_name, points) \n                VALUES (?, ?, 10)\n            ");
            $stmt->execute([$userId, $name]);
            $pointsAwarded = 10;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Build success message
    $message = 'Reservation confirmed! ';
    
    if ($hasPayment) {
        $message .= 'Payment is pending verification. Your order will be sent to the kitchen after staff approval. ';
    }
    
    $message .= 'We look forward to seeing you.';
    
    if ($pointsAwarded > 0) {
        $message .= " You've earned {$pointsAwarded} loyalty points!";
    }
    
    json_success($message, [
        'reservation_id'    => $reservationId,
        'user_id'           => $userId,
        'points_awarded'    => $pointsAwarded,
        'transaction_id'    => $transactionId,
        'bill_id'           => $billId,
        'payment_pending'   => $hasPayment,
        'payment_status'    => $paymentStatus,
        'table_id'          => $tableId,
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reservation Error: ' . $e->getMessage());
    json_error('An error occurred while processing your reservation: ' . $e->getMessage());
}
?>
