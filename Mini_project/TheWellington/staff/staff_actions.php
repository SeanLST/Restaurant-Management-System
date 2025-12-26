<?php
/* ========================================================================
   UPDATED STAFF ACTIONS - COMPLETE INTEGRATION WITH NEW SCHEMA (thewellington1)
   Properly integrated with new reservation system and database
   Database: thewellington1
   Schema: accounts, memberships, staffs, reservations, table_availability, etc.
   ======================================================================== */

session_start();
header('Content-Type: application/json');

// Database connection
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    // UPDATED: Use thewellington1 database (new schema)
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
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}

$pdo = getDBConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    switch ($action) {
        
        // ============================================
        // RESERVATION MANAGEMENT
        // ============================================
        
        case 'get_reservations':
            // UPDATED: Use new schema - reservations -> member_id -> memberships -> account_id -> accounts
            // reservations -> availability_id -> table_availability -> table_id -> restaurant_tables
            $stmt = $pdo->prepare("
                SELECT 
                    r.reservation_id,
                    r.member_id,
                    mem.member_name as full_name,
                    mem.email,
                    mem.phone,
                    ta.reservation_date,
                    ta.reservation_time,
                    r.party_size,
                    rt.table_id,
                    rt.table_number,
                    rt.capacity,
                    r.created_at,
                    mem.points as member_points,
                    pt.payment_status,
                    pt.payment_method,
                    b.total_amount,
                    r.status as reservation_status
                FROM reservations r
                JOIN memberships mem ON r.member_id = mem.member_id
                JOIN accounts a ON mem.account_id = a.account_id
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.bill_id = pt.bill_id
                ORDER BY ta.reservation_date DESC, ta.reservation_time DESC
            ");
            $stmt->execute();
            $reservations = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'reservations' => $reservations
            ]);
            break;
            
        case 'get_todays_reservations':
            // UPDATED: Use new schema with table_availability
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    rt.table_number,
                    rt.capacity,
                    mem.member_name as full_name,
                    mem.email,
                    mem.phone,
                    mem.points as member_points,
                    pt.payment_status,
                    b.total_amount,
                    ta.reservation_date,
                    ta.reservation_time
                FROM reservations r
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                LEFT JOIN memberships mem ON r.member_id = mem.member_id
                LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.bill_id = pt.bill_id
                WHERE ta.reservation_date = CURDATE()
                ORDER BY ta.reservation_time
            ");
            $stmt->execute();
            $reservations = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'reservations' => $reservations
            ]);
            break;
            
        case 'get_reservations_by_date':
            $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
            
            try {
                // UPDATED: Use new schema
                $stmt = $pdo->prepare("
                    SELECT 
                        r.*,
                        rt.table_number,
                        mem.member_name as full_name,
                        mem.phone,
                        mem.points as member_points,
                        ta.reservation_date,
                        ta.reservation_time
                    FROM reservations r
                    JOIN table_availability ta ON r.availability_id = ta.availability_id
                    LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                    LEFT JOIN memberships mem ON r.member_id = mem.member_id
                    WHERE ta.reservation_date = ?
                    ORDER BY ta.reservation_time ASC
                ");
                $stmt->execute([$date]);
                $reservations = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'reservations' => $reservations,
                    'date' => $date
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching reservations: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'update_reservation':
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            $date = trim($_POST['reservation_date'] ?? '');
            $time = trim($_POST['reservation_time'] ?? '');
            $partySize = intval($_POST['party_size'] ?? 0);
            $tableId = intval($_POST['table_id'] ?? 0);
            
            if ($reservationId <= 0 || empty($date) || empty($time)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                break;
            }
            
            // UPDATED: Check table availability via table_availability (exclude current reservation)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM table_availability ta
                JOIN reservations r ON ta.availability_id = r.availability_id
                WHERE ta.table_id = ?
                AND ta.reservation_date = ?
                AND ta.reservation_time = ?
                AND r.reservation_id != ?
            ");
            $stmt->execute([$tableId, $date, $time, $reservationId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Table already booked for this time slot'
                ]);
                break;
            }
            
            // UPDATED: Update reservation and table_availability
            $pdo->beginTransaction();
            try {
                // Get current availability_id
                $stmt = $pdo->prepare("SELECT availability_id FROM reservations WHERE reservation_id = ?");
                $stmt->execute([$reservationId]);
                $res = $stmt->fetch();
                if (!$res) {
                    throw new Exception('Reservation not found');
                }
                $availabilityId = $res['availability_id'];
                
                // Update table_availability
                $stmt = $pdo->prepare("
                    UPDATE table_availability
                    SET table_id = ?,
                        reservation_date = ?,
                        reservation_time = ?
                    WHERE availability_id = ?
                ");
                $stmt->execute([$tableId, $date, $time, $availabilityId]);
                
                // Update reservation
                $stmt = $pdo->prepare("
                    UPDATE reservations
                    SET party_size = ?
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$partySize, $reservationId]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservation updated successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating reservation: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'delete_reservation':
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            
            if ($reservationId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // UPDATED: Delete related records (new schema)
                
                // Delete bill items
                $stmt = $pdo->prepare("
                    DELETE bi FROM bill_items bi
                    INNER JOIN bills b ON bi.bill_id = b.bill_id
                    WHERE b.reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete payment transactions
                $stmt = $pdo->prepare("
                    DELETE pt FROM payment_transactions pt
                    INNER JOIN bills b ON pt.bill_id = b.bill_id
                    WHERE b.reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete bills
                $stmt = $pdo->prepare("
                    DELETE FROM bills
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Get availability_id before deleting reservation
                $stmt = $pdo->prepare("SELECT availability_id FROM reservations WHERE reservation_id = ?");
                $stmt->execute([$reservationId]);
                $res = $stmt->fetch();
                $availabilityId = $res ? $res['availability_id'] : null;
                
                // Delete reservation
                $stmt = $pdo->prepare("
                    DELETE FROM reservations
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete table_availability
                if ($availabilityId) {
                    $stmt = $pdo->prepare("DELETE FROM table_availability WHERE availability_id = ?");
                    $stmt->execute([$availabilityId]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservation deleted successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting reservation: ' . $e->getMessage()
                ]);
            }
            break;
            
        // ============================================
        // TABLE MANAGEMENT
        // ============================================
        
        case 'get_tables':
            // UPDATED: Use restaurant_tables and table_availability
            $stmt = $pdo->prepare("
                SELECT 
                    rt.*,
                    COUNT(CASE WHEN ta.reservation_date = CURDATE() THEN 1 END) as bookings_today
                FROM restaurant_tables rt
                LEFT JOIN table_availability ta ON rt.table_id = ta.table_id
                GROUP BY rt.table_id
                ORDER BY rt.table_number
            ");
            $stmt->execute();
            $tables = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tables' => $tables
            ]);
            break;
            
        case 'get_table_availability':
            // UPDATED: Use table_availability for reservations
            $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
            
            // Get all tables
            $stmt = $pdo->prepare("
                SELECT table_id, table_number, capacity
                FROM restaurant_tables
                ORDER BY table_number
            ");
            $stmt->execute();
            $tables = $stmt->fetchAll();
            
            // Get reservations for the selected date
            $stmt = $pdo->prepare("
                SELECT ta.table_id, ta.reservation_time, mem.member_name as full_name, r.party_size
                FROM table_availability ta
                JOIN reservations r ON ta.availability_id = r.availability_id
                LEFT JOIN memberships mem ON r.member_id = mem.member_id
                WHERE ta.reservation_date = ?
                ORDER BY ta.reservation_time
            ");
            $stmt->execute([$date]);
            $reservations = $stmt->fetchAll();
            
            // Time slots
            $timeSlots = [
                '16:00:00' => '4:00 PM',
                '18:00:00' => '6:00 PM',
                '20:00:00' => '8:00 PM',
                '22:00:00' => '10:00 PM',
            ];
            
            // Build availability map
            $availability = [];
            foreach ($tables as $table) {
                $availability[$table['table_id']] = [];
                foreach ($timeSlots as $time => $label) {
                    $isBooked = false;
                    $customer = null;
                    $partySize = null;
                    
                    foreach ($reservations as $res) {
                        if ($res['table_id'] == $table['table_id'] && $res['reservation_time'] == $time) {
                            $isBooked = true;
                            $customer = $res['full_name'] ?? 'Guest';
                            $partySize = $res['party_size'];
                            break;
                        }
                    }
                    
                    $availability[$table['table_id']][$time] = [
                        'status' => $isBooked ? 'occupied' : 'available',
                        'customer' => $customer,
                        'party_size' => $partySize,
                        'label' => $label
                    ];
                }
            }
            
            // Calculate stats
            $totalSlots = count($tables) * count($timeSlots);
            $occupiedSlots = 0;
            foreach ($availability as $tableSlots) {
                foreach ($tableSlots as $slot) {
                    if ($slot['status'] === 'occupied') $occupiedSlots++;
                }
            }
            $availableSlots = $totalSlots - $occupiedSlots;
            $occupancyRate = $totalSlots > 0 ? ($occupiedSlots / $totalSlots) * 100 : 0;
            
            echo json_encode([
                'success' => true,
                'availability' => $availability,
                'tables' => $tables,
                'timeSlots' => $timeSlots,
                'stats' => [
                    'total_tables' => count($tables),
                    'total_slots' => $totalSlots,
                    'occupied_slots' => $occupiedSlots,
                    'available_slots' => $availableSlots,
                    'occupancy_rate' => round($occupancyRate, 1)
                ],
                'date' => $date
            ]);
            break;
            
        // ============================================
        // PAYMENT VERIFICATION
        // ============================================
        
        case 'get_payments_by_date':
            $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
            
            try {
                // UPDATED: Use new schema - payment_transactions -> bills -> reservations
                $stmt = $pdo->prepare("
                    SELECT pt.*, r.reservation_id, mem.member_name as full_name, mem.phone, rt.table_number
                    FROM payment_transactions pt
                    JOIN bills b ON pt.bill_id = b.bill_id
                    JOIN reservations r ON b.reservation_id = r.reservation_id
                    LEFT JOIN memberships mem ON r.member_id = mem.member_id
                    LEFT JOIN table_availability ta ON r.availability_id = ta.availability_id
                    LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                    WHERE pt.payment_status = 'pending'
                    AND DATE(pt.created_at) = ?
                    ORDER BY pt.created_at DESC
                ");
                $stmt->execute([$date]);
                $payments = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'payments' => $payments,
                    'date' => $date
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching payments: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'get_pending_payments':
            // UPDATED: Use new schema
            $stmt = $pdo->prepare("
                SELECT 
                    pt.*,
                    mem.member_name as full_name,
                    mem.email,
                    mem.phone,
                    ta.reservation_date,
                    ta.reservation_time,
                    rt.table_number,
                    b.bill_id
                FROM payment_transactions pt
                JOIN bills b ON pt.bill_id = b.bill_id
                JOIN reservations r ON b.reservation_id = r.reservation_id
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                LEFT JOIN memberships mem ON r.member_id = mem.member_id
                LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                WHERE pt.payment_status = 'pending'
                ORDER BY pt.created_at DESC
            ");
            $stmt->execute();
            $payments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'payments' => $payments
            ]);
            break;
            
        case 'get_bill_items':
            $reservationId = intval($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
            
            if ($reservationId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid reservation ID'
                ]);
                break;
            }
            
            try {
                // UPDATED: Use menu (lowercase) and bill_items
                $stmt = $pdo->prepare("
                    SELECT 
                        bi.bill_item_id,
                        bi.item_id,
                        bi.quantity,
                        bi.unit_price,
                        m.name as item_name,
                        m.category,
                        (bi.quantity * bi.unit_price) as item_total
                    FROM bill_items bi
                    JOIN bills b ON bi.bill_id = b.bill_id
                    JOIN menu m ON bi.item_id = m.item_id
                    WHERE b.reservation_id = ?
                    ORDER BY bi.bill_item_id
                ");
                $stmt->execute([$reservationId]);
                $billItems = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'items' => $billItems
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching bill items: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'verify_payment':
            $transactionId = intval($_POST['transaction_id'] ?? 0);
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            $accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? null;
            
            if ($transactionId <= 0 || !$accountId) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // UPDATED: Get staff_id from account_id
                $stmt = $pdo->prepare("SELECT staff_id FROM staffs WHERE account_id = ? LIMIT 1");
                $stmt->execute([$accountId]);
                $staff = $stmt->fetch();
                
                if (!$staff) {
                    throw new Exception('Staff member not found');
                }
                
                $staffId = $staff['staff_id'];
                
                // Get reservation_id from transaction if not provided
                if ($reservationId <= 0) {
                    $stmt = $pdo->prepare("
                        SELECT b.reservation_id 
                        FROM payment_transactions pt
                        JOIN bills b ON pt.bill_id = b.bill_id
                        WHERE pt.transaction_id = ?
                    ");
                    $stmt->execute([$transactionId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        $reservationId = intval($result['reservation_id']);
                    }
                }
                
                if ($reservationId <= 0) {
                    throw new Exception('Reservation ID not found');
                }
                
                // Update payment with staff_id
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET payment_status = 'verified',
                        verified_by = ?,
                        verified_at = NOW()
                    WHERE transaction_id = ?
                ");
                $stmt->execute([$staffId, $transactionId]);
                
                // Get bill_id
                $stmt = $pdo->prepare("SELECT bill_id FROM bills WHERE reservation_id = ? AND bill_id IN (SELECT bill_id FROM payment_transactions WHERE transaction_id = ?)");
                $stmt->execute([$reservationId, $transactionId]);
                $bill = $stmt->fetch();
                $billId = $bill ? $bill['bill_id'] : null;
                
                // Update Bills payment_status
                if ($billId) {
                    try {
                        $stmt = $pdo->prepare("UPDATE bills SET payment_status = 'verified' WHERE bill_id = ?");
                        $stmt->execute([$billId]);
                    } catch (PDOException $e) {
                        // Column may not exist
                    }
                }
                
                // UPDATED: Check if kitchen orders already exist for this bill
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as order_count
                    FROM kitchen k
                    JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
                    JOIN bills b ON bi.bill_id = b.bill_id
                    WHERE b.reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                $existingOrders = $stmt->fetch();
                
                // Only create kitchen orders if they don't exist for this reservation
                if ($existingOrders['order_count'] == 0) {
                    // Get bill items
                    $stmt = $pdo->prepare("
                        SELECT 
                            bi.bill_item_id,
                            bi.item_id,
                            bi.quantity
                        FROM bill_items bi
                        JOIN bills b ON bi.bill_id = b.bill_id
                        WHERE b.reservation_id = ?
                    ");
                    $stmt->execute([$reservationId]);
                    $billItems = $stmt->fetchAll();
                    
                    $kitchenOrdersCreated = 0;
                    
                    // Kitchen table only has: bill_item_id, item_id, quantity, status, confirm_order
                    foreach ($billItems as $item) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO kitchen (bill_item_id, item_id, quantity, confirm_order, status)
                                VALUES (?, ?, ?, 0, 'preparing')
                            ");
                            $stmt->execute([
                                $item['bill_item_id'],
                                $item['item_id'],
                                $item['quantity']
                            ]);
                            $kitchenOrdersCreated++;
                        } catch (PDOException $e) {
                            error_log('Error inserting kitchen order: ' . $e->getMessage());
                        }
                    }
                    
                    $pdo->commit();
                    
                    echo json_encode([
                        'status' => 'success',
                        'success' => true,
                        'message' => "Payment verified! {$kitchenOrdersCreated} order(s) sent to kitchen.",
                        'data' => [
                            'kitchen_orders_created' => $kitchenOrdersCreated,
                            'total_items' => count($billItems)
                        ]
                    ]);
                } else {
                    $pdo->commit();
                    
                    echo json_encode([
                        'status' => 'success',
                        'success' => true,
                        'message' => 'Payment verified! (Orders already in kitchen)',
                        'data' => [
                            'kitchen_orders_created' => 0,
                            'existing_orders' => $existingOrders['order_count']
                        ]
                    ]);
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
        
        case 'reject_payment':
            $transactionId = intval($_POST['transaction_id'] ?? 0);
            
            if ($transactionId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid transaction']);
                break;
            }
            
            $stmt = $pdo->prepare("
                UPDATE payment_transactions
                SET payment_status = 'failed'
                WHERE transaction_id = ?
            ");
            $stmt->execute([$transactionId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment rejected'
            ]);
            break;

        // ============================================
        // KITCHEN ORDERS
        // ============================================
        
        case 'get_kitchen_orders':
            try {
                // UPDATED: Kitchen table doesn't have reservation_id, table_id, reservation_time
                // Join through bill_items -> bills -> reservations -> table_availability -> restaurant_tables
                $stmt = $pdo->prepare("
                    SELECT 
                        k.kitchen_id,
                        k.bill_item_id,
                        r.reservation_id,
                        ta.table_id,
                        k.item_id,
                        k.quantity,
                        ta.reservation_time,
                        k.confirm_order,
                        k.status,
                        m.name as item_name,
                        m.category,
                        m.price,
                        rt.table_number,
                        r.party_size,
                        ta.reservation_date,
                        mem.member_name as customer_name
                    FROM kitchen k
                    JOIN menu m ON k.item_id = m.item_id
                    JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
                    JOIN bills b ON bi.bill_id = b.bill_id
                    JOIN reservations r ON b.reservation_id = r.reservation_id
                    JOIN table_availability ta ON r.availability_id = ta.availability_id
                    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                    LEFT JOIN memberships mem ON r.member_id = mem.member_id
                    WHERE (ta.reservation_date = CURDATE() OR ta.reservation_date IS NULL)
                    GROUP BY r.reservation_id, k.item_id, ta.table_id
                    ORDER BY 
                        CASE 
                            WHEN ta.reservation_time IS NOT NULL THEN ta.reservation_time
                            ELSE '23:59:59'
                        END ASC,
                        k.kitchen_id DESC
                ");
                
                $stmt->execute();
                $orders = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'orders' => $orders
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'orders' => []
                ]);
            }
            break;
            
        case 'update_kitchen_status':
            $kitchenId = intval($_POST['kitchen_id'] ?? 0);
            $status = trim($_POST['status'] ?? 'preparing');
            
            if ($kitchenId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid kitchen order ID']);
                break;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE kitchen SET status = ? WHERE kitchen_id = ?");
                $stmt->execute([$status, $kitchenId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Kitchen order status updated'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating status: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'add_kitchen_order':
            echo json_encode([
                'success' => false,
                'message' => 'Direct kitchen orders not supported. Please use the reservation/order system.'
            ]);
            break;
            
        case 'complete_order':
            $orderId = intval($_POST['order_id'] ?? 0);
            
            if ($orderId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
                break;
            }
            
            try {
                // UPDATED: Kitchen table has status column, just update by kitchen_id
                $stmt = $pdo->prepare("UPDATE kitchen SET status = 'completed', confirm_order = 1 WHERE kitchen_id = ?");
                $stmt->execute([$orderId]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order completed - ready to serve'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error completing order: ' . $e->getMessage()
                ]);
            }
            break;
        
        // ============================================
        // MEMBER MANAGEMENT
        // ============================================
        
        case 'get_members':
            // UPDATED: Use accounts -> memberships (new schema)
            $stmt = $pdo->prepare("
                SELECT 
                    a.account_id as user_id,
                    a.username,
                    mem.member_id,
                    mem.member_name as full_name,
                    mem.email,
                    mem.phone,
                    a.register_time,
                    mem.points,
                    COUNT(DISTINCT r.reservation_id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_spent
                FROM accounts a
                JOIN memberships mem ON a.account_id = mem.account_id
                LEFT JOIN reservations r ON mem.member_id = r.member_id
                LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                WHERE a.role = 'customer'
                GROUP BY a.account_id, mem.member_id
                ORDER BY mem.points DESC
            ");
            $stmt->execute();
            $members = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'members' => $members
            ]);
            break;
            
        case 'update_member_points':
            $memberId = intval($_POST['member_id'] ?? $_POST['user_id'] ?? 0);
            
            if ($memberId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid member']);
                break;
            }
            
            // UPDATED: Use memberships table with member_id
            $points = intval($_POST['points'] ?? 0);
            $stmt = $pdo->prepare("
                UPDATE memberships
                SET points = ?
                WHERE member_id = ?
            ");
            $stmt->execute([$points, $memberId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Points updated successfully'
            ]);
            break;
            
        case 'get_member_history':
            $memberId = intval($_GET['member_id'] ?? $_GET['user_id'] ?? 0);
            
            if ($memberId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid member']);
                break;
            }
            
            // UPDATED: Use new schema
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    rt.table_number,
                    b.total_amount,
                    pt.payment_status,
                    pt.payment_method,
                    ta.reservation_date,
                    ta.reservation_time
                FROM reservations r
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.bill_id = pt.bill_id
                WHERE r.member_id = ?
                ORDER BY ta.reservation_date DESC, ta.reservation_time DESC
            ");
            $stmt->execute([$memberId]);
            $history = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'get_member_orders':
            $memberId = intval($_GET['member_id'] ?? $_POST['member_id'] ?? 0);
            
            if ($memberId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid member ID'
                ]);
                break;
            }
            
            try {
                // UPDATED: Use new schema
                $stmt = $pdo->prepare("
                    SELECT 
                        r.reservation_id,
                        ta.reservation_date,
                        ta.reservation_time,
                        r.party_size,
                        rt.table_number,
                        b.bill_id,
                        b.total_amount
                    FROM reservations r
                    JOIN table_availability ta ON r.availability_id = ta.availability_id
                    LEFT JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                    LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                    WHERE r.member_id = ?
                    ORDER BY ta.reservation_date DESC, ta.reservation_time DESC
                ");
                $stmt->execute([$memberId]);
                $reservations = $stmt->fetchAll();
                
                $orders = [];
                $totalSpent = 0;
                
                foreach ($reservations as $reservation) {
                    $order = [
                        'reservation_id' => $reservation['reservation_id'],
                        'reservation_date' => $reservation['reservation_date'],
                        'reservation_time' => $reservation['reservation_time'],
                        'party_size' => $reservation['party_size'],
                        'table_number' => $reservation['table_number'] ?? 'N/A',
                        'total_amount' => $reservation['total_amount'] ?? 0,
                        'items' => []
                    ];
                    
                    // Get items for this reservation if bill exists
                    if ($reservation['bill_id']) {
                        $stmt = $pdo->prepare("
                            SELECT 
                                bi.quantity,
                                bi.unit_price,
                                m.name as item_name,
                                (bi.quantity * bi.unit_price) as item_total
                            FROM bill_items bi
                            JOIN menu m ON bi.item_id = m.item_id
                            WHERE bi.bill_id = ?
                        ");
                        $stmt->execute([$reservation['bill_id']]);
                        $items = $stmt->fetchAll();
                        
                        $order['items'] = $items;
                    }
                    
                    if ($reservation['total_amount']) {
                        $totalSpent += floatval($reservation['total_amount']);
                    }
                    
                    $orders[] = $order;
                }
                
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'orders' => $orders,
                    'total_spent' => number_format($totalSpent, 2)
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error loading orders: ' . $e->getMessage()
                ]);
            }
            break;
            
        // ============================================
        // STATISTICS & REPORTS
        // ============================================
        
        case 'get_dashboard_stats':
            // UPDATED: Use new schema
            // Today's reservations
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM reservations r
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                WHERE ta.reservation_date = CURDATE()
            ");
            $stmt->execute();
            $todayReservations = $stmt->fetch()['count'];
            
            // Today's revenue
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(pt.amount), 0) as total
                FROM payment_transactions pt
                JOIN bills b ON pt.bill_id = b.bill_id
                JOIN reservations r ON b.reservation_id = r.reservation_id
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                WHERE ta.reservation_date = CURDATE()
                AND pt.payment_status = 'verified'
            ");
            $stmt->execute();
            $todayRevenue = $stmt->fetch()['total'];
            
            // Pending payments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_transactions WHERE payment_status = 'pending'");
            $stmt->execute();
            $pendingPayments = $stmt->fetch()['count'];
            
            // Kitchen orders (count all orders)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM kitchen WHERE status != 'served'");
            $stmt->execute();
            $activeOrders = $stmt->fetch()['count'];
            
            // Total members
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM memberships");
            $stmt->execute();
            $totalMembers = $stmt->fetch()['count'];
            
            // Tables occupied today
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT ta.table_id) as count 
                FROM table_availability ta
                WHERE ta.reservation_date = CURDATE()
            ");
            $stmt->execute();
            $tablesOccupied = $stmt->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'today_reservations' => $todayReservations,
                    'today_revenue' => number_format($todayRevenue, 2),
                    'pending_payments' => $pendingPayments,
                    'active_orders' => $activeOrders,
                    'total_members' => $totalMembers,
                    'tables_occupied' => $tablesOccupied
                ]
            ]);
            break;
        
        // ============================================
        // MENU ITEM MANAGEMENT
        // ============================================
        
        case 'add_menu_item':
            $itemId = trim($_POST['item_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            
            // Validation
            if (empty($itemId) || empty($name) || empty($category) || empty($description) || $price <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required and price must be greater than 0'
                ]);
                break;
            }
            
            // UPDATED: Use menu (lowercase)
            $stmt = $pdo->prepare("SELECT item_id FROM menu WHERE item_id = ?");
            $stmt->execute([$itemId]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Item ID already exists. Please use a different ID.'
                ]);
                break;
            }
            
            // Insert new menu item
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO menu (item_id, name, category, description, price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$itemId, $name, $category, $description, $price]);
            
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item added successfully!'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error adding menu item: ' . $e->getMessage()
                ]);
            }
            break;
        
        case 'update_menu_item':
            $itemId = trim($_POST['item_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            
            // Validation
            if (empty($itemId) || empty($name) || empty($category) || empty($description) || $price <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required and price must be greater than 0'
                ]);
                break;
            }
            
            // UPDATED: Use menu (lowercase)
            $stmt = $pdo->prepare("SELECT item_id FROM menu WHERE item_id = ?");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Menu item not found.'
                ]);
                break;
            }
            
            // Update menu item
            try {
                $stmt = $pdo->prepare("
                    UPDATE menu 
                    SET name = ?, category = ?, description = ?, price = ?
                    WHERE item_id = ?
                ");
                $stmt->execute([$name, $category, $description, $price, $itemId]);
            
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully!'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating menu item: ' . $e->getMessage()
                ]);
            }
            break;
        
        case 'delete_menu_item':
            $itemId = trim($_POST['item_id'] ?? '');
            
            // Validation
            if (empty($itemId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Item ID is required'
                ]);
                break;
            }
            
            // UPDATED: Use menu (lowercase)
            $stmt = $pdo->prepare("SELECT item_id FROM menu WHERE item_id = ?");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Menu item not found.'
                ]);
                break;
            }
            
            // Delete menu item
            try {
                $stmt = $pdo->prepare("DELETE FROM menu WHERE item_id = ?");
                $stmt->execute([$itemId]);
            
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully!'
                ]);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'foreign key') !== false) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Cannot delete this item because it is being used in orders or bills.'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error deleting menu item: ' . $e->getMessage()
                    ]);
                }
            }
            break;
        
        case 'get_staff_details':
            $accountId = intval($_GET['user_id'] ?? $_GET['account_id'] ?? 0);
            
            if ($accountId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid account ID'
                ]);
                break;
            }
            
            try {
                // UPDATED: Use accounts and staffs
                $stmt = $pdo->prepare("
                    SELECT a.account_id as user_id, a.username, s.staff_name as full_name, s.email, s.phone, s.role
                    FROM accounts a
                    JOIN staffs s ON a.account_id = s.account_id
                    WHERE a.account_id = ? AND a.role = 'staff'
                ");
                $stmt->execute([$accountId]);
                $staff = $stmt->fetch();
                
                if (!$staff) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Staff member not found'
                    ]);
                    break;
                }
                
                echo json_encode([
                    'success' => true,
                    'staff' => $staff
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching staff details: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'update_staff':
            $accountId = intval($_POST['user_id'] ?? $_POST['account_id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = trim($_POST['role'] ?? '');
            
            if ($accountId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid account ID'
                ]);
                break;
            }
            
            if (empty($fullName) || empty($phone) || empty($role)) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'All fields are required'
                ]);
                break;
            }
            
            // Validate role
            $validRoles = ['waiter', 'chef', 'manager', 'cashier'];
            if (!in_array($role, $validRoles)) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid role'
                ]);
                break;
            }
            
            try {
                $pdo->beginTransaction();
                
                // UPDATED: Update staffs table (accounts doesn't have full_name, phone)
                $stmt = $pdo->prepare("
                    UPDATE staffs 
                    SET staff_name = ?, phone = ?, role = ?
                    WHERE account_id = ?
                ");
                $stmt->execute([$fullName, $phone, $role, $accountId]);
                
                if ($stmt->rowCount() == 0) {
                    // Create new Staff record if it doesn't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO staffs (staff_name, account_id, role, phone)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$fullName, $accountId, $role, $phone]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Staff member updated successfully'
                ]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error updating staff: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'delete_staff':
            $accountId = intval($_POST['user_id'] ?? $_POST['account_id'] ?? 0);
            
            if ($accountId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid account ID'
                ]);
                break;
            }
            
            // Check if user exists and is a staff member
            $stmt = $pdo->prepare("SELECT account_id, role FROM accounts WHERE account_id = ?");
            $stmt->execute([$accountId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'User not found'
                ]);
                break;
            }
            
            if ($user['role'] !== 'staff') {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'User is not a staff member'
                ]);
                break;
            }
            
            try {
                $pdo->beginTransaction();
                
                // UPDATED: Delete from staffs table first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM staffs WHERE account_id = ?");
                $stmt->execute([$accountId]);
                
                // Delete from accounts table
                $stmt = $pdo->prepare("DELETE FROM accounts WHERE account_id = ?");
                $stmt->execute([$accountId]);
                
                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Staff member deleted successfully'
                ]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error deleting staff: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'delete_member':
            $accountId = intval($_POST['user_id'] ?? $_POST['account_id'] ?? 0);
            
            if ($accountId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid account ID'
                ]);
                break;
            }
            
            // Check if user exists and is a member (not staff)
            $stmt = $pdo->prepare("SELECT account_id, role FROM accounts WHERE account_id = ?");
            $stmt->execute([$accountId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'User not found'
                ]);
                break;
            }
            
            if ($user['role'] === 'staff') {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Cannot delete staff members from this section'
                ]);
                break;
            }
            
            try {
                $pdo->beginTransaction();
                
                // UPDATED: Delete from memberships table first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM memberships WHERE account_id = ?");
                $stmt->execute([$accountId]);
                
                // Delete from accounts table (this will cascade to reservations if foreign keys are set up)
                $stmt = $pdo->prepare("DELETE FROM accounts WHERE account_id = ?");
                $stmt->execute([$accountId]);
                
                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Member deleted successfully'
                ]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error deleting member: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'register_staff':
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $staffRole = trim($_POST['staff_role'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = trim($_POST['role'] ?? 'staff');
            
            // Validation
            if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($phone) || empty($staffRole) || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'All fields are required'
                ]);
                break;
            }
            
            if (strlen($password) < 8) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Password must be at least 8 characters'
                ]);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // UPDATED: Check if username already exists in accounts
                $stmt = $pdo->prepare("SELECT account_id FROM accounts WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already exists');
                }
                
                // Check if email already exists in staffs
                $stmt = $pdo->prepare("SELECT staff_id FROM staffs WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                // Create account
                $fullName = $firstName . ' ' . $lastName;
                
                $stmt = $pdo->prepare("
                    INSERT INTO accounts (username, password, role, account_status)
                    VALUES (?, ?, ?, 'active')
                ");
                $stmt->execute([$username, $password, $role]);
                $accountId = $pdo->lastInsertId();
                
                // Create staff record
                $stmt = $pdo->prepare("
                    INSERT INTO staffs (staff_name, account_id, role, email, phone)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$fullName, $accountId, $staffRole, $email, $phone]);
                
                $pdo->commit();
                
                // Get the newly created staff member data
                $stmt = $pdo->prepare("
                    SELECT a.account_id as user_id, a.username, s.staff_name as full_name, s.email, s.phone, a.register_time, s.role
                    FROM accounts a
                    JOIN staffs s ON a.account_id = s.account_id
                    WHERE a.account_id = ?
                ");
                $stmt->execute([$accountId]);
                $newStaff = $stmt->fetch();
                
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Staff account created successfully!',
                    'staff' => $newStaff
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
        
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action: ' . $action
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log('Staff Action Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
