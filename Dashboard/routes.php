<?php
session_start(); // Start the session
include '../conn.php'; // Include the database connection file

// Check if the user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle adding a new route
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add' && $isAdmin) {
    $start_point = mysqli_real_escape_string($conn, $_POST['start_point']);
    $middle_point = mysqli_real_escape_string($conn, $_POST['middle_point']);
    $end_point = mysqli_real_escape_string($conn, $_POST['end_point']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate required fields
    if (empty($start_point) || empty($end_point) || empty($status)) {
        $error = "Start point, end point, and status are required.";
    } else {
        // Insert the new route into the database
        $sql = "INSERT INTO routes (start_point, middle_point, end_point, status) 
                VALUES ('$start_point', '$middle_point', '$end_point', '$status')";

        if (mysqli_query($conn, $sql)) {
            $success = "Route added successfully!";
        } else {
            $error = "Error adding route: " . mysqli_error($conn);
        }
    }
}

// Handle editing an existing route
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'edit' && $isAdmin) {
    $route_id = intval($_POST['route_id']);
    $start_point = mysqli_real_escape_string($conn, $_POST['start_point']);
    $middle_point = mysqli_real_escape_string($conn, $_POST['middle_point']);
    $end_point = mysqli_real_escape_string($conn, $_POST['end_point']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate required fields
    if (empty($start_point) || empty($end_point) || empty($status)) {
        $error = "Start point, end point, and status are required.";
    } else {
        // Update the route in the database
        $sql = "UPDATE routes 
                SET start_point = '$start_point', 
                    middle_point = '$middle_point', 
                    end_point = '$end_point', 
                    status = '$status' 
                WHERE id = $route_id";

        if (mysqli_query($conn, $sql)) {
            $success = "Route updated successfully!";
        } else {
            $error = "Error updating route: " . mysqli_error($conn);
        }
    }
}

// Fetch all routes from the database
$sql = "SELECT id, start_point, middle_point, end_point, status, created_at FROM routes ORDER BY id ASC";
$result = mysqli_query($conn, $sql);

// Check for query errors
if (!$result) {
    die("Error executing query: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Routes Management | Transport System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a0ca3;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --success: #4cc9f0;
      --warning: #f72585;
      --danger: #f72585;
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    body { 
      font-family: 'Poppins', sans-serif; 
      background-color: #f5f7ff; 
      color: var(--dark);
    }

    .dashboard-layout {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 280px;
      background: linear-gradient(180deg, var(--primary), var(--primary-dark));
      color: white;
      padding: 25px 0;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 10;
    }
    
    .brand {
      padding: 0 25px 25px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .brand h2 { 
      font-size: 24px; 
      font-weight: 600; 
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .brand-icon {
      color: var(--success);
    }
    
    .sidebar nav {
      flex: 1;
      padding: 25px 0;
      overflow-y: auto;
    }
    
    .sidebar nav a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 15px;
      padding: 12px 25px;
      margin: 5px 0;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 3px solid transparent;
      transition: var(--transition);
    }
    
    .sidebar nav a:hover, 
    .sidebar nav a.active {
      color: white;
      background: rgba(255, 255, 255, 0.1);
      border-left: 3px solid var(--accent);
    }
    
    .sidebar nav a i {
      width: 20px;
      text-align: center;
    }
    
    .sidebar-footer {
      padding: 20px 25px 0;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .user-info small {
      display: block;
      color: rgba(255, 255, 255, 0.6);
      font-size: 12px;
    }

    .main-content {
      flex: 1;
      padding: 25px;
      overflow-y: auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    
    .page-title h1 {
      font-size: 28px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .page-title p {
      color: var(--gray);
      font-size: 14px;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .btn {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
    }
    
    .btn:hover { 
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }
    
    .btn i {
      font-size: 12px;
    }

    .btn-success {
      background: linear-gradient(135deg, #4cc9f0, #4895ef);
    }

    .btn-danger {
      background: linear-gradient(135deg, #f72585, #b5179e);
    }

    .notification-btn {
      position: relative;
      background: none;
      border: none;
      color: var(--gray);
      font-size: 18px;
      cursor: pointer;
    }
    
    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: var(--warning);
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
      transition: var(--transition);
      margin-bottom: 25px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .card-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--primary-dark);
    }

    .table-responsive {
      overflow-x: auto;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
    }

    .data-table thead th {
      background-color: var(--primary);
      color: white;
      padding: 12px 15px;
      text-align: left;
      font-weight: 500;
      position: sticky;
      top: 0;
    }

    .data-table tbody tr {
      border-bottom: 1px solid #dddddd;
      transition: var(--transition);
    }

    .data-table tbody tr:nth-of-type(even) {
      background-color: #f9f9f9;
    }

    .data-table tbody tr:last-of-type {
      border-bottom: 2px solid var(--primary);
    }

    .data-table tbody tr:hover {
      background-color: #f1f1f1;
    }

    .data-table td {
      padding: 12px 15px;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }

    .status-active {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }

    .status-inactive {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }

    .action-btn {
      padding: 6px 12px;
      border-radius: 5px;
      font-size: 13px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .action-btn i {
      font-size: 12px;
    }

    .action-btn:hover {
      transform: translateY(-2px);
    }

    .edit-btn {
      background-color: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
    }

    .delete-btn {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1050;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      background-color: rgba(0, 0, 0, 0.5);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal.show {
      opacity: 1;
    }

    .modal-dialog {
      max-width: 500px;
      margin: 10% auto;
      transform: translateY(-50px);
      transition: transform 0.3s ease;
    }

    .modal.show .modal-dialog {
      transform: translateY(0);
    }

    .modal-content {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .modal-header {
      padding: 15px 20px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-title {
      font-size: 18px;
      font-weight: 600;
    }

    .close {
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      opacity: 0.8;
      transition: opacity 0.2s;
    }

    .close:hover {
      opacity: 1;
    }

    .modal-body {
      padding: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      transition: border-color 0.3s;
    }

    .form-control:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .modal-footer {
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* Alert Messages */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
      border-left: 4px solid #28a745;
    }

    .alert-danger {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
      border-left: 4px solid #dc3545;
    }

    .alert i {
      font-size: 18px;
    }

    @media (max-width: 768px) {
      .dashboard-layout {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
      }
      
      .modal-dialog {
        margin: 20px auto;
        width: 95%;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <div class="sidebar">
      <div class="brand">
        <h2><i class="fas fa-bus-alt brand-icon"></i> Transport System</h2>
      </div>
      
      <nav>
        <a href="dash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="userdetails.php"><i class="fas fa-users"></i> User Management</a>
        <a href="routes.php" class="active"><i class="fas fa-route"></i> Route Management</a>
        <a href="driverdetails.php"><i class="fas fa-id-card"></i> Driver Reports</a>
        <a href="passengerdetails.php"><i class="fas fa-user-tag"></i> Passenger Reports</a>
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
          </div>
          <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <small>Admin</small>
          </div>
        </div>
        <a href="logout.php" class="btn" style="display: block; text-align: center; margin-top: 15px;">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>
    
    <div class="main-content">
      <div class="page-header">
        <div class="page-title">
          <h1>Route Management</h1>
          <p>Manage all transportation routes in the system</p>
        </div>
        
        <div class="header-actions">
          <?php if ($isAdmin): ?>
            <button class="btn btn-success" onclick="openAddModal()">
              <i class="fas fa-plus"></i> Add Route
            </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">All Routes</h2>
          <div class="card-actions">
            <span class="badge">Total: <?php echo mysqli_num_rows($result); ?></span>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Start Point</th>
                <th>Middle Point</th>
                <th>End Point</th>
                <th>Status</th>
                <th>Created At</th>
                <?php if ($isAdmin): ?>
                  <th>Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php $counter = 1; ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <td><?php echo $counter++; ?></td>
                  <td><?php echo htmlspecialchars($row['start_point']); ?></td>
                  <td><?php echo htmlspecialchars($row['middle_point']); ?></td>
                  <td><?php echo htmlspecialchars($row['end_point']); ?></td>
                  <td>
                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                      <?php echo ucfirst($row['status']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                  <?php if ($isAdmin): ?>
                    <td>
                      <button class="action-btn edit-btn" 
                        onclick="openEditModal(
                          <?php echo $row['id']; ?>, 
                          '<?php echo htmlspecialchars($row['start_point']); ?>', 
                          '<?php echo htmlspecialchars($row['middle_point']); ?>', 
                          '<?php echo htmlspecialchars($row['end_point']); ?>', 
                          '<?php echo htmlspecialchars($row['status']); ?>'
                        )">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Route Modal -->
  <div id="addRouteModal" class="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Route</h5>
          <button type="button" class="close" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
              <label for="add_start_point" class="form-label">Start Point</label>
              <input type="text" class="form-control" id="add_start_point" name="start_point" placeholder="Enter start point" required>
            </div>
            
            <div class="form-group">
              <label for="add_middle_point" class="form-label">Middle Point (Optional)</label>
              <input type="text" class="form-control" id="add_middle_point" name="middle_point" placeholder="Enter middle point">
            </div>
            
            <div class="form-group">
              <label for="add_end_point" class="form-label">End Point</label>
              <input type="text" class="form-control" id="add_end_point" name="end_point" placeholder="Enter end point" required>
            </div>
            
            <div class="form-group">
              <label for="add_status" class="form-label">Status</label>
              <select class="form-control" id="add_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeAddModal()">Cancel</button>
            <button type="submit" class="btn btn-success">Add Route</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Route Modal -->
  <div id="editRouteModal" class="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Route</h5>
          <button type="button" class="close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <input type="hidden" id="edit_route_id" name="route_id">
            <input type="hidden" name="action" value="edit">
            
            <div class="form-group">
              <label for="edit_start_point" class="form-label">Start Point</label>
              <input type="text" class="form-control" id="edit_start_point" name="start_point" required>
            </div>
            
            <div class="form-group">
              <label for="edit_middle_point" class="form-label">Middle Point (Optional)</label>
              <input type="text" class="form-control" id="edit_middle_point" name="middle_point">
            </div>
            
            <div class="form-group">
              <label for="edit_end_point" class="form-label">End Point</label>
              <input type="text" class="form-control" id="edit_end_point" name="end_point" required>
            </div>
            
            <div class="form-group">
              <label for="edit_status" class="form-label">Status</label>
              <select class="form-control" id="edit_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
            <button type="submit" class="btn btn-success">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Modal functions
    function openAddModal() {
      const modal = document.getElementById('addRouteModal');
      modal.style.display = 'block';
      setTimeout(() => modal.classList.add('show'), 10);
    }

    function closeAddModal() {
      const modal = document.getElementById('addRouteModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    function openEditModal(id, startPoint, middlePoint, endPoint, status) {
      document.getElementById('edit_route_id').value = id;
      document.getElementById('edit_start_point').value = startPoint;
      document.getElementById('edit_middle_point').value = middlePoint;
      document.getElementById('edit_end_point').value = endPoint;
      document.getElementById('edit_status').value = status;
      
      const modal = document.getElementById('editRouteModal');
      modal.style.display = 'block';
      setTimeout(() => modal.classList.add('show'), 10);
    }

    function closeEditModal() {
      const modal = document.getElementById('editRouteModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const addModal = document.getElementById('addRouteModal');
      const editModal = document.getElementById('editRouteModal');
      
      if (event.target === addModal) {
        closeAddModal();
      } else if (event.target === editModal) {
        closeEditModal();
      }
    }

    // Show success/error messages and hide after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => alert.style.display = 'none', 300);
        }, 5000);
      });
    });
  </script>
</body>
</html>