<?php
session_start();
include '../conn.php';

// Check if the user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle adding a new agency
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add' && $isAdmin) {
    $agency_name = mysqli_real_escape_string($conn, $_POST['agency_name']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $creator = $_SESSION['username'];

    if (empty($agency_name) || empty($status)) {
        $error = "Agency name and status are required.";
    } else {
        $sql = "INSERT INTO agencies (agency_name, status, creator) VALUES ('$agency_name', '$status', '$creator')";
        if (mysqli_query($conn, $sql)) {
            $success = "Agency added successfully!";
        } else {
            $error = "Error adding agency: " . mysqli_error($conn);
        }
    }
}

// Handle editing an agency
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'edit' && $isAdmin) {
    $agency_id = intval($_POST['agency_id']);
    $agency_name = mysqli_real_escape_string($conn, $_POST['agency_name']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($agency_name) || empty($status)) {
        $error = "Agency name and status are required.";
    } else {
        $sql = "UPDATE agencies SET agency_name = '$agency_name', status = '$status' WHERE id = $agency_id";
        if (mysqli_query($conn, $sql)) {
            $success = "Agency updated successfully!";
        } else {
            $error = "Error updating agency: " . mysqli_error($conn);
        }
    }
}

// Handle CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="agencies.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Agency Number', 'Agency Name', 'Status', 'Date Created', 'Creator']);

    $sql = "SELECT id, agency_name, status, created_at, creator FROM agencies";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Fetch agencies with optional search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT * FROM agencies WHERE agency_name LIKE '%$search%' ORDER BY id ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
        }
        .back-button {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #2a2ad8;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #1f1fbf;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .search-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 10px;
            font-size: 16px;
            width: 70%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .search-bar button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #3b47f1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-bar button:hover {
            background-color: #2a2ad8;
        }
        .download-button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .download-button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #3b47f1;
            color: white;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .add-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .add-button:hover {
            background-color: #218838;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
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
            text-align: center;
            color: #3b47f1;
        }
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-content form input, .modal-content form select, .modal-content form button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .modal-content form button {
            background-color: #3b47f1;
            color: white;
            cursor: pointer;
        }
        .modal-content form button:hover {
            background-color: #2a2ad8;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        Agency Management
    </div>
    <a href="dash.php" class="back-button">← Back to Dashboard</a>
    <div class="container">
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search agencies..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
            <a href="?download=csv" class="download-button">Download CSV</a>
        </div>
        <?php if ($isAdmin): ?>
            <button class="add-button" onclick="openAddModal()">+ Add New Agency</button>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Agency Number</th>
                    <th>Agency Name</th>
                    <th>Status</th>
                    <th>Date Created</th>
                    <th>Creator</th>
                    <?php if ($isAdmin): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['agency_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['creator']); ?></td>
                        <?php if ($isAdmin): ?>
                            <td>
                                <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['agency_name']); ?>', '<?php echo htmlspecialchars($row['status']); ?>')">Edit</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Agency Modal -->
    <div id="addAgencyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New Agency</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <label for="add_agency_name">Agency Name</label>
                <input type="text" id="add_agency_name" name="agency_name" required>
                <label for="add_status">Status</label>
                <select id="add_status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit">Add Agency</button>
            </form>
        </div>
    </div>

    <!-- Edit Agency Modal -->
    <div id="editAgencyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Agency</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_agency_id" name="agency_id">
                <input type="hidden" name="action" value="edit">
                <label for="edit_agency_name">Agency Name</label>
                <input type="text" id="edit_agency_name" name="agency_name" required>
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
            document.getElementById('addAgencyModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addAgencyModal').style.display = 'none';
        }

        function openEditModal(id, name, status) {
            document.getElementById('edit_agency_id').value = id;
            document.getElementById('edit_agency_name').value = name;
            document.getElementById('edit_status').value = status;
            document.getElementById('editAgencyModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editAgencyModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const addModal = document.getElementById('addAgencyModal');
            const editModal = document.getElementById('editAgencyModal');
            if (event.target === addModal) {
                closeAddModal();
            } else if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>