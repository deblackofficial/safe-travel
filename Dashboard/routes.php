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
  <title>Routes Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
    }

    .header {
      background-color: #3b47f1;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .back-button {
      position: absolute;
      margin-top: 60px;
      left: 10px;
      padding: 10px 20px;
      font-size: 16px;
      font-weight: bold;
      color: white;
      background-color: #2a2ad8;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .back-button:hover {
      background-color: #1f1fbf;
      transform: translateY(-2px);
    }

    .add-button {
      position: absolute;
      top: 20px;
      right: 20px;
      padding: 10px 20px;
      font-size: 16px;
      font-weight: bold;
      color: white;
      background-color: #28a745;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .add-button:hover {
      background-color: #218838;
      transform: translateY(-2px);
    }

    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table-container {
      max-height: 400px;
      overflow-y: auto;
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
      top: 0;
      z-index: 1;
    }

    table tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    table tr:hover {
      background-color: #f1f1f1;
    }

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
    }

    .modal-content h2 {
      margin-top: 0;
      color: #3b47f1;
      text-align: center;
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

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      font-weight: bold;
      color: #333;
    }

    input, select, button {
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    input:focus, select:focus {
      border-color: #3b47f1;
      outline: none;
    }

    button {
      background-color: #3b47f1;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #2a2ad8;
    }
  </style>
</head>
<body>
  <div class="header">
    <a href="dash.php" class="back-button">← Back to Dashboard</a>
    Routes Management
    <?php if ($isAdmin): ?>
      <button class="add-button" onclick="openAddModal()">+ Add New Route</button>
    <?php endif; ?>
  </div>

  <div class="container">
    <h1>All Routes</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Start Point</th>
            <th>Middle Point</th>
            <th>End Point</th>
            <th>Status</th>
            <th>Date Created</th>
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
              <td><?php echo htmlspecialchars($row['status']); ?></td>
              <td><?php echo htmlspecialchars($row['created_at']); ?></td>
              <?php if ($isAdmin): ?>
                <td>
                  <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['start_point']); ?>', '<?php echo htmlspecialchars($row['middle_point']); ?>', '<?php echo htmlspecialchars($row['end_point']); ?>', '<?php echo htmlspecialchars($row['status']); ?>')">Edit</button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add New Route Modal -->
  <div id="addRouteModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAddModal()">&times;</span>
      <h2>Add New Route</h2>
      <form method="POST" action="">
        <input type="hidden" name="action" value="add">

        <label for="add_start_point">Start Point</label>
        <input type="text" id="add_start_point" name="start_point" placeholder="Enter start point" required>

        <label for="add_middle_point">Middle Point</label>
        <input type="text" id="add_middle_point" name="middle_point" placeholder="Enter middle point (optional)">

        <label for="add_end_point">End Point</label>
        <input type="text" id="add_end_point" name="end_point" placeholder="Enter end point" required>

        <label for="add_status">Status</label>
        <select id="add_status" name="status" required>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>

        <button type="submit">Add Route</button>
      </form>
    </div>
  </div>

  <!-- Edit Route Modal -->
  <div id="editRouteModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2>Edit Route</h2>
      <form method="POST" action="">
        <input type="hidden" id="edit_route_id" name="route_id">
        <input type="hidden" name="action" value="edit">

        <label for="edit_start_point">Start Point</label>
        <input type="text" id="edit_start_point" name="start_point" required>

        <label for="edit_middle_point">Middle Point</label>
        <input type="text" id="edit_middle_point" name="middle_point">

        <label for="edit_end_point">End Point</label>
        <input type="text" id="edit_end_point" name="end_point" required>

        <label for="edit_status">Status</label>
        <select id="edit_status" name="status" required>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>

        <button type="submit">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() {
      document.getElementById('addRouteModal').style.display = 'block';
    }

    function closeAddModal() {
      document.getElementById('addRouteModal').style.display = 'none';
    }

    function openEditModal(id, startPoint, middlePoint, endPoint, status) {
      document.getElementById('edit_route_id').value = id;
      document.getElementById('edit_start_point').value = startPoint;
      document.getElementById('edit_middle_point').value = middlePoint;
      document.getElementById('edit_end_point').value = endPoint;
      document.getElementById('edit_status').value = status;
      document.getElementById('editRouteModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editRouteModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const addModal = document.getElementById('addRouteModal');
      const editModal = document.getElementById('editRouteModal');
      if (event.target === addModal) {
        closeAddModal();
      } else if (event.target === editModal) {
        closeEditModal();
      }
    }
  </script>
</body>
</html>