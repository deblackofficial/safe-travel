<?php
header('Content-Type: application/json');
include 'conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

$uid = isset($input['uid']) ? strtoupper(trim($input['uid'])) : '';
$journeyActive = isset($input['journey_active']) && $input['journey_active'] == '1';

try {
    if (empty($uid)) {
        throw new Exception('No RFID UID provided');
    }

    // Check if bus is registered
    $stmt = $conn->prepare("SELECT b.id, b.plate_number, b.capacity, 
                           o.current_passengers, o.max_capacity 
                           FROM buses b
                           LEFT JOIN bus_occupancy o ON b.id = o.bus_id
                           WHERE b.rfid_uid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $bus = $result->fetch_assoc();
        
        // Handle journey state
        $conn->begin_transaction();
        
        try {
            if ($journeyActive) {
                // Ending current journey
                $update = $conn->prepare("UPDATE bus_occupancy SET current_passengers = 0 WHERE bus_id = ?");
                $update->bind_param("i", $bus['id']);
                $update->execute();
                
                echo json_encode([
                    'status' => 'journey_ended',
                    'message' => 'Journey completed successfully',
                    'action' => 'journey_end_beep',
                    'bus' => [
                        'plate_number' => $bus['plate_number'],
                        'capacity' => $bus['capacity']
                    ]
                ]);
            } else {
                // Starting new journey
                $update = $conn->prepare("UPDATE bus_occupancy SET current_passengers = 0 WHERE bus_id = ?");
                $update->bind_param("i", $bus['id']);
                $update->execute();
                
                echo json_encode([
                    'status' => 'journey_started', 
                    'message' => 'New journey started',
                    'action' => 'journey_start_beep',
                    'bus' => [
                        'id' => $bus['id'],
                        'plate_number' => $bus['plate_number'],
                        'capacity' => $bus['capacity'],
                        'max_capacity' => $bus['max_capacity']
                    ]
                ]);
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } else {
        // Bus not registered
        echo json_encode([
            'status' => 'available',
            'message' => 'Tag not registered to any bus',
            'action' => 'available_beep',
            'rfid_uid' => $uid
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'action' => 'error_beep'
    ]);
}
?>