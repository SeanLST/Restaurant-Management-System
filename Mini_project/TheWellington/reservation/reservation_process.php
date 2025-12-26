<?php
/**
 * Reservation Process - UPDATED FOR NEW SCHEMA (thewellington1)
 * Database: thewellington1
 * Tables: accounts, memberships, restaurant_tables, table_availability, reservations
 * 
 * Key Changes from OLD schema:
 * - Uses accounts instead of Users
 * - Uses memberships with account_id instead of user_id
 * - Uses restaurant_tables instead of restaurant_table
 * - Creates table_availability entries
 * - Creates reservations linked to availability_id
 */

session_start();
header('Content-Type: application/json');

// Database connection function
if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        // UPDATED: Use thewellington1 database
        $dsn = 'mysql:host=localhost;dbname=thewellington1;charset=utf8mb4';
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
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $orderItems      = json_decode($_POST['order_items'] ?? '[]', true);
    $paymentMethod   = trim($_POST['payment_method'] ?? '');
    $preOrderTotal   = floatval($_POST['pre_order_total'] ?? 0);
    
    // Get member_id from session if logged in
    $memberId = $_SESSION['member_id'] ?? null;
    $accountId = $_SESSION['account_id'] ?? null;
    
    // If phone is missing but user is logged in, fetch from database
    if (empty($phone) && $memberId) {
        try {
            $stmt = $pdo->prepare("SELECT phone FROM memberships WHERE member_id = ? LIMIT 1");
            $stmt->execute([$memberId]);
            $memberRow = $stmt->fetch();
            if ($memberRow && !empty($memberRow['phone'])) {
                $phone = $memberRow['phone'];
            }
        } catch (Exception $e) {
            error_log('Error fetching phone from database: ' . $e->getMessage());
        }
    }
    
    // Validation
    if (empty($name) || empty($email) || empty($date) || empty($time)) {
        json_error('Please fill in all required fields (name, email, date, time).');
    }
    
    // Phone is optional if user is logged in
    if (empty($phone) && !$memberId) {
        json_error('Phone number is required for new accounts.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Please enter a valid email address.');
    }
    
    // Validate payment method
    if (!empty($paymentMethod) && !in_array($paymentMethod, ['touch_n_go', 'bank_transfer', 'cash', 'credit_card'])) {
        json_error('Invalid payment method selected.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get or create member
    if (!$memberId) {
        // Check if member exists by email
        $stmt = $pdo->prepare("SELECT m.member_id, m.account_id, a.username 
                               FROM memberships m 
                               JOIN accounts a ON m.account_id = a.account_id 
                               WHERE m.email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingMember = $stmt->fetch();
        
        if ($existingMember) {
            $memberId = (int)$existingMember['member_id'];
            $accountId = (int)$existingMember['account_id'];
            $_SESSION['member_id'] = $memberId;
            $_SESSION['account_id'] = $accountId;
            $_SESSION['user'] = 'customer';
            if (!isset($_SESSION['username']) && !empty($existingMember['username'])) {
                $_SESSION['username'] = $existingMember['username'];
            }
        } else {
            // Create new account and membership
            $username = strtolower(str_replace(' ', '', $name)) . rand(1000, 9999);
            
            // Create account first
            $stmt = $pdo->prepare("
                INSERT INTO accounts (username, password, role, account_status) 
                VALUES (?, '', 'customer', 'active')
            ");
            $stmt->execute([$username]);
            $accountId = (int)$pdo->lastInsertId();
            
            if (!$accountId) {
                throw new Exception('Failed to create account');
            }
            
            // Create membership
            $stmt = $pdo->prepare("
                INSERT INTO memberships (account_id, member_name, email, phone, points) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$accountId, $name, $email, $phone]);
            $memberId = (int)$pdo->lastInsertId();
            
            if (!$memberId) {
                throw new Exception('Failed to create membership');
            }
            
            // Store in session
            $_SESSION['member_id'] = $memberId;
            $_SESSION['account_id'] = $accountId;
            $_SESSION['username'] = $username;
            $_SESSION['user'] = 'customer';
        }
    }
    
    // Parse table selection
    $tableId = null;
    if (!empty($tableInput) && $tableInput !== 'Auto-assigned') {
        $tableNumber = preg_replace('/[^0-9]/', '', $tableInput);
        if (!empty($tableNumber)) {
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_tables WHERE table_number = ? LIMIT 1");
            $stmt->execute([intval($tableNumber)]);
            $tableRow = $stmt->fetch();
            if ($tableRow) {
                $tableId = (int)$tableRow['table_id'];
            }
        }
    }
    
    // Auto-assign table if not specified
    if (!$tableId) {
        $stmt = $pdo->prepare("
            SELECT rt.table_id 
            FROM restaurant_tables rt
            WHERE rt.capacity >= ?
            AND NOT EXISTS (
                SELECT 1 FROM table_availability ta
                WHERE ta.table_id = rt.table_id
                AND ta.reservation_date = ?
                AND ta.reservation_time = ?
                AND ta.status IN ('reserved', 'occupied')
            )
            ORDER BY rt.capacity ASC
            LIMIT 1
        ");
        $stmt->execute([$guests, $date, $time]);
        $availableTable = $stmt->fetch();
        
        if ($availableTable) {
            $tableId = (int)$availableTable['table_id'];
        } else {
            $pdo->rollBack();
            json_error('No tables available for this time slot. Please select another time.');
        }
    } else {
        // Check if specified table is available
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM table_availability
            WHERE table_id = ?
            AND reservation_date = ?
            AND reservation_time = ?
            AND status IN ('reserved', 'occupied')
        ");
        $stmt->execute([$tableId, $date, $time]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $pdo->rollBack();
            json_error('Sorry, this table is no longer available. Please select another table.');
        }
    }
    
    // Create table_availability entry
    $stmt = $pdo->prepare("
        INSERT INTO table_availability (table_id, reservation_date, reservation_time, status)
        VALUES (?, ?, ?, 'reserved')
    ");
    $stmt->execute([$tableId, $date, $time]);
    $availabilityId = (int)$pdo->lastInsertId();
    
    if (!$availabilityId) {
        $pdo->rollBack();
        json_error('Failed to reserve table slot.');
    }
    
    // Create reservation
    $stmt = $pdo->prepare("
        INSERT INTO reservations (member_id, availability_id, party_size, status, special_requests)
        VALUES (?, ?, ?, 'confirmed', ?)
    ");
    $stmt->execute([$memberId, $availabilityId, $guests, $specialRequests]);
    $reservationId = (int)$pdo->lastInsertId();
    
    if (!$reservationId) {
        $pdo->rollBack();
        json_error('Failed to create reservation. Please try again.');
    }
    
    // Update table_availability with reservation_id
    $stmt = $pdo->prepare("
        UPDATE table_availability 
        SET reservation_id = ? 
        WHERE availability_id = ?
    ");
    $stmt->execute([$reservationId, $availabilityId]);
    
    // Initialize variables for payment
    $transactionId = null;
    $billId = null;
    
    // Handle pre-orders with payment (if applicable)
    if (!empty($orderItems) && !empty($paymentMethod) && $preOrderTotal > 0) {
        // Get a staff_id for the bill (use first available staff, or create a system staff)
        $stmt = $pdo->query("SELECT staff_id FROM staffs LIMIT 1");
        $staffRow = $stmt->fetch();
        $staffId = $staffRow ? $staffRow['staff_id'] : null;
        
        if (!$staffId) {
            error_log('Warning: No staff found for bill creation. Skipping bill creation.');
        } else {
            // Create bill
            $stmt = $pdo->prepare("
                INSERT INTO bills (staff_id, reservation_id, total_amount, payment_status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$staffId, $reservationId, $preOrderTotal]);
            $billId = (int)$pdo->lastInsertId();
            
            if ($billId) {
                // Create payment transaction
                $stmt = $pdo->prepare("
                    INSERT INTO payment_transactions (payment_method, amount, payment_status, bill_id)
                    VALUES (?, ?, 'pending', ?)
                ");
                $stmt->execute([$paymentMethod, $preOrderTotal, $billId]);
                $transactionId = (int)$pdo->lastInsertId();
                
                // Add items to bill_items
                foreach ($orderItems as $item) {
                    try {
                        $stmt = $pdo->prepare("SELECT item_id, price FROM menu WHERE name = ? LIMIT 1");
                        $stmt->execute([$item['name']]);
                        $menuItem = $stmt->fetch();
                        
                        if ($menuItem) {
                            $quantity = intval($item['qty'] ?? 1);
                            $unitPrice = floatval($menuItem['price']);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO bill_items (bill_id, item_id, quantity, unit_price)
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->execute([$billId, $menuItem['item_id'], $quantity, $unitPrice]);
                        }
                    } catch (Exception $e) {
                        error_log('Error saving bill item: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    // Award membership points (10 points per reservation)
    $pointsAwarded = 0;
    if ($memberId) {
        $stmt = $pdo->prepare("
            UPDATE memberships 
            SET points = points + 10 
            WHERE member_id = ?
        ");
        $stmt->execute([$memberId]);
        $pointsAwarded = 10;
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Build success message
    $message = 'Reservation confirmed! ';
    
    if ($transactionId) {
        $message .= 'Payment is pending verification. Your order will be processed after staff approval. ';
    }
    
    $message .= 'We look forward to seeing you.';
    
    if ($pointsAwarded > 0) {
        $message .= " You've earned {$pointsAwarded} loyalty points!";
    }
    
    json_success($message, [
        'reservation_id'    => $reservationId,
        'member_id'         => $memberId,
        'points_awarded'    => $pointsAwarded,
        'transaction_id'    => $transactionId,
        'bill_id'           => $billId,
        'payment_pending'   => !empty($transactionId),
        'table_id'          => $tableId,
        'availability_id'   => $availabilityId,
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reservation Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    json_error('An error occurred while processing your reservation: ' . $e->getMessage());
}
?>
