<?php
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

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    switch ($action) {
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
            $stmt = $pdo->prepare("SELECT table_id FROM restaurant_table WHERE table_number = ?");
            $stmt->execute([$tableNumber]);
            $table = $stmt->fetch();
            
            if (!$table) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Table not found'
                ]);
                break;
            }
            
            // Check if status column exists
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM Kitchen LIKE 'status'");
                $hasStatusColumn = $checkStmt->rowCount() > 0;
            } catch (PDOException $e) {
                $hasStatusColumn = false;
            }
            
            if ($hasStatusColumn) {
                // Update status to 'served' for completed orders at this table
                // Updated to use reservation_id for more accurate matching
                $stmt = $pdo->prepare("
                    UPDATE Kitchen k
                    INNER JOIN Reservations r ON k.reservation_id = r.reservation_id
                    SET k.status = 'served'
                    WHERE k.table_id = ? 
                    AND (k.status = 'completed' OR k.status = 'ready')
                    AND DATE(r.reservation_date) = CURDATE()
                ");
                $stmt->execute([$table['table_id']]);
                
                // If no rows updated, try without reservation join (fallback)
                if ($stmt->rowCount() == 0) {
                    $stmt = $pdo->prepare("
                        UPDATE Kitchen 
                        SET status = 'served' 
                        WHERE table_id = ? 
                        AND (status = 'completed' OR status = 'ready')
                    ");
                    $stmt->execute([$table['table_id']]);
                }
            } else {
                // Delete kitchen orders for this table (marking as served)
                $stmt = $pdo->prepare("DELETE FROM Kitchen WHERE table_id = ?");
                $stmt->execute([$table['table_id']]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Orders marked as served'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action'
            ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

