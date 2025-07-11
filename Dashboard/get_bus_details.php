<?php
session_start();
include 'conn.php';

header('Content-Type: application/json');

if (!isset($_GET['bus_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bus ID not provided']);
    exit;
}

$bus_id = intval($_GET['bus_id']);

try {
    $stmt = $conn->prepare("
        SELECT b.*, r.start_point, r.middle_point, r.end_point 
        FROM buses b
        LEFT JOIN routes r ON b.route_id = r.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $bus = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'bus' => $bus]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Bus not found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>