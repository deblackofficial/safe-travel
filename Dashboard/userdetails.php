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
  <title>User Details</title>
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
    }

    .back-button {
      display: inline-block;
      margin: 20px;
      padding: 10px 20px;
      font-size: 16px;
      font-weight: bold;
      color: white;
      background-color: #3b47f1;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .back-button:hover {
      background-color: #2a2ad8;
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

    h1 {
      text-align: center;
      color: #3b47f1;
      margin-bottom: 20px;
    }

    .actions {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .actions form {
      display: flex;
      gap: 10px;
    }

    .actions select, .actions input, .actions button, .actions a {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      text-decoration: none;
      color: white;
      background-color: #3b47f1;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .actions input {
      background-color: transparent;
      color: #333;
    }

    .actions input:focus {
      border-color: #3b47f1;
    }

    .actions a:hover, .actions button:hover {
      background-color: #2a2ad8;
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

    .btn {
      padding: 8px 12px;
      background-color: #3b47f1;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #2a2ad8;
    }

    .btn-deactivate {
      background-color: #f44336;
    }

    .btn-deactivate:hover {
      background-color: #d32f2f;
    }

    .no-data {
      text-align: center;
      color: #999;
      font-size: 18px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="header">
    User Management Dashboard
  </div>

  <a href="dash.php" class="back-button">← Back to Dashboard</a>

  <div class="container">
    <h1>User Details</h1>

    <div class="actions">
      <form method="GET">
        <input type="text" class="search" name="search" placeholder="Search by username, name, or email" value="<?php echo htmlspecialchars($search); ?>">
        <select name="filter">
          <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
          <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>No Active</option>
        </select>
        <button type="submit">Filter</button>
      </form>
      <a href="?download_csv=1">Download CSV</a>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>No</th>
              <th>Username</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Phone Number</th>
              <th>Email</th>
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
                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <?php if ($isAdmin): ?>
                  <td>
                    <a href="edituser.php?id=<?php echo $row['id']; ?>" class="btn">Edit</a>
                    <?php if ($row['status'] === 'active'): ?>
                      <a href="toggleuser.php?id=<?php echo $row['id']; ?>&action=deactivate" class="btn btn-deactivate" onclick="return confirm('Are you sure you want to deactivate this user?');">Deactivate</a>
                    <?php else: ?>
                      <a href="toggleuser.php?id=<?php echo $row['id']; ?>&action=activate" class="btn" onclick="return confirm('Are you sure you want to activate this user?');">Activate</a>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="no-data">No users found.</p>
    <?php endif; ?>
  </div>
</body>
</html>