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
  <title>Admin Dashboard</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background-color: #f1f1f1; display: flex; min-height: 100vh; }
    
    .sidebar {
      width: 250px;
      background-color: #3b47f1;
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .sidebar h2 { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
    .sidebar nav a {
      color: white;
      text-decoration: none;
      font-size: 18px;
      padding: 10px 0;
      margin: 10px 0;
      display: block;
      transition: background 0.3s;
    }
    .sidebar nav a:hover { background-color: #1a2bcf; border-radius: 5px; }

    .main-content {
      flex-grow: 1;
      padding: 20px;
      background-color: #ffffff;
      overflow-y: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #3b47f1;
      color: white;
      padding: 15px;
      border-radius: 5px;
    }

    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .card:hover { transform: translateY(-5px); }
    .card h3 { font-size: 18px; margin-bottom: 10px; }
    .card p { font-size: 15px; color: #555; }

    .btn {
      background-color: #3b47f1;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 25px;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      margin-top: 15px;
    }

    .btn:hover { background-color: #1a2bcf; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><strong>Dashboard</strong></h2>
    <nav>
      <a href="userdetails.php">Users</a>
      <a href="agency.php">Agency</a>
      <a href="routes.php">Routes - Lines</a>
      <a href="driverdetails.php" class="btn">View Driver Reports</a>
      <a href="passengerdetails.php" class="btn">View Passenger Reports</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
  <div class="main-content">
    <div class="header">
      <h1>Admin Dashboard</h1>
    </div>

    <div class="dashboard-cards">
      <?php if (!empty($data)): ?>
        <?php foreach ($data as $item): ?>
          <div class="card">
            <h3>Place: <?php echo htmlspecialchars($item['place']); ?></h3>
            <p>Total Reports: <?php echo htmlspecialchars($item['count']); ?></p>
            <a href="details.php?place=<?php echo urlencode($item['place']); ?>" class="btn">View Details</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No data available.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>