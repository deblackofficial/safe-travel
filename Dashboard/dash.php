<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

include '../conn.php'; // Include the database connection file

// Fetch data from the database, group by place, and count occurrences
$sql = "SELECT place, COUNT(*) AS count FROM (
            SELECT place FROM passenger_report
            UNION ALL
            SELECT place FROM driver_report
        ) AS combined_reports
        GROUP BY place";
$result = mysqli_query($conn, $sql);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard | Transport Analytics</title>
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
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    body { 
      font-family: 'Poppins', 'Segoe UI', sans-serif; 
      background-color: #f5f7ff; 
      display: flex; 
      min-height: 100vh; 
      color: var(--dark);
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

    .header {
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
    
    .user-actions {
      display: flex;
      align-items: center;
      gap: 15px;
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

    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 20px;
    }

    .card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
      transition: var(--transition);
      border-top: 4px solid var(--accent);
      position: relative;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--accent), var(--success));
    }

    .card:hover { 
      transform: translateY(-5px); 
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .card-icon {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      background-color: rgba(72, 149, 239, 0.1);
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }
    
    .card h3 { 
      font-size: 16px; 
      font-weight: 500;
      color: var(--gray);
      margin-bottom: 5px;
    }
    
    .card .count {
      font-size: 28px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .card .place {
      font-size: 18px;
      font-weight: 600;
      margin: 10px 0;
      color: var(--primary-dark);
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
      margin-top: 15px;
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
    
    .no-data {
      grid-column: 1 / -1;
      text-align: center;
      padding: 40px;
      background: white;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
    }
    
    .no-data i {
      font-size: 40px;
      color: var(--gray);
      margin-bottom: 15px;
    }
    
    .no-data p {
      color: var(--gray);
      font-size: 16px;
    }
    
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
      }
      
      .dashboard-cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="brand">
      <h2><i class="fas fa-bus-alt brand-icon"></i> Transport Analytics</h2>
    </div>
    
    <nav>
      <a href="dash.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> IoT</a>
      <a href="userdetails.php"><i class="fas fa-users"></i> Users</a>
      <a href="routes.php"><i class="fas fa-route"></i> Routes & Lines</a>
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
    <div class="header">
      <div class="page-title">
        <h1>Incident Analytics</h1>
        <p>Monitor and analyze reported incidents across locations</p>
      </div>
      
      <div class="user-actions">
      </div>
    </div>

    <div class="dashboard-cards">
      <?php if (!empty($data)): ?>
        <?php foreach ($data as $item): ?>
          <div class="card">
            <div class="card-header">
              <div>
                <h3>Location</h3>
                <div class="place"><?php echo htmlspecialchars($item['place']); ?></div>
              </div>
              <div class="card-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
            </div>
            
            <div class="card-body">
              <h3>Total Reports</h3>
              <div class="count"><?php echo htmlspecialchars($item['count']); ?></div>
              <a href="details.php?place=<?php echo urlencode($item['place']); ?>" class="btn">
                <i class="fas fa-search"></i> View Details
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-data">
          <i class="fas fa-database"></i>
          <p>No incident data available at this time</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>