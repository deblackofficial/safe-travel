<?php
include 'conn.php';

$trip_id = intval($_POST['trip_id']);
$bus_id = intval($_POST['bus_id']);

mysqli_begin_transaction($conn);

try {
    // Mark trip as exited
    $updateTrip = mysqli_prepare($conn, 
        "UPDATE passenger_trips 
         SET exit_time = NOW(), is_active = FALSE 
         WHERE id = ?");
    mysqli_stmt_bind_param($updateTrip, "i", $trip_id);
    mysqli_stmt_execute($updateTrip);
    
    // Check if this was an overlimit passenger
    $checkStatus = mysqli_prepare($conn, 
        "SELECT status FROM passenger_trips WHERE id = ?");
    mysqli_stmt_bind_param($checkStatus, "i", $trip_id);
    mysqli_stmt_execute($checkStatus);
    $status = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStatus))['status'];
    
    $current_passengers = 0;
    if ($status !== 'over_limit') {
        // Decrement passenger count
        $updateOccupancy = mysqli_prepare($conn, 
            "UPDATE bus_occupancy 
             SET current_passengers = GREATEST(0, current_passengers - 1) 
             WHERE bus_id = ?");
        mysqli_stmt_bind_param($updateOccupancy, "i", $bus_id);
        mysqli_stmt_execute($updateOccupancy);
    }
    
    // Get updated count
    $getCount = mysqli_prepare($conn, 
        "SELECT current_passengers FROM bus_occupancy WHERE bus_id = ?");
    mysqli_stmt_bind_param($getCount, "i", $bus_id);
    mysqli_stmt_execute($getCount);
    $current_passengers = mysqli_fetch_assoc(mysqli_stmt_get_result($getCount))['current_passengers'];
    
    mysqli_commit($conn);
    
    echo json_encode([
        'status' => 'success',
        'current_passengers' => $current_passengers
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>