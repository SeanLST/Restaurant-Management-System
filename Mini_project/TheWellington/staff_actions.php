<?php
/* ========================================================================
   UPDATED STAFF ACTIONS - COMPLETE INTEGRATION
   Properly integrated with new reservation system and database
   Replace your existing staff_actions.php with this file
   ======================================================================== */

session_start();

// Database connection
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
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}

$pdo = getDBConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ============================================
        // RESERVATION MANAGEMENT
        // ============================================
        
        case 'get_reservations':
            // Get all reservations with complete details
            // Get user info from Users table via JOIN (full_name, email, phone removed from Reservations)
            $stmt = $pdo->prepare("
                SELECT 
                    r.reservation_id,
                    r.user_id,
                    u.full_name,
                    u.email,
                    u.phone,
                    r.reservation_date,
                    r.reservation_time,
                    r.party_size,
                    r.table_id,
                    r.created_at,
                    rt.table_number,
                    rt.capacity,
                    m.points as member_points,
                    pt.payment_status,
                    pt.payment_method,
                    b.total_amount
                FROM Reservations r
                LEFT JOIN Users u ON r.user_id = u.user_id
                LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
                LEFT JOIN Memberships m ON r.user_id = m.user_id
                LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.transaction_id = pt.transaction_id
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
            ");
            $stmt->execute();
            $reservations = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'reservations' => $reservations
            ]);
            break;
            
        case 'get_todays_reservations':
            // Get today's reservations specifically
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    rt.table_number,
                    rt.capacity,
                    m.points as member_points,
                    pt.payment_status,
                    b.total_amount
                FROM Reservations r
                LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
                LEFT JOIN Memberships m ON r.user_id = m.user_id
                LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.transaction_id = pt.transaction_id
                WHERE r.reservation_date = CURDATE()
                ORDER BY r.reservation_time
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
                $stmt = $pdo->prepare("
                    SELECT 
                        r.*,
                        rt.table_number,
                        u.full_name,
                        u.phone,
                        m.points as member_points
                    FROM Reservations r
                    LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
                    LEFT JOIN Users u ON r.user_id = u.user_id
                    LEFT JOIN Memberships m ON r.user_id = m.user_id
                    WHERE r.reservation_date = ?
                    ORDER BY r.reservation_time ASC
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
            // full_name, email, phone, special_requests removed - user info is in Users table
            
            if ($reservationId <= 0 || empty($date) || empty($time)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                break;
            }
            
            // Check table availability (exclude current reservation)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM Reservations
                WHERE table_id = ?
                AND reservation_date = ?
                AND reservation_time = ?
                AND reservation_id != ?
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
            
            // Update reservation - only reservation-specific fields (user info is in Users table)
            $stmt = $pdo->prepare("
                UPDATE Reservations
                SET reservation_date = ?,
                    reservation_time = ?,
                    party_size = ?,
                    table_id = ?
                WHERE reservation_id = ?
            ");
            $stmt->execute([
                $date, $time, $partySize, $tableId, $reservationId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reservation updated successfully'
            ]);
            break;
            
        case 'delete_reservation':
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            
            if ($reservationId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Delete related records first (due to foreign key constraints)
                
                // Delete bill items
                $stmt = $pdo->prepare("
                    DELETE bi FROM Bill_Items bi
                    INNER JOIN Bills b ON bi.bill_id = b.bill_id
                    WHERE b.reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete payment transactions
                $stmt = $pdo->prepare("
                    DELETE FROM payment_transactions
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete bills
                $stmt = $pdo->prepare("
                    DELETE FROM Bills
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
                // Delete reservation
                $stmt = $pdo->prepare("
                    DELETE FROM Reservations
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                
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
            $stmt = $pdo->prepare("
                SELECT 
                    rt.*,
                    COUNT(CASE WHEN r.reservation_date = CURDATE() THEN 1 END) as bookings_today
                FROM restaurant_table rt
                LEFT JOIN Reservations r ON rt.table_id = r.table_id
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
            // Get table availability for a specific date with time slots
            $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
            
            // Get all tables
            $stmt = $pdo->prepare("
                SELECT table_id, table_number, capacity
                FROM restaurant_table
                ORDER BY table_number
            ");
            $stmt->execute();
            $tables = $stmt->fetchAll();
            
            // Get reservations for the selected date
            $stmt = $pdo->prepare("
                SELECT r.table_id, r.reservation_time, u.full_name, r.party_size
                FROM Reservations r
                LEFT JOIN Users u ON r.user_id = u.user_id
                WHERE r.reservation_date = ?
                ORDER BY r.reservation_time
            ");
            $stmt->execute([$date]);
            $reservations = $stmt->fetchAll();
            
            // Time slots (same as reservation page)
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
                $stmt = $pdo->prepare("
                    SELECT pt.*, pt.reservation_id, u.full_name, u.phone, rt.table_number
                    FROM payment_transactions pt
                    JOIN Reservations r ON pt.reservation_id = r.reservation_id
                    LEFT JOIN Users u ON r.user_id = u.user_id
                    LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
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
            $stmt = $pdo->prepare("
                SELECT 
                    pt.*,
                    u.full_name,
                    u.email,
                    u.phone,
                    r.reservation_date,
                    r.reservation_time,
                    rt.table_number,
                    b.bill_id
                FROM payment_transactions pt
                JOIN Reservations r ON pt.reservation_id = r.reservation_id
                LEFT JOIN Users u ON r.user_id = u.user_id
                JOIN Bills b ON pt.transaction_id = b.transaction_id
                LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
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
                // Get bill items with item names from Menu table
                $stmt = $pdo->prepare("
                    SELECT 
                        bi.bill_item_id,
                        bi.item_id,
                        bi.quantity,
                        bi.unit_price,
                        m.name as item_name,
                        m.category,
                        (bi.quantity * bi.unit_price) as item_total
                    FROM Bill_Items bi
                    JOIN Bills b ON bi.bill_id = b.bill_id
                    JOIN Menu m ON bi.item_id = m.item_id
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
            $staffId = $_SESSION['user_id'] ?? null;
            
            if ($transactionId <= 0 || !$staffId) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Get reservation_id from transaction if not provided
                if ($reservationId <= 0) {
                    $stmt = $pdo->prepare("SELECT reservation_id FROM payment_transactions WHERE transaction_id = ?");
                    $stmt->execute([$transactionId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        $reservationId = intval($result['reservation_id']);
                    }
                }
                
                if ($reservationId <= 0) {
                    throw new Exception('Reservation ID not found');
                }
                
                // Update payment status
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET payment_status = 'verified',
                        verified_by = ?,
                        verified_at = NOW()
                    WHERE transaction_id = ?
                ");
                $stmt->execute([$staffId, $transactionId]);
                
                // Get bill_id
                $stmt = $pdo->prepare("SELECT bill_id FROM Bills WHERE reservation_id = ? AND transaction_id = ?");
                $stmt->execute([$reservationId, $transactionId]);
                $bill = $stmt->fetch();
                $billId = $bill ? $bill['bill_id'] : null;
                
                // Update Bills payment_status
                if ($billId) {
                    try {
                        $stmt = $pdo->prepare("UPDATE Bills SET payment_status = 'verified' WHERE bill_id = ?");
                        $stmt->execute([$billId]);
                    } catch (PDOException $e) {
                        // Column may not exist
                    }
                }
                
                // **CRITICAL FIX**: Check if kitchen orders already exist for THIS SPECIFIC reservation
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as order_count
                    FROM Kitchen
                    WHERE reservation_id = ?
                ");
                $stmt->execute([$reservationId]);
                $existingOrders = $stmt->fetch();
                
                // Only create kitchen orders if they don't exist for this reservation
                if ($existingOrders['order_count'] == 0) {
                    // Get bill items with reservation details including reservation_time
                    $stmt = $pdo->prepare("
                        SELECT 
                            bi.bill_item_id,
                            bi.item_id,
                            bi.quantity,
                            r.table_id,
                            r.reservation_id,
                            r.reservation_time
                        FROM Bill_Items bi
                        JOIN Bills b ON bi.bill_id = b.bill_id
                        JOIN Reservations r ON b.reservation_id = r.reservation_id
                        WHERE b.reservation_id = ?
                    ");
                    $stmt->execute([$reservationId]);
                    $billItems = $stmt->fetchAll();
                    
                    $kitchenOrdersCreated = 0;
                    
                    // **CRITICAL FIX**: Insert with all required fields matching the Kitchen table schema
                    foreach ($billItems as $item) {
                        try {
                            // Insert with all fields: bill_item_id, reservation_id, table_id, item_id, quantity, reservation_time, confirm_order, status, order_time
                            $stmt = $pdo->prepare("
                                INSERT INTO Kitchen (
                                    bill_item_id,
                                    reservation_id,
                                    table_id,
                                    item_id,
                                    quantity,
                                    reservation_time,
                                    confirm_order,
                                    status,
                                    order_time
                                )
                                VALUES (?, ?, ?, ?, ?, ?, 0, 'preparing', NOW())
                            ");
                            $stmt->execute([
                                $item['bill_item_id'],
                                $item['reservation_id'],
                                $item['table_id'],
                                $item['item_id'],
                                $item['quantity'],
                                $item['reservation_time']
                            ]);
                            $kitchenOrdersCreated++;
                        } catch (PDOException $e) {
                            error_log('Error inserting kitchen order: ' . $e->getMessage());
                            // If error, try without optional fields
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO Kitchen (
                                        reservation_id,
                                        table_id,
                                        item_id,
                                        quantity,
                                        reservation_time,
                                        status,
                                        order_time
                                    )
                                    VALUES (?, ?, ?, ?, ?, 'preparing', NOW())
                                ");
                                $stmt->execute([
                                    $item['reservation_id'],
                                    $item['table_id'],
                                    $item['item_id'],
                                    $item['quantity'],
                                    $item['reservation_time']
                                ]);
                                $kitchenOrdersCreated++;
                            } catch (PDOException $e2) {
                                error_log('Error inserting kitchen order (fallback): ' . $e2->getMessage());
                            }
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
                // **CRITICAL FIX**: Use reservation_id and reservation_time from Kitchen table
                // This ensures each order shows only once with the correct time
                $stmt = $pdo->prepare("
                    SELECT 
                        k.kitchen_id,
                        k.bill_item_id,
                        k.reservation_id,
                        k.table_id,
                        k.item_id,
                        k.quantity,
                        k.reservation_time,
                        k.confirm_order,
                        k.status,
                        m.name as item_name,
                        m.category,
                        m.price,
                        rt.table_number,
                        r.party_size,
                        r.reservation_date,
                        u.full_name as customer_name
                    FROM Kitchen k
                    JOIN Menu m ON k.item_id = m.item_id
                    JOIN restaurant_table rt ON k.table_id = rt.table_id
                    LEFT JOIN Reservations r ON k.reservation_id = r.reservation_id
                    LEFT JOIN Users u ON r.user_id = u.user_id
                    WHERE (r.reservation_date = CURDATE() OR r.reservation_date IS NULL)
                    GROUP BY k.reservation_id, k.item_id, k.table_id
                    ORDER BY 
                        CASE 
                            WHEN k.reservation_time IS NOT NULL THEN k.reservation_time
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
            // Kitchen table doesn't have status column
            // This functionality is not available without status column
            echo json_encode([
                'success' => false,
                'message' => 'Kitchen status update not available - status column does not exist in Kitchen table'
            ]);
            break;
            
        case 'add_kitchen_order':
            $tableId = intval($_POST['table_id'] ?? 0);
            $itemId = trim($_POST['item_id'] ?? '');
            $quantity = intval($_POST['quantity'] ?? 1);
            $notes = trim($_POST['notes'] ?? '');
            
            if ($tableId <= 0 || empty($itemId) || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                break;
            }
            
            // Kitchen table doesn't have status or notes columns
            $stmt = $pdo->prepare("
                INSERT INTO Kitchen (table_id, item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$tableId, $itemId, $quantity]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Order sent to kitchen',
                'kitchen_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'complete_order':
            $orderId = intval($_POST['order_id'] ?? 0);
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            $itemId = trim($_POST['item_id'] ?? '');
            $tableId = intval($_POST['table_id'] ?? 0);
            
            if ($orderId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
                break;
            }
            
            try {
                // Check if status column exists, if not, add it
                try {
                    $stmt = $pdo->query("SHOW COLUMNS FROM Kitchen LIKE 'status'");
                    if ($stmt->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE Kitchen ADD COLUMN status VARCHAR(20) DEFAULT 'preparing'");
                    }
                } catch (PDOException $e) {
                    // Column might already exist or table doesn't exist, continue
                }
                
                // First try to update by kitchen_id (for single orders)
                $stmt = $pdo->prepare("UPDATE Kitchen SET status = 'completed' WHERE kitchen_id = ?");
                $stmt->execute([$orderId]);
                
                // If no rows updated and we have reservation_id/item_id/table_id, update all matching orders
                if ($stmt->rowCount() == 0 && ($reservationId > 0 || !empty($itemId) || $tableId > 0)) {
                    // Get the order details first to find matching orders
                    $stmt = $pdo->prepare("SELECT reservation_id, item_id, table_id FROM Kitchen WHERE kitchen_id = ?");
                    $stmt->execute([$orderId]);
                    $orderDetails = $stmt->fetch();
                    
                    if ($orderDetails) {
                        // Update all orders matching this reservation_id, item_id, and table_id
                        $updateStmt = $pdo->prepare("
                            UPDATE Kitchen 
                            SET status = 'completed' 
                            WHERE reservation_id = ? 
                            AND item_id = ? 
                            AND table_id = ?
                            AND status != 'served'
                        ");
                        $updateStmt->execute([
                            $orderDetails['reservation_id'],
                            $orderDetails['item_id'],
                            $orderDetails['table_id']
                        ]);
                        
                        if ($updateStmt->rowCount() > 0) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Order completed - ready to serve'
                            ]);
                            break;
                        }
                    }
                }
                
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
                // Fallback: try to update by matching criteria
                try {
                    // Get order details
                    $stmt = $pdo->prepare("SELECT reservation_id, item_id, table_id FROM Kitchen WHERE kitchen_id = ?");
                    $stmt->execute([$orderId]);
                    $orderDetails = $stmt->fetch();
                    
                    if ($orderDetails) {
                        $updateStmt = $pdo->prepare("
                            UPDATE Kitchen 
                            SET status = 'completed' 
                            WHERE reservation_id = ? 
                            AND item_id = ? 
                            AND table_id = ?
                        ");
                        $updateStmt->execute([
                            $orderDetails['reservation_id'],
                            $orderDetails['item_id'],
                            $orderDetails['table_id']
                        ]);
                        
                        if ($updateStmt->rowCount() > 0) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Order completed - ready to serve'
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Order not found: ' . $e->getMessage()
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Order not found: ' . $e->getMessage()
                        ]);
                    }
                } catch (PDOException $e2) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error completing order: ' . $e2->getMessage()
                    ]);
                }
            }
            break;
        
        // ============================================
        // MEMBER MANAGEMENT
        // ============================================
        
        case 'get_members':
            $stmt = $pdo->prepare("
                SELECT 
                    u.user_id,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.register_time,
                    m.membership_id,
                    m.points,
                    COUNT(DISTINCT r.reservation_id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_spent
                FROM Users u
                LEFT JOIN Memberships m ON u.user_id = m.user_id
                LEFT JOIN Reservations r ON u.user_id = r.user_id
                LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
                WHERE u.role = 'customer'
                GROUP BY u.user_id
                ORDER BY m.points DESC
            ");
            $stmt->execute();
            $members = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'members' => $members
            ]);
            break;
            
        case 'update_member_points':
            $userId = intval($_POST['user_id'] ?? 0);
            $points = intval($_POST['points'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                break;
            }
            
            $stmt = $pdo->prepare("
                UPDATE Memberships
                SET points = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$points, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Points updated successfully'
            ]);
            break;
            
        case 'get_member_history':
            $userId = intval($_GET['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    rt.table_number,
                    b.total_amount,
                    pt.payment_status,
                    pt.payment_method
                FROM Reservations r
                LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
                LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
                LEFT JOIN payment_transactions pt ON b.transaction_id = pt.transaction_id
                WHERE r.user_id = ?
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
            ");
            $stmt->execute([$userId]);
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
                // Get all reservations for this member
            $stmt = $pdo->prepare("
                SELECT 
                    r.reservation_id,
                    r.reservation_date,
                    r.reservation_time,
                    r.party_size,
                    rt.table_number,
                    b.bill_id,
                        b.total_amount
                FROM Reservations r
                LEFT JOIN restaurant_table rt ON r.table_id = rt.table_id
                    LEFT JOIN Bills b ON r.reservation_id = b.reservation_id
                WHERE r.user_id = ?
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
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
                            FROM Bill_Items bi
                            JOIN Menu m ON bi.item_id = m.item_id
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
            // Today's reservations
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Reservations WHERE reservation_date = CURDATE()");
            $stmt->execute();
            $todayReservations = $stmt->fetch()['count'];
            
            // Today's revenue
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(b.total_amount), 0) as total
                FROM Bills b
                JOIN Reservations r ON b.reservation_id = r.reservation_id
                WHERE r.reservation_date = CURDATE()
            ");
            $stmt->execute();
            $todayRevenue = $stmt->fetch()['total'];
            
            // Pending payments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_transactions WHERE payment_status = 'pending'");
            $stmt->execute();
            $pendingPayments = $stmt->fetch()['count'];
            
            // Kitchen orders (count all orders since Kitchen table doesn't have time_submitted or status columns)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Kitchen");
            $stmt->execute();
            $activeOrders = $stmt->fetch()['count'];
            
            // Total members
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Memberships");
            $stmt->execute();
            $totalMembers = $stmt->fetch()['count'];
            
            // Tables occupied today
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT table_id) as count FROM Reservations WHERE reservation_date = CURDATE()");
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
            
            // Check if item_id already exists
            $stmt = $pdo->prepare("SELECT item_id FROM Menu WHERE item_id = ?");
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
                    INSERT INTO Menu (item_id, name, category, description, price)
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
            
            // Check if item exists
            $stmt = $pdo->prepare("SELECT item_id FROM Menu WHERE item_id = ?");
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
                UPDATE Menu 
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
            
            // Check if item exists
            $stmt = $pdo->prepare("SELECT item_id FROM Menu WHERE item_id = ?");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Menu item not found.'
                ]);
                break;
            }
            
            // Check if item is referenced in other tables (optional - for safety)
            // You might want to check if it's used in Bills, Kitchen, etc.
            // For now, we'll allow deletion and let foreign key constraints handle it
            
            // Delete menu item
            try {
                $stmt = $pdo->prepare("DELETE FROM Menu WHERE item_id = ?");
                $stmt->execute([$itemId]);
            
            echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully!'
                ]);
            } catch (PDOException $e) {
                // Check if deletion failed due to foreign key constraint
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
            $userId = intval($_GET['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ]);
                break;
            }
            
            try {
            $stmt = $pdo->prepare("
                    SELECT u.user_id, u.username, u.full_name, u.email, u.phone, s.role
                    FROM Users u
                    LEFT JOIN Staff s ON u.user_id = s.account_id
                    WHERE u.user_id = ? AND u.role = 'staff'
                ");
                $stmt->execute([$userId]);
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
            $userId = intval($_POST['user_id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = trim($_POST['role'] ?? '');
            
            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid user ID'
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
                
                // Update Users table
                $stmt = $pdo->prepare("
                    UPDATE Users 
                    SET full_name = ?, phone = ?
                    WHERE user_id = ? AND role = 'staff'
                ");
                $stmt->execute([$fullName, $phone, $userId]);
                
                // Check if Staff record exists
                $stmt = $pdo->prepare("SELECT staff_id FROM Staff WHERE account_id = ?");
                $stmt->execute([$userId]);
                $staffExists = $stmt->fetch();
                
                if ($staffExists) {
                    // Update existing Staff record
                        $stmt = $pdo->prepare("
                        UPDATE Staff 
                        SET staff_name = ?, role = ?
                        WHERE account_id = ?
                    ");
                    $stmt->execute([$fullName, $role, $userId]);
                } else {
                    // Create new Staff record if it doesn't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO Staff (staff_name, account_id, role)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$fullName, $userId, $role]);
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
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid user ID'
                ]);
                break;
            }
            
            // Check if user exists and is a staff member
            $stmt = $pdo->prepare("SELECT user_id, role FROM Users WHERE user_id = ?");
            $stmt->execute([$userId]);
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
                
                // Delete from Staff table first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM Staff WHERE account_id = ?");
                $stmt->execute([$userId]);
                
                // Delete from Users table
                $stmt = $pdo->prepare("DELETE FROM Users WHERE user_id = ?");
                $stmt->execute([$userId]);
                
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
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Invalid user ID'
                ]);
                break;
            }
            
            // Check if user exists and is a member (not staff)
            $stmt = $pdo->prepare("SELECT user_id, role FROM Users WHERE user_id = ?");
            $stmt->execute([$userId]);
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
                
                // Delete from Memberships table first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM Memberships WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                // Delete from Users table (this will cascade to reservations if foreign keys are set up)
                $stmt = $pdo->prepare("DELETE FROM Users WHERE user_id = ?");
                $stmt->execute([$userId]);
                
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
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already exists');
                }
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                // Create user account
                $fullName = $firstName . ' ' . $lastName;
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO Users (username, password, full_name, email, phone, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $hashedPassword, $fullName, $email, $phone, $role]);
                $userId = $pdo->lastInsertId();
                
                // Create staff record
                $stmt = $pdo->prepare("
                    INSERT INTO Staff (staff_name, account_id, role)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$fullName, $userId, $staffRole]);
                
                $pdo->commit();
                
                // Get the newly created staff member data
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.username, u.full_name, u.email, u.phone, u.register_time, s.role
                    FROM Users u
                    LEFT JOIN Staff s ON u.user_id = s.account_id
                    WHERE u.user_id = ?
                ");
                $stmt->execute([$userId]);
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
