<?php
session_start();

// Authentication helpers
if (!function_exists('isStaff')) {
    function isStaff(): bool {
        return isset($_SESSION['user']) && $_SESSION['user'] === 'staff';
    }
}
if (!function_exists('redirect')) {
    function redirect(string $path): void {
        // If path is relative and doesn't start with http:// or https://, 
        // construct absolute path from current script location
        if (!preg_match('#^(https?://|/)#', $path)) {
            // Get directory of current script (e.g., /TheWellington/staff)
            $currentDir = dirname($_SERVER['PHP_SELF']);
            // Build full path
            $path = rtrim($currentDir, '/') . '/' . ltrim($path, '/');
        }
        header("Location: {$path}");
        exit;
    }
}
if (!function_exists('getDBConnection')) {
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
            die('Connection failed: ' . $e->getMessage());
        }
    }
}

// Check authentication
if (!isStaff()) {
    // Construct path to account.php in parent directory
    $currentPath = $_SERVER['PHP_SELF']; // e.g., /TheWellington/staff/waiter.php
    $parentPath = dirname(dirname($currentPath)); // e.g., /TheWellington
    $redirectPath = $parentPath . '/account.php'; // e.g., /TheWellington/account.php
    header("Location: {$redirectPath}");
    exit;
}

// Verify user is a waiter
$pdo = getDBConnection();
// UPDATED: Use account_id from session (new schema)
$accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? 0;
if ($accountId > 0) {
    // UPDATED: Use staffs table (lowercase, new schema)
    $stmt = $pdo->prepare("
        SELECT s.role 
        FROM staffs s 
        WHERE s.account_id = ?
    ");
    $stmt->execute([$accountId]);
    $staffRole = $stmt->fetch();
    
    // If user is not a waiter, redirect to staff panel
    // Since waiter.php is in staff/ folder, redirect to staff.php in same directory
    if (!$staffRole || strtolower($staffRole['role']) !== 'waiter') {
        // Use absolute path from web root - this ensures it always works correctly
        // Detect web root base path from current script location
        $scriptPath = $_SERVER['PHP_SELF']; // e.g., /TheWellington/staff/waiter.php
        // Extract base path (everything before /staff/)
        if (preg_match('#^(/[^/]+)/staff/#', $scriptPath, $matches)) {
            $basePath = $matches[1]; // e.g., /TheWellington
        } else {
            $basePath = ''; // Fallback to empty if pattern doesn't match
        }
        $redirectPath = $basePath . '/staff/staff.php';
        header("Location: {$redirectPath}");
        exit;
    }
}

// Check if both status and confirm_order columns exist
$hasStatusColumn = false;
$hasConfirmColumn = false;

try {
    // UPDATED: Use kitchen table (lowercase, new schema)
    $checkStmt = $pdo->query("SHOW COLUMNS FROM kitchen LIKE 'status'");
    $hasStatusColumn = $checkStmt->rowCount() > 0;
    
    $checkStmt = $pdo->query("SHOW COLUMNS FROM kitchen LIKE 'confirm_order'");
    $hasConfirmColumn = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasStatusColumn = false;
    $hasConfirmColumn = false;
}

if ($hasStatusColumn || $hasConfirmColumn) {
    // Build query based on available columns
    // UPDATED: Use table_availability for date/time (new schema)
    $selectFields = "
        rt.table_number,
        rt.capacity,
        r.reservation_id,
        r.party_size,
        ta.reservation_date,
        ta.reservation_time,
        mem.member_name as customer_name,
        k.item_id,
        m.name as item_name,
        m.category,
        SUM(k.quantity) as total_quantity";
    
    // Add status-related fields based on what columns exist
    if ($hasStatusColumn && $hasConfirmColumn) {
        // Both columns exist - use both for accurate status
        $selectFields .= ",
        MAX(CASE 
            WHEN k.confirm_order = 1 THEN 'ready'
            WHEN k.status = 'completed' THEN 'ready'
            WHEN k.status = 'ready' THEN 'ready'
            WHEN k.confirm_order = 0 THEN 'preparing'
            WHEN k.status = 'preparing' THEN 'preparing'
            WHEN k.status = 'pending' THEN 'preparing'
            ELSE 'preparing'
        END) as order_status,
        MAX(k.confirm_order) as is_confirmed,
        MAX(CASE 
            WHEN k.confirm_order = 1 THEN 'ready'
            WHEN k.status IN ('completed', 'ready') THEN 'ready'
            ELSE 'preparing'
        END) as display_status";
    } elseif ($hasConfirmColumn) {
        // Only confirm_order exists
        $selectFields .= ",
        MAX(CASE 
            WHEN k.confirm_order = 1 THEN 'ready'
            ELSE 'preparing'
        END) as order_status,
        MAX(k.confirm_order) as is_confirmed,
        MAX(CASE 
            WHEN k.confirm_order = 1 THEN 'ready'
            ELSE 'preparing'
        END) as display_status";
    } else {
        // Only status exists
        $selectFields .= ",
        MAX(CASE 
            WHEN k.status IN ('completed', 'ready') THEN 'ready'
            WHEN k.status IN ('preparing', 'pending') THEN 'preparing'
            ELSE 'preparing'
        END) as order_status,
        0 as is_confirmed,
        MAX(CASE 
            WHEN k.status IN ('completed', 'ready') THEN 'ready'
            ELSE 'preparing'
        END) as display_status";
    }
    
    // Build WHERE clause based on available columns - Show ALL active orders (preparing AND ready)
    // UPDATED: Waiter interface should show both preparing and ready orders
    // Only exclude served and cancelled orders, include NULL status (treat as preparing)
    $whereClause = "WHERE k.item_id IS NOT NULL AND ta.reservation_date = CURDATE()";
    if ($hasStatusColumn && $hasConfirmColumn) {
        // Both columns exist - show all active orders (not served or cancelled, including NULL)
        $whereClause .= " AND (k.status IS NULL OR k.status NOT IN ('served', 'cancelled'))";
    } elseif ($hasConfirmColumn) {
        // Only confirm_order exists - show all orders (no filtering needed)
        // Remove the redundant condition
    } elseif ($hasStatusColumn) {
        // Only status exists - show all active orders (not served or cancelled, including NULL)
        $whereClause .= " AND (k.status IS NULL OR k.status NOT IN ('served', 'cancelled'))";
    }
    
    // UPDATED: Use new schema - kitchen links through: kitchen -> bill_items -> bills -> reservations -> table_availability -> restaurant_tables
    $query = "
        SELECT " . $selectFields . "
        FROM kitchen k
        JOIN menu m ON k.item_id = m.item_id
        JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
        JOIN bills b ON bi.bill_id = b.bill_id
        JOIN reservations r ON b.reservation_id = r.reservation_id
        JOIN table_availability ta ON r.availability_id = ta.availability_id
        JOIN restaurant_tables rt ON ta.table_id = rt.table_id
        LEFT JOIN memberships mem ON r.member_id = mem.member_id
        " . $whereClause . "
        GROUP BY rt.table_number, k.item_id, r.reservation_id
        HAVING SUM(k.quantity) > 0
        ORDER BY rt.table_number, ta.reservation_time
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} else {
    // Fallback: No status or confirm_order columns - show all orders as preparing
    $stmt = $pdo->prepare("
        SELECT 
            rt.table_number,
            rt.capacity,
            r.reservation_id,
            r.party_size,
            ta.reservation_date,
            ta.reservation_time,
            mem.member_name as customer_name,
            k.item_id,
            m.name as item_name,
            m.category,
            SUM(k.quantity) as total_quantity,
            'preparing' as order_status,
            0 as is_confirmed,
            'preparing' as display_status
        FROM kitchen k
        JOIN menu m ON k.item_id = m.item_id
        JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
        JOIN bills b ON bi.bill_id = b.bill_id
        JOIN reservations r ON b.reservation_id = r.reservation_id
        JOIN table_availability ta ON r.availability_id = ta.availability_id
        JOIN restaurant_tables rt ON ta.table_id = rt.table_id
        LEFT JOIN memberships mem ON r.member_id = mem.member_id
        WHERE ta.reservation_date = CURDATE()
        GROUP BY rt.table_number, k.item_id, r.reservation_id
        HAVING SUM(k.quantity) > 0
        ORDER BY rt.table_number, ta.reservation_time
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();
}

// Organize orders by table
$ordersByTable = [];
foreach ($orders as $order) {
    $tableNum = $order['table_number'];
    
    if (!isset($ordersByTable[$tableNum])) {
        $ordersByTable[$tableNum] = [
            'table_number' => $tableNum,
            'capacity' => $order['capacity'],
            'customer_name' => $order['customer_name'],
            'party_size' => $order['party_size'],
            'reservation_time' => $order['reservation_time'],
            'orders' => [],
            'status' => 'preparing' // Default status
        ];
    }
    
    // Add order to this table
    $ordersByTable[$tableNum]['orders'][] = [
        'item_name' => $order['item_name'],
        'category' => $order['category'],
        'quantity' => $order['total_quantity'],
        'order_status' => $order['order_status'],
        'display_status' => $order['display_status'],
        'is_confirmed' => $order['is_confirmed'] ?? 0
    ];
    
    // Update table status - check if ALL orders for this table are ready
    // If all orders are ready, table status is 'ready', otherwise 'preparing'
    $allReady = true;
    $hasReadyOrder = false;
    
    foreach ($ordersByTable[$tableNum]['orders'] as $tableOrder) {
        $isOrderReady = ($tableOrder['display_status'] === 'ready' || 
                        $tableOrder['order_status'] === 'ready' ||
                        ($tableOrder['is_confirmed'] ?? 0) == 1);
        
        if ($isOrderReady) {
            $hasReadyOrder = true;
        } else {
            $allReady = false;
        }
    }
    
    // If all orders are ready, table is ready to serve
    if ($allReady && $hasReadyOrder) {
        $ordersByTable[$tableNum]['status'] = 'ready';
    } else {
        $ordersByTable[$tableNum]['status'] = 'preparing';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Interface - The Wellington</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .waiter-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .waiter-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .waiter-header h1 {
            color: #2c3e50;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .waiter-header h1 i {
            color: #667eea;
        }

        .waiter-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .waiter-name {
            color: #6c757d;
            font-size: 16px;
        }

        .waiter-name strong {
            color: #2c3e50;
        }

        .logout-btn {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .table-card.preparing {
            border-left: 5px solid #ffc107;
        }

        .table-card.ready {
            border-left: 5px solid #28a745;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
            50% { box-shadow: 0 10px 30px rgba(40, 167, 69, 0.5); }
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .table-number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }

        .table-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-preparing {
            background: #fff3cd;
            color: #856404;
        }

        .status-ready {
            background: #d4edda;
            color: #155724;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .table-info {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #6c757d;
        }

        .table-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .table-info i {
            color: #667eea;
        }

        .order-items {
            margin-top: 15px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .order-item-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-item-quantity {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .order-item-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .item-preparing {
            background: #fff3cd;
            color: #856404;
        }

        .item-ready {
            background: #d4edda;
            color: #155724;
        }

        .serve-btn {
            width: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.2s;
        }

        .serve-btn:hover {
            transform: translateY(-2px);
        }

        .serve-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            color: #6c757d;
        }

        .no-orders i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transition: transform 0.3s;
        }

        .refresh-btn:hover {
            transform: rotate(180deg) scale(1.1);
        }

        .auto-refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.9);
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 12px;
            color: #6c757d;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Modal Styles */
        .logout-modal,
        .serve-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .logout-modal.show,
        .serve-modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            cursor: pointer;
        }

        .modal-container {
            position: relative;
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .logout-modal.show .modal-container {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            text-align: center;
            padding: 40px 30px 30px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            border-radius: 25px 25px 0 0;
        }

        .modal-header h2 {
            margin: 15px 0 8px;
            font-size: 28px;
            font-weight: 700;
        }

        .modal-header .subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }

        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .modal-body p {
            color: #6c757d;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .modal-footer button {
            padding: 12px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: #e9ecef;
            color: #6c757d;
        }

        .btn-cancel:hover {
            background: #dee2e6;
            transform: translateY(-2px);
        }

        .btn-confirm,
        .btn-logout-confirm {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .btn-logout-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.3);
        }
    </style>
</head>
<body>
    <div class="waiter-container">
        <div class="waiter-header">
            <h1>
                <i class="fas fa-concierge-bell"></i>
                Waiter Interface
            </h1>
            <div class="waiter-info">
                <div class="waiter-name">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Waiter'); ?></strong>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <div class="auto-refresh-indicator">
            <i class="fas fa-sync-alt fa-spin"></i> Auto-refreshing every 10 seconds
        </div>

        <?php if (empty($ordersByTable)): ?>
            <div class="no-orders">
                <i class="fas fa-utensils"></i>
                <h2>No Active Orders</h2>
                <p>Kitchen orders will appear here when available</p>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($ordersByTable as $table): ?>
                    <?php 
                    $isReady = ($table['status'] === 'ready');
                    $cardClass = $isReady ? 'ready' : 'preparing';
                    ?>
                    <div class="table-card <?php echo $cardClass; ?>">
                        <div class="table-header">
                            <div class="table-number">Table <?php echo $table['table_number']; ?></div>
                            <div class="table-status status-<?php echo $isReady ? 'ready' : 'preparing'; ?>">
                                <?php echo $isReady ? 'Ready to Serve' : 'Preparing'; ?>
                            </div>
                        </div>

                        <div class="table-info">
                            <?php if ($table['reservation_time']): ?>
                                <span>
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($table['reservation_time'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($table['party_size']): ?>
                                <span>
                                    <i class="fas fa-users"></i>
                                    <?php echo $table['party_size']; ?> Guests
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="order-items">
                            <?php foreach ($table['orders'] as $order): ?>
                                <div class="order-item">
                                    <div>
                                        <div class="order-item-name"><?php echo htmlspecialchars($order['item_name']); ?></div>
                                        <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">
                                            <?php echo htmlspecialchars($order['category']); ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span class="order-item-quantity"><?php echo $order['quantity']; ?>x</span>
                                        <span class="order-item-status item-<?php echo ($order['display_status'] === 'ready' || $order['order_status'] === 'ready') ? 'ready' : 'preparing'; ?>">
                                            <?php echo ($order['display_status'] === 'ready' || $order['order_status'] === 'ready') ? 'Ready' : 'Preparing'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($isReady): ?>
                            <button class="serve-btn" onclick="markAsServed(<?php echo $table['table_number']; ?>)">
                                <i class="fas fa-check-circle"></i> Mark as Served
                            </button>
                        <?php else: ?>
                            <button class="serve-btn" disabled>
                                <i class="fas fa-hourglass-half"></i> Waiting for Kitchen...
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <button class="refresh-btn" onclick="location.reload()" title="Refresh">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        // Auto-refresh every 10 seconds
        setInterval(() => {
            location.reload();
        }, 10000);

        // Fetch orders function
        function fetchOrders() {
            fetch('waiter_actions.php?action=get_ready_orders')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // data.orders contains all orders with:
                        // - item_name
                        // - table_number  
                        // - status ("preparing", "completed", etc.)
                        // - status_display ("Preparing", "Ready to Serve")
                        console.log('Orders fetched:', data.orders);
                        console.log('Total orders:', data.count);
                        
                        // You can process the orders here
                        // For example, group by table, update UI, etc.
                        processOrders(data.orders);
                    } else {
                        console.error('Error fetching orders:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }

        // Process orders (example function - customize as needed)
        function processOrders(orders) {
            // Group orders by table
            const ordersByTable = {};
            
            orders.forEach(order => {
                const tableNum = order.table_number;
                if (!ordersByTable[tableNum]) {
                    ordersByTable[tableNum] = [];
                }
                ordersByTable[tableNum].push(order);
            });
            
            // Log grouped orders
            console.log('Orders grouped by table:', ordersByTable);
            
            // Example: Count preparing vs ready orders
            const preparing = orders.filter(o => o.status === 'preparing' || o.status === null).length;
            const ready = orders.filter(o => o.status === 'completed' || o.status === 'ready').length;
            console.log(`Preparing: ${preparing}, Ready: ${ready}`);
        }

        function logout() {
            showLogoutModal();
        }

        function showLogoutModal() {
            const modal = document.createElement('div');
            modal.className = 'logout-modal';
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeLogoutModal()"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <div class="icon-circle">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <h2>Confirm Logout</h2>
                        <p class="subtitle">Are you sure you want to logout?</p>
                    </div>
                    
                    <div class="modal-body">
                        <p>You will be redirected to the login page.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeLogoutModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-logout-confirm" onclick="confirmLogout()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeLogoutModal() {
            const modal = document.querySelector('.logout-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function confirmLogout() {
            // Since waiter.php is in staff/ subdirectory, go up one level to reach logout.php in root
            window.location.href = '../logout.php';
        }

        function markAsServed(tableNumber) {
            showServeModal(tableNumber);
        }

        function showServeModal(tableNumber) {
            const modal = document.createElement('div');
            modal.id = 'serve-modal';
            modal.className = 'serve-modal';
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeServeModal()"></div>
                <div class="modal-container">
                    <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <div class="icon-circle" style="background: rgba(255, 255, 255, 0.3);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Mark Orders as Served</h2>
                        <p class="subtitle">Are you sure you want to mark all orders for Table ${tableNumber} as served?</p>
                    </div>
                    
                    <div class="modal-body">
                        <p>This will mark all ready orders for this table as served and remove them from the active orders list.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeServeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-confirm" onclick="confirmMarkAsServed(${tableNumber})" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                            <i class="fas fa-check-circle"></i> Mark as Served
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';

            // Close on Escape key
            const escapeHandler = function(e) {
                if (e.key === 'Escape') {
                    closeServeModal();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        }

        function closeServeModal() {
            const modal = document.getElementById('serve-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.remove();
                    document.body.style.overflow = '';
                }, 300);
            }
        }

        function confirmMarkAsServed(tableNumber) {
            closeServeModal();
            
            fetch('waiter_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_served&table_number=${tableNumber}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    </script>
</body>
</html>

