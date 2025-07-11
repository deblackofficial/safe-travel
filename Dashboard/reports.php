<?php
include 'conn.php';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passenger Trip Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .card-header { font-weight: 500; }
        .table-responsive { overflow-x: auto; }
        .badge-ol { background-color: #dc3545; }
        .badge-boarded { background-color: #28a745; }
        .badge-completed { background-color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Passenger Trip Reports</h5>
                    <div>
                        <button class="btn btn-sm btn-light" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="reports.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Passenger</th>
                                <th>Bus Plate</th>
                                <th>Route</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT t.*, 
                                     u.first_name, u.last_name,
                                     b.plate_number,
                                     r.start_point, r.end_point
                              FROM passenger_trips t
                              JOIN users u ON t.passenger_id = u.id
                              JOIN buses b ON t.bus_id = b.id
                              LEFT JOIN routes r ON t.route_id = r.id
                              WHERE DATE(t.entry_time) BETWEEN ? AND ?
                              ORDER BY t.entry_time DESC";
                            
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($trip = mysqli_fetch_assoc($result)) {
                                    $duration = '';
                                    if ($trip['exit_time']) {
                                        $entry = new DateTime($trip['entry_time']);
                                        $exit = new DateTime($trip['exit_time']);
                                        $interval = $entry->diff($exit);
                                        $duration = $interval->format('%Hh %Im');
                                    }
                                    
                                    $statusClass = '';
                                    if ($trip['status'] === 'over_limit') {
                                        $statusClass = 'badge-ol';
                                    } elseif ($trip['status'] === 'boarding') {
                                        $statusClass = 'badge-boarded';
                                    } else {
                                        $statusClass = 'badge-completed';
                                    }
                                    
                                    echo "<tr>
                                        <td>{$trip['first_name']} {$trip['last_name']}</td>
                                        <td>{$trip['plate_number']}</td>
                                        <td>{$trip['start_point']} to {$trip['end_point']}</td>
                                        <td>" . date('M j, Y H:i', strtotime($trip['entry_time'])) . "</td>
                                        <td>" . ($trip['exit_time'] ? date('M j, Y H:i', strtotime($trip['exit_time'])) : '-') . "</td>
                                        <td><span class='badge {$statusClass}'>" . strtoupper($trip['status']) . "</span></td>
                                        <td>{$duration}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='7' class='text-center py-4 text-muted'>
                                        <i class='bi bi-info-circle'></i> No trips found for selected date range
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Trips</h5>
                                <p class="card-text display-6">
                                    <?php
                                    $countQuery = "SELECT COUNT(*) as total 
                                                  FROM passenger_trips 
                                                  WHERE DATE(entry_time) BETWEEN ? AND ?";
                                    $stmt = mysqli_prepare($conn, $countQuery);
                                    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    $total = mysqli_fetch_assoc($result);
                                    echo $total['total'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Completed Trips</h5>
                                <p class="card-text display-6">
                                    <?php
                                    $countQuery = "SELECT COUNT(*) as completed 
                                                  FROM passenger_trips 
                                                  WHERE status = 'completed' 
                                                  AND DATE(entry_time) BETWEEN ? AND ?";
                                    $stmt = mysqli_prepare($conn, $countQuery);
                                    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    $completed = mysqli_fetch_assoc($result);
                                    echo $completed['completed'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-danger mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Over Limit Trips</h5>
                                <p class="card-text display-6">
                                    <?php
                                    $countQuery = "SELECT COUNT(*) as over_limit 
                                                  FROM passenger_trips 
                                                  WHERE status = 'over_limit' 
                                                  AND DATE(entry_time) BETWEEN ? AND ?";
                                    $stmt = mysqli_prepare($conn, $countQuery);
                                    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    $overLimit = mysqli_fetch_assoc($result);
                                    echo $overLimit['over_limit'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>