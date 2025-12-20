<?php
/**
 * Customer History Backend
 * Fetches customer's reservation and payment history
 */
session_start();
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thewellingtondb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Check if user is logged in as customer
    if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'customer') {
        echo json_encode(['status' => 'error', 'message' => 'Please login to view history']);
        exit;
    }
    
    // Get username from session
    $sessionUsername = $_SESSION['username'] ?? '';
    
    if (empty($sessionUsername)) {
        echo json_encode(['status' => 'error', 'message' => 'User session invalid']);
        exit;
    }
    
    // Get user details
    $stmt = $conn->prepare("SELECT user_id, full_name, email, phone FROM Users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $sessionUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    $userId = $user['user_id'];
    
    // Get customer's reservations with payment status and verification details
    // Get user info from Users table via JOIN (full_name, email, phone removed from Reservations)
    $stmt = $conn->prepare("
        SELECT 
            r.reservation_id,
            u.full_name,
            u.email,
            u.phone,
            r.reservation_date,
            r.reservation_time,
            r.party_size,
            r.status as reservation_status,
            r.created_at,
            rt.table_number,
            rt.capacity,
            pt.transaction_id,
            pt.payment_method,
            pt.amount,
            pt.payment_status,
            pt.verified_at,
            pt.verified_by,
            pt.created_at as payment_date,
            b.bill_id,
            b.total_amount,
            b.payment_status as bill_payment_status,
            CASE 
                WHEN pt.payment_status = 'verified' THEN 'verified'
                WHEN pt.payment_status = 'pending' THEN 'pending'
                WHEN pt.payment_status IS NULL AND r.reservation_date >= CURDATE() THEN 'confirmed'
                WHEN pt.payment_status IS NULL AND r.reservation_date < CURDATE() THEN 'completed'
                ELSE 'pending'
            END as display_status
        FROM Reservations r
        LEFT JOIN Users u ON r.user_id = u.user_id
        LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
        LEFT JOIN payment_transactions pt ON r.reservation_id = pt.reservation_id
        LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
        WHERE r.user_id = ?
        ORDER BY r.reservation_date DESC, r.reservation_time DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservations = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $stmt->close();
    
    // Get order items for each reservation
    foreach ($reservations as &$reservation) {
        $resId = $reservation['reservation_id'];
        $reservation['order_items'] = [];
        
        // Get items from Bill_Items if bill exists
        if ($reservation['bill_id']) {
            $stmt = $conn->prepare("
                SELECT 
                    bi.item_id,
                    m.name as item_name,
                    bi.quantity,
                    bi.unit_price,
                    (bi.quantity * bi.unit_price) as item_total,
                    m.category
                FROM Bill_Items bi
                LEFT JOIN Menu m ON bi.item_id = m.item_id
                WHERE bi.bill_id = ?
            ");
            $stmt->bind_param("i", $reservation['bill_id']);
            $stmt->execute();
            $itemsResult = $stmt->get_result();
            
            while ($itemRow = $itemsResult->fetch_assoc()) {
                $reservation['order_items'][] = $itemRow;
            }
            $stmt->close();
        }
    }
    
    // Get membership points (no tier calculation)
    $stmt = $conn->prepare("
        SELECT points, member_name 
        FROM Memberships 
        WHERE user_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $membership = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'user' => $user,
        'reservations' => $reservations,
        'membership' => $membership,
        'total_reservations' => count($reservations)
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching history: ' . $e->getMessage()
    ]);
}
?>
