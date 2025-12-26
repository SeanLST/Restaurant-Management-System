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
    // Since staff.php is in staff/ folder, go up one level to reach account.php
    redirect('../account.php');
}

$pdo = getDBConnection();

// Check if user is a waiter and redirect to waiter interface
// Cashiers and other staff can access the main staff panel
// UPDATED: Use account_id from session (new schema)
$accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? 0;
$currentStaffRole = null;
if ($accountId > 0) {
    try {
        // UPDATED: Use staffs table (lowercase, new schema)
        $stmt = $pdo->prepare("
            SELECT s.role 
            FROM staffs s 
            WHERE s.account_id = ?
        ");
        $stmt->execute([$accountId]);
        $staffRole = $stmt->fetch();
        
        // Store staff role for navigation display
        if ($staffRole) {
            $currentStaffRole = $staffRole['role'];
        }
        
        // If user is a waiter, redirect to waiter interface
        if ($staffRole && strtolower($staffRole['role']) === 'waiter') {
            redirect('waiter.php');
        }
        // If user is a cashier, redirect to cashier interface
        if ($staffRole && strtolower($staffRole['role']) === 'cashier') {
            redirect('cashier.php');
        }
        // If no staffs table entry exists, allow access (for backward compatibility)
    } catch (PDOException $e) {
        // If staffs table doesn't have entry or query fails, continue to staff panel
        // This allows all staff members to access
    }
}
$today = date('Y-m-d');

// Get Recent Activity (Last 10 activities)
$recentActivity = [];

// New Reservations
// UPDATED: Use new schema with table_availability and memberships
$stmt = $pdo->prepare("
    SELECT 'new_reservation' as type, r.reservation_id as id, 
           COALESCE(mem.member_name, 'Guest') as name, 
           ta.reservation_date, ta.reservation_time, r.created_at
    FROM reservations r
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    LEFT JOIN memberships mem ON r.member_id = mem.member_id
    WHERE ta.reservation_date = CURDATE()
    ORDER BY r.reservation_id DESC LIMIT 5
");
$stmt->execute();
$newReservations = $stmt->fetchAll();

// Kitchen Updates (Orders submitted)
// UPDATED: Kitchen links through bill_items -> bills -> reservations -> table_availability -> restaurant_tables
$stmt = $pdo->prepare("
    SELECT 'kitchen_order' as type, k.kitchen_id as id, 
           rt.table_number, m.name as item_name, k.quantity,
           k.order_time as time_submitted
    FROM kitchen k
    JOIN menu m ON k.item_id = m.item_id
    JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
    JOIN bills b ON bi.bill_id = b.bill_id
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
    ORDER BY k.kitchen_id DESC LIMIT 5
");
$stmt->execute();
$kitchenUpdates = $stmt->fetchAll();

// Completed Orders
// UPDATED: Use new schema with table_availability and memberships
$stmt = $pdo->prepare("
    SELECT 'order_complete' as type, b.bill_id as id, mem.member_name as name,
           b.total_amount, pt.payment_method, ta.reservation_date
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    LEFT JOIN memberships mem ON r.member_id = mem.member_id
    LEFT JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE DATE(ta.reservation_date) = CURDATE()
    ORDER BY b.bill_id DESC LIMIT 5
");
$stmt->execute();
$completedOrders = $stmt->fetchAll();

// Merge and sort all activities
$recentActivity = array_merge($newReservations, $kitchenUpdates, $completedOrders);
usort($recentActivity, function($a, $b) {
    $timeA = $a['created_at'] ?? $a['time_submitted'] ?? $a['reservation_date'];
    $timeB = $b['created_at'] ?? $b['time_submitted'] ?? $b['reservation_date'];
    return strtotime($timeB) - strtotime($timeA);
});
$recentActivity = array_slice($recentActivity, 0, 10);

// Get Stats
// UPDATED: Use new schema with table_availability
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM reservations r
         JOIN table_availability ta ON r.availability_id = ta.availability_id
         WHERE ta.reservation_date = CURDATE()) as today_reservations,
        (SELECT COUNT(*) FROM kitchen) as today_orders,
        (SELECT COUNT(*) FROM bills b
         JOIN reservations r ON b.reservation_id = r.reservation_id
         JOIN table_availability ta ON r.availability_id = ta.availability_id
         WHERE DATE(ta.reservation_date) = CURDATE()) as completed_bills
");
$stmt->execute();
$stats = $stmt->fetch();

// Get Today's Revenue (only verified payments)
// UPDATED: Use new schema - payment_method is in payment_transactions, not bills
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(b.total_amount), 0) as total
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE ta.reservation_date = CURDATE()
    AND pt.payment_status = 'verified'
");
$stmt->execute();
$todayRevenue = $stmt->fetch()['total'] ?? 0;

// Get This Week's Revenue (only verified payments)
// UPDATED: Use new schema with table_availability
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(b.total_amount), 0) as total
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE YEARWEEK(ta.reservation_date, 1) = YEARWEEK(CURDATE(), 1)
    AND pt.payment_status = 'verified'
");
$stmt->execute();
$weekRevenue = $stmt->fetch()['total'] ?? 0;

// Get This Month's Revenue (only verified payments)
// UPDATED: Use new schema with table_availability
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(b.total_amount), 0) as total
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE YEAR(ta.reservation_date) = YEAR(CURDATE()) AND MONTH(ta.reservation_date) = MONTH(CURDATE())
    AND pt.payment_status = 'verified'
");
$stmt->execute();
$monthRevenue = $stmt->fetch()['total'] ?? 0;

// Get Revenue Breakdown by Payment Method (Today)
// UPDATED: payment_method is in payment_transactions, not bills
$stmt = $pdo->prepare("
    SELECT 
        pt.payment_method,
        COUNT(*) as transaction_count,
        COALESCE(SUM(b.total_amount), 0) as total_amount
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE ta.reservation_date = CURDATE()
    AND pt.payment_status = 'verified'
    GROUP BY pt.payment_method
    ORDER BY total_amount DESC
");
$stmt->execute();
$todayRevenueByMethod = $stmt->fetchAll();

// Get Revenue Breakdown by Payment Method (This Week)
// UPDATED: payment_method is in payment_transactions, not bills
$stmt = $pdo->prepare("
    SELECT 
        pt.payment_method,
        COUNT(*) as transaction_count,
        COALESCE(SUM(b.total_amount), 0) as total_amount
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE YEARWEEK(ta.reservation_date, 1) = YEARWEEK(CURDATE(), 1)
    AND pt.payment_status = 'verified'
    GROUP BY pt.payment_method
    ORDER BY total_amount DESC
");
$stmt->execute();
$weekRevenueByMethod = $stmt->fetchAll();

// Get Revenue Breakdown by Payment Method (This Month)
// UPDATED: payment_method is in payment_transactions, not bills
$stmt = $pdo->prepare("
    SELECT 
        pt.payment_method,
        COUNT(*) as transaction_count,
        COALESCE(SUM(b.total_amount), 0) as total_amount
    FROM bills b
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE YEAR(ta.reservation_date) = YEAR(CURDATE()) 
    AND MONTH(ta.reservation_date) = MONTH(CURDATE())
    AND pt.payment_status = 'verified'
    GROUP BY pt.payment_method
    ORDER BY total_amount DESC
");
$stmt->execute();
$monthRevenueByMethod = $stmt->fetchAll();

// Get Pending Payments (Today only by default)
// UPDATED: Use new schema - payment_transactions links to bills, not reservations directly
$stmt = $pdo->prepare("
    SELECT pt.*, mem.member_name as full_name, mem.phone, rt.table_number
    FROM payment_transactions pt
    JOIN bills b ON pt.bill_id = b.bill_id
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
    LEFT JOIN memberships mem ON r.member_id = mem.member_id
    WHERE pt.payment_status = 'pending'
    AND DATE(pt.created_at) = CURDATE()
    ORDER BY pt.created_at DESC
");
$stmt->execute();
$pendingPayments = $stmt->fetchAll();

// Get Table Status with Time Slots
// UPDATED: Use restaurant_tables (new schema)
$stmt = $pdo->prepare("
    SELECT rt.*
    FROM restaurant_tables rt
    ORDER BY rt.table_number
");
$stmt->execute();
$tables = $stmt->fetchAll();

// Get all reservations for today with time slots
// UPDATED: Use new schema with table_availability
$stmt = $pdo->prepare("
    SELECT ta.table_id, ta.reservation_time, mem.member_name as full_name, r.party_size
    FROM reservations r
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    LEFT JOIN memberships mem ON r.member_id = mem.member_id
    WHERE ta.reservation_date = CURDATE()
    ORDER BY ta.reservation_time
");
$stmt->execute();
$todayReservations = $stmt->fetchAll();

// Create time slot availability map (same as reservation page)
$timeSlots = [
    '16:00:00' => '4:00 PM',
    '18:00:00' => '6:00 PM',
    '20:00:00' => '8:00 PM',
    '22:00:00' => '10:00 PM',
];

// Build table availability by time slot
$tableAvailability = [];
foreach ($tables as $table) {
    $tableAvailability[$table['table_id']] = [];
    foreach ($timeSlots as $time => $label) {
        $isBooked = false;
        $customer = null;
        $partySize = null;
        
        foreach ($todayReservations as $res) {
            if ($res['table_id'] == $table['table_id'] && $res['reservation_time'] == $time) {
                $isBooked = true;
                $customer = $res['full_name'];
                $partySize = $res['party_size'];
                break;
            }
        }
        
        $tableAvailability[$table['table_id']][$time] = [
            'status' => $isBooked ? 'occupied' : 'available',
            'customer' => $customer,
            'party_size' => $partySize,
            'label' => $label
        ];
    }
}

// Get Today's Reservations (by default)
// UPDATED: Use new schema with table_availability and memberships
$stmt = $pdo->prepare("
    SELECT r.*, rt.table_number, ta.reservation_date, ta.reservation_time, 
           mem.member_name as full_name, mem.phone, mem.points as member_points
    FROM reservations r
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
    LEFT JOIN memberships mem ON r.member_id = mem.member_id
    WHERE ta.reservation_date = CURDATE()
    ORDER BY ta.reservation_time ASC
");
$stmt->execute();
$reservations = $stmt->fetchAll();

// Get Kitchen Orders with reservation time
// UPDATED: Kitchen table doesn't have reservation_id, table_id, or reservation_time - get from joined tables
$stmt = $pdo->prepare("
    SELECT 
        MIN(k.kitchen_id) as kitchen_id,
        MAX(k.bill_item_id) as bill_item_id,
        r.reservation_id,
        ta.table_id,
        k.item_id,
        SUM(k.quantity) as quantity,
        ta.reservation_time,
        MAX(k.confirm_order) as confirm_order,
        MAX(k.status) as status,
        m.name as item_name, 
        m.category, 
        m.price as item_price,
        rt.table_number,
        MAX(ta.reservation_date) as reservation_date,
        MAX(r.party_size) as party_size
    FROM kitchen k
    JOIN menu m ON k.item_id = m.item_id
    JOIN bill_items bi ON k.bill_item_id = bi.bill_item_id
    JOIN bills b ON bi.bill_id = b.bill_id
    JOIN reservations r ON b.reservation_id = r.reservation_id
    JOIN table_availability ta ON r.availability_id = ta.availability_id
    JOIN restaurant_tables rt ON ta.table_id = rt.table_id
    WHERE DATE(ta.reservation_date) >= CURDATE()
    GROUP BY r.reservation_id, k.item_id, ta.table_id, ta.reservation_time, 
             m.name, m.category, m.price, rt.table_number
    ORDER BY MIN(k.kitchen_id) ASC, COALESCE(ta.reservation_time, '00:00:00') ASC
");
$stmt->execute();
$kitchenOrders = $stmt->fetchAll();

// Get Members (Customers)
// UPDATED: Use new schema - accounts and memberships
$stmt = $pdo->query("
    SELECT a.account_id as user_id, a.username, a.register_time, 
           mem.member_id as membership_id, mem.member_name as full_name, 
           mem.email, mem.phone, mem.points,
           COUNT(DISTINCT r.reservation_id) as total_reservations
    FROM accounts a
    JOIN memberships mem ON a.account_id = mem.account_id
    LEFT JOIN reservations r ON mem.member_id = r.member_id
    WHERE a.role = 'customer'
    GROUP BY a.account_id
    ORDER BY a.register_time DESC
");
$members = $stmt->fetchAll();

// Get Staff List
// UPDATED: Use new schema - accounts and staffs
$stmt = $pdo->query("
    SELECT a.account_id as user_id, a.username, a.register_time,
           s.staff_id, s.staff_name as full_name, s.email, s.phone, s.role as staff_role
    FROM accounts a
    JOIN staffs s ON a.account_id = s.account_id
    WHERE a.role = 'staff'
    ORDER BY s.staff_name
");
$staffList = $stmt->fetchAll();

// Get Top Selling Items (only from verified payments)
// UPDATED: Use new schema - payment_transactions links to bills via bill_id
$stmt = $pdo->query("
    SELECT m.name, m.category, m.price,
           SUM(bi.quantity) as total_sold,
           COUNT(DISTINCT bi.bill_id) as times_ordered,
           SUM(bi.quantity * bi.unit_price) as total_revenue
    FROM bill_items bi
    JOIN menu m ON bi.item_id = m.item_id
    JOIN bills b ON bi.bill_id = b.bill_id
    JOIN payment_transactions pt ON pt.bill_id = b.bill_id
    WHERE pt.payment_status = 'verified'
    GROUP BY bi.item_id
    ORDER BY total_sold DESC
    LIMIT 10
");
$topItems = $stmt->fetchAll();

// Get Menu Items
// UPDATED: Use menu table (lowercase, new schema)
$stmt = $pdo->query("SELECT * FROM menu ORDER BY category, name");
$menuItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - The Wellington</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #1a1a1a;
            --light: #ecf0f1;
            --white: #ffffff;
            --gray: #95a5a6;
            --border: #dfe6e9;
            --hover: #34495e;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: var(--white);
            box-shadow: var(--shadow-lg);
            padding: 30px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            padding: 0 30px 30px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 28px;
            color: var(--secondary);
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin: 5px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 15px;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--white);
            transform: translateX(5px);
        }

        .nav-link i {
            font-size: 18px;
            width: 20px;
        }

        .user-info {
            padding: 20px 30px;
            margin-top: 20px;
            border-top: 2px solid var(--border);
        }

        .user-info p {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background: var(--danger);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            overflow-y: auto;
        }

        .page-header {
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            border-left: 5px solid var(--secondary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--white);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-info h3 {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Section */
        .section {
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--secondary);
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            color: var(--white);
            font-weight: 600;
            font-size: 14px;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Scrollable Table Container */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5568d3 0%, #653a91 100%);
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-primary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Activity Timeline */
        .activity-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-left: 3px solid var(--border);
            margin-left: 20px;
            position: relative;
            margin-bottom: 15px;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 20px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: var(--secondary);
            border: 3px solid var(--white);
        }

        .activity-item.new-reservation::before {
            background: var(--success);
        }

        .activity-item.kitchen-order::before {
            background: var(--warning);
        }

        .activity-item.order-complete::before {
            background: var(--primary);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--white);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 15px;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .activity-content p {
            font-size: 13px;
            color: var(--gray);
        }

        .activity-time {
            font-size: 12px;
            color: var(--gray);
        }

        /* Table Status Grid */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .table-card {
            background: var(--white);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .table-card.available {
            border-color: var(--success);
            background: #d4edda;
        }

        .table-card.occupied {
            border-color: var(--danger);
            background: #f8d7da;
        }

        .table-number {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .table-info {
            font-size: 13px;
            color: var(--gray);
        }

        /* Search and Filter */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        /* Revenue Cards */
        .revenue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .revenue-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .revenue-card p {
            font-size: 32px;
            font-weight: 800;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }

        /* Hidden sections */
        .content-section.hidden {
            display: none;
        }

        /* Staff Registration Form Styles */
        .staff-registration-form-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .staff-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-label .required {
            color: #dc3545;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            color: #667eea;
            font-size: 16px;
        }

        .input-with-icon .form-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .input-with-icon .form-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-with-icon select.form-input {
            cursor: pointer;
        }

        .password-toggle-btn {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }

        .password-toggle-btn:hover {
            color: #667eea;
        }

        .form-hint {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }

        .info-box i {
            color: #2196F3;
            font-size: 24px;
            flex-shrink: 0;
        }

        .info-box strong {
            display: block;
            color: #0d47a1;
            margin-bottom: 5px;
        }

        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-message-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 500;
        }

        .form-message-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-message-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Role Badges */
        .role-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .role-waiter { background: #cfe2ff; color: #084298; }
        .role-chef { background: #f8d7da; color: #842029; }
        .role-manager { background: #d1e7dd; color: #0f5132; }
        .role-cashier { background: #fff3cd; color: #664d03; }
        .role-host { background: #e2d9f3; color: #6f42c1; }
        .role-staff { background: #e9ecef; color: #495057; }

        /* Action Buttons */
        .btn-icon {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
        }

        .btn-edit {
            background: #cfe2ff;
            color: #0d6efd;
        }

        .btn-edit:hover {
            background: #0d6efd;
            color: white;
        }

        .btn-delete {
            background: #f8d7da;
            color: #dc3545;
        }

        .btn-delete:hover {
            background: #dc3545;
            color: white;
        }

        /* ========================================================================
           CREATIVE MODAL POPUPS STYLING
           Beautiful animated modals for staff panel
           ======================================================================== */

        /* ========================================================================
           BASE MODAL STYLES
           ======================================================================== */

        .creative-modal {
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

        .creative-modal.show {
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
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .creative-modal.show .modal-container {
            transform: scale(1) translateY(0);
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #666;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .close-btn:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: rotate(90deg);
        }

        /* ========================================================================
           MODAL HEADERS
           ======================================================================== */

        .modal-header {
            text-align: center;
            padding: 40px 30px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .icon-circle.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* ========================================================================
           MODAL BODY
           ======================================================================== */

        .modal-body {
            padding: 30px;
        }

        /* ========================================================================
           PAYMENT MODAL SPECIFIC
           ======================================================================== */

        .payment-container {
            max-width: 500px;
        }

        .payment-details-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row.highlight {
            background: linear-gradient(135deg, #667eea15, #764ba215);
            margin: 0 -20px;
            padding: 15px 20px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .detail-row .label {
            color: #6c757d;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-row .value {
            font-weight: 600;
            color: #2c3e50;
        }

        .detail-row .value.amount {
            font-size: 24px;
            color: #667eea;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .warning-box i {
            color: #ff9800;
            font-size: 24px;
            margin-top: 2px;
        }

        .warning-box p {
            margin: 0;
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ========================================================================
           MODAL FOOTER
           ======================================================================== */

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

        .btn-verify {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-verify:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-complete {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* ========================================================================
           MEMBER MODAL SPECIFIC
           ======================================================================== */

        .member-container {
            max-width: 700px;
        }

        .member-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
        }

        .member-points-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
        }

        .member-points-badge i {
            font-size: 32px;
            margin-bottom: 10px;
            animation: starPulse 2s infinite;
        }

        @keyframes starPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .points-large {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 5px;
        }

        .points-label {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .member-info {
            display: flex;
            gap: 25px;
            justify-content: center;
            margin-top: 15px;
            opacity: 0.95;
            font-size: 14px;
        }

        .member-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 20px 30px;
            background: #f8f9fa;
        }

        .stat-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-label {
            display: block;
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .order-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #667eea;
            animation: slideInUp 0.3s ease forwards;
            opacity: 0;
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
            from {
                opacity: 0;
                transform: translateY(10px);
            }
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .order-id {
            font-weight: 700;
            color: #667eea;
            font-size: 16px;
        }

        .order-date {
            color: #6c757d;
            font-size: 14px;
        }

        .order-details {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #6c757d;
        }

        .detail-item i {
            color: #667eea;
        }

        .order-items {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .order-items h5 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: #6c757d;
        }

        .item-price {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
        }

        .total-amount {
            color: #667eea;
            font-size: 20px;
            font-weight: 700;
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-orders i {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 20px;
        }

        /* ========================================================================
           KITCHEN MODAL SPECIFIC
           ======================================================================== */

        .kitchen-container {
            max-width: 650px;
        }

        .kitchen-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .kitchen-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            animation: pulse 2s infinite;
        }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 10px;
        }

        .urgency-banner {
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
        }

        .urgency-banner.normal {
            background: #d4edda;
            color: #155724;
        }

        .urgency-banner.warning {
            background: #fff3cd;
            color: #856404;
        }

        .urgency-banner.urgent {
            background: #f8d7da;
            color: #721c24;
            animation: urgentPulse 1s infinite;
        }

        @keyframes urgentPulse {
            0%, 100% { background: #f8d7da; }
            50% { background: #f5c6cb; }
        }

        .urgency-banner i {
            font-size: 28px;
        }

        .urgency-banner div {
            flex: 1;
        }

        .urgency-banner strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .urgency-banner span {
            display: block;
            font-size: 14px;
            opacity: 0.8;
        }

        .kitchen-items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .kitchen-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            animation: slideInUp 0.3s ease forwards;
            opacity: 0;
        }

        .item-quantity {
            min-width: 50px;
        }

        .qty-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
        }

        .item-details {
            flex: 1;
        }

        .item-details h4 {
            margin: 0 0 5px;
            font-size: 16px;
            color: #2c3e50;
        }

        .item-category {
            margin: 0;
            font-size: 13px;
            color: #6c757d;
        }

        .cooking-icon {
            color: #ff6b6b;
            font-size: 24px;
            animation: fire 1s infinite;
        }

        @keyframes fire {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .order-summary {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid #dee2e6;
        }

        .summary-row strong {
            color: #2c3e50;
        }

        .priority-normal { color: #28a745; }
        .priority-medium { color: #ffc107; }
        .priority-high { color: #dc3545; }

        .text-danger {
            color: #dc3545 !important;
        }

        /* ========================================================================
           SUCCESS POPUP
           ======================================================================== */

        .success-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            z-index: 11000;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .success-popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .success-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            min-width: 300px;
        }

        .success-checkmark {
            margin-bottom: 20px;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            animation: checkmarkAppear 0.5s ease;
        }

        @keyframes checkmarkAppear {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .checkmark-circle {
            stroke: #28a745;
            stroke-width: 2;
            stroke-miterlimit: 10;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark-check {
            stroke: #28a745;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.3s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        .success-content h3 {
            margin: 0 0 10px;
            font-size: 24px;
            color: #28a745;
        }

        .success-content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        /* ========================================================================
           LOADING SPINNER
           ======================================================================== */

        .loading-spinner-large {
            text-align: center;
            padding: 80px 40px;
            color: #667eea;
        }

        .loading-spinner-large i {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .loading-spinner-large p {
            margin: 0;
            font-size: 16px;
            color: #6c757d;
        }

        /* ========================================================================
           SCROLLBAR STYLING
           ======================================================================== */

        .modal-container::-webkit-scrollbar,
        .orders-list::-webkit-scrollbar {
            width: 8px;
        }

        .modal-container::-webkit-scrollbar-track,
        .orders-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-container::-webkit-scrollbar-thumb,
        .orders-list::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        /* ========================================================================
           RESPONSIVE
           ======================================================================== */

        @media (max-width: 768px) {
            .modal-container {
                width: 95%;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 30px 20px 20px;
            }
            
            .modal-header h2 {
                font-size: 22px;
            }
            
            .icon-circle {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .modal-footer {
                padding: 15px 20px;
                flex-direction: column;
            }
            
            .modal-footer button {
                width: 100%;
                justify-content: center;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .order-details {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-utensils"></i> Wellington</h1>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" data-section="dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="payments">
                            <i class="fas fa-credit-card"></i>
                            <span>Payments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="tables">
                            <i class="fas fa-table"></i>
                            <span>Tables</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="menu">
                            <i class="fas fa-book-open"></i>
                            <span>Menu</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="reservations">
                            <i class="fas fa-calendar-check"></i>
                            <span>Reservations</span>
                        </a>
                    </li>
                    <?php if(isset($currentStaffRole) && (strtolower($currentStaffRole) === 'cashier' || strtolower($currentStaffRole) === 'manager')): ?>
                    <li class="nav-item">
                        <a href="cashier.php" class="nav-link">
                            <i class="fas fa-cash-register"></i>
                            <span>Cashier POS</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="kitchen">
                            <i class="fas fa-fire-burner"></i>
                            <span>Kitchen</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="members">
                            <i class="fas fa-users"></i>
                            <span>Members</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="staff">
                            <i class="fas fa-user-tie"></i>
                            <span>Staff</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="sales">
                            <i class="fas fa-chart-line"></i>
                            <span>Item Sales</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-section="revenue">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Revenue</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="user-info">
                <p>Logged in as <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></strong></p>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section">
                <div class="page-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back! Here's what's happening today</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Today's Reservations</h3>
                            <p><?php echo $stats['today_reservations']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Kitchen Orders</h3>
                            <p><?php echo $stats['today_orders']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Completed Bills</h3>
                            <p><?php echo $stats['completed_bills']; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Today's Revenue</h3>
                            <p>RM <?php echo number_format($todayRevenue, 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-clock"></i> Recent Activity
                        </h2>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <?php if ($activity['type'] === 'new_reservation'): ?>
                                <div class="activity-item new-reservation">
                                    <div class="activity-icon" style="background: var(--success);">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>New Reservation</h4>
                                        <p><?php echo htmlspecialchars($activity['name']); ?> - <?php echo date('M d, Y', strtotime($activity['reservation_date'])); ?> at <?php echo date('g:i A', strtotime($activity['reservation_time'])); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            <?php elseif ($activity['type'] === 'kitchen_order'): ?>
                                <div class="activity-item kitchen-order">
                                    <div class="activity-icon" style="background: var(--warning);">
                                        <i class="fas fa-fire-burner"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Kitchen Order</h4>
                                        <p>Table <?php echo $activity['table_number']; ?> - <?php echo $activity['quantity']; ?>x <?php echo htmlspecialchars($activity['item_name']); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php 
                                        if (isset($activity['time_submitted']) && $activity['time_submitted']) {
                                            echo date('g:i A', strtotime($activity['time_submitted']));
                                        } else {
                                            echo date('g:i A');
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php elseif ($activity['type'] === 'order_complete'): ?>
                                <div class="activity-item order-complete">
                                    <div class="activity-icon" style="background: var(--primary);">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Order Completed</h4>
                                        <p><?php echo htmlspecialchars($activity['name']); ?> - RM <?php echo number_format($activity['total_amount'], 2); ?> (<?php echo ucfirst(str_replace('_', ' ', $activity['payment_method'])); ?>)</p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('g:i A', strtotime($activity['reservation_date'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($recentActivity)): ?>
                            <p style="text-align: center; color: var(--gray); padding: 40px;">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payments Section -->
            <div id="payments-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Payment Confirmations</h1>
                    <p>Manage and verify customer payments</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-hourglass-half"></i> Pending Payments
                        </h2>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label for="payment-date" style="font-size: 14px; color: var(--gray); font-weight: 600;">
                                    <i class="fas fa-calendar-alt"></i> Select Date:
                                </label>
                                <input type="date" 
                                       id="payment-date" 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s;"
                                       onchange="loadPaymentsForDate(this.value)"
                                       onmouseover="this.style.borderColor='#667eea'"
                                       onmouseout="this.style.borderColor='#e0e0e0'">
                            </div>
                            <span id="payment-date-display" style="font-size: 14px; color: var(--gray);">
                                <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="table-container" id="payments-table-container">
                        <div id="payments-loading" style="display: none; text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                            <p style="margin-top: 15px; color: var(--gray);">Loading payments...</p>
                        </div>
                        <table class="data-table" id="payments-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Table</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <tr data-reservation-id="<?php echo $payment['reservation_id']; ?>">
                                        <td>#<?php echo $payment['transaction_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['phone']); ?></td>
                                        <td>Table <?php echo $payment['table_number'] ?? 'N/A'; ?></td>
                                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></span></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-success btn-sm" onclick="verifyPayment(<?php echo $payment['transaction_id']; ?>)">
                                                <i class="fas fa-check"></i> Verify
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pendingPayments)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray);">
                                            No pending payments for today
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tables Section -->
            <div id="tables-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Table Management</h1>
                    <p>Monitor table availability by time slots for today</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-clock"></i> Table Availability Schedule
                        </h2>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label for="availability-date" style="font-size: 14px; color: var(--gray); font-weight: 600;">
                                    <i class="fas fa-calendar-alt"></i> Select Date:
                                </label>
                                <input type="date" 
                                       id="availability-date" 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s;"
                                       onchange="loadTableAvailability(this.value)"
                                       onmouseover="this.style.borderColor='#667eea'"
                                       onmouseout="this.style.borderColor='#e0e0e0'">
                            </div>
                            <span id="selected-date-display" style="font-size: 14px; color: var(--gray);">
                                <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div style="display: flex; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 20px; height: 20px; background: #d4edda; border: 2px solid var(--success); border-radius: 4px;"></div>
                            <span style="font-size: 14px;">Available</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 20px; height: 20px; background: #f8d7da; border: 2px solid var(--danger); border-radius: 4px;"></div>
                            <span style="font-size: 14px;">Occupied</span>
                        </div>
                    </div>

                    <!-- Time Slot Table -->
                    <div class="table-container" id="table-availability-container">
                        <div id="table-availability-loading" style="text-align: center; padding: 40px; display: none;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #667eea; margin-bottom: 15px;"></i>
                            <p style="color: var(--gray);">Loading table availability...</p>
                        </div>
                        <table class="data-table" id="availability-table" style="min-width: 1200px;">
                            <thead>
                                <tr>
                                    <th style="position: sticky; left: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); z-index: 10;">Time</th>
                                    <?php foreach ($tables as $table): ?>
                                        <th style="text-align: center;">
                                            Table <?php echo $table['table_number']; ?>
                                            <br>
                                            <small style="opacity: 0.8;">(<?php echo $table['capacity']; ?> seats)</small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody id="availability-tbody">
                                <?php foreach ($timeSlots as $time => $label): ?>
                                    <tr>
                                        <td style="position: sticky; left: 0; background: white; font-weight: 600; z-index: 5;">
                                            <?php echo $label; ?>
                                        </td>
                                        <?php foreach ($tables as $table): ?>
                                            <?php 
                                                $slot = $tableAvailability[$table['table_id']][$time];
                                                $isAvailable = $slot['status'] === 'available';
                                            ?>
                                            <td style="text-align: center; padding: 12px; background: <?php echo $isAvailable ? '#d4edda' : '#f8d7da'; ?>; border-left: 1px solid #dee2e6;">
                                                <?php if ($isAvailable): ?>
                                                    <div style="color: var(--success); font-weight: 600;">
                                                        <i class="fas fa-check-circle"></i> Available
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color: var(--danger); font-weight: 600; margin-bottom: 4px;">
                                                        <i class="fas fa-user"></i> Booked
                                                    </div>
                                                    <div style="font-size: 12px; color: #721c24;">
                                                        <?php echo htmlspecialchars($slot['customer']); ?>
                                                        <br>
                                                        <small>(<?php echo $slot['party_size']; ?> guests)</small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Quick Stats -->
                    <div id="availability-stats" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <?php
                        $totalSlots = count($tables) * count($timeSlots);
                        $occupiedSlots = 0;
                        foreach ($tableAvailability as $tableSlots) {
                            foreach ($tableSlots as $slot) {
                                if ($slot['status'] === 'occupied') $occupiedSlots++;
                            }
                        }
                        $availableSlots = $totalSlots - $occupiedSlots;
                        $occupancyRate = $totalSlots > 0 ? ($occupiedSlots / $totalSlots) * 100 : 0;
                        ?>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                                <i class="fas fa-chair"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Tables</h3>
                                <p id="stat-total-tables"><?php echo count($tables); ?></p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Slots</h3>
                                <p id="stat-total-slots"><?php echo $totalSlots; ?></p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Occupied Slots</h3>
                                <p id="stat-occupied-slots"><?php echo $occupiedSlots; ?></p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Occupancy Rate</h3>
                                <p id="stat-occupancy-rate"><?php echo number_format($occupancyRate, 1); ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Section -->
            <div id="menu-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Menu Management</h1>
                    <p>View and update menu items</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-book-open"></i> Menu Items
                        </h2>
                        <button class="btn btn-primary" onclick="showMenuModal()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>

                    <div class="search-box">
                        <input type="text" id="menu-search" placeholder="Search menu items..." onkeyup="searchTable('menu-search', 'menu-table')">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="menu-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menuItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                        <td><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></td>
                                        <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="showMenuModal('<?php echo htmlspecialchars($item['item_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['category'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['description'], ENT_QUOTES); ?>', <?php echo $item['price']; ?>)">
                                                <i class="fas fa-edit"></i> Modify
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reservations Section -->
            <div id="reservations-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Reservations</h1>
                    <p>Manage customer bookings</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Reservations
                            <span id="reservation-count-badge" style="margin-left: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                <?php echo count($reservations); ?> Today
                            </span>
                        </h2>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label for="reservation-date" style="font-size: 14px; color: var(--gray); font-weight: 600;">
                                    <i class="fas fa-calendar-alt"></i> Select Date:
                                </label>
                                <input type="date" 
                                       id="reservation-date" 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s;"
                                       onchange="loadReservationsForDate(this.value)"
                                       onmouseover="this.style.borderColor='#667eea'"
                                       onmouseout="this.style.borderColor='#e0e0e0'">
                            </div>
                            <span id="reservation-date-display" style="font-size: 14px; color: var(--gray);">
                                <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="search-box">
                        <input type="text" id="reservation-search" placeholder="Search reservations..." onkeyup="searchTable('reservation-search', 'reservation-table')">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="table-container" id="reservations-table-container">
                        <div id="reservations-loading" style="display: none; text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                            <p style="margin-top: 15px; color: var(--gray);">Loading reservations...</p>
                        </div>
                        <table class="data-table" id="reservation-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Guests</th>
                                    <th>Table</th>
                                </tr>
                            </thead>
                            <tbody id="reservations-tbody">
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></td>
                                        <td><?php echo $reservation['party_size']; ?></td>
                                        <td>Table <?php echo $reservation['table_number']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray);">
                                            No reservations for today
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kitchen Section -->
            <div id="kitchen-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Kitchen Orders</h1>
                    <p>Monitor and manage kitchen orders</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-fire-burner"></i> Today's Kitchen Orders
                        </h2>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Reservation Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="kitchen-orders-tbody">
                                <?php foreach ($kitchenOrders as $order): ?>
                                    <tr id="kitchen-order-<?php echo $order['kitchen_id']; ?>">
                                        <td>Table <?php echo $order['table_number']; ?></td>
                                        <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($order['category']); ?></span></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td>
                                            <?php 
                                            if ($order['reservation_time']) {
                                                echo date('g:i A', strtotime($order['reservation_time']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="completeOrder(<?php echo $order['kitchen_id']; ?>, <?php echo $order['reservation_id'] ?? 'null'; ?>, '<?php echo htmlspecialchars($order['item_id'] ?? '', ENT_QUOTES); ?>', <?php echo $order['table_id'] ?? 'null'; ?>)">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($kitchenOrders)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">
                                            No kitchen orders today
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Members Section -->
            <div id="members-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Member Management</h1>
                    <p>View member details and order history</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i> All Members
                        </h2>
                    </div>

                    <div class="search-box">
                        <input type="text" id="member-search" placeholder="Search members..." onkeyup="searchTable('member-search', 'member-table')">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="member-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Points</th>
                                    <th>Total Reservations</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td>#<?php echo $member['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></td>
                                        <td><span class="badge badge-success"><?php echo $member['points'] ?? 0; ?></span></td>
                                        <td><?php echo $member['total_reservations']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['register_time'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="viewMemberOrders(<?php echo $member['user_id']; ?>, '<?php echo addslashes($member['full_name']); ?>')">
                                                <i class="fas fa-eye"></i> View Orders
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================================
                 STAFF REGISTRATION SECTION
                 ======================================================================== -->

            <div id="staff-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Staff Management</h1>
                    <p>Create new staff accounts and manage team members</p>
                </div>

                <!-- Add New Staff Member -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-user-plus"></i> Add New Staff Member
                        </h2>
                    </div>

                    <div class="staff-registration-form-container">
                        <form id="staffRegisterForm" class="staff-form">
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" id="staff_first_name" class="form-input" placeholder="Enter first name" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" id="staff_last_name" class="form-input" placeholder="Enter last name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Username <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user-circle"></i>
                                        <input type="text" id="staff_username" class="form-input" placeholder="Choose username" required>
                                    </div>
                                    <small class="form-hint">Must be unique</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Email <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-envelope"></i>
                                        <input type="email" id="staff_email" class="form-input" placeholder="Enter email address" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-phone"></i>
                                        <input type="tel" id="staff_phone" class="form-input" placeholder="Enter phone number" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Staff Role <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-briefcase"></i>
                                        <select id="staff_staff_role" class="form-input" required>
                                            <option value="">Select role...</option>
                                            <option value="waiter">Waiter</option>
                                            <option value="chef">Chef</option>
                                            <option value="manager">Manager</option>
                                            <option value="cashier">Cashier</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Password <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" id="staff_password" class="form-input" placeholder="Create password" required>
                                        <i class="fas fa-eye password-toggle-btn" onclick="toggleStaffPassword('staff_password')"></i>
                                    </div>
                                    <small class="form-hint">Minimum 8 characters</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" id="staff_confirm_password" class="form-input" placeholder="Confirm password" required>
                                        <i class="fas fa-eye password-toggle-btn" onclick="toggleStaffPassword('staff_confirm_password')"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Information Box -->
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Account Creation</strong>
                                    <p>New staff members will receive their login credentials. Ensure all information is correct before submitting.</p>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="resetStaffForm()">
                                    <i class="fas fa-redo"></i> Reset Form
                                </button>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-user-plus"></i> Create Staff Account
                                </button>
                            </div>
                        </form>

                        <div id="staffRegisterMessage" class="form-message-box" style="display: none;"></div>
                    </div>
                </div>

                <!-- Current Staff List -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i> Current Staff Members
                        </h2>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="staffListBody">
                            <?php
                            // Fetch all staff members
                            // UPDATED: Use new schema - accounts and staffs
                            $stmt = $pdo->prepare("
                                SELECT a.account_id as user_id, a.username, a.register_time, 
                                       s.staff_id, s.staff_name as full_name, s.email, s.phone, s.role
                                FROM accounts a
                                JOIN staffs s ON a.account_id = s.account_id
                                WHERE a.role = 'staff'
                                ORDER BY a.register_time DESC
                            ");
                            $stmt->execute();
                            $staffMembers = $stmt->fetchAll();
                            
                            foreach ($staffMembers as $staff): ?>
                                <tr>
                                    <td><?php echo $staff['user_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($staff['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo strtolower($staff['role'] ?? 'staff'); ?>">
                                            <?php echo ucfirst($staff['role'] ?? 'Staff'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($staff['register_time'])); ?></td>
                                    <td>
                                        <button class="btn-icon btn-edit" onclick="editStaff(<?php echo $staff['user_id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="confirmDeleteStaff(<?php echo $staff['user_id']; ?>, '<?php echo htmlspecialchars($staff['full_name']); ?>')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sales Section -->
            <div id="sales-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Item Sales Analytics</h1>
                    <p>View top-selling menu items</p>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-trophy"></i> Top Selling Items
                        </h2>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Unit Price</th>
                                    <th>Total Sold</th>
                                    <th>Times Ordered</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($topItems as $item): ?>
                                    <tr>
                                        <td><strong><?php echo $rank++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                        <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                        <td><strong><?php echo $item['total_sold']; ?></strong></td>
                                        <td><?php echo $item['times_ordered']; ?></td>
                                        <td><strong>RM <?php echo number_format($item['total_revenue'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topItems)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--gray);">
                                            No sales data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Revenue Section -->
            <div id="revenue-section" class="content-section hidden">
                <div class="page-header">
                    <h1>Revenue Analytics</h1>
                    <p>Track earnings and financial performance with detailed breakdowns</p>
                </div>

                <!-- Revenue Summary Cards -->
                <div class="revenue-grid">
                    <div class="revenue-card">
                        <h3><i class="fas fa-calendar-day"></i> Today</h3>
                        <p>RM <?php echo number_format($todayRevenue, 2); ?></p>
                        <small style="opacity: 0.9; font-size: 12px;">
                            <?php 
                            $todayCount = array_sum(array_column($todayRevenueByMethod, 'transaction_count'));
                            echo $todayCount; ?> transactions
                        </small>
                    </div>

                    <div class="revenue-card">
                        <h3><i class="fas fa-calendar-week"></i> This Week</h3>
                        <p>RM <?php echo number_format($weekRevenue, 2); ?></p>
                        <small style="opacity: 0.9; font-size: 12px;">
                            <?php 
                            $weekCount = array_sum(array_column($weekRevenueByMethod, 'transaction_count'));
                            echo $weekCount; ?> transactions
                        </small>
                    </div>

                    <div class="revenue-card">
                        <h3><i class="fas fa-calendar-alt"></i> This Month</h3>
                        <p>RM <?php echo number_format($monthRevenue, 2); ?></p>
                        <small style="opacity: 0.9; font-size: 12px;">
                            <?php 
                            $monthCount = array_sum(array_column($monthRevenueByMethod, 'transaction_count'));
                            echo $monthCount; ?> transactions
                        </small>
                    </div>
                </div>

                <!-- Today's Revenue Breakdown -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-chart-pie"></i> Today's Revenue by Payment Method
                        </h2>
                    </div>
                    
                    <?php if (empty($todayRevenueByMethod)): ?>
                        <p style="text-align: center; padding: 40px; color: var(--gray);">
                            <i class="fas fa-inbox" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                            No revenue recorded today
                        </p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <?php foreach ($todayRevenueByMethod as $method): ?>
                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: var(--shadow);">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                        <h3 style="margin: 0; font-size: 16px; opacity: 0.9;">
                                            <?php 
                                            $methodIcons = [
                                                'touch_n_go' => 'fa-mobile-alt',
                                                'bank_transfer' => 'fa-university',
                                                'cash' => 'fa-money-bill-wave',
                                                'online' => 'fa-credit-card'
                                            ];
                                            $icon = $methodIcons[$method['payment_method']] ?? 'fa-dollar-sign';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $method['payment_method'])); ?>
                                        </h3>
                                        <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                            <?php echo $method['transaction_count']; ?> txns
                                        </span>
                                    </div>
                                    <p style="margin: 0; font-size: 32px; font-weight: 800;">
                                        RM <?php echo number_format($method['total_amount'], 2); ?>
                                    </p>
                                    <div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                                        Avg: RM <?php echo $method['transaction_count'] > 0 ? number_format($method['total_amount'] / $method['transaction_count'], 2) : '0.00'; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Detailed Table -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Transactions</th>
                                        <th>Total Amount</th>
                                        <th>Average</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayRevenueByMethod as $method): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo ucwords(str_replace('_', ' ', $method['payment_method'])); ?></strong>
                                            </td>
                                            <td><?php echo $method['transaction_count']; ?></td>
                                            <td><strong>RM <?php echo number_format($method['total_amount'], 2); ?></strong></td>
                                            <td>RM <?php echo $method['transaction_count'] > 0 ? number_format($method['total_amount'] / $method['transaction_count'], 2) : '0.00'; ?></td>
                                            <td>
                                                <?php $percentage = $todayRevenue > 0 ? ($method['total_amount'] / $todayRevenue) * 100 : 0; ?>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="flex: 1; background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                                                        <div style="width: <?php echo $percentage; ?>%; background: linear-gradient(135deg, #667eea, #764ba2); height: 100%;"></div>
                                                    </div>
                                                    <span style="min-width: 50px; text-align: right; font-weight: 600;">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot style="background: #f8f9fa; font-weight: bold;">
                                    <tr>
                                        <td>TOTAL</td>
                                        <td><?php echo array_sum(array_column($todayRevenueByMethod, 'transaction_count')); ?></td>
                                        <td>RM <?php echo number_format($todayRevenue, 2); ?></td>
                                        <td>-</td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- This Week's Revenue Breakdown -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-week"></i> This Week's Revenue by Payment Method
                        </h2>
                    </div>
                    
                    <?php if (empty($weekRevenueByMethod)): ?>
                        <p style="text-align: center; padding: 40px; color: var(--gray);">No revenue this week</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Transactions</th>
                                        <th>Total Amount</th>
                                        <th>Average</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weekRevenueByMethod as $method): ?>
                                        <tr>
                                            <td><strong><?php echo ucwords(str_replace('_', ' ', $method['payment_method'])); ?></strong></td>
                                            <td><?php echo $method['transaction_count']; ?></td>
                                            <td><strong>RM <?php echo number_format($method['total_amount'], 2); ?></strong></td>
                                            <td>RM <?php echo $method['transaction_count'] > 0 ? number_format($method['total_amount'] / $method['transaction_count'], 2) : '0.00'; ?></td>
                                            <td>
                                                <?php $percentage = $weekRevenue > 0 ? ($method['total_amount'] / $weekRevenue) * 100 : 0; ?>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="flex: 1; background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                                                        <div style="width: <?php echo $percentage; ?>%; background: linear-gradient(135deg, #667eea, #764ba2); height: 100%;"></div>
                                                    </div>
                                                    <span style="min-width: 50px; text-align: right; font-weight: 600;">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot style="background: #f8f9fa; font-weight: bold;">
                                    <tr>
                                        <td>TOTAL</td>
                                        <td><?php echo array_sum(array_column($weekRevenueByMethod, 'transaction_count')); ?></td>
                                        <td>RM <?php echo number_format($weekRevenue, 2); ?></td>
                                        <td>-</td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- This Month's Revenue Breakdown -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> This Month's Revenue by Payment Method
                        </h2>
                    </div>
                    
                    <?php if (empty($monthRevenueByMethod)): ?>
                        <p style="text-align: center; padding: 40px; color: var(--gray);">No revenue this month</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Transactions</th>
                                        <th>Total Amount</th>
                                        <th>Average</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthRevenueByMethod as $method): ?>
                                        <tr>
                                            <td><strong><?php echo ucwords(str_replace('_', ' ', $method['payment_method'])); ?></strong></td>
                                            <td><?php echo $method['transaction_count']; ?></td>
                                            <td><strong>RM <?php echo number_format($method['total_amount'], 2); ?></strong></td>
                                            <td>RM <?php echo $method['transaction_count'] > 0 ? number_format($method['total_amount'] / $method['transaction_count'], 2) : '0.00'; ?></td>
                                            <td>
                                                <?php $percentage = $monthRevenue > 0 ? ($method['total_amount'] / $monthRevenue) * 100 : 0; ?>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="flex: 1; background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                                                        <div style="width: <?php echo $percentage; ?>%; background: linear-gradient(135deg, #667eea, #764ba2); height: 100%;"></div>
                                                    </div>
                                                    <span style="min-width: 50px; text-align: right; font-weight: 600;">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot style="background: #f8f9fa; font-weight: bold;">
                                    <tr>
                                        <td>TOTAL</td>
                                        <td><?php echo array_sum(array_column($monthRevenueByMethod, 'transaction_count')); ?></td>
                                        <td>RM <?php echo number_format($monthRevenue, 2); ?></td>
                                        <td>-</td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.add('hidden');
                });
                
                // Show selected section
                const sectionName = this.dataset.section;
                const sectionElement = document.getElementById(sectionName + '-section');
                if (sectionElement) {
                    sectionElement.classList.remove('hidden');
                    
                    // If payments section is opened, ensure today's payments are loaded
                    if (sectionName === 'payments') {
                        const paymentDateInput = document.getElementById('payment-date');
                        if (paymentDateInput) {
                            // Set to today's date if not already set
                            const today = new Date().toISOString().split('T')[0];
                            if (!paymentDateInput.value || paymentDateInput.value !== today) {
                                paymentDateInput.value = today;
                            }
                            // Load payments for the selected date
                            loadPaymentsForDate(paymentDateInput.value);
                        }
                    }
                    
                    // If reservations section is opened, ensure today's reservations are loaded
                    if (sectionName === 'reservations') {
                        const reservationDateInput = document.getElementById('reservation-date');
                        if (reservationDateInput) {
                            // Set to today's date if not already set
                            const today = new Date().toISOString().split('T')[0];
                            if (!reservationDateInput.value || reservationDateInput.value !== today) {
                                reservationDateInput.value = today;
                            }
                            // Load reservations for the selected date
                            loadReservationsForDate(reservationDateInput.value);
                        }
                    }
                }
            });
        });

        // ========================================================================
        // STAFF REGISTRATION FORM HANDLER
        // ========================================================================

        const staffRegisterForm = document.getElementById('staffRegisterForm');
        if (staffRegisterForm) {
            staffRegisterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form values
                const firstName = document.getElementById('staff_first_name').value.trim();
                const lastName = document.getElementById('staff_last_name').value.trim();
                const username = document.getElementById('staff_username').value.trim();
                const email = document.getElementById('staff_email').value.trim();
                const phone = document.getElementById('staff_phone').value.trim();
                const staffRole = document.getElementById('staff_staff_role').value;
                const password = document.getElementById('staff_password').value;
                const confirmPassword = document.getElementById('staff_confirm_password').value;
                
                // Validation
                if (!firstName || !lastName || !username || !email || !phone || !staffRole) {
                    showStaffMessage('Please fill in all required fields', 'error');
                    return;
                }
                
                if (password.length < 8) {
                    showStaffMessage('Password must be at least 8 characters', 'error');
                    return;
                }
                
                if (password !== confirmPassword) {
                    showStaffMessage('Passwords do not match', 'error');
                    return;
                }
                
                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'register_staff');
                formData.append('first_name', firstName);
                formData.append('last_name', lastName);
                formData.append('username', username);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('staff_role', staffRole);
                formData.append('password', password);
                formData.append('role', 'staff'); // User role is always staff
                
                // Submit
                fetch('staff_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.success) {
                        showStaffMessage('Staff account created successfully! Username: ' + username, 'success');
                        resetStaffForm();
                        // Add new staff member to the table
                        if (data.staff) {
                            addStaffMemberToTable(data.staff);
                        }
                    } else {
                        showStaffMessage(data.message || 'Error creating staff account', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showStaffMessage('An error occurred. Please try again.', 'error');
                });
            });
        }

        function showStaffMessage(message, type) {
            const messageBox = document.getElementById('staffRegisterMessage');
            if (!messageBox) return;
            messageBox.textContent = message;
            messageBox.className = 'form-message-box ' + type;
            messageBox.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }

        function resetStaffForm() {
            const form = document.getElementById('staffRegisterForm');
            if (form) form.reset();
            const box = document.getElementById('staffRegisterMessage');
            if (box) box.style.display = 'none';
        }

        function addStaffMemberToTable(staff) {
            const tbody = document.getElementById('staffListBody');
            if (!tbody || !staff) return;
            
            // Remove empty message if exists
            const emptyRow = tbody.querySelector('tr:only-child td[colspan]');
            if (emptyRow) {
                emptyRow.closest('tr').remove();
            }
            
            // Format date
            const registerDate = new Date(staff.register_time);
            const formattedDate = registerDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
            
            // Create new row
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${staff.user_id}</td>
                <td><strong>${escapeHtml(staff.full_name)}</strong></td>
                <td>${escapeHtml(staff.username)}</td>
                <td>${escapeHtml(staff.email)}</td>
                <td>${escapeHtml(staff.phone || 'N/A')}</td>
                <td>
                    <span class="role-badge role-${(staff.role || 'staff').toLowerCase()}">
                        ${(staff.role || 'Staff').charAt(0).toUpperCase() + (staff.role || 'Staff').slice(1)}
                    </span>
                </td>
                <td>${formattedDate}</td>
                <td>
                    <button class="btn-icon btn-edit" onclick="editStaff(${staff.user_id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="confirmDeleteStaff(${staff.user_id}, '${escapeHtml(staff.full_name)}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            // Add row at the top (newest first)
            tbody.insertBefore(newRow, tbody.firstChild);
            
            // Add highlight animation
            newRow.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                newRow.style.transition = 'background-color 2s';
                newRow.style.backgroundColor = '';
            }, 100);
        }

        function toggleStaffPassword(fieldId) {
            const field = document.getElementById(fieldId);
            if (!field) return;
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                field.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }

        function editStaff(userId) {
            // Fetch staff data first
            fetch(`staff_actions.php?action=get_staff_details&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.staff) {
                        showEditStaffModal(data.staff);
                    } else {
                        alert('Error loading staff details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load staff details');
                });
        }

        function showEditStaffModal(staff) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal staff-edit-modal';
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container staff-edit-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <h2>Edit Staff Member</h2>
                        <p class="subtitle">Update staff information</p>
                    </div>
                    
                    <div class="modal-body">
                        <form id="editStaffForm" style="display: flex; flex-direction: column; gap: 20px;">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray);">
                                    <i class="fas fa-user"></i> Full Name
                                </label>
                                <input type="text" 
                                       id="edit_staff_name" 
                                       class="form-input" 
                                       value="${escapeHtml(staff.full_name)}" 
                                       required
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray);">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input type="tel" 
                                       id="edit_staff_phone" 
                                       class="form-input" 
                                       value="${escapeHtml(staff.phone || '')}" 
                                       required
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray);">
                                    <i class="fas fa-briefcase"></i> Staff Role
                                </label>
                                <select id="edit_staff_role" 
                                        class="form-input" 
                                        required
                                        style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                                    <option value="waiter" ${staff.role === 'waiter' ? 'selected' : ''}>Waiter</option>
                                    <option value="chef" ${staff.role === 'chef' ? 'selected' : ''}>Chef</option>
                                    <option value="manager" ${staff.role === 'manager' ? 'selected' : ''}>Manager</option>
                                    <option value="cashier" ${staff.role === 'cashier' ? 'selected' : ''}>Cashier</option>
                                </select>
                            </div>
                            
                            <div class="info-box" style="background: #f0f7ff; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                                <i class="fas fa-info-circle" style="color: #667eea;"></i>
                                <span style="margin-left: 10px; color: var(--gray); font-size: 14px;">
                                    Username and email cannot be changed for security reasons.
                                </span>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeEditStaffModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="saveStaffChanges(${staff.user_id})">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeEditStaffModal() {
            const modal = document.querySelector('.staff-edit-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function saveStaffChanges(userId) {
            const name = document.getElementById('edit_staff_name').value.trim();
            const phone = document.getElementById('edit_staff_phone').value.trim();
            const role = document.getElementById('edit_staff_role').value;
            
            if (!name || !phone || !role) {
                alert('Please fill in all fields');
                return;
            }
            
            // Show loading state
            const saveBtn = document.querySelector('.staff-edit-modal .btn-verify');
            const originalHTML = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Call AJAX to update
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_staff&user_id=${userId}&full_name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}&role=${encodeURIComponent(role)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    closeEditStaffModal();
                    showSuccessAnimation('Success!', 'Staff member updated successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHTML;
            });
        }

        function confirmDeleteStaff(userId, name) {
            showDeleteStaffModal(userId, name);
        }

        function showDeleteStaffModal(userId, name) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal delete-staff-modal';
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container delete-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Delete Staff Member</h2>
                        <p class="subtitle">This action cannot be undone</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="warning-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="color: #856404; margin-right: 10px;"></i>
                            <p style="margin: 0; color: #856404; font-weight: 600;">
                                Are you sure you want to delete staff member: <strong>${escapeHtml(name)}</strong>?
                            </p>
                        </div>
                        
                        <div class="info-box" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <i class="fas fa-info-circle" style="color: #6c757d;"></i>
                            <span style="margin-left: 10px; color: #6c757d; font-size: 14px;">
                                This will permanently delete the staff member's account and all associated data.
                            </span>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeDeleteStaffModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="proceedDeleteStaff(${userId})" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-trash"></i> Delete Staff Member
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeDeleteStaffModal() {
            const modal = document.querySelector('.delete-staff-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function proceedDeleteStaff(userId) {
            closeDeleteStaffModal();
            deleteStaff(userId);
        }

        function deleteStaff(userId) {
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_staff&user_id=' + encodeURIComponent(userId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessAnimation('Staff Deleted!', 'Staff member has been deleted successfully.');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        // Delete member functions
        function confirmDeleteMember(userId, name) {
            showDeleteMemberModal(userId, name);
        }

        function showDeleteMemberModal(userId, name) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal delete-member-modal';
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container delete-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Delete Member</h2>
                        <p class="subtitle">This action cannot be undone</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="warning-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="color: #856404; margin-right: 10px;"></i>
                            <p style="margin: 0; color: #856404; font-weight: 600;">
                                Are you sure you want to delete member: <strong>${escapeHtml(name)}</strong>?
                            </p>
                        </div>
                        
                        <div class="info-box" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <i class="fas fa-info-circle" style="color: #6c757d;"></i>
                            <span style="margin-left: 10px; color: #6c757d; font-size: 14px;">
                                This will permanently delete the member account and all associated data.
                            </span>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeDeleteMemberModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="proceedDeleteMember(${userId})" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-trash"></i> Delete Member
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeDeleteMemberModal() {
            const modal = document.querySelector('.delete-member-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function proceedDeleteMember(userId) {
            closeDeleteMemberModal();
            deleteMember(userId);
        }

        function deleteMember(userId) {
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_member&user_id=' + encodeURIComponent(userId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessAnimation('Member Deleted!', 'Member has been deleted successfully.');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        // Search function
        function searchTable(searchInputId, tableId) {
            const input = document.getElementById(searchInputId);
            const filter = input.value.toUpperCase();
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let txtValue = tr[i].textContent || tr[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }

        // Verify payment - Updated to use modal
        function verifyPayment(transactionId) {
            // Get payment details from the table row
            let row = event.target.closest('tr');
            if (!row) {
                // Fallback: try to find row by transaction ID
                const rows = document.querySelectorAll('#payments-section tbody tr');
                for (let r of rows) {
                    if (r.cells[0] && r.cells[0].textContent.includes('#' + transactionId)) {
                        row = r;
                        break;
                    }
                }
            }
            
            if (!row) {
                alert('Could not find payment details');
                return;
            }
            
            const customerName = row.cells[1].textContent.trim();
            const amount = row.cells[4].textContent.replace('RM ', '').replace(',', '').trim();
            const paymentMethod = row.cells[5].querySelector('.badge')?.textContent.trim().toLowerCase().replace(' ', '_') || 'touch_n_go';
            const reservationId = row.dataset.reservationId || row.getAttribute('data-reservation-id') || 0;
            
            showPaymentVerifyModal(transactionId, customerName, amount, paymentMethod, parseInt(reservationId));
        }

        // Complete kitchen order - removes the order
        // Load kitchen orders dynamically
        function loadKitchenOrders() {
            const tbody = document.getElementById('kitchen-orders-tbody');
            if (!tbody) return;
            
            // Show loading state
            const originalHTML = tbody.innerHTML;
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading orders...</td></tr>';
            
            fetch('staff_actions.php?action=get_kitchen_orders')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.orders && data.orders.length > 0) {
                        renderKitchenOrders(data.orders);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">No kitchen orders today</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = originalHTML;
                });
        }

        function renderKitchenOrders(orders) {
            const tbody = document.getElementById('kitchen-orders-tbody');
            if (!tbody) return;
            
            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">No kitchen orders today</td></tr>';
                return;
            }
            
            let html = '';
            orders.forEach(order => {
                const reservationTime = order.reservation_time 
                    ? new Date('2000-01-01T' + order.reservation_time).toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true
                    })
                    : 'N/A';
                
                html += `
                    <tr id="kitchen-order-${order.kitchen_id}">
                        <td>Table ${order.table_number || 'N/A'}</td>
                        <td>${escapeHtml(order.item_name || 'N/A')}</td>
                        <td><span class="badge badge-warning">${escapeHtml(order.category || 'N/A')}</span></td>
                        <td>${order.quantity || 1}</td>
                        <td>${reservationTime}</td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="completeOrder(${order.kitchen_id}, ${order.reservation_id || 'null'}, '${escapeHtml(order.item_id || '')}', ${order.table_id || 'null'})">
                                <i class="fas fa-check"></i> Complete
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }

        function completeOrder(orderId, reservationId, itemId, tableId) {
            if (confirm('Complete and remove this order from the kitchen?')) {
                const params = new URLSearchParams({
                    action: 'complete_order',
                    order_id: orderId
                });
                
                if (reservationId && reservationId !== 'null') {
                    params.append('reservation_id', reservationId);
                }
                if (itemId && itemId !== '') {
                    params.append('item_id', itemId);
                }
                if (tableId && tableId !== 'null') {
                    params.append('table_id', tableId);
                }
                
                fetch('staff_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: params.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.getElementById('kitchen-order-' + orderId);
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                // Check if table is empty
                                const tbody = document.querySelector('#kitchen-section tbody');
                                if (tbody && tbody.children.length === 0) {
                                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">No kitchen orders today</td></tr>';
                                }
                            }, 300);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }

        // View member orders - Updated to use modal
        function viewMemberOrders(userId, userName) {
            // Get member details from the table row
            const row = event.target.closest('tr');
            let memberPhone = '';
            let memberPoints = 0;
            
            if (row) {
                memberPhone = row.cells[3]?.textContent.trim() || '';
                const pointsText = row.cells[4]?.textContent.trim() || '0';
                memberPoints = parseInt(pointsText.replace(/\D/g, '')) || 0;
            }
            
            showMemberOrdersModal(userId, userName, memberPhone, memberPoints);
        }

        // Logout
        function logout() {
            showLogoutModal();
        }

        function showLogoutModal() {
            const modal = document.createElement('div');
            modal.className = 'creative-modal logout-modal';
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container logout-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <h2>Logout</h2>
                        <p class="subtitle">Are you sure you want to logout?</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="info-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                            <i class="fas fa-info-circle" style="color: #667eea; font-size: 24px; margin-bottom: 10px;"></i>
                            <p style="margin: 0; color: #6c757d; font-size: 14px;">
                                You will be redirected to the login page after logging out.
                            </p>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeLogoutModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="proceedLogout()">
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

        function proceedLogout() {
            // Since staff.php is in staff/ subdirectory, go up one level to reach logout.php in root
            window.location.href = '../logout.php';
        }


        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            if (document.querySelector('.nav-link.active').dataset.section === 'dashboard') {
                location.reload();
            }
        }, 30000);

        /* ========================================================================
           STAFF PANEL CREATIVE MODAL POPUPS
           Beautiful animated popups for Payment Verification, Member Orders, Kitchen
           ======================================================================== */

        // ========================================================================
        // PAYMENT VERIFICATION MODAL
        // ========================================================================

        function showPaymentVerifyModal(transactionId, customerName, amount, paymentMethod, reservationId) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal payment-modal';
            
            // First show modal with loading state for items
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container payment-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h2>Verify Payment</h2>
                        <p class="subtitle">Confirm payment approval for this transaction</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="payment-details-card">
                            <div class="detail-row">
                                <span class="label"><i class="fas fa-user"></i> Customer</span>
                                <span class="value">${customerName}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i class="fas fa-hashtag"></i> Transaction ID</span>
                                <span class="value">#${transactionId}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i class="fas fa-wallet"></i> Payment Method</span>
                                <span class="value">${paymentMethod.replace('_', ' ').toUpperCase()}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="label"><i class="fas fa-money-bill-wave"></i> Amount</span>
                                <span class="value amount">RM ${parseFloat(amount).toFixed(2)}</span>
                            </div>
                        </div>
                        
                        <div id="bill-items-container" style="margin-top: 20px;">
                            <div style="text-align: center; padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Loading order items...
                            </div>
                        </div>
                        
                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p><strong>Important:</strong> Once verified, this action cannot be undone. The order will be sent to the kitchen automatically.</p>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closePaymentModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="confirmPaymentVerification(${transactionId}, ${reservationId})">
                            <i class="fas fa-check-circle"></i> Verify Payment
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
            
            // Fetch bill items with item names
            if (reservationId > 0) {
                fetch(`staff_actions.php?action=get_bill_items&reservation_id=${reservationId}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('bill-items-container');
                        if (data.success && data.items && data.items.length > 0) {
                            let itemsHTML = `
                                <div class="payment-details-card" style="margin-top: 0;">
                                    <h4 style="margin-bottom: 15px; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-utensils"></i> Order Items
                                    </h4>
                                    ${data.items.map(item => `
                                        <div class="detail-row">
                                            <span class="label">
                                                <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px;"></i>
                                                ${escapeHtml(item.item_name || 'Unknown Item')}
                                                <span style="margin-left: 10px; color: var(--gray); font-size: 12px;">
                                                    (${item.quantity}x)
                                                </span>
                                            </span>
                                            <span class="value">RM ${parseFloat(item.item_total || 0).toFixed(2)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                            container.innerHTML = itemsHTML;
                        } else {
                            container.innerHTML = `
                                <div class="payment-details-card" style="margin-top: 0;">
                                    <p style="text-align: center; color: var(--gray); padding: 20px;">
                                        <i class="fas fa-info-circle"></i> No order items found
                                    </p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching bill items:', error);
                        const container = document.getElementById('bill-items-container');
                        container.innerHTML = `
                            <div class="payment-details-card" style="margin-top: 0;">
                                <p style="text-align: center; color: var(--danger); padding: 20px;">
                                    <i class="fas fa-exclamation-triangle"></i> Error loading order items
                                </p>
                            </div>
                        `;
                    });
            } else {
                const container = document.getElementById('bill-items-container');
                container.innerHTML = `
                    <div class="payment-details-card" style="margin-top: 0;">
                        <p style="text-align: center; color: var(--gray); padding: 20px;">
                            <i class="fas fa-info-circle"></i> Reservation ID not available
                        </p>
                    </div>
                `;
            }
        }

        function closePaymentModal() {
            const modal = document.querySelector('.payment-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function confirmPaymentVerification(transactionId, reservationId) {
            // Show loading state
            const verifyBtn = document.querySelector('.btn-verify');
            const originalHTML = verifyBtn.innerHTML;
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Call AJAX to verify
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=verify_payment&transaction_id=${transactionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success animation
                    showSuccessAnimation('Payment Verified!', 'Order has been sent to kitchen');
                    closePaymentModal();
                    // Reload payments for current date
                    const paymentDateInput = document.getElementById('payment-date');
                    if (paymentDateInput && paymentDateInput.value) {
                        setTimeout(() => loadPaymentsForDate(paymentDateInput.value), 1500);
                    } else {
                        setTimeout(() => location.reload(), 1500);
                    }
                    // Refresh kitchen section if it's currently visible
                    setTimeout(() => {
                        const kitchenSection = document.getElementById('kitchen-section');
                        if (kitchenSection && !kitchenSection.classList.contains('hidden')) {
                            loadKitchenOrders();
                        }
                    }, 1500);
                } else {
                    alert('Error: ' + data.message);
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = originalHTML;
            });
        }

        // ========================================================================
        // MEMBER ORDERS MODAL
        // ========================================================================

        function showMemberOrdersModal(memberId, memberName, memberPhone, memberPoints) {
            // Show loading first
            const loadingModal = document.createElement('div');
            loadingModal.className = 'creative-modal member-modal';
            loadingModal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container member-container">
                    <div class="loading-spinner-large">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading member orders...</p>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingModal);
            setTimeout(() => loadingModal.classList.add('show'), 10);
            
            // Fetch member orders
            fetch(`staff_actions.php?action=get_member_orders&member_id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    loadingModal.remove();
                    
                    if (data.status === 'success') {
                        displayMemberOrdersModal(memberName, memberPhone, memberPoints, data.orders, data.total_spent);
                    } else {
                        alert('Error loading orders: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingModal.remove();
                    alert('Failed to load member orders');
                });
        }

        function displayMemberOrdersModal(name, phone, points, orders, totalSpent) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal member-modal';
            
            let ordersHTML = '';
            if (orders && orders.length > 0) {
                ordersHTML = orders.map((order, index) => `
                    <div class="order-card" style="animation-delay: ${index * 0.1}s">
                        <div class="order-header">
                            <span class="order-id">#${order.reservation_id}</span>
                            <span class="order-date">${new Date(order.reservation_date).toLocaleDateString()}</span>
                        </div>
                        <div class="order-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i> ${order.reservation_time.substring(0, 5)}
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-users"></i> ${order.party_size} guests
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-table"></i> Table ${order.table_number}
                            </div>
                        </div>
                        ${order.items && order.items.length > 0 ? `
                            <div class="order-items">
                                <h5><i class="fas fa-utensils"></i> Items Ordered:</h5>
                                ${order.items.map(item => `
                                    <div class="item-row">
                                        <span>${item.quantity}x ${item.item_name}</span>
                                        <span class="item-price">RM ${parseFloat(item.item_total).toFixed(2)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${order.total_amount ? `
                            <div class="order-total">
                                <strong>Total:</strong> <span class="total-amount">RM ${parseFloat(order.total_amount).toFixed(2)}</span>
                            </div>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                ordersHTML = `
                    <div class="no-orders">
                        <i class="fas fa-receipt"></i>
                        <p>No orders found for this member</p>
                    </div>
                `;
            }
            
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeMemberModal()"></div>
                <div class="modal-container member-container">
                    <button class="close-btn" onclick="closeMemberModal()">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="modal-header member-header">
                        <div class="member-points-badge">
                            <i class="fas fa-star"></i>
                            <span class="points-large">${points}</span>
                            <span class="points-label">Points</span>
                        </div>
                        <h2>${name}</h2>
                        <div class="member-info">
                            <span><i class="fas fa-phone"></i> ${phone}</span>
                        </div>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">${orders ? orders.length : 0}</span>
                                <span class="stat-label">Total Orders</span>
                            </div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value">RM ${parseFloat(totalSpent || 0).toFixed(2)}</span>
                                <span class="stat-label">Total Spent</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-body">
                        <h3 class="section-title">
                            <i class="fas fa-history"></i> Order History
                        </h3>
                        <div class="orders-list">
                            ${ordersHTML}
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeMemberModal() {
            const modal = document.querySelector('.member-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        // ========================================================================
        // KITCHEN ORDER DETAILS MODAL
        // ========================================================================

        function showKitchenOrderModal(kitchenId, tableNumber, items, timeSubmitted, priority) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal kitchen-modal';
            
            // Parse time
            const submitTime = new Date(timeSubmitted);
            const now = new Date();
            const minutesAgo = Math.floor((now - submitTime) / 60000);
            
            // Determine urgency
            let urgencyClass = 'normal';
            let urgencyText = 'Normal';
            if (minutesAgo > 20) {
                urgencyClass = 'urgent';
                urgencyText = 'URGENT!';
            } else if (minutesAgo > 10) {
                urgencyClass = 'warning';
                urgencyText = 'Getting Late';
            }
            
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeKitchenModal()"></div>
                <div class="modal-container kitchen-container">
                    <button class="close-btn" onclick="closeKitchenModal()">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="modal-header kitchen-header">
                        <div class="kitchen-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h2>Kitchen Order #${kitchenId}</h2>
                        <div class="table-badge">
                            <i class="fas fa-table"></i> Table ${tableNumber}
                        </div>
                    </div>
                    
                    <div class="urgency-banner ${urgencyClass}">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>${urgencyText}</strong>
                            <span>Submitted ${minutesAgo} minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="modal-body">
                        <h3 class="section-title">
                            <i class="fas fa-list-ul"></i> Order Items
                        </h3>
                        <div class="kitchen-items-list">
                            ${items.map((item, index) => `
                                <div class="kitchen-item" style="animation-delay: ${index * 0.1}s">
                                    <div class="item-quantity">
                                        <span class="qty-badge">${item.quantity}x</span>
                                    </div>
                                    <div class="item-details">
                                        <h4>${item.item_name}</h4>
                                        <p class="item-category">${item.category || 'Main Course'}</p>
                                    </div>
                                    <div class="item-status">
                                        <i class="fas fa-fire cooking-icon"></i>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Total Items:</span>
                                <strong>${items.reduce((sum, item) => sum + parseInt(item.quantity), 0)}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Priority:</span>
                                <strong class="priority-${priority || 'normal'}">${(priority || 'Normal').toUpperCase()}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Waiting Time:</span>
                                <strong class="${minutesAgo > 15 ? 'text-danger' : ''}">${minutesAgo} minutes</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-secondary" onclick="closeKitchenModal()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button class="btn-complete" onclick="completeKitchenOrder(${kitchenId})">
                            <i class="fas fa-check-circle"></i> Mark as Complete
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeKitchenModal() {
            const modal = document.querySelector('.kitchen-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function completeKitchenOrder(kitchenId) {
            const completeBtn = document.querySelector('.btn-complete');
            const originalHTML = completeBtn.innerHTML;
            completeBtn.disabled = true;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=complete_kitchen_order&kitchen_id=${kitchenId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessAnimation('Order Completed!', 'Well done chef!');
                    closeKitchenModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + data.message);
                    completeBtn.disabled = false;
                    completeBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                completeBtn.disabled = false;
                completeBtn.innerHTML = originalHTML;
            });
        }

        // ========================================================================
        // SUCCESS ANIMATION
        // ========================================================================

        function showSuccessAnimation(title, message) {
            const success = document.createElement('div');
            success.className = 'success-popup';
            success.innerHTML = `
                <div class="success-content">
                    <div class="success-checkmark">
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                    <h3>${title}</h3>
                    <p>${message}</p>
                </div>
            `;
            
            document.body.appendChild(success);
            setTimeout(() => success.classList.add('show'), 10);
            setTimeout(() => {
                success.classList.remove('show');
                setTimeout(() => success.remove(), 300);
            }, 2000);
        }

        // ========================================================================
        // ESCAPE KEY TO CLOSE
        // ========================================================================

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
                closeMemberModal();
                closeKitchenModal();
            }
        });

        // ========================================================================
        // TABLE AVAILABILITY CALENDAR
        // ========================================================================

        function loadTableAvailability(date) {
            if (!date) return;
            
            const loadingEl = document.getElementById('table-availability-loading');
            const tableEl = document.getElementById('availability-table');
            const tbodyEl = document.getElementById('availability-tbody');
            const dateDisplayEl = document.getElementById('selected-date-display');
            
            // Show loading
            if (loadingEl) loadingEl.style.display = 'block';
            if (tableEl) tableEl.style.display = 'none';
            
            // Format date for display
            const dateObj = new Date(date + 'T00:00:00');
            const formattedDate = dateObj.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            if (dateDisplayEl) {
                dateDisplayEl.innerHTML = `<i class="fas fa-calendar"></i> ${formattedDate}`;
            }
            
            // Fetch availability
            const formData = new FormData();
            formData.append('action', 'get_table_availability');
            formData.append('date', date);
            
            fetch('staff_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && tbodyEl) {
                    // Clear existing rows
                    tbodyEl.innerHTML = '';
                    
                    // Build new rows
                    const timeSlots = data.timeSlots;
                    const tables = data.tables;
                    const availability = data.availability;
                    
                    Object.keys(timeSlots).forEach(time => {
                        const label = timeSlots[time];
                        const row = document.createElement('tr');
                        
                        // Time cell
                        const timeCell = document.createElement('td');
                        timeCell.style.cssText = 'position: sticky; left: 0; background: white; font-weight: 600; z-index: 5;';
                        timeCell.textContent = label;
                        row.appendChild(timeCell);
                        
                        // Table cells
                        tables.forEach(table => {
                            const slot = availability[table.table_id][time];
                            const isAvailable = slot.status === 'available';
                            
                            const cell = document.createElement('td');
                            cell.style.cssText = `text-align: center; padding: 12px; background: ${isAvailable ? '#d4edda' : '#f8d7da'}; border-left: 1px solid #dee2e6;`;
                            
                            if (isAvailable) {
                                cell.innerHTML = `
                                    <div style="color: var(--success); font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Available
                                    </div>
                                `;
                            } else {
                                cell.innerHTML = `
                                    <div style="color: var(--danger); font-weight: 600; margin-bottom: 4px;">
                                        <i class="fas fa-user"></i> Booked
                                    </div>
                                    <div style="font-size: 12px; color: #721c24;">
                                        ${slot.customer || 'Guest'}
                                        <br>
                                        <small>(${slot.party_size} guests)</small>
                                    </div>
                                `;
                            }
                            
                            row.appendChild(cell);
                        });
                        
                        tbodyEl.appendChild(row);
                    });
                    
                    // Update stats
                    updateAvailabilityStats(data.stats);
                    
                    // Hide loading, show table
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (tableEl) tableEl.style.display = 'table';
                } else {
                    alert('Error loading table availability: ' + (data.message || 'Unknown error'));
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (tableEl) tableEl.style.display = 'table';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading table availability. Please try again.');
                if (loadingEl) loadingEl.style.display = 'none';
                if (tableEl) tableEl.style.display = 'table';
            });
        }
        
        function updateAvailabilityStats(stats) {
            // Update stats using IDs
            const totalTablesEl = document.getElementById('stat-total-tables');
            const totalSlotsEl = document.getElementById('stat-total-slots');
            const occupiedSlotsEl = document.getElementById('stat-occupied-slots');
            const occupancyRateEl = document.getElementById('stat-occupancy-rate');
            
            if (totalTablesEl) totalTablesEl.textContent = stats.total_tables;
            if (totalSlotsEl) totalSlotsEl.textContent = stats.total_slots;
            if (occupiedSlotsEl) occupiedSlotsEl.textContent = stats.occupied_slots;
            if (occupancyRateEl) occupancyRateEl.textContent = stats.occupancy_rate.toFixed(1) + '%';
        }
        // ========================================================================
        // MENU ITEM MODAL
        // ========================================================================

        function showMenuModal(itemId = '', name = '', category = '', description = '', price = '') {
            const isEdit = itemId !== '';
            // Escape HTML and quotes for safe insertion
            const escapeHtml = (str) => {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };
            
            const modal = document.createElement('div');
            modal.className = 'creative-modal menu-modal';
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeMenuModal()"></div>
                <div class="modal-container menu-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse">
                            <i class="fas fa-${isEdit ? 'edit' : 'plus'}"></i>
                        </div>
                        <h2>${isEdit ? 'Modify Menu Item' : 'Add New Menu Item'}</h2>
                        <p class="subtitle">${isEdit ? 'Update menu item details' : 'Enter the details for the new menu item'}</p>
                    </div>
                    
                    <div class="modal-body">
                        <form id="menu-item-form" onsubmit="saveMenuItem(event)">
                            <div class="form-group">
                                <label for="menu-item-id">
                                    <i class="fas fa-hashtag"></i> Item ID <span style="color: red;">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="menu-item-id" 
                                    name="item_id" 
                                    value="${escapeHtml(itemId)}" 
                                    required
                                    ${isEdit ? 'readonly' : ''}
                                    placeholder="e.g., W1, A1, D1"
                                    maxlength="10"
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;"
                                >
                                <small style="color: #6c757d; font-size: 12px;">${isEdit ? 'Item ID cannot be changed' : 'Unique identifier for the menu item'}</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu-item-name">
                                    <i class="fas fa-utensils"></i> Name <span style="color: red;">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="menu-item-name" 
                                    name="name" 
                                    value="${escapeHtml(name)}" 
                                    required
                                    placeholder="e.g., Beef Wellington"
                                    maxlength="100"
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="menu-item-category">
                                    <i class="fas fa-tags"></i> Category <span style="color: red;">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="menu-item-category" 
                                    name="category" 
                                    value="${escapeHtml(category)}" 
                                    required
                                    placeholder="e.g., Main, Appetizer, Dessert, Drink"
                                    maxlength="50"
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="menu-item-description">
                                    <i class="fas fa-align-left"></i> Description <span style="color: red;">*</span>
                                </label>
                                <textarea 
                                    id="menu-item-description" 
                                    name="description" 
                                    required
                                    placeholder="Enter a detailed description of the menu item"
                                    rows="3"
                                    maxlength="255"
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"
                                >${escapeHtml(description)}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu-item-price">
                                    <i class="fas fa-dollar-sign"></i> Price (RM) <span style="color: red;">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    id="menu-item-price" 
                                    name="price" 
                                    value="${escapeHtml(price)}" 
                                    required
                                    min="0.01"
                                    step="0.01"
                                    placeholder="0.00"
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;"
                                >
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeMenuModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="document.getElementById('menu-item-form').requestSubmit()">
                            <i class="fas fa-${isEdit ? 'save' : 'check'}"></i> ${isEdit ? 'Update Item' : 'Add Item'}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeMenuModal() {
            const modal = document.querySelector('.menu-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function saveMenuItem(event) {
            event.preventDefault();
            
            const form = document.getElementById('menu-item-form');
            const formData = new FormData(form);
            const itemId = formData.get('item_id');
            const itemIdField = document.getElementById('menu-item-id');
            const isEdit = itemIdField && itemIdField.hasAttribute('readonly');
            
            // Show loading state
            const submitBtn = document.querySelector('.menu-modal .btn-verify');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Prepare data
            const data = {
                action: isEdit ? 'update_menu_item' : 'add_menu_item',
                item_id: formData.get('item_id'),
                name: formData.get('name'),
                category: formData.get('category'),
                description: formData.get('description'),
                price: formData.get('price')
            };
            
            // Send request
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAnimation(
                        isEdit ? 'Menu Item Updated!' : 'Menu Item Added!',
                        data.message
                    );
                    closeMenuModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenuModal();
                closeDeleteMenuModal();
            }
        });

        // ========================================================================
        // DELETE MENU ITEM MODAL
        // ========================================================================

        function showDeleteMenuModal(itemId, itemName) {
            const modal = document.createElement('div');
            modal.className = 'creative-modal delete-menu-modal';
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeDeleteMenuModal()"></div>
                <div class="modal-container delete-container">
                    <div class="modal-header">
                        <div class="icon-circle pulse" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Delete Menu Item</h2>
                        <p class="subtitle">Are you sure you want to delete this item?</p>
                    </div>
                    
                    <div class="modal-body">
                        <div class="warning-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-right: 10px;"></i>
                            <strong>Warning:</strong> This action cannot be undone. If this item is used in any orders or bills, deletion will be prevented.
                        </div>
                        
                        <div class="payment-details-card">
                            <div class="detail-row">
                                <span class="label"><i class="fas fa-hashtag"></i> Item ID</span>
                                <span class="value">${escapeHtml(itemId)}</span>
                            </div>
                            <div class="detail-row highlight">
                                <span class="label"><i class="fas fa-utensils"></i> Item Name</span>
                                <span class="value">${escapeHtml(itemName)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeDeleteMenuModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-verify" onclick="confirmDeleteMenuItem('${escapeHtml(itemId)}')" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-trash"></i> Delete Item
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeDeleteMenuModal() {
            const modal = document.querySelector('.delete-menu-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function confirmDeleteMenuItem(itemId) {
            // Show loading state
            const deleteBtn = document.querySelector('.delete-menu-modal .btn-verify');
            const originalHTML = deleteBtn.innerHTML;
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            // Send delete request
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_menu_item&item_id=${encodeURIComponent(itemId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAnimation(
                        'Menu Item Deleted!',
                        data.message
                    );
                    closeDeleteMenuModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + data.message);
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalHTML;
            });
        }

        // Helper function for HTML escaping (global)
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ========================================================================
        // PAYMENT DATE FILTER
        // ========================================================================

        function loadPaymentsForDate(date) {
            if (!date) return;
            
            const tbody = document.getElementById('payments-tbody');
            const loading = document.getElementById('payments-loading');
            const table = document.getElementById('payments-table');
            const dateDisplay = document.getElementById('payment-date-display');
            
            // Show loading
            if (loading) loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
            
            // Update date display
            if (dateDisplay) {
                const dateObj = new Date(date + 'T00:00:00');
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                dateDisplay.innerHTML = '<i class="fas fa-calendar"></i> ' + dateObj.toLocaleDateString('en-US', options);
            }
            
            // Fetch payments for selected date
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_payments_by_date&date=${date}`
            })
            .then(response => response.json())
            .then(data => {
                if (loading) loading.style.display = 'none';
                if (table) table.style.display = 'table';
                
                if (data.success && data.payments) {
                    renderPaymentsTable(data.payments);
                } else {
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray);">
                                    ${data.message || 'No pending payments for selected date'}
                                </td>
                            </tr>
                        `;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (loading) loading.style.display = 'none';
                if (table) table.style.display = 'table';
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray);">
                            Error loading payments. Please try again.
                            </td>
                        </tr>
                    `;
                }
            });
        }

        function renderPaymentsTable(payments) {
            const tbody = document.getElementById('payments-tbody');
            if (!tbody) return;
            
            if (payments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray);">
                            No pending payments for selected date
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            payments.forEach(payment => {
                const paymentMethod = payment.payment_method || 'touch_n_go';
                const methodDisplay = paymentMethod.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const createdDate = new Date(payment.created_at);
                const dateFormatted = createdDate.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                html += `
                    <tr data-reservation-id="${payment.reservation_id}">
                        <td>#${payment.transaction_id}</td>
                        <td>${escapeHtml(payment.full_name || 'Guest')}</td>
                        <td>${escapeHtml(payment.phone || 'N/A')}</td>
                        <td>Table ${payment.table_number || 'N/A'}</td>
                        <td>RM ${parseFloat(payment.amount).toFixed(2)}</td>
                        <td><span class="badge badge-info">${methodDisplay}</span></td>
                        <td>${dateFormatted}</td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="verifyPayment(${payment.transaction_id})">
                                <i class="fas fa-check"></i> Verify
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }

        // ========================================================================
        // RESERVATION DATE FILTER
        // ========================================================================

        function loadReservationsForDate(date) {
            if (!date) return;
            
            const tbody = document.getElementById('reservations-tbody');
            const loading = document.getElementById('reservations-loading');
            const table = document.getElementById('reservation-table');
            const dateDisplay = document.getElementById('reservation-date-display');
            
            // Show loading
            if (loading) loading.style.display = 'block';
            if (table) table.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
            
            // Update date display
            if (dateDisplay) {
                const dateObj = new Date(date + 'T00:00:00');
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                dateDisplay.innerHTML = '<i class="fas fa-calendar"></i> ' + dateObj.toLocaleDateString('en-US', options);
            }
            
            // Fetch reservations for selected date
            fetch('staff_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_reservations_by_date&date=${date}`
            })
            .then(response => response.json())
            .then(data => {
                if (loading) loading.style.display = 'none';
                if (table) table.style.display = 'table';
                
                if (data.success && data.reservations) {
                    renderReservationsTable(data.reservations);
                    // Update reservation count badge
                    const countBadge = document.getElementById('reservation-count-badge');
                    if (countBadge) {
                        const selectedDate = new Date(date + 'T00:00:00');
                        const today = new Date();
                        const isToday = selectedDate.toDateString() === today.toDateString();
                        countBadge.textContent = `${data.reservations.length} ${isToday ? 'Today' : 'Reservations'}`;
                    }
                } else {
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray);">
                                    ${data.message || 'No reservations for selected date'}
                                </td>
                            </tr>
                        `;
                    }
                    // Update count badge to 0
                    const countBadge = document.getElementById('reservation-count-badge');
                    if (countBadge) {
                        const selectedDate = new Date(date + 'T00:00:00');
                        const today = new Date();
                        const isToday = selectedDate.toDateString() === today.toDateString();
                        countBadge.textContent = `0 ${isToday ? 'Today' : 'Reservations'}`;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (loading) loading.style.display = 'none';
                if (table) table.style.display = 'table';
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray);">
                            Error loading reservations. Please try again.
                            </td>
                        </tr>
                    `;
                }
            });
        }

        function renderReservationsTable(reservations) {
            const tbody = document.getElementById('reservations-tbody');
            if (!tbody) return;
            
            if (reservations.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray);">
                            No reservations for selected date
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            reservations.forEach(reservation => {
                const reservationDate = new Date(reservation.reservation_date);
                const formattedDate = reservationDate.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                
                const reservationTime = new Date('2000-01-01T' + reservation.reservation_time);
                const formattedTime = reservationTime.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit',
                    hour12: true
                });
                
                html += `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${formattedTime}</td>
                        <td>${reservation.party_size}</td>
                        <td>Table ${reservation.table_number || 'N/A'}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }

    </script>
</body>
</html>
