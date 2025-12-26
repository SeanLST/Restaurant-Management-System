<?php
/**
 * Customer History Backend - UPDATED FOR NEW SCHEMA (thewellington1)
 * Fetches customer's reservation and payment history
 * 
 * Key Changes:
 * - Uses thewellington1 database
 * - Uses accounts/memberships instead of Users
 * - Uses member_id instead of user_id
 * - Uses restaurant_tables instead of restaurant_table
 * - Uses staffs instead of Staff
 */
session_start();
header('Content-Type: application/json');

require '../db_connect.php';

// Check if DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check if user is logged in as customer
    if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'customer') {
        echo json_encode(['status' => 'error', 'message' => 'Please login to view history']);
        exit;
    }
    
    // Get member_id from session (new schema uses member_id)
    $memberId = $_SESSION['member_id'] ?? null;
    $accountId = $_SESSION['account_id'] ?? null;
    
    if (!$memberId) {
        // Try to get member_id from account_id if available
        if ($accountId) {
            $stmt = $conn->prepare("SELECT member_id FROM memberships WHERE account_id = ? LIMIT 1");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();
            $stmt->close();
            
            if ($member) {
                $memberId = $member['member_id'];
                $_SESSION['member_id'] = $memberId;
            }
        }
        
        if (!$memberId) {
            echo json_encode(['status' => 'error', 'message' => 'User session invalid - member not found']);
            exit;
        }
    }
    
    // Get member details
    $stmt = $conn->prepare("
        SELECT 
            m.member_id,
            m.member_name,
            m.email,
            m.phone,
            m.points,
            a.username,
            a.account_id
        FROM memberships m
        JOIN accounts a ON m.account_id = a.account_id
        WHERE m.member_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();
    
    if (!$member) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        exit;
    }
    
    // Get customer's reservations using new schema
    // New schema: reservations -> member_id, availability_id
    $stmt = $conn->prepare("
        SELECT 
            r.reservation_id,
            r.member_id,
            r.availability_id,
            r.party_size,
            r.status as reservation_status,
            r.special_requests,
            r.created_at as reservation_created_at,
            ta.reservation_date,
            ta.reservation_time,
            ta.table_id,
            rt.table_number,
            rt.capacity,
            rt.section,
            pt.transaction_id,
            pt.payment_method,
            pt.amount,
            pt.payment_status,
            pt.created_at as payment_date,
            pt.verified_by,
            s.staff_name as verified_by_name,
            b.bill_id,
            b.total_amount,
            b.payment_status as bill_payment_status,
            CASE 
                WHEN pt.payment_status = 'verified' THEN 'verified'
                WHEN pt.payment_status = 'pending' THEN 'pending'
                WHEN pt.payment_status IS NULL AND ta.reservation_date >= CURDATE() THEN 'confirmed'
                WHEN pt.payment_status IS NULL AND ta.reservation_date < CURDATE() THEN 'completed'
                ELSE 'pending'
            END as display_status
        FROM reservations r
        JOIN table_availability ta ON r.availability_id = ta.availability_id
        LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
        LEFT JOIN bills b ON r.reservation_id = b.reservation_id
        LEFT JOIN payment_transactions pt ON b.bill_id = pt.bill_id
        LEFT JOIN staffs s ON pt.verified_by = s.staff_id
        WHERE r.member_id = ?
        ORDER BY ta.reservation_date DESC, ta.reservation_time DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservations = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $stmt->close();
    
    // Get order items for each reservation (from bill_items)
    foreach ($reservations as &$reservation) {
        $reservation['order_items'] = [];
        
        // Get items from bill_items if bill exists
        if (!empty($reservation['bill_id'])) {
            $stmt = $conn->prepare("
                SELECT 
                    bi.bill_item_id,
                    bi.item_id,
                    m.name as item_name,
                    bi.quantity,
                    bi.unit_price,
                    (bi.quantity * bi.unit_price) as item_total,
                    m.category
                FROM bill_items bi
                LEFT JOIN menu m ON bi.item_id = m.item_id
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
    
    // Build user data structure for compatibility
    $userData = [
        'user_id' => $member['account_id'], // For backward compatibility
        'member_id' => $member['member_id'],
        'full_name' => $member['member_name'],
        'email' => $member['email'],
        'phone' => $member['phone'],
        'username' => $member['username']
    ];
    
    // Build membership data
    $membershipData = [
        'points' => (int)($member['points'] ?? 0),
        'member_name' => $member['member_name'],
        'member_id' => $member['member_id']
    ];
    
    echo json_encode([
        'status' => 'success',
        'user' => $userData,
        'reservations' => $reservations,
        'membership' => $membershipData,
        'total_reservations' => count($reservations)
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    error_log('Customer History Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching history: ' . $e->getMessage()
    ]);
}
?>
