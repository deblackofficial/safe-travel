<?php
session_start(); // Start the session
include '../conn.php'; // Include the database connection file

// Determine if viewing archived reports
$isArchived = isset($_GET['archived']) && $_GET['archived'] == 1;

// Fetch data from the database
$sql = "SELECT * FROM driver_report WHERE archived = " . ($isArchived ? 1 : 0) . " ORDER BY datetime DESC";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM driver_report WHERE archived = " . ($isArchived ? 1 : 0) . " 
            AND (phone LIKE '%$search%' OR agency LIKE '%$search%' OR plate LIKE '%$search%') 
            ORDER BY datetime DESC";
}
$result = mysqli_query($conn, $sql);

// Handle CSV download
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="driver_details.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Phone', 'Agency', 'Plate', 'Place', 'Date & Time', 'Latitude', 'Longitude', 'Accident', 'Unauthorized', 'Description', 'Permit']);

    $counter = 1;
    $csvResult = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($csvResult)) {
        fputcsv($output, [
            $counter++,
            $row['phone'],
            $row['agency'],
            $row['plate'],
            $row['place'],
            $row['datetime'],
            $row['latitude'],
            $row['longitude'],
            $row['accident'] ? 'Yes' : 'No',
            $row['unauthorized'] ? 'Yes' : 'No',
            $row['description'],
            $row['permit']
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
  <title>Driver Reports | Transport System</title>
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
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
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
      vertical-align: middle;
    }

    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }

    .badge-yes {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }

    .badge-no {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }

    .badge-primary {
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

    .view-btn {
      background-color: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
    }

    .download-btn {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }

    .archive-btn {
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
      max-width: 600px;
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
      max-height: 60vh;
      overflow-y: auto;
    }

    .modal-body p {
      line-height: 1.6;
      white-space: pre-wrap;
    }

    .modal-footer {
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: flex-end;
    }

    .toggle-btn {
      background: linear-gradient(135deg, #f8961e, #f3722c);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .toggle-btn:hover {
      background: linear-gradient(135deg, #e07e0c, #d45a1a);
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
      
      .search-input, .filter-select, .filter-btn, .export-btn, .toggle-btn {
        width: 100%;
      }
      
      .data-table {
        font-size: 12px;
      }
      
      .data-table td, .data-table th {
        padding: 8px 10px;
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
        <a href="routes.php"><i class="fas fa-route"></i> Route Management</a>
        <a href="driverdetails.php" class="active"><i class="fas fa-id-card"></i> Driver Reports</a>
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
          <h1>Driver Reports</h1>
          <p><?php echo $isArchived ? 'Archived Reports' : 'Active Reports'; ?></p>
        </div>
        
        <div class="header-actions">
      
          <a href="?archived=<?php echo $isArchived ? 0 : 1; ?>" class="toggle-btn">
            <i class="fas fa-<?php echo $isArchived ? 'eye' : 'archive'; ?>"></i>
            <?php echo $isArchived ? 'View Active Reports' : 'View Archived Reports'; ?>
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Driver Report Details</h2>
          <div class="card-actions">
            <a href="?download_csv=1" class="export-btn">
              <i class="fas fa-file-export"></i> Export CSV
            </a>
          </div>
        </div>
        
        <form method="GET" class="filter-controls">
          <?php if ($isArchived): ?>
            <input type="hidden" name="archived" value="1">
          <?php endif; ?>
          <input type="text" class="search-input" name="search" 
                 placeholder="Search by phone, agency, or plate number" 
                 value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button type="submit" class="filter-btn">
            <i class="fas fa-search"></i> Search
          </button>
        </form>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Phone</th>
                  <th>Agency</th>
                  <th>Plate</th>
                  <th>Place</th>
                  <th>Date & Time</th>
                  <th>Accident</th>
                  <th>Unauthorized</th>
                  <th>Description</th>
                  <th>Permit</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $counter = 1; ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['agency']); ?></td>
                    <td><?php echo htmlspecialchars($row['plate']); ?></td>
                    <td><?php echo htmlspecialchars($row['place']); ?></td>
                    <td><?php echo htmlspecialchars($row['datetime']); ?></td>
                    <td>
                      <span class="badge badge-<?php echo $row['accident'] ? 'yes' : 'no'; ?>">
                        <?php echo $row['accident'] ? 'Yes' : 'No'; ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-<?php echo $row['unauthorized'] ? 'yes' : 'no'; ?>">
                        <?php echo $row['unauthorized'] ? 'Yes' : 'No'; ?>
                      </span>
                    </td>
                    <td>
                      <button class="action-btn view-btn" onclick="showModal(`<?php echo addslashes(htmlspecialchars($row['description'])); ?>`)">
                        <i class="fas fa-eye"></i> View
                      </button>
                    </td>
                    <td>
                      <?php if (!empty($row['permit'])): ?>
                        <a href="../uploads/<?php echo htmlspecialchars($row['permit']); ?>" 
                           target="_blank" 
                           class="action-btn download-btn">
                          <i class="fas fa-download"></i> Download
                        </a>
                      <?php else: ?>
                        <span class="badge">N/A</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!$isArchived): ?>
                        <a href="archivedriver.php?id=<?php echo $row['id']; ?>" 
                           class="action-btn archive-btn" 
                           onclick="return confirm('Are you sure you want to archive this report?');">
                          <i class="fas fa-archive"></i> Archive
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="no-data">
            <i class="fas fa-clipboard-list"></i>
            <p>No driver reports found matching your criteria</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Description Modal -->
  <div id="descriptionModal" class="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Report Description</h5>
          <button type="button" class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
          <p id="modalDescription"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn" onclick="closeModal()">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Show the modal with the description
    function showModal(description) {
      document.getElementById('modalDescription').textContent = description;
      const modal = document.getElementById('descriptionModal');
      modal.style.display = 'block';
      setTimeout(() => modal.classList.add('show'), 10);
    }

    // Close the modal
    function closeModal() {
      const modal = document.getElementById('descriptionModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close the modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('descriptionModal');
      if (event.target === modal) {
        closeModal();
      }
    }

    // Confirmation for archive actions
    document.addEventListener('DOMContentLoaded', function() {
      const archiveButtons = document.querySelectorAll('.archive-btn');
      
      archiveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          if (!confirm('Are you sure you want to archive this report?')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>
</html>