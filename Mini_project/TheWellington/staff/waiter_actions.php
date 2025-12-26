<?php
/**
 * Waiter Actions Backend - UPDATED FOR NEW SCHEMA (thewellington1)
 * Handles waiter-specific operations:
 * - Get orders ready to serve
 * - Mark orders as served
 * - View table status
 */
session_start();
header('Content-Type: application/json');

// Database connection
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
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
        // GET ORDERS READY TO SERVE
        // Shows all orders with their status
        // ============================================
        case 'get_ready_orders':
            try {
                // Get all today's orders grouped by table
                $stmt = $pdo->prepare("
                    SELECT 
                        k.kitchen_id,
                        k.bill_item_id,
                        k.item_id,
                        k.quantity,
                        k.status,
                        k.confirm_order,
                        m.name as item_name,
                        m.category,
                        m.price,
                        rt.table_number,
                        ta.table_id,
                        r.reservation_id,
                        r.party_size,
                        ta.reservation_date,
                        ta.reservation_time,
                        mem.member_name as customer_name,
                        CASE k.status
                            WHEN 'completed' THEN 'Ready to Serve'
                            WHEN 'ready' THEN 'Ready to Serve'
                            WHEN 'preparing' THEN 'Preparing'
                            WHEN 'served' THEN 'Served'
                            ELSE 'Unknown'
                        END as status_display
                    FROM kitchen k
                    JOIN menu m ON k.item_id = m.item_id
                    JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
                    JOIN bills b ON bi.bill_id = b.bill_id
                    JOIN reservations r ON b.reservation_id = r.reservation_id
                    JOIN table_availability ta ON r.availability_id = ta.availability_id
                    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
                    LEFT JOIN memberships mem ON r.member_id = mem.member_id
                    WHERE DATE(ta.reservation_date) = CURDATE()
                    AND k.status != 'served'
                    ORDER BY 
                        CASE k.status
                            WHEN 'completed' THEN 1
                            WHEN 'ready' THEN 1
                            WHEN 'preparing' THEN 2
                            ELSE 3
                        END,
                        rt.table_number ASC,
                        ta.reservation_time ASC
                ");
                
                $stmt->execute();
                $orders = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'orders' => $orders,
                    'count' => count($orders)
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching orders: ' . $e->getMessage(),
                    'orders' => []
                ]);
            }
            break;
        
        // ============================================
        // MARK ORDERS AS SERVED
        // Marks all ready orders for a specific table as served
        // ============================================
        case 'mark_served':
            $tableNumber = intval($_POST['table_number'] ?? 0);
            
            if ($tableNumber <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid table number'
                ]);
                break;
            }
            
            // Get table_id from table_number
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_tables WHERE table_number = ?");
            $stmt->execute([$tableNumber]);
            $table = $stmt->fetch();
            
            if (!$table) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Table not found'
                ]);
                break;
            }
            
            // Update status to 'served' for ready/completed orders at this table
            // Join through bill_items -> bills -> reservations -> table_availability
            $stmt = $pdo->prepare("
                UPDATE kitchen k
                JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
                JOIN bills b ON bi.bill_id = b.bill_id
                JOIN reservations r ON b.reservation_id = r.reservation_id
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                SET k.status = 'served'
                WHERE ta.table_id = ? 
                AND k.status IN ('completed', 'ready')
                AND DATE(ta.reservation_date) = CURDATE()
            ");
            $stmt->execute([$table['table_id']]);
            
            $rowsAffected = $stmt->rowCount();
            
            if ($rowsAffected > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "Marked {$rowsAffected} item(s) as served for Table {$tableNumber}",
                    'rows_affected' => $rowsAffected
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No ready orders found for this table'
                ]);
            }
            break;
        
        // ============================================
        // MARK SINGLE ITEM AS SERVED
        // Marks a specific kitchen order as served
        // ============================================
        case 'mark_item_served':
            $kitchenId = intval($_POST['kitchen_id'] ?? 0);
            
            if ($kitchenId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ]);
                break;
            }
            
            $stmt = $pdo->prepare("
                UPDATE kitchen 
                SET status = 'served' 
                WHERE kitchen_id = ? 
                AND status IN ('completed', 'ready')
            ");
            $stmt->execute([$kitchenId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Item marked as served'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found or not ready'
                ]);
            }
            break;
        
        // ============================================
        // GET TABLE STATUS
        // Shows which tables have orders and their status
        // ============================================
        case 'get_table_status':
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        rt.table_number,
                        rt.table_id,
                        rt.capacity,
                        COUNT(DISTINCT k.kitchen_id) as total_orders,
                        SUM(CASE WHEN k.status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                        SUM(CASE WHEN k.status IN ('completed', 'ready') THEN 1 ELSE 0 END) as ready,
                        SUM(CASE WHEN k.status = 'served' THEN 1 ELSE 0 END) as served,
                        ta.reservation_time,
                        mem.member_name as customer_name
                    FROM restaurant_tables rt
                    LEFT JOIN table_availability ta ON rt.table_id = ta.table_id AND DATE(ta.reservation_date) = CURDATE()
                    LEFT JOIN reservations r ON ta.reservation_id = r.reservation_id
                    LEFT JOIN memberships mem ON r.member_id = mem.member_id
                    LEFT JOIN bills b ON r.reservation_id = b.reservation_id
                    LEFT JOIN bill_items bi ON b.bill_id = bi.bill_id
                    LEFT JOIN kitchen k ON bi.bill_item_id = k.bill_item_id AND k.status != 'served'
                    GROUP BY rt.table_number, rt.table_id
                    ORDER BY rt.table_number
                ");
                
                $stmt->execute();
                $tables = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'tables' => $tables
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching table status: ' . $e->getMessage(),
                    'tables' => []
                ]);
            }
            break;
        
        // ============================================
        // DEFAULT CASE
        // ============================================
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action: ' . $action
            ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>