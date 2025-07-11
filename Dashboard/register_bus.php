<?php
session_start();
include 'conn.php';

// Create temporary scans table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS temp_rfid_scans (
        uid VARCHAR(32) PRIMARY KEY,
        timestamp INT NOT NULL,
        status ENUM('available', 'registered') NOT NULL,
        bus_id INT NULL,
        plate_number VARCHAR(20) NULL
    )
");

// Handle bus deletion
if (isset($_GET['delete_bus'])) {
    header('Content-Type: application/json');
    $bus_id = intval($_GET['delete_bus']);
    
    try {
        $conn->begin_transaction();
        // Delete from buses table
        $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->bind_param("i", $bus_id);
        $stmt->execute();
        
        // Delete from occupancy table
        $stmt = $conn->prepare("DELETE FROM bus_occupancy WHERE bus_id = ?");
        $stmt->bind_param("i", $bus_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Bus deleted successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle bus status update
if (isset($_POST['update_bus_status'])) {
    header('Content-Type: application/json');
    $bus_id = intval($_POST['bus_id']);
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE buses SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $bus_id);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Bus status updated']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle bus details update
if (isset($_POST['update_bus_details'])) {
    header('Content-Type: application/json');
    
    $bus_id = intval($_POST['bus_id']);
    $plate_number = trim($_POST['plate_number']);
    $capacity = intval($_POST['capacity']);
    $route_id = intval($_POST['route_id']);

    if (empty($plate_number)) throw new Exception('Plate number required');
    if ($capacity <= 0) throw new Exception('Invalid capacity');

    try {
        $conn->begin_transaction();
        
        // Update bus details
        $stmt = $conn->prepare("UPDATE buses SET plate_number = ?, capacity = ?, route_id = ? WHERE id = ?");
        $stmt->bind_param("siii", $plate_number, $capacity, $route_id, $bus_id);
        $stmt->execute();
        
        // Update capacity in occupancy table
        $stmt = $conn->prepare("UPDATE bus_occupancy SET max_capacity = ? WHERE bus_id = ?");
        $stmt->bind_param("ii", $capacity, $bus_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Bus details updated successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    try {
        if (isset($_GET['check_rfid'])) {
            handleRFIDCheck();
        }
        elseif (isset($_GET['debug_scans'])) {
            echo json_encode(getRecentScans());
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['update_bus_details'])) {
                // Already handled above
            } else {
                handleRegistration();
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle RFID reporting from Arduino
if (isset($_GET['report_rfid'])) {
    header('Content-Type: application/json');
    if (!isset($_GET['uid'])) {
        echo json_encode(['status' => 'error', 'message' => 'No UID provided']);
        exit;
    }
    
    $uid = strtoupper(trim($_GET['uid']));
    
    // Check if this RFID is already registered
    $stmt = $conn->prepare("SELECT id, plate_number FROM buses WHERE rfid_uid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Already registered
        $bus = $result->fetch_assoc();
        storeScan($uid, 'registered', $bus['id'], $bus['plate_number']);
        echo json_encode([
            'status' => 'already_registered', 
            'message' => 'Bus already registered',
            'bus' => $bus
        ]);
    } else {
        // Available for registration
        storeScan($uid, 'available');
        echo json_encode([
            'status' => 'success', 
            'rfid_uid' => $uid,
            'message' => 'RFID available for registration'
        ]);
    }
    exit;
}

function storeScan($uid, $status, $bus_id = null, $plate_number = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO temp_rfid_scans (uid, timestamp, status, bus_id, plate_number)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            timestamp = VALUES(timestamp), 
            status = VALUES(status), 
            bus_id = VALUES(bus_id),
            plate_number = VALUES(plate_number)
    ");
    $timestamp = time();
    $stmt->bind_param("sisis", $uid, $timestamp, $status, $bus_id, $plate_number);
    $stmt->execute();
}

function handleRFIDCheck() {
    global $conn;
    
    // Get the most recent scan within the last 30 seconds
    $stmt = $conn->prepare("
        SELECT * FROM temp_rfid_scans 
        WHERE timestamp > ? 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $cutoff = time() - 30;
    $stmt->bind_param("i", $cutoff);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $scan = $result->fetch_assoc();
        
        if ($scan['status'] === 'available') {
            echo json_encode([
                'status' => 'available',
                'rfid_uid' => $scan['uid'],
                'message' => 'Tag available for registration'
            ]);
        } else {
            echo json_encode([
                'status' => 'registered',
                'bus' => [
                    'id' => $scan['bus_id'],
                    'plate_number' => $scan['plate_number']
                ],
                'rfid_uid' => $scan['uid']
            ]);
        }
    } else {
        echo json_encode(['status' => 'waiting']);
    }
}

function getRecentScans() {
    global $conn;
    $result = $conn->query("SELECT * FROM temp_rfid_scans ORDER BY timestamp DESC LIMIT 10");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function handleRegistration() {
    global $conn;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $rfid_uid = strtoupper(trim($input['rfid_uid']));
    $plate_number = trim($input['plate_number']);
    $capacity = intval($input['capacity']);
    $route_id = intval($input['route_id']);

    if (empty($rfid_uid)) throw new Exception('RFID UID required');
    if (empty($plate_number)) throw new Exception('Plate number required');
    if ($capacity <= 0) throw new Exception('Invalid capacity');
    if ($route_id <= 0) throw new Exception('Route selection required');

    // Check if RFID already exists
    $check = $conn->prepare("SELECT id FROM buses WHERE rfid_uid = ?");
    $check->bind_param("s", $rfid_uid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('RFID UID already registered');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO buses (rfid_uid, plate_number, capacity, route_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $rfid_uid, $plate_number, $capacity, $route_id);
        $stmt->execute();
        
        $bus_id = $stmt->insert_id;
        $occ = $conn->prepare("INSERT INTO bus_occupancy (bus_id, max_capacity, current_passengers) VALUES (?, ?, 0)");
        $occ->bind_param("ii", $bus_id, $capacity);
        $occ->execute();
        
        // Update the scan status
        storeScan($rfid_uid, 'registered', $bus_id, $plate_number);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'bus_id' => $bus_id, 'message' => 'Bus registered successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Get all registered buses with route information
$buses = $conn->query("
    SELECT b.*, r.start_point, r.middle_point, r.end_point 
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.id
    ORDER BY b.created_at DESC
");

// Get all active routes for edit form
$routes = $conn->query("SELECT id, start_point, middle_point, end_point FROM routes WHERE status = 'active'");
$routes_list = [];
while ($route = $routes->fetch_assoc()) {
    $routes_list[] = $route;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .scan-section {
            border: 2px dashed #0d6efd;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            transition: all 0.3s;
        }
        .scan-section.detected {
            border-color: #198754;
            background-color: #d1e7dd;
        }
        .blink {
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        .auto-scan {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
        }
        #debugInfo {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            max-width: 300px;
            z-index: 1000;
        }
        .bus-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .bus-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-active {
            border-left: 4px solid #198754;
        }
        .status-maintenance {
            border-left: 4px solid #fd7e14;
        }
        .status-inactive {
            border-left: 4px solid #dc3545;
        }
        .bus-list {
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .dropdown-menu-wide {
            min-width: 300px;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        <div class="row">
            <!-- Left Side - Bus List -->
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-bus-front"></i> Registered Buses</h4>
                    </div>
                    <div class="card-body bus-list">
                        <?php if ($buses->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($bus = $buses->fetch_assoc()): 
                                    $routeName = '';
                                    if ($bus['route_id']) {
                                        $routeName = $bus['start_point'];
                                        if (!empty($bus['middle_point'])) {
                                            $routeName .= ' - ' . $bus['middle_point'];
                                        }
                                        $routeName .= ' - ' . $bus['end_point'];
                                    }
                                ?>
                                <div class="list-group-item p-3 mb-2 bus-card status-<?= $bus['status'] ?>" data-bus-id="<?= $bus['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($bus['plate_number']) ?></h5>
                                            <small class="text-muted">RFID: <?= htmlspecialchars($bus['rfid_uid']) ?></small>
                                            <div class="mt-1">
                                                <span class="badge bg-secondary">Capacity: <?= $bus['capacity'] ?></span>
                                                <?php if ($routeName): ?>
                                                    <span class="badge bg-info">Route: <?= htmlspecialchars($routeName) ?></span>
                                                <?php endif; ?>
                                                <span class="badge bg-<?= 
                                                    $bus['status'] === 'active' ? 'success' : 
                                                    ($bus['status'] === 'maintenance' ? 'warning' : 'danger') 
                                                ?>">
                                                    <?= ucfirst($bus['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item edit-bus" href="#" data-bus-id="<?= $bus['id'] ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <select class="form-select form-select-sm status-select" 
                                                        data-bus-id="<?= $bus['id'] ?>">
                                                        <option value="active" <?= $bus['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                        <option value="maintenance" <?= $bus['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                        <option value="inactive" <?= $bus['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger delete-bus" 
                                                        data-bus-id="<?= $bus['id'] ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No buses registered yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side - Registration Form -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-bus-front"></i> <span id="formTitle">Register New Bus</span></h4>
                        <button type="button" class="btn btn-sm btn-light" id="resetFormBtn">
                            <i class="bi bi-plus-circle"></i> Add New Bus
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info auto-scan" id="autoScanAlert">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span>Waiting for RFID tag... Place bus card near reader.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="debugBtn">Debug</button>
                            </div>
                        </div>

                        <form id="busForm" novalidate>
                            <input type="hidden" id="bus_id" name="bus_id" value="">
                            <div class="mb-3">
                                <label class="form-label">RFID UID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="rfid_uid" name="rfid_uid" required readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="clearBtn">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                                <div class="form-text">RFID will be automatically detected when card is scanned</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Plate Number</label>
                                <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Passenger Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Route (optional)</label>
                                <select class="form-select" id="route_id" name="route_id">
                                    <option value="">-- No route assigned --</option>
                                    <?php foreach ($routes_list as $route): 
                                        $routeName = $route['start_point'];
                                        if (!empty($route['middle_point'])) {
                                            $routeName .= ' - ' . $route['middle_point'];
                                        }
                                        $routeName .= ' - ' . $route['end_point'];
                                    ?>
                                    <option value="<?= $route['id'] ?>"><?= htmlspecialchars($routeName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2" id="submitBtn" disabled>
                                <i class="bi bi-save"></i> <span id="submitBtnText">Register Bus</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="debugInfo" style="display: none;">
        <h6>Debug Information</h6>
        <div>Last Check: <span id="lastCheck">-</span></div>
        <div>Status: <span id="debugStatus">-</span></div>
        <div>Response: <span id="debugResponse">-</span></div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this bus? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const autoScanAlert = document.getElementById('autoScanAlert');
        const rfidInput = document.getElementById('rfid_uid');
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        const clearBtn = document.getElementById('clearBtn');
        const debugBtn = document.getElementById('debugBtn');
        const debugInfo = document.getElementById('debugInfo');
        const busForm = document.getElementById('busForm');
        const formTitle = document.getElementById('formTitle');
        const resetFormBtn = document.getElementById('resetFormBtn');
        const deleteModal = new bootstrap.Modal('#deleteModal');
        
        const plateNumberInput = document.getElementById('plate_number');
        const capacityInput = document.getElementById('capacity');
        const routeSelect = document.getElementById('route_id');
        const busIdInput = document.getElementById('bus_id');
        
        let scanInterval;
        let busToDelete = null;

        // Toggle debug info
        debugBtn.addEventListener('click', function() {
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
            fetchRecentScans();
        });

        // Reset form to register new bus
        resetFormBtn.addEventListener('click', function() {
            resetForm();
        });

        function resetForm() {
            busForm.reset();
            busIdInput.value = '';
            rfidInput.value = '';
            submitBtn.disabled = true;
            formTitle.textContent = 'Register New Bus';
            submitBtnText.textContent = 'Register Bus';
            updateScanStatus('waiting');
        }

        // Handle edit bus button clicks
        document.querySelectorAll('.edit-bus').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const busId = this.dataset.busId;
                const busCard = this.closest('.bus-card');
                
                // Fetch bus details via AJAX
                fetch(`get_bus_details.php?bus_id=${busId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Populate the form
                            busIdInput.value = data.bus.id;
                            rfidInput.value = data.bus.rfid_uid;
                            plateNumberInput.value = data.bus.plate_number;
                            capacityInput.value = data.bus.capacity;
                            routeSelect.value = data.bus.route_id || '';
                            
                            // Update form title and button
                            formTitle.textContent = `Edit Bus: ${data.bus.plate_number}`;
                            submitBtnText.textContent = 'Update Bus';
                            submitBtn.disabled = false;
                            
                            // Scroll to form
                            document.querySelector('.col-md-8').scrollIntoView({ behavior: 'smooth' });
                        } else {
                            alert('Error: ' + (data.message || 'Failed to load bus details'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to load bus details');
                    });
            });
        });

        // Handle bus status changes
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const busId = this.dataset.busId;
                const newStatus = this.value;
                
                fetch('register_bus.php?api=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `update_bus_status=1&bus_id=${busId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update the UI
                        const busCard = this.closest('.bus-card');
                        busCard.className = busCard.className.replace(/\bstatus-\w+/g, '');
                        busCard.classList.add(`status-${newStatus}`);
                        
                        // Update status badge
                        const statusBadge = busCard.querySelector('.badge.bg-success, .badge.bg-warning, .badge.bg-danger');
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-' + 
                                (newStatus === 'active' ? 'success' : 
                                 newStatus === 'maintenance' ? 'warning' : 'danger');
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        }
                        
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show mt-2';
                        alert.innerHTML = `
                            <span>Bus status updated successfully!</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        busCard.parentNode.insertBefore(alert, busCard.nextSibling);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            bootstrap.Alert.getOrCreateInstance(alert).close();
                        }, 3000);
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update status'));
                        // Reset to original value
                        this.value = this.dataset.originalValue;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update bus status');
                    this.value = this.dataset.originalValue;
                });
            });
        });

        // Handle bus deletion
        document.querySelectorAll('.delete-bus').forEach(btn => {
            btn.addEventListener('click', function() {
                busToDelete = this.dataset.busId;
                deleteModal.show();
            });
        });

        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!busToDelete) return;
            
            fetch(`register_bus.php?delete_bus=${busToDelete}&api=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove the bus card from UI
                        document.querySelector(`.delete-bus[data-bus-id="${busToDelete}"]`).closest('.bus-card').remove();
                        deleteModal.hide();
                        
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show mt-2';
                        alert.innerHTML = `
                            <span>Bus deleted successfully!</span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.bus-list').prepend(alert);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            bootstrap.Alert.getOrCreateInstance(alert).close();
                        }, 3000);
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete bus'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete bus');
                })
                .finally(() => {
                    busToDelete = null;
                });
        });

        // Start auto-scanning
        startAutoScanning();

        function startAutoScanning() {
            scanInterval = setInterval(checkRFID, 1000);
            updateScanStatus('waiting');
        }

        function checkRFID() {
            const checkUrl = 'register_bus.php?check_rfid=1&api=1&t=' + Date.now();
            updateDebug('Checking URL', checkUrl);
            
            fetch(checkUrl)
                .then(response => {
                    updateDebug('Response status', response.status);
                    return response.json();
                })
                .then(data => {
                    updateDebug('RFID Check Response', JSON.stringify(data));
                    if (data.status === 'available' && data.rfid_uid) {
                        rfidInput.value = data.rfid_uid;
                        updateScanStatus('detected', data.rfid_uid);
                        submitBtn.disabled = false;
                    } else if (data.status === 'registered') {
                        updateScanStatus('already_registered', data.rfid_uid);
                    } else {
                        updateScanStatus('waiting');
                    }
                })
                .catch(error => {
                    updateDebug('RFID Check Error', error.message);
                    updateScanStatus('error');
                });
        }

        function fetchRecentScans() {
            fetch('register_bus.php?debug_scans=1&api=1')
                .then(response => response.json())
                .then(data => {
                    updateDebug('Recent Scans', JSON.stringify(data, null, 2));
                });
        }

        function updateScanStatus(status, uid = '') {
            switch(status) {
                case 'waiting':
                    autoScanAlert.className = 'alert alert-info auto-scan';
                    autoScanAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            <span>Waiting for RFID tag... Place bus card near reader.</span>
                        </div>`;
                    break;
                case 'detected':
                    autoScanAlert.className = 'alert alert-success';
                    autoScanAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i>
                            <span>RFID Detected: <strong>${uid}</strong> - Ready to register!</span>
                        </div>`;
                    break;
                case 'already_registered':
                    autoScanAlert.className = 'alert alert-warning';
                    autoScanAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <span>RFID <strong>${uid}</strong> is already registered. Try another card.</span>
                        </div>`;
                    break;
                case 'error':
                    autoScanAlert.className = 'alert alert-danger';
                    autoScanAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <span>Connection error. Check Arduino connection.</span>
                        </div>`;
                    break;
            }
        }

        clearBtn.addEventListener('click', function() {
            rfidInput.value = '';
            submitBtn.disabled = true;
            updateScanStatus('waiting');
        });

        busForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            const isEdit = busIdInput.value !== '';
            const action = isEdit ? 'update_bus_details' : 'register_bus';
            
            // Add action to form data
            formData.append(isEdit ? 'update_bus_details' : 'register_bus', '1');
            
            fetch('register_bus.php?api=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(`Bus ${isEdit ? 'updated' : 'registered'} successfully!`);
                    resetForm();
                    // Reload the page to show the updated bus list
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || `${isEdit ? 'Update' : 'Registration'} failed`));
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`${isEdit ? 'Update' : 'Registration'} failed. Please try again.`);
                submitBtn.disabled = false;
            })
            .finally(() => {
                submitBtn.innerHTML = `<i class="bi bi-save"></i> ${isEdit ? 'Update' : 'Register'} Bus`;
            });
        });

        function updateDebug(label, value) {
            const now = new Date().toLocaleTimeString();
            document.getElementById('lastCheck').textContent = now;
            document.getElementById('debugStatus').textContent = label;
            document.getElementById('debugResponse').textContent = value;
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            clearInterval(scanInterval);
        });
    });
    </script>
</body>
</html>