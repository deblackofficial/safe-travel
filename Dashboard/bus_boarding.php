<?php
// bus_boarding.php
include 'conn.php';

if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handlePassengerScanAPI($_POST);  
        }
        elseif (isset($_GET['scan_bus']) && isset($_GET['rfid_uid'])) {
            handleBusScanAPI();
        } 
        elseif (isset($_GET['check_card']) && isset($_GET['card_uid'])) {
            handleCardCheckAPI();
        }
        elseif (isset($_GET['check_active_trip'])) {
            handleActiveTripCheckAPI();
        }
        elseif (isset($_GET['get_passengers']) && isset($_GET['bus_id'])) {
            handleGetPassengersAPI();
        }
        elseif (isset($_GET['get_bus_status']) && isset($_GET['bus_id'])) {
            handleGetBusStatusAPI();
        }
        elseif (isset($_GET['get_overlimit_passengers'])) {
            handleGetOverlimitPassengersAPI();
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        if (isset($input['bus_id']) && isset($input['card_uid'])) {
            handlePassengerScanAPI($input);
        }
        elseif (isset($input['end_trip']) && isset($input['bus_id'])) {
            handleEndTripAPI($input['bus_id']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

function handleBusScanAPI() {
    global $conn;

    $rfid_uid = strtoupper(trim($_GET['rfid_uid']));
    if (empty($rfid_uid)) {
        throw new Exception('No RFID UID provided');
    }

    // Check if bus is on active trip
    $tripCheck = $conn->prepare("SELECT bat.id, bat.start_time, b.capacity, bo.current_passengers 
                                FROM bus_active_trips bat 
                                JOIN buses b ON bat.bus_id = b.id 
                                JOIN bus_occupancy bo ON b.id = bo.bus_id 
                                WHERE b.rfid_uid = ? AND bat.end_time IS NULL");
    $tripCheck->bind_param("s", $rfid_uid);
    $tripCheck->execute();
    $tripResult = $tripCheck->get_result();

    if ($tripResult->num_rows > 0) {
        $trip = $tripResult->fetch_assoc();
        endBusTrip($trip['id']);

        echo json_encode([
            'status' => 'trip_ended',
            'message' => 'Trip ended successfully',
            'passenger_count' => $trip['current_passengers'],
            'duration' => time() - strtotime($trip['start_time'])
        ]);
        return;
    }

    // Start new trip
    $busQuery = $conn->prepare("SELECT b.id, b.plate_number, b.capacity, b.route_id, 
                               r.start_point, r.end_point, bo.current_passengers 
                               FROM buses b 
                               LEFT JOIN routes r ON b.route_id = r.id 
                               LEFT JOIN bus_occupancy bo ON b.id = bo.bus_id 
                               WHERE b.rfid_uid = ?");
    $busQuery->bind_param("s", $rfid_uid);
    $busQuery->execute();
    $busResult = $busQuery->get_result();

    if ($busResult->num_rows > 0) {
        $bus = $busResult->fetch_assoc();

        $conn->begin_transaction();
        try {
            $tripInsert = $conn->prepare("INSERT INTO bus_active_trips (bus_id, route_id, start_time) VALUES (?, ?, NOW())");
            $tripInsert->bind_param("ii", $bus['id'], $bus['route_id']);
            $tripInsert->execute();

            $updateOccupancy = $conn->prepare("UPDATE bus_occupancy SET current_passengers = 0 WHERE bus_id = ?");
            $updateOccupancy->bind_param("i", $bus['id']);
            $updateOccupancy->execute();

            $conn->commit();

            echo json_encode([
                'status' => 'trip_started',
                'message' => 'Trip started successfully',
                'bus' => $bus,
                'remaining_capacity' => $bus['capacity'] - $bus['current_passengers']
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception('Bus not found');
    }
}

function handleCardCheckAPI() {
    global $conn;
    $card_uid = strtoupper(trim($_GET['card_uid'] ?? ''));
    
    if (empty($card_uid)) {
        throw new Exception('No card UID provided');
    }

    $busCheck = $conn->prepare("SELECT id, plate_number FROM buses WHERE rfid_uid = ?");
    $busCheck->bind_param("s", $card_uid);
    $busCheck->execute();
    
    if ($busCheck->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'bus', 'card_uid' => $card_uid]);
        return;
    }
    
    $passengerCheck = $conn->prepare("SELECT u.id, u.first_name, u.last_name FROM rfid_cards r JOIN users u ON r.user_id = u.id WHERE r.card_uid = ? AND r.card_type = 'passenger'");
    $passengerCheck->bind_param("s", $card_uid);
    $passengerCheck->execute();
    $passengerResult = $passengerCheck->get_result();
    
    if ($passengerResult->num_rows > 0) {
        echo json_encode(['status' => 'passenger', 'card_uid' => $card_uid, 'passenger' => $passengerResult->fetch_assoc()]);
        return;
    }
    
    echo json_encode(['status' => 'unknown', 'card_uid' => $card_uid]);
}

function handleActiveTripCheckAPI() {
    global $conn;
    
    $query = $conn->prepare("SELECT bat.id, b.*, bo.current_passengers 
                           FROM bus_active_trips bat 
                           JOIN buses b ON bat.bus_id = b.id 
                           JOIN bus_occupancy bo ON b.id = bo.bus_id 
                           WHERE bat.end_time IS NULL");
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $bus = $result->fetch_assoc();
        echo json_encode([
            'status' => 'active',
            'bus' => $bus,
            'current_passengers' => $bus['current_passengers']
        ]);
    } else {
        echo json_encode(['status' => 'inactive']);
    }
}

function handlePassengerScanAPI($input) {
    global $conn;
    $bus_id = intval($input['bus_id']);
    $card_uid = strtoupper(trim($input['card_uid']));

    // Check if bus is on an active trip
    $tripCheck = $conn->prepare("SELECT id, route_id FROM bus_active_trips WHERE bus_id = ? AND end_time IS NULL LIMIT 1");
    $tripCheck->bind_param("i", $bus_id);
    $tripCheck->execute();
    $tripResult = $tripCheck->get_result();

    if ($tripResult->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Bus is not on an active trip']);
        return;
    }
    $tripData = $tripResult->fetch_assoc();
    $trip_id = $tripData['id'];
    $route_id = $tripData['route_id'];

    // Check if passenger is already on board
    $existingQuery = $conn->prepare(
        "SELECT pt.id, u.first_name, u.last_name, pt.status 
         FROM rfid_cards rc 
         JOIN users u ON rc.user_id = u.id 
         JOIN passenger_trips pt ON pt.passenger_id = u.id AND pt.is_active = TRUE 
         WHERE rc.card_uid = ? AND pt.bus_id = ?");
    $existingQuery->bind_param("si", $card_uid, $bus_id);
    $existingQuery->execute();
    $existingResult = $existingQuery->get_result();

    if ($existingResult->num_rows > 0) {
        // Passenger is on board - mark as exited
        $passenger = $existingResult->fetch_assoc();
        $passenger_trip_id = $passenger['id'];
        $was_over_limit = ($passenger['status'] === 'over_limit');

        $conn->begin_transaction();
        try {
            $updateTrip = $conn->prepare("UPDATE passenger_trips SET exit_time = NOW(), is_active = FALSE WHERE id = ?");
            $updateTrip->bind_param("i", $passenger_trip_id);
            $updateTrip->execute();

            if (!$was_over_limit) {
                $updateOccupancy = $conn->prepare(
                    "UPDATE bus_occupancy SET current_passengers = GREATEST(0, current_passengers - 1) WHERE bus_id = ?");
                $updateOccupancy->bind_param("i", $bus_id);
                $updateOccupancy->execute();
            }

            $getCount = $conn->prepare("SELECT current_passengers, max_capacity FROM bus_occupancy WHERE bus_id = ?");
            $getCount->bind_param("i", $bus_id);
            $getCount->execute();
            $capacity = $getCount->get_result()->fetch_assoc();

            $conn->commit();

            // Log passenger exit
            error_log("Passenger exited: {$passenger['first_name']} {$passenger['last_name']} from bus ID: $bus_id");

            echo json_encode([
                'status' => 'success',
                'action' => 'exited',
                'passenger' => [
                    'first_name' => $passenger['first_name'],
                    'last_name' => $passenger['last_name']
                ],
                'current_passengers' => $capacity['current_passengers'],
                'max_capacity' => $capacity['max_capacity'],
                'was_over_limit' => $was_over_limit
            ]);
            return;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Passenger exit failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Exit failed: ' . $e->getMessage()]);
            return;
        }
    }

    // Passenger not onboard yet — board now
    $getUser = $conn->prepare(
        "SELECT u.id, u.first_name, u.last_name 
         FROM rfid_cards rc 
         JOIN users u ON rc.user_id = u.id 
         WHERE rc.card_uid = ?");
    $getUser->bind_param("s", $card_uid);
    $getUser->execute();
    $userResult = $getUser->get_result();

    if ($userResult->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Card UID not linked to any user']);
        return;
    }

    $user = $userResult->fetch_assoc();
    $passenger_id = $user['id'];

    // Check capacity
    $getCapacity = $conn->prepare("SELECT current_passengers, max_capacity FROM bus_occupancy WHERE bus_id = ?");
    $getCapacity->bind_param("i", $bus_id);
    $getCapacity->execute();
    $capacity = $getCapacity->get_result()->fetch_assoc();

    $current = intval($capacity['current_passengers']);
    $max = intval($capacity['max_capacity']);
    $over_limit = $current >= $max;

    $conn->begin_transaction();
    try {
        $status = $over_limit ? 'over_limit' : 'boarding';
        $insertTrip = $conn->prepare("INSERT INTO passenger_trips (bus_id, route_id, passenger_id, entry_time, is_active, status) 
                              VALUES (?, ?, ?, NOW(), TRUE, ?)");

        $insertTrip->bind_param("iiis", $bus_id, $route_id, $passenger_id, $status);
        $insertTrip->execute();

        // Increase count if within limit
        if (!$over_limit) {
            $updateOccupancy = $conn->prepare("UPDATE bus_occupancy SET current_passengers = current_passengers + 1 WHERE bus_id = ?");
            $updateOccupancy->bind_param("i", $bus_id);
            $updateOccupancy->execute();
        }

        // Get updated values
        $getUpdated = $conn->prepare("SELECT current_passengers, max_capacity FROM bus_occupancy WHERE bus_id = ?");
        $getUpdated->bind_param("i", $bus_id);
        $getUpdated->execute();
        $updated = $getUpdated->get_result()->fetch_assoc();

        $conn->commit();

        // Log passenger boarding
        error_log("Passenger boarded: {$user['first_name']} {$user['last_name']} on bus ID: $bus_id (Status: $status)");

        // FIXED: Send notification BEFORE returning response
        if ($over_limit) {
            require_once 'send_notification.php';
            
            // Use updated current passenger count for notification
            $final_current = intval($updated['current_passengers']);
            
            error_log("Bus $bus_id is over capacity. Attempting to send notification...");
            
            if (sendOverlimitNotification($bus_id, $final_current, $max)) {
                error_log("✅ Over-limit notification sent successfully to admins for bus $bus_id");
            } else {
                error_log("❌ Failed to send over-limit notification for bus $bus_id");
            }

            // Log this event to database
            try {
                $bus_stmt = $conn->prepare("SELECT plate_number FROM buses WHERE id = ?");
                $bus_stmt->bind_param("i", $bus_id);
                $bus_stmt->execute();
                $bus_result = $bus_stmt->get_result();
                $bus_data = $bus_result->fetch_assoc();

                $log_query = "INSERT INTO system_notifications 
                             (bus_id, notification_type, message, created_at) 
                             VALUES (?, 'over_capacity', ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $message = "Bus {$bus_data['plate_number']} exceeded capacity ({$final_current}/{$max})";
                $log_stmt->bind_param("is", $bus_id, $message);
                $log_stmt->execute();
                
                error_log("System notification logged for bus {$bus_data['plate_number']}");
            } catch (Exception $e) {
                error_log("Failed to log system notification: " . $e->getMessage());
            }
        }

        echo json_encode([
            'status' => 'success',
            'action' => 'boarded',
            'passenger' => [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ],
            'bus_status' => $over_limit ? 'over_limit' : 'within_limit',
            'current_passengers' => $updated['current_passengers'],
            'max_capacity' => $updated['max_capacity']
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Passenger boarding failed: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Boarding failed: ' . $e->getMessage()]);
        return;
    }
}

function handleGetPassengersAPI() {
    global $conn;
    
    // Ensure we're in API mode
    if (!isset($_GET['api'])) {
        die(json_encode(['status' => 'error', 'message' => 'API parameter missing']));
    }

    // Validate bus_id
    if (!isset($_GET['bus_id']) || !is_numeric($_GET['bus_id'])) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'Invalid bus ID']));
    }

    $bus_id = intval($_GET['bus_id']);
    
    try {
        $query = $conn->prepare("SELECT pt.id, u.first_name, u.last_name, pt.entry_time, 
                                (CASE WHEN pt.is_active THEN 'active' ELSE 'completed' END) as status
                                FROM passenger_trips pt
                                JOIN users u ON pt.passenger_id = u.id
                                WHERE pt.bus_id = ?
                                ORDER BY pt.entry_time DESC");
        $query->bind_param("i", $bus_id);
        $query->execute();
        $result = $query->get_result();
        
        $passengers = [];
        while ($row = $result->fetch_assoc()) {
            $passengers[] = $row;
        }
        
        // Ensure we send proper JSON header
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'passengers' => $passengers,
            'count' => count($passengers)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function handleGetOverlimitPassengersAPI() {
    global $conn;
    
    try {
        $query = $conn->prepare("
            SELECT pt.id, u.first_name, u.last_name, pt.entry_time, b.plate_number
            FROM passenger_trips pt
            JOIN users u ON pt.passenger_id = u.id
            JOIN buses b ON pt.bus_id = b.id
            WHERE pt.status = 'over_limit'
            ORDER BY pt.entry_time DESC
            LIMIT 50
        ");
        $query->execute();
        $result = $query->get_result();
        
        $passengers = [];
        while ($row = $result->fetch_assoc()) {
            $passengers[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'passengers' => $passengers,
            'count' => count($passengers)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

function handlePassengerExit($trip_id, $bus_id) {
    global $conn;
    
    $conn->begin_transaction();
    try {
        $updateTrip = $conn->prepare("UPDATE passenger_trips SET exit_time = NOW(), is_active = FALSE WHERE id = ?");
        $updateTrip->bind_param("i", $trip_id);
        $updateTrip->execute();
        
        $updateOccupancy = $conn->prepare("UPDATE bus_occupancy SET current_passengers = GREATEST(0, current_passengers - 1) WHERE bus_id = ?");
        $updateOccupancy->bind_param("i", $bus_id);
        $updateOccupancy->execute();
        
        $getCount = $conn->prepare("SELECT current_passengers, max_capacity FROM bus_occupancy WHERE bus_id = ?");
        $getCount->bind_param("i", $bus_id);
        $getCount->execute();
        $count = $getCount->get_result()->fetch_assoc();
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'action' => 'exited',
            'current_passengers' => $count['current_passengers'],
            'max_capacity' => $count['max_capacity']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function endBusTrip($trip_id) {
    global $conn;
    
    $conn->begin_transaction();
    try {
        $endTrip = $conn->prepare("UPDATE bus_active_trips SET end_time = NOW() WHERE id = ?");
        $endTrip->bind_param("i", $trip_id);
        $endTrip->execute();
        
        $exitPassengers = $conn->prepare("UPDATE passenger_trips SET exit_time = NOW(), is_active = FALSE WHERE bus_id = (SELECT bus_id FROM bus_active_trips WHERE id = ?) AND is_active = TRUE");
        $exitPassengers->bind_param("i", $trip_id);
        $exitPassengers->execute();
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }
        
        .scan-section {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }
        
        .scan-section:hover {
            background-color: rgba(67, 97, 238, 0.05);
            border-color: var(--primary);
        }
        
        .scan-section.active {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
            animation: pulse 1.5s infinite;
        }
        
        .scan-section.driver {
            border-color: var(--warning);
            background-color: rgba(248, 150, 30, 0.1);
        }
        
        .scan-section.passenger {
            border-color: var(--success);
            background-color: rgba(76, 201, 240, 0.1);
        }
        
        .progress {
            height: 12px;
            border-radius: 6px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 6px;
            transition: width 0.6s ease;
        }
        
        .passenger-list {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        
        .passenger-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .passenger-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .blink {
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(67, 97, 238, 0); }
            100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .passenger-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .bus-status-card {
            border-left: 4px solid var(--primary);
        }
        
        .bus-status-card.active {
            border-left-color: var(--success);
        }
        
        .bus-status-card.ended {
            border-left-color: var(--danger);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .action-btn {
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-white shadow-sm">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0 text-primary">
                                    <i class="bi bi-bus-front me-2"></i> Boarding System
                                </h4>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-wifi me-1"></i>
                                        <span id="connectionStatus">Connected</span>
                                    </span>
                                </div>
                                <div id="currentTime" class="text-muted"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bus-front me-2"></i> Bus Status</h5>
                        <span id="busStatusBadge" class="badge bg-light text-dark">Inactive</span>
                    </div>
                    <div class="card-body">
                        <div class="scan-section mb-3" id="busScanTarget">
                            <div class="animate__animated animate__pulse animate__infinite">
                                <i class="bi bi-credit-card fs-1 text-primary"></i>
                                <h5 class="mt-2">Tap Bus RFID Tag</h5>
                                <p class="text-muted mb-0">Place the bus tag near the reader</p>
                            </div>
                        </div>
                        <div id="busInfo" style="display:none;">
                            <div class="card bus-status-card active mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 id="busPlate" class="card-title mb-1"></h5>
                                            <h6 id="busRoute" class="card-subtitle text-muted"></h6>
                                        </div>
                                        <div id="busStatusIcon" class="text-success">
                                            <i class="bi bi-check-circle-fill fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Capacity:</span>
                                            <strong id="busCapacity"></strong>
                                        </div>
                                        <div class="progress">
                                            <div id="capacityProgress" class="progress-bar" role="progressbar"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted" id="busStatus"></small>
                                        <small class="text-muted" id="busPassengers"></small>
                                    </div>
                                </div>
                            </div>

                          <span id="endTripBtn"></span>
                          <span id="refreshBusBtn"></span>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Overlimit Passengers</h5>
            <span id="overlimitCountBadge" class="badge bg-light text-dark">0 overlimit</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="passenger-list p-3" id="overlimitPassengers">
            <div class="text-center text-muted py-4">
                <i class="bi bi-check-circle fs-3"></i>
                <p>No overlimit passengers currently</p>
            </div>
        </div>
    </div>
</div>
    
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i> System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="d-flex justify-content-between">
                                <span>RFID Reader:</span>
                                <span id="rfidStatus" class="badge bg-success">Connected</span>
                            </h6>
                        </div>
                        <div class="mb-3">
                            <h6 class="d-flex justify-content-between">
                                <span>Database:</span>
                                <span id="dbStatus" class="badge bg-success">Connected</span>
                            </h6>
                        </div>
                        <div>
                            <h6 class="d-flex justify-content-between">
                                <span>Last Update:</span>
                                <span id="lastUpdate" class="badge bg-light text-dark">Just now</span>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i> Board Passengers</h5>
                            <div id="boardingStatus"></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="boardingArea" class="text-center py-4" style="display:none;">
                            <div class="scan-section mx-auto mb-4" style="max-width: 400px;" id="passengerScanTarget">
                                <i class="bi bi-credit-card fs-1 text-success"></i>
                                <h5 class="mt-2">Tap Passenger Card</h5>
                                <p class="text-muted mb-0">Place the passenger card near the reader</p>
                            </div>
                            <div id="passengerInfo" class="animate__animated animate__fadeIn"></div>
                        </div>
                        <div id="noBusSelected" class="text-center py-5">
                            <div class="animate__animated animate__pulse animate__infinite">
                                <i class="bi bi-bus-front fs-1 text-muted"></i>
                                <h4 class="text-muted mt-3">Please scan a bus first</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i> Current Passengers</h5>
                            <button id="refreshPassengersBtn" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="passengerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">Passenger</th>
                                        <th style="width: 25%">Boarding Time</th>
                                        <th style="width: 15%">Status</th>
                                        <th style="width: 20%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="passengerTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No passengers boarded yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let currentBus = null;
        let currentTrip = null;
        let autoRefreshInterval = null;
        let connectionCheckInterval = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            updateClock();
            setInterval(updateClock, 1000);
            
            document.getElementById('busScanTarget').addEventListener('click', startBusScan);
            document.getElementById('passengerScanTarget').addEventListener('click', startPassengerScan);
            document.getElementById('endTripBtn').addEventListener('click', endCurrentTrip);
            document.getElementById('refreshBusBtn').addEventListener('click', refreshBusStatus);
            document.getElementById('refreshPassengersBtn').addEventListener('click', loadCurrentPassengers);
            
            checkSystemStatus();
            checkActiveTrip();
            startConnectionMonitoring();
        });

        function updateClock() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString();
            document.getElementById('lastUpdate').textContent = 'Last: ' + now.toLocaleTimeString();
        }

        function checkSystemStatus() {
            // Simulate status checks - replace with actual API calls in production
            setTimeout(() => {
                document.getElementById('rfidStatus').className = 'badge bg-success';
                document.getElementById('rfidStatus').textContent = 'Connected';
                
                document.getElementById('dbStatus').className = 'badge bg-success';
                document.getElementById('dbStatus').textContent = 'Connected';
            }, 1500);
        }

        function startConnectionMonitoring() {
            connectionCheckInterval = setInterval(() => {
                fetch('bus_boarding.php?api=1&check_active_trip=1')
                    .then(response => {
                        document.getElementById('connectionStatus').textContent = 'Connected';
                        document.getElementById('connectionStatus').parentElement.className = 'badge bg-light text-dark';
                    })
                    .catch(error => {
                        document.getElementById('connectionStatus').textContent = 'Disconnected';
                        document.getElementById('connectionStatus').parentElement.className = 'badge bg-danger';
                    });
            }, 5000);
        }

        function startBusScan() {
            const scanTarget = document.getElementById('busScanTarget');
            scanTarget.innerHTML = `
                <div class="animate__animated animate__pulse animate__infinite">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mt-2">Scanning Bus...</h5>
                    <p class="text-muted">Looking for bus RFID tag</p>
                </div>
            `;
            scanTarget.classList.add('active');
            
            // In a real implementation, this would come from actual RFID scan
            // For demo purposes, we'll simulate a scan after 1.5 seconds
            setTimeout(() => {
                fetch('bus_boarding.php?api=1&scan_bus=1&rfid_uid=TESTBUS123')
                    .then(handleAPIResponse)
                    .then(data => {
                        if (data.status === 'trip_started') {
                            currentBus = data.bus;
                            displayBusInfo(data.bus, 0);
                            document.getElementById('endTripBtn').style.display = 'block';
                            document.getElementById('busStatusBadge').className = 'badge bg-success';
                            document.getElementById('busStatusBadge').textContent = 'Active';
                            setupPassengerScanning();
                            loadCurrentPassengers();
                            startAutoRefresh();
                            showToast('Trip started successfully', 'success');
                        } else if (data.status === 'trip_ended') {
                            showToast(`Trip ended. Carried ${data.passenger_count} passengers`, 'info');
                            resetBusScan();
                            stopAutoRefresh();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        scanTarget.innerHTML = `
                            <div class="animate__animated animate__shakeX">
                                <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                                <h5 class="mt-2">${error.message || 'Error scanning bus'}</h5>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="startBusScan()">
                                    <i class="bi bi-arrow-repeat me-1"></i> Retry
                                </button>
                            </div>
                        `;
                        showToast(error.message || 'Error scanning bus', 'danger');
                    })
                    .finally(() => {
                        scanTarget.classList.remove('active');
                    });
            }, 1500);
        }
        
        function startPassengerScan() {
            if (!currentBus) return;
            
            const scanTarget = document.getElementById('passengerScanTarget');
            scanTarget.innerHTML = `
                <div class="animate__animated animate__pulse animate__infinite">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mt-2">Scanning Passenger...</h5>
                    <p class="text-muted">Looking for passenger card</p>
                </div>
            `;
            scanTarget.classList.add('active');
            
            // Simulate passenger scan after 1.5 seconds
            setTimeout(() => {
                fetch('bus_boarding.php?api=1&check_card=1&card_uid=TESTPASS456')
                    .then(handleAPIResponse)
                    .then(cardData => {
                        if (cardData.status === 'passenger') {
                            return boardPassenger(cardData.card_uid);
                        }
                        throw new Error('Invalid card type - Only passenger cards can be scanned here');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        scanTarget.innerHTML = `
                            <div class="animate__animated animate__shakeX">
                                <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                                <h5 class="mt-2">${error.message || 'Error scanning card'}</h5>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="startPassengerScan()">
                                    <i class="bi bi-arrow-repeat me-1"></i> Retry
                                </button>
                            </div>
                        `;
                        showToast(error.message || 'Error scanning card', 'danger');
                    })
                    .finally(() => {
                        scanTarget.classList.remove('active');
                    });
            }, 1500);
        }
        
        function boardPassenger(cardUid) {
            const formData = new FormData();
            formData.append('bus_id', currentBus.id);
            formData.append('card_uid', cardUid);
            
            return fetch('bus_boarding.php?api=1', {
                method: 'POST',
                body: formData
            })
            .then(handleAPIResponse)
            .then(data => {
                const passengerInfo = document.getElementById('passengerInfo');
                
                if (data.bus_status === 'over_limit') {
                    passengerInfo.innerHTML = `
                        <div class="alert alert-danger animate__animated animate__fadeIn">
                            <div class="d-flex align-items-center">
                                <div class="passenger-avatar me-3">
                                    ${data.passenger.first_name.charAt(0)}${data.passenger.last_name.charAt(0)}
                                </div>
                                <div>
                                    <strong>${data.passenger.first_name} ${data.passenger.last_name}</strong>
                                    <div class="text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        Boarded as OVER LIMIT (${data.current_passengers}/${data.max_capacity})
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    showToast('Passenger boarded as OVER LIMIT', 'warning');
                } else {
                    passengerInfo.innerHTML = `
                        <div class="alert alert-success animate__animated animate__fadeIn">
                            <div class="d-flex align-items-center">
                                <div class="passenger-avatar me-3">
                                    ${data.passenger.first_name.charAt(0)}${data.passenger.last_name.charAt(0)}
                                </div>
                                <div>
                                    <strong>${data.passenger.first_name} ${data.passenger.last_name}</strong>
                                    <div class="text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Boarded successfully (${data.current_passengers}/${data.max_capacity})
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    showToast('Passenger boarded successfully', 'success');
                }
                
                updateBusCapacity(data.current_passengers, data.max_capacity);
                addRecentBoarding(data.passenger, data.bus_status);
                loadCurrentPassengers();
                
                setTimeout(() => {
                    passengerInfo.innerHTML = '';
                    document.getElementById('passengerScanTarget').innerHTML = `
                        <i class="bi bi-credit-card fs-1 text-success"></i>
                        <h5 class="mt-2">Tap Passenger Card</h5>
                        <p class="text-muted">Place the passenger card near the reader</p>
                    `;
                }, 3000);
            })
            .catch(error => {
                showToast(error.message || 'Error boarding passenger', 'danger');
            });
        }
        
        function displayBusInfo(bus, currentPassengers) {
            document.getElementById('busScanTarget').style.display = 'none';
            document.getElementById('busInfo').style.display = 'block';
            
            document.getElementById('busPlate').textContent = bus.plate_number;
            document.getElementById('busRoute').textContent = 
                `${bus.start_point} to ${bus.end_point}`;
            
            updateBusCapacity(currentPassengers, bus.capacity);
            
            document.getElementById('boardingArea').style.display = 'block';
            document.getElementById('noBusSelected').style.display = 'none';
            document.getElementById('boardingStatus').innerHTML = `
                <span class="badge bg-success">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Ready for boarding
                </span>
            `;
        }
        
        function updateBusCapacity(current, max) {
            document.getElementById('busCapacity').textContent = `${current} / ${max}`;
            document.getElementById('busPassengers').textContent = `${current} of ${max} passengers`;
            
            const progress = (current / max) * 100;
            const progressBar = document.getElementById('capacityProgress');
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
            
            if (current >= max) {
                progressBar.className = 'progress-bar bg-danger';
                document.getElementById('busStatus').textContent = 'FULL CAPACITY';
                document.getElementById('busStatusIcon').className = 'text-danger';
                document.getElementById('busStatusIcon').innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-4"></i>';
                document.getElementById('boardingStatus').innerHTML = `
                    <span class="badge bg-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Full capacity
                    </span>
                `;
            } else {
                progressBar.className = 'progress-bar bg-success';
                document.getElementById('busStatus').textContent = 
                    `${max - current} seats available`;
                document.getElementById('busStatusIcon').className = 'text-success';
                document.getElementById('busStatusIcon').innerHTML = '<i class="bi bi-check-circle-fill fs-4"></i>';
                document.getElementById('boardingStatus').innerHTML = `
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Ready for boarding
                    </span>
                `;
            }
        }
        
        function addRecentBoarding(passenger, status) {
            const recentBoardings = document.getElementById('recentBoardings');
            
            if (recentBoardings.children.length === 1 && 
                recentBoardings.children[0].className.includes('text-muted')) {
                recentBoardings.innerHTML = '';
            }
            
            const alertClass = status === 'over_limit' ? 'alert-danger' : 'alert-success';
            const icon = status === 'over_limit' ? 'exclamation-triangle-fill' : 'check-circle-fill';
            
            const boardingElement = document.createElement('div');
            boardingElement.className = `alert ${alertClass} py-2 mb-2 animate__animated animate__fadeIn`;
            boardingElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="passenger-avatar me-3">
                        ${passenger.first_name.charAt(0)}${passenger.last_name.charAt(0)}
                    </div>
                    <div class="flex-grow-1">
                        <strong>${passenger.first_name} ${passenger.last_name}</strong>
                        <div class="d-flex justify-content-between">
                            <small>${new Date().toLocaleTimeString()}</small>
                            <span class="badge ${status === 'over_limit' ? 'bg-danger' : 'bg-success'}">
                                <i class="bi bi-${icon} me-1"></i>
                                ${status === 'over_limit' ? 'OVER LIMIT' : 'BOARDED'}
                            </span>
                        </div>
                    </div>
                </div>
            `;
            
            if (recentBoardings.children.length > 0) {
                recentBoardings.insertBefore(boardingElement, recentBoardings.children[0]);
            } else {
                recentBoardings.appendChild(boardingElement);
            }
            
            if (recentBoardings.children.length > 10) {
                recentBoardings.removeChild(recentBoardings.lastChild);
            }
        }
        
        function loadCurrentPassengers() {
            if (!currentBus) return;
            
            fetch(`bus_boarding.php?api=1&get_passengers=1&bus_id=${currentBus.id}`)
                .then(handleAPIResponse)
                .then(data => {
                    const tableBody = document.getElementById('passengerTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.passengers.length > 0) {
                        data.passengers.forEach(passenger => {
                            const row = document.createElement('tr');
                            row.className = 'animate__animated animate__fadeIn';
                            row.innerHTML = `
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="passenger-avatar me-3">
                                            ${passenger.first_name.charAt(0)}${passenger.last_name.charAt(0)}
                                        </div>
                                        <div>
                                            <strong>${passenger.first_name} ${passenger.last_name}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>${new Date(passenger.entry_time).toLocaleTimeString()}</td>
                                <td>
                                    ${passenger.status === 'active' ? 
                                        '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>BOARDED</span>' : 
                                        '<span class="badge bg-secondary"><i class="bi bi-box-arrow-right me-1"></i>EXITED</span>'}
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary action-btn" 
                                            onclick="exitPassenger(${passenger.id}, ${currentBus.id})"
                                            ${passenger.status !== 'active' ? 'disabled' : ''}>
                                        <i class="bi bi-box-arrow-right me-1"></i> Exit
                                    </button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No passengers boarded yet
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading passengers:', error);
                });
        }
        
        function exitPassenger(tripId, busId) {
            if (!confirm('Mark this passenger as exited?')) return;
            
            const formData = new FormData();
            formData.append('bus_id', busId);
            formData.append('trip_id', tripId);
            
            fetch('bus_boarding.php?api=1', {
                method: 'POST',
                body: formData
            })
            .then(handleAPIResponse)
            .then(data => {
                loadCurrentPassengers();
                updateBusCapacity(data.current_passengers, currentBus.capacity);
                showToast('Passenger exited successfully', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(error.message || 'Failed to update passenger status', 'danger');
            });
        }
        
        function endCurrentTrip() {
            if (!currentBus || !confirm('End this trip? All passengers will be logged out.')) return;
            
            const formData = new FormData();
            formData.append('end_trip', '1');
            formData.append('bus_id', currentBus.id);
            
            fetch('bus_boarding.php?api=1', {
                method: 'POST',
                body: formData
            })
            .then(handleAPIResponse)
            .then(data => {
                showToast('Trip ended successfully', 'success');
                resetBusScan();
                stopAutoRefresh();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(error.message || 'Failed to end trip', 'danger');
            });
        }
        
        function refreshBusStatus() {
            if (!currentBus) {
                checkActiveTrip();
                return;
            }
            
            fetch(`bus_boarding.php?api=1&get_bus_status=1&bus_id=${currentBus.id}`)
                .then(handleAPIResponse)
                .then(data => {
                    updateBusCapacity(data.current_passengers, data.max_capacity);
                    showToast('Bus status refreshed', 'info');
                })
                .catch(error => {
                    console.error('Error refreshing bus status:', error);
                });
        }
        function resetBusScan() {
    document.getElementById('busScanTarget').style.display = 'block';
    document.getElementById('busScanTarget').innerHTML = `
        <div class="animate__animated animate__pulse animate__infinite">
            <i class="bi bi-credit-card fs-1 text-primary"></i>
            <h5 class="mt-2">Tap Bus RFID Tag</h5>
            <p class="text-muted">Place the bus tag near the reader</p>
        </div>
    `;
    document.getElementById('busInfo').style.display = 'none';
    document.getElementById('boardingArea').style.display = 'none';
    document.getElementById('noBusSelected').style.display = 'block';
    document.getElementById('endTripBtn').style.display = 'none';
    document.getElementById('busStatusBadge').className = 'badge bg-secondary';
    document.getElementById('busStatusBadge').textContent = 'Inactive';
    document.getElementById('boardingStatus').innerHTML = '';
    
    // Reset recent boardings
    document.getElementById('recentBoardings').innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="bi bi-arrow-repeat fs-3"></i>
            <p>Scan a bus to begin</p>
        </div>
    `;
    
    // Reset passenger table
    document.getElementById('passengerTableBody').innerHTML = `
        <tr>
            <td colspan="4" class="text-center text-muted py-4">
                No passengers boarded yet
            </td>
        </tr>
    `;
    
    currentBus = null;
    currentTrip = null;
}

function checkActiveTrip() {
    fetch('bus_boarding.php?api=1&check_active_trip=1')
        .then(handleAPIResponse)
        .then(data => {
            if (data.status === 'active') {
                currentBus = data.bus;
                displayBusInfo(data.bus, data.current_passengers);
                document.getElementById('endTripBtn').style.display = 'block';
                document.getElementById('busStatusBadge').className = 'badge bg-success';
                document.getElementById('busStatusBadge').textContent = 'Active';
                setupPassengerScanning();
                loadCurrentPassengers();
                startAutoRefresh();
                
                // Show success message
                showToast(`Active trip found: ${data.bus.plate_number}`, 'success');
            }
        })
        .catch(error => {
            console.log('No active trip found: ' + error.message);
        });
}

function startAutoRefresh() {
    stopAutoRefresh();
    autoRefreshInterval = setInterval(() => {
        if (currentBus) {
            loadCurrentPassengers();
            fetch(`bus_boarding.php?api=1&get_bus_status=1&bus_id=${currentBus.id}`)
                .then(handleAPIResponse)
                .then(data => {
                    updateBusCapacity(data.current_passengers, data.max_capacity);
                })
                .catch(error => {
                    console.error('Auto-refresh error:', error);
                });
        }
    }, 10000); // Refresh every 10 seconds
}

// Function to load and display overlimit passengers
function loadOverlimitPassengers() {
    fetch('bus_boarding.php?api=1&get_overlimit_passengers=1')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('overlimitPassengers');
            const countBadge = document.getElementById('overlimitCountBadge');
            
            // Update count badge
            countBadge.textContent = `${data.count} overlimit`;
            
            if (data.count === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle fs-3"></i>
                        <p>No overlimit passengers currently</p>
                    </div>
                `;
                return;
            }
            
            // Clear existing content
            container.innerHTML = '';
            
            // Add each overlimit passenger
            data.passengers.forEach(passenger => {
                const passengerElement = document.createElement('div');
                passengerElement.className = 'alert alert-danger py-2 mb-2';
                passengerElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="passenger-avatar bg-danger text-white me-3">
                            ${passenger.first_name.charAt(0)}${passenger.last_name.charAt(0)}
                        </div>
                        <div class="flex-grow-1">
                            <strong>${passenger.first_name} ${passenger.last_name}</strong>
                            <div class="d-flex justify-content-between small">
                                <span>Bus: ${passenger.plate_number}</span>
                                <span>${new Date(passenger.entry_time).toLocaleTimeString()}</span>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(passengerElement);
            });
        })
        .catch(error => {
            console.error('Error loading overlimit passengers:', error);
            showToast('Failed to load overlimit passengers', 'danger');
        });
}

// Call this function when page loads and after passenger boarding
document.addEventListener('DOMContentLoaded', loadOverlimitPassengers);

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function setupPassengerScanning() {
    document.getElementById('passengerScanTarget').innerHTML = `
        <i class="bi bi-credit-card fs-1 text-success"></i>
        <h5 class="mt-2">Tap Passenger Card</h5>
        <p class="text-muted">Place the passenger card near the reader</p>
    `;
}

function handleAPIResponse(response) {
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
        return response.text().then(text => {
            throw new Error(text || 'Invalid server response');
        });
    }
    return response.json().then(data => {
        if (data.status === 'error') {
            throw new Error(data.message);
        }
        return data;
    });
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    const icon = {
        'success': 'check-circle-fill',
        'danger': 'exclamation-triangle-fill',
        'warning': 'exclamation-triangle-fill',
        'info': 'info-fill'
    }[type] || 'info-fill';

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.style.marginBottom = '10px';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i class="bi bi-${icon} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-remove toast after 5 seconds
    setTimeout(() => {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            toastElement.classList.remove('show');
            setTimeout(() => toastElement.remove(), 300);
        }
    }, 5000);
}

// Initialize any additional UI components
function initAdditionalComponents() {
    // Add any additional initialization code here
    // For example, tooltips, popovers, etc.
    
    // Example tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Add this near your other interval declarations
let tripCheckInterval = setInterval(checkActiveTrip, 1000); // Check every 5 seconds

// Don't forget to clear it when needed
function stopAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    if (tripCheckInterval) clearInterval(tripCheckInterval);
    autoRefreshInterval = null;
    tripCheckInterval = null;
}

// Call initialization functions when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initAdditionalComponents();
});


</script>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
</body>
</html>