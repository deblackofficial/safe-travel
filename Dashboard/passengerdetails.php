<?php
session_start(); // Start the session
include '../conn.php'; // Include the database connection file

// Determine if viewing archived reports
$isArchived = isset($_GET['archived']) && $_GET['archived'] == 1;

// Fetch data from the database
$sql = "SELECT * FROM passenger_report WHERE archived = " . ($isArchived ? 1 : 0) . " ORDER BY datetime DESC";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM passenger_report WHERE archived = " . ($isArchived ? 1 : 0) . " 
            AND (ticket LIKE '%$search%' OR agency LIKE '%$search%' OR plate LIKE '%$search%') 
            ORDER BY datetime DESC";
}
$result = mysqli_query($conn, $sql);

// Handle CSV download
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="passenger_details.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Ticket', 'Agency', 'Plate', 'Place', 'Date & Time', 'Latitude', 'Longitude', 'Overloading', 'Accident', 'Unauthorized', 'Description', 'Permit']);

    $counter = 1;
    $csvResult = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($csvResult)) {
        fputcsv($output, [
            $counter++,
            $row['ticket'],
            $row['agency'],
            $row['plate'],
            $row['place'],
            $row['datetime'],
            $row['latitude'],
            $row['longitude'],
            $row['overloading'] ? 'Yes' : 'No',
            $row['accident'] ? 'Yes' : 'No',
            $row['unauthorized'] ? 'Yes' : 'No',
            $row['description'],
            $row['upload']
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
  <title>Passenger Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      color: #3b47f1;
      margin-bottom: 20px;
    }

    .actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .actions a, .actions button {
      padding: 10px 15px;
      background-color: #3b47f1;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      border: none;
    }

    .actions a:hover, .actions button:hover {
      background-color: #2a2ad8;
    }

    .actions input[type="text"] {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      width: 300px;
    }

    .table-container {
      max-height: 400px; /* Set the height of the scrollable area */
      overflow-y: auto; /* Enable vertical scrolling */
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th, table td {
      padding: 12px 15px;
      text-align: left;
      border: 1px solid #ddd;
    }

    table th {
      background-color: #3b47f1;
      color: white;
      font-weight: bold;
      position: sticky;
      top: 0; /* Keep table headers sticky */
      z-index: 1;
    }

    table tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    table tr:hover {
      background-color: #f1f1f1;
    }

    .icon {
      font-size: 18px;
      cursor: pointer;
      margin: 0 5px;
    }

    .icon-download {
      color: #28a745;
    }

    .icon-download:hover {
      color: #218838;
    }

    .icon-archive {
      color: #f44336;
    }

    .icon-archive:hover {
      color: #d32f2f;
    }

    .no-data {
      text-align: center;
      color: #999;
      font-size: 18px;
      margin-top: 20px;
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

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
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
    <h1>Passenger Reports Details</h1>

    <div class="actions">
      <form method="GET" style="display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Search by Ticket, Agency, or Plate" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
      </form>
      <a href="?archived=<?php echo $isArchived ? 0 : 1; ?>">
        <?php echo $isArchived ? 'View Active Reports' : 'View Archived Reports'; ?>
      </a>
      <a href="?download_csv=1">Download CSV</a>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Ticket</th>
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
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $counter = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?php echo $counter++; ?></td>
                <td><?php echo htmlspecialchars($row['ticket']); ?></td>
                <td><?php echo htmlspecialchars($row['agency']); ?></td>
                <td><?php echo htmlspecialchars($row['plate']); ?></td>
                <td><?php echo htmlspecialchars($row['place']); ?></td>
                <td><?php echo htmlspecialchars($row['datetime']); ?></td>
                <td><?php echo htmlspecialchars($row['latitude']); ?></td>
                <td><?php echo htmlspecialchars($row['longitude']); ?></td>
                <td><?php echo $row['overloading'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $row['accident'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $row['unauthorized'] ? 'Yes' : 'No'; ?></td>
                <td>
                  <button class="btn" onclick="showModal(`<?php echo addslashes(htmlspecialchars($row['description'])); ?>`)">View</button>
                </td>
                <td>
                  <?php if (!empty($row['upload'])): ?>
                    <a href="../uploads/<?php echo htmlspecialchars($row['upload']); ?>" target="_blank" class="icon icon-download">⬇</a>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$isArchived): ?>
                    <a href="archivepassenger.php?id=<?php echo $row['id']; ?>" class="icon icon-archive" onclick="return confirm('Are you sure you want to archive this record?');">🗑</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="no-data">No passenger reports found.</p>
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