<?php
// check_availability.php
// Updated to show occupied tables and time slots
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "thewellingtondb1";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Get request data
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    
    if (empty($date) || empty($time)) {
        echo json_encode([
            'success' => false,
            'message' => 'Date and time are required'
        ]);
        exit;
    }
    
    // Get all tables
    $stmt = $conn->prepare("SELECT table_id, table_number, capacity, status FROM restaurant_table ORDER BY table_number");
    $stmt->execute();
    $result    = $stmt->get_result();
    $allTables = [];
    while ($row = $result->fetch_assoc()) {
        $allTables[] = $row;
    }
    $stmt->close();
    
    // Get booked tables for the specific date and time with customer info
    // Get full_name from Users table (more reliable than Reservations table)
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            r.table_id, 
            rt.table_number, 
            COALESCE(u.full_name, 'Guest') as full_name, 
            r.party_size
        FROM Reservations r
        JOIN restaurant_table rt ON r.table_id = rt.table_id
        LEFT JOIN Users u ON r.user_id = u.user_id
        WHERE r.reservation_date = ? 
        AND r.reservation_time = ?
    ");
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result       = $stmt->get_result();
    $bookedTables = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTables[$row['table_id']] = $row;
    }
    $stmt->close();
    
    // Mark tables as available or occupied
    $tableAvailability = [];
    foreach ($allTables as $table) {
        $isOccupied = isset($bookedTables[$table['table_id']]);
        
        $tableAvailability[] = [
            'table_id'     => $table['table_id'],
            'table_number' => $table['table_number'],
            'capacity'     => $table['capacity'],
            'status'       => $isOccupied ? 'occupied' : 'available',
            'booked_by'    => $isOccupied ? $bookedTables[$table['table_id']]['full_name']  : null,
            'party_size'   => $isOccupied ? $bookedTables[$table['table_id']]['party_size'] : null,
        ];
    }
    
    // Get available time slots for the date
    $availableTimeSlots = [];
    $timeSlots          = [
        '16:00:00' => '4:00 PM',
        '18:00:00' => '6:00 PM',
        '20:00:00' => '8:00 PM',
        '22:00:00' => '10:00 PM',
    ];
    
    foreach ($timeSlots as $timeValue => $timeLabel) {
        // Count booked tables for this time slot
        $stmt = $conn->prepare("\n            SELECT COUNT(*) as booked_count\n            FROM Reservations r\n            WHERE r.reservation_date = ?\n            AND r.reservation_time = ?\n        ");
        $stmt->bind_param("ss", $date, $timeValue);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        
        $bookedCount = (int)($row['booked_count'] ?? 0);
        $totalTables = count($allTables);
        
        $availableTimeSlots[] = [
            'time'            => $timeValue,
            'label'           => $timeLabel,
            'available'       => ($bookedCount < $totalTables),
            'available_tables'=> $totalTables - $bookedCount,
            'booked_tables'   => $bookedCount,
        ];
    }
    
    echo json_encode([
        'success'     => true,
        'tables'      => $tableAvailability,
        'time_slots'  => $availableTimeSlots,
        'date'        => $date,
        'time'        => $time,
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('check_availability.php error: ' . $e->getMessage());
    
    // Return JSON error response
    echo json_encode([
        'success' => false,
        'message' => 'Error checking availability: ' . $e->getMessage(),
        'error' => $e->getMessage(),
    ]);
    exit;
} catch (Error $e) {
    // Catch PHP 7+ errors
    error_log('check_availability.php fatal error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
    ]);
    exit;
}
?>
