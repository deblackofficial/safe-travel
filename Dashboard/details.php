<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

include '../conn.php'; // Include the database connection file

// Get the place from the query parameter
$place = isset($_GET['place']) ? mysqli_real_escape_string($conn, $_GET['place']) : '';

if (empty($place)) {
    echo "No place specified.";
    exit();
}

// Fetch reports for the specified place from both driver_report and passenger_report
$sql = "SELECT 'Driver' AS report_type, id, phone AS identifier, agency, plate, place, datetime, latitude, longitude, NULL AS overloading, accident, unauthorized, description, permit 
        FROM driver_report 
        WHERE place = '$place'
        UNION ALL
        SELECT 'Passenger' AS report_type, id, ticket AS identifier, agency, plate, place, datetime, latitude, longitude, overloading, accident, unauthorized, description, upload AS permit 
        FROM passenger_report 
        WHERE place = '$place'
        ORDER BY datetime DESC";
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Details for <?php echo htmlspecialchars($place); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-color: #f4f7fc;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
      background-color: #ffffff;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    h1 {
      text-align: center;
      color: #3b47f1;
      margin-bottom: 30px;
      font-size: 28px;
      font-weight: bold;
    }

    .btn {
      display: inline-block;
      margin-bottom: 20px;
      padding: 10px 20px;
      background-color: #3b47f1;
      color: white;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn:hover {
      background-color: #2a2ad8;
      transform: translateY(-2px);
    }

    .table-container {
      max-height: 500px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
    }

    table th, table td {
      padding: 15px;
      text-align: left;
      border: 1px solid #ddd;
    }

    table th {
      background-color: #3b47f1;
      color: white;
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 1;
      text-transform: uppercase;
    }

    table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    table tr:hover {
      background-color: #f1f1f1;
      transition: background-color 0.3s ease;
    }

    .no-data {
      text-align: center;
      color: #999;
      font-size: 18px;
      margin-top: 20px;
    }

    .icon {
      font-size: 18px;
      cursor: pointer;
      margin: 0 5px;
    }

    .icon-download {
      color: #3b47f1;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }

    .icon-download:hover {
      color: #2a2ad8;
    }

    footer {
      text-align: center;
      margin-top: auto;
      padding: 20px;
      background-color: #3b47f1;
      color: white;
      font-size: 14px;
    }

    footer a {
      color: #ffdd57;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }

    footer a:hover {
      color: #ffd700;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      width: 50%;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal-content h2 {
      margin-top: 0;
      color: #3b47f1;
      text-align: center;
    }

    .modal-content p {
      font-size: 16px;
      color: #333;
      line-height: 1.6;
      text-align: justify;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: #000;
    }

    @media (max-width: 768px) {
      table th, table td {
        font-size: 14px;
        padding: 10px;
      }

      .modal-content {
        width: 90%;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Details for Place: <?php echo htmlspecialchars($place); ?></h1>
    <a href="dash.php" class="btn">← Back to Dashboard</a>

    <?php if (!empty($data)): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Identifier</th>
              <th>Agency</th>
              <th>Plate</th>
              <th>Place</th>
              <th>Date & Time</th>
              <th>Latitude</th>
              <th>Longitude</th>
              <th>Overloading</th>
              <th>Accident</th>
              <th>Unauthorized</th>
              <th>Description</th>
              <th>Permit</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['report_type']); ?></td>
                <td><?php echo htmlspecialchars($row['identifier']); ?></td>
                <td><?php echo htmlspecialchars($row['agency']); ?></td>
                <td><?php echo htmlspecialchars($row['plate']); ?></td>
                <td><?php echo htmlspecialchars($row['place']); ?></td>
                <td><?php echo htmlspecialchars($row['datetime']); ?></td>
                <td><?php echo htmlspecialchars($row['latitude']); ?></td>
                <td><?php echo htmlspecialchars($row['longitude']); ?></td>
                <td><?php echo isset($row['overloading']) ? ($row['overloading'] ? 'Yes' : 'No') : 'N/A'; ?></td>
                <td><?php echo $row['accident'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $row['unauthorized'] ? 'Yes' : 'No'; ?></td>
                <td>
                  <button class="btn" onclick="showModal(`<?php echo addslashes(htmlspecialchars($row['description'])); ?>`)">View</button>
                </td>
                <td>
                  <?php if (!empty($row['permit'])): ?>
                    <a href="../uploads/<?php echo htmlspecialchars($row['permit']); ?>" target="_blank" class="icon icon-download">⬇</a>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="no-data">No reports found for this place.</p>
    <?php endif; ?>
  </div>

  <!-- Modal -->
  <div id="descriptionModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2>Description</h2>
      <p id="modalDescription"></p>
    </div>
  </div>

  <footer>
    © 2025 Report Management System. Made with ❤️ by <a href="https://example.com" target="_blank">Your Company</a>.
  </footer>

  <script>
    // Show the modal with the description
    function showModal(description) {
      document.getElementById('modalDescription').textContent = description;
      document.getElementById('descriptionModal').style.display = 'block';
    }

    // Close the modal
    function closeModal() {
      document.getElementById('descriptionModal').style.display = 'none';
    }

    // Close the modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('descriptionModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>