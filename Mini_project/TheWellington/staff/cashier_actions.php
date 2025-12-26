<?php
/**
 * Cashier Actions Backend
 * Handles walk-in customer orders from POS system
 * Connects to kitchen for order processing
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

// Check if user is cashier
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ============================================
        // GET MENU ITEMS
        // ============================================
        case 'get_menu':
            $stmt = $pdo->prepare("
                SELECT item_id, name, description, price, category
                FROM menu
                ORDER BY 
                    CASE category
                        WHEN 'Main' THEN 1
                        WHEN 'Starter' THEN 2
                        WHEN 'Pasta' THEN 3
                        WHEN 'Side' THEN 4
                        WHEN 'Dessert' THEN 5
                        WHEN 'Beverages' THEN 6
                        ELSE 7
                    END,
                    name ASC
            ");
            $stmt->execute();
            $menu = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'menu' => $menu
            ]);
            break;
        
        // ============================================
        // PROCESS WALK-IN ORDER
        // ============================================
        case 'process_order':
            $tableId = intval($_POST['table_id'] ?? 0);
            $items = json_decode($_POST['items'] ?? '[]', true);
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $subtotal = floatval($_POST['subtotal'] ?? 0);
            $tax = floatval($_POST['tax'] ?? 0);
            $total = floatval($_POST['total'] ?? 0);
            // UPDATED: Use staff_id from session (new schema)
            $staffId = $_SESSION['staff_id'] ?? null;
            
            // Validation
            if ($tableId <= 0) {
                throw new Exception('Please select a table');
            }
            
            if (empty($items)) {
                throw new Exception('No items in order');
            }
            
            if (!in_array($paymentMethod, ['cash', 'bank_transfer', 'touch_n_go', 'credit_card'])) {
                throw new Exception('Invalid payment method');
            }
            
            if (!$staffId) {
                throw new Exception('Staff session invalid');
            }
            
            $pdo->beginTransaction();
            
            // UPDATED: Create walk-in customer account and membership (new schema)
            // Check if walk-in customer exists
            $stmt = $pdo->prepare("SELECT account_id FROM accounts WHERE username = 'walkin_customer' LIMIT 1");
            $stmt->execute();
            $walkInAccount = $stmt->fetch();
            
            if ($walkInAccount) {
                $accountId = $walkInAccount['account_id'];
                // Get member_id from memberships
                $stmt = $pdo->prepare("SELECT member_id FROM memberships WHERE account_id = ? LIMIT 1");
                $stmt->execute([$accountId]);
                $member = $stmt->fetch();
                $memberId = $member ? $member['member_id'] : null;
            } else {
                // Create walk-in customer account
                $stmt = $pdo->prepare("
                    INSERT INTO accounts (username, password, role, account_status)
                    VALUES ('walkin_customer', '', 'customer', 'active')
                ");
                $stmt->execute();
                $accountId = (int)$pdo->lastInsertId();
                
                // Create membership for walk-in customer
                $stmt = $pdo->prepare("
                    INSERT INTO memberships (account_id, member_name, email, phone, points)
                    VALUES (?, 'Walk-In Customer', 'walkin@restaurant.com', '', 0)
                ");
                $stmt->execute([$accountId]);
                $memberId = (int)$pdo->lastInsertId();
            }
            
            if (!$memberId) {
                throw new Exception('Failed to create/get walk-in customer');
            }
            
            // Get table_id from table number
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_tables WHERE table_number = ? LIMIT 1");
            $stmt->execute([$tableId]);
            $tableData = $stmt->fetch();
            
            if (!$tableData) {
                throw new Exception('Invalid table');
            }
            
            $actualTableId = $tableData['table_id'];
            
            // UPDATED: Create table_availability entry first (new schema)
            $reservationDate = date('Y-m-d');
            $reservationTime = date('H:i:s');
            
            $stmt = $pdo->prepare("
                INSERT INTO table_availability (table_id, reservation_date, reservation_time, status)
                VALUES (?, ?, ?, 'occupied')
            ");
            $stmt->execute([$actualTableId, $reservationDate, $reservationTime]);
            $availabilityId = (int)$pdo->lastInsertId();
            
            if (!$availabilityId) {
                throw new Exception('Failed to create table availability');
            }
            
            // UPDATED: Create reservation using member_id and availability_id (new schema)
            $stmt = $pdo->prepare("
                INSERT INTO reservations (member_id, availability_id, party_size, status)
                VALUES (?, ?, 1, 'confirmed')
            ");
            $stmt->execute([$memberId, $availabilityId]);
            $reservationId = (int)$pdo->lastInsertId();
            
            if (!$reservationId) {
                throw new Exception('Failed to create reservation');
            }
            
            // UPDATED: Update table_availability with reservation_id
            $stmt = $pdo->prepare("
                UPDATE table_availability 
                SET reservation_id = ? 
                WHERE availability_id = ?
            ");
            $stmt->execute([$reservationId, $availabilityId]);
            
            // UPDATED: Create Bill with staff_id (new schema)
            $stmt = $pdo->prepare("
                INSERT INTO bills (staff_id, reservation_id, total_amount, payment_status)
                VALUES (?, ?, ?, 'paid')
            ");
            $stmt->execute([$staffId, $reservationId, $total]);
            $billId = (int)$pdo->lastInsertId();
            
            if (!$billId) {
                throw new Exception('Failed to create bill');
            }
            
            // UPDATED: Create payment transaction (payment_method goes here, not in bills)
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (payment_method, amount, payment_status, bill_id, verified_by)
                VALUES (?, ?, 'verified', ?, ?)
            ");
            $stmt->execute([$paymentMethod, $total, $billId, $staffId]);
            $transactionId = (int)$pdo->lastInsertId();
            
            // Add items to bill_items and kitchen
            $kitchenOrdersCreated = 0;
            
            foreach ($items as $item) {
                // Insert into bill_items
                $stmt = $pdo->prepare("
                    INSERT INTO bill_items (bill_id, item_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $billId,
                    $item['itemId'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                $billItemId = (int)$pdo->lastInsertId();
                
                // UPDATED: Kitchen table only has bill_item_id, item_id, quantity, status, confirm_order (new schema)
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO kitchen (bill_item_id, item_id, quantity, confirm_order, status)
                        VALUES (?, ?, ?, 0, 'preparing')
                    ");
                    $stmt->execute([
                        $billItemId,
                        $item['itemId'],
                        $item['quantity']
                    ]);
                    $kitchenOrdersCreated++;
                } catch (PDOException $e) {
                    error_log('Kitchen insert error: ' . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Order processed successfully',
                'data' => [
                    'reservation_id' => $reservationId,
                    'bill_id' => $billId,
                    'transaction_id' => $transactionId,
                    'table_id' => $actualTableId,
                    'total' => $total,
                    'kitchen_orders' => $kitchenOrdersCreated,
                    'payment_method' => $paymentMethod
                ]
            ]);
            break;
        
        // ============================================
        // GET AVAILABLE TABLES
        // ============================================
        case 'get_available_tables':
            // UPDATED: Use table_availability for status check (new schema)
            $stmt = $pdo->prepare("
                SELECT 
                    rt.table_id,
                    rt.table_number,
                    rt.capacity,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM table_availability ta
                            WHERE ta.table_id = rt.table_id
                            AND ta.reservation_date = CURDATE()
                            AND ta.status IN ('reserved', 'occupied')
                        ) THEN 'occupied'
                        ELSE 'available'
                    END as status
                FROM restaurant_tables rt
                ORDER BY rt.table_number
            ");
            $stmt->execute();
            $tables = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'tables' => $tables
            ]);
            break;
        
        // ============================================
        // GET TODAY'S SALES SUMMARY
        // ============================================
        case 'get_sales_summary':
            // UPDATED: Use table_availability for date check and payment_transactions for payment method (new schema)
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT b.bill_id) as total_orders,
                    COALESCE(SUM(b.total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN pt.payment_method = 'cash' THEN b.total_amount ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN pt.payment_method IN ('bank_transfer', 'touch_n_go') THEN b.total_amount ELSE 0 END), 0) as bank_sales
                FROM bills b
                JOIN reservations r ON b.reservation_id = r.reservation_id
                JOIN table_availability ta ON r.availability_id = ta.availability_id
                LEFT JOIN payment_transactions pt ON pt.bill_id = b.bill_id
                WHERE ta.reservation_date = CURDATE()
                AND b.payment_status = 'paid'
            ");
            $stmt->execute();
            $summary = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);
            break;
        
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action'
            ]);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Cashier Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>