<?php
/**
 * Get Booked Tables - UPDATED FOR NEW SCHEMA (thewellington1)
 * Returns list of booked table numbers for a given date/time
 */

session_start();
header('Content-Type: application/json');

require '../db_connect.php';

// Check if DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['error' => 'Connection failed', 'success' => false]);
    exit;
}

// Get date and time from request
$date = $_GET['date'] ?? date('Y-m-d');
$time = $_GET['time'] ?? '12:00';

try {
    // Get booked table numbers for this date/time using new schema
    $stmt = $conn->prepare("
        SELECT DISTINCT rt.table_number
        FROM table_availability ta
        JOIN restaurant_tables rt ON ta.table_id = rt.table_id
        WHERE ta.reservation_date = ?
        AND ta.reservation_time = ?
        AND ta.status IN ('reserved', 'occupied')
    ");
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedTables = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTables[] = intval($row['table_number']);
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'bookedTables' => $bookedTables,
        'date' => $date,
        'time' => $time
    ]);
    
} catch (Exception $e) {
    error_log('get_booked_tables.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching booked tables: ' . $e->getMessage(),
        'bookedTables' => []
    ]);
    if (isset($conn)) {
        $conn->close();
    }
}
?>
