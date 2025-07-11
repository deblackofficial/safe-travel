<?php
session_start(); // Start the session
include '../conn.php'; // Include the database connection file

// Check if the user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle search/filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$searchQuery = "";

// Non-admin users can only view active users
if (!$isAdmin) {
    $filter = 'active';
}

if ($filter === 'active') {
    $searchQuery = "WHERE status = 'active'";
} elseif ($filter === 'inactive' && $isAdmin) {
    $searchQuery = "WHERE status = 'inactive'";
}

// Add search functionality
if (!empty($search)) {
    $searchQuery .= (empty($searchQuery) ? "WHERE" : " AND") . " (username LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

// Fetch all users from the database
$sql = "SELECT id, username, first_name, last_name, phone_number, email, role, status FROM users $searchQuery ORDER BY role ASC";
$result = mysqli_query($conn, $sql);

// Check for query errors
if (!$result) {
    die("Error executing query: " . mysqli_error($conn));
}

// Handle CSV download
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_details.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'First Name', 'Last Name', 'Phone Number', 'Email', 'Role', 'Status']);

    $csvResult = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($csvResult)) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['first_name'],
            $row['last_name'],
            $row['phone_number'],
            $row['email'],
            $row['role'],
            $row['status']
        ]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | Transport System</title>
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

    .btn-warning {
      background: linear-gradient(135deg, #f8961e, #f3722c);
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

    .filter-controls {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .search-input {
      flex: 1;
      min-width: 250px;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s;
    }

    .search-input:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .filter-select {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      background-color: white;
      cursor: pointer;
    }

    .filter-select:focus {
      border-color: var(--primary);
      outline: none;
    }

    .filter-btn {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
    }

    .filter-btn:hover {
      background: var(--primary-dark);
    }

    .export-btn {
      background: var(--success);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: var(--transition);
    }

    .export-btn:hover {
      background: #3aa8d8;
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

    .role-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary);
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

    .activate-btn {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }

    .deactivate-btn {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }

    .no-data {
      text-align: center;
      padding: 40px;
      color: var(--gray);
      font-size: 16px;
    }

    .no-data i {
      font-size: 40px;
      margin-bottom: 15px;
      color: var(--gray);
    }

    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
    }

    .badge-primary {
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary);
    }

    @media (max-width: 768px) {
      .dashboard-layout {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
      }
      
      .filter-controls {
        flex-direction: column;
      }
      
      .search-input, .filter-select, .filter-btn, .export-btn {
        width: 100%;
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
        <a href="userdetails.php" class="active"><i class="fas fa-users"></i> User Management</a>
        <a href="routes.php"><i class="fas fa-route"></i> Route Management</a>
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
          <h1>User Management</h1>
          <p>Manage all system users and their permissions</p>
        </div>
        
        <div class="header-actions">
          
          <?php if ($isAdmin): ?>
            <a href="adduser.php" class="btn btn-success">
              <i class="fas fa-user-plus"></i> Add User
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">User List</h2>
          <span class="badge badge-primary">Total: <?php echo mysqli_num_rows($result); ?></span>
        </div>
        
        <form method="GET" class="filter-controls">
          <input type="text" class="search-input" name="search" placeholder="Search by username, name, or email" value="<?php echo htmlspecialchars($search); ?>">
          <select name="filter" class="filter-select">
            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
            <?php if ($isAdmin): ?>
              <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
            <?php endif; ?>
          </select>
          <button type="submit" class="filter-btn">
            <i class="fas fa-filter"></i> Filter
          </button>
          <a href="?download_csv=1" class="export-btn">
            <i class="fas fa-file-export"></i> Export CSV
          </a>
        </form>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Username</th>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Role</th>
                  <th>Status</th>
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
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                    </td>
                    <td>
                      <div><?php echo htmlspecialchars($row['phone_number']); ?></div>
                      <div class="text-muted"><?php echo htmlspecialchars($row['email']); ?></div>
                    </td>
                    <td>
                      <span class="role-badge"><?php echo htmlspecialchars(ucfirst($row['role'])); ?></span>
                    </td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                        <?php echo ucfirst($row['status']); ?>
                      </span>
                    </td>
                    <?php if ($isAdmin): ?>
                      <td>
                        <div style="display: flex; gap: 8px;">
                          <a href="edituser.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                            <i class="fas fa-edit"></i> Edit
                          </a>
                          <?php if ($row['status'] === 'active'): ?>
                            <a href="toggleuser.php?id=<?php echo $row['id']; ?>&action=deactivate" 
                               class="action-btn deactivate-btn" 
                               onclick="return confirm('Are you sure you want to deactivate this user?');">
                              <i class="fas fa-user-slash"></i> Deactivate
                            </a>
                          <?php else: ?>
                            <a href="toggleuser.php?id=<?php echo $row['id']; ?>&action=activate" 
                               class="action-btn activate-btn" 
                               onclick="return confirm('Are you sure you want to activate this user?');">
                              <i class="fas fa-user-check"></i> Activate
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="no-data">
            <i class="fas fa-user-times"></i>
            <p>No users found matching your criteria</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Confirmation for user actions
    document.addEventListener('DOMContentLoaded', function() {
      const actionButtons = document.querySelectorAll('.action-btn');
      
      actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          if (this.classList.contains('deactivate-btn') || this.classList.contains('activate-btn')) {
            if (!confirm(this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?')) {
              e.preventDefault();
            }
          }
        });
      });
    });
  </script>
</body>
</html>