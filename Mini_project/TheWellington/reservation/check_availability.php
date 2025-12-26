<?php
/**
 * Check Table Availability - UPDATED FOR NEW SCHEMA (thewellington1)
 * Returns available tables for a given date and time
 */

session_start();
header('Content-Type: application/json');

require '../db_connect.php';

// Helper function for JSON error response
function json_error($message) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $message]);
    exit;
}

// Check if DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    json_error('Database connection failed.');
}

// Get date and time from request
$date = $_GET['date'] ?? date('Y-m-d');
$time = $_GET['time'] ?? '12:00';

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error('Invalid date format.');
}

// Validate time format
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    json_error('Invalid time format.');
}

try {
    // Get all restaurant tables with their availability status
    $stmt = $conn->prepare("
        SELECT 
            rt.table_id,
            rt.table_number,
            rt.capacity,
            rt.section,
            COALESCE(ta.status, 'available') as availability_status,
            ta.reservation_id
        FROM restaurant_tables rt
        LEFT JOIN table_availability ta ON rt.table_id = ta.table_id 
            AND ta.reservation_date = ?
            AND ta.reservation_time = ?
            AND ta.status IN ('reserved', 'occupied')
        ORDER BY rt.table_number ASC
    ");
    
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tables = [];
    $availableCount = 0;
    $bookedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['availability_status'];
        $isAvailable = ($status === 'available' || $status === null);
        
        if ($isAvailable) {
            $availableCount++;
        } else {
            $bookedCount++;
        }
        
        $tables[] = [
            'table_id' => (int)$row['table_id'],
            'table_number' => (int)$row['table_number'],
            'capacity' => (int)$row['capacity'],
            'section' => $row['section'],
            'available' => $isAvailable,
            'status' => $status
        ];
    }
    
    $stmt->close();
    
    // Get list of booked table numbers for compatibility
    $bookedTableNumbers = [];
    foreach ($tables as $table) {
        if (!$table['available']) {
            $bookedTableNumbers[] = $table['table_number'];
        }
    }
    
    // Get available time slots for the date (for compatibility with existing JS)
    $availableTimeSlots = [];
    $timeSlots = [
        '16:00:00' => '4:00 PM',
        '18:00:00' => '6:00 PM',
        '20:00:00' => '8:00 PM',
        '22:00:00' => '10:00 PM',
    ];
    
    foreach ($timeSlots as $timeValue => $timeLabel) {
        // Count booked tables for this time slot
        $stmt = $conn->prepare("
            SELECT COUNT(*) as booked_count
            FROM table_availability ta
            WHERE ta.reservation_date = ?
            AND ta.reservation_time = ?
            AND ta.status IN ('reserved', 'occupied')
        ");
        $stmt->bind_param("ss", $date, $timeValue);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $bookedCount = (int)($row['booked_count'] ?? 0);
        $totalTables = count($tables);
        
        $availableTimeSlots[] = [
            'time' => $timeValue,
            'label' => $timeLabel,
            'available' => ($bookedCount < $totalTables),
            'available_tables' => $totalTables - $bookedCount,
            'booked_tables' => $bookedCount,
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'time' => $time,
        'tables' => $tables,
        'time_slots' => $availableTimeSlots, // Required by JavaScript
        'available_count' => $availableCount,
        'booked_count' => $bookedCount,
        'booked_tables' => $bookedTableNumbers, // For backward compatibility
        'success' => true,
        'bookedTables' => $bookedTableNumbers // For backward compatibility
    ]);
    
} catch (Exception $e) {
    error_log('Availability check error: ' . $e->getMessage());
    json_error('Error checking availability: ' . $e->getMessage());
}

$conn->close();
?>

