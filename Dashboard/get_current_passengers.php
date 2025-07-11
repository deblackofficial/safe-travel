<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    include 'conn.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;
    
    if ($bus_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid bus ID']);
        exit;
    }
    
    $query = "SELECT t.id, u.first_name, u.last_name, t.entry_time, t.status
              FROM passenger_trips t
              JOIN users u ON t.passenger_id = u.id
              WHERE t.bus_id = ? AND t.exit_time IS NULL
              ORDER BY t.entry_time DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $bus_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $passengers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $passengers[] = [
            'id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'entry_time' => $row['entry_time'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'passengers' => $passengers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>