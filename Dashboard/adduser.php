<?php
session_start(); // Start the session
include '../conn.php'; // Include the database connection file

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dash.php"); // Redirect non-admin users to the dashboard
    exit();
}

// Initialize variables for form data and error messages
$username = $first_name = $last_name = $phone_number = $email = $role = $status = "";
$error = $success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validate required fields
    if (empty($username) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($email) || empty($role) || empty($status) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $sql = "INSERT INTO users (username, first_name, last_name, phone_number, email, role, status, password) 
                VALUES ('$username', '$first_name', '$last_name', '$phone_number', '$email', '$role', '$status', '$hashed_password')";

        if (mysqli_query($conn, $sql)) {
            $success = "User added successfully!";
            // Clear the form fields
            $username = $first_name = $last_name = $phone_number = $email = $role = $status = "";
        } else {
            $error = "Error adding user: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New User</title>
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
      max-width: 600px;
      margin: 50px auto;
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

    .message {
      text-align: center;
      font-size: 16px;
      margin-top: 10px;
    }

    .message.error {
      color: #f44336;
    }

    .message.success {
      color: #4caf50;
    }
  </style>
</head>
<body>
  <div class="header">
    Add New User
  </div>

  <a href="userdetails.php" class="back-button">← Back to User Details</a>

  <div class="container">
    <?php if (!empty($error)): ?>
      <p class="message error"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <p class="message success"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

      <label for="first_name">First Name</label>
      <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>

      <label for="last_name">Last Name</label>
      <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>

      <label for="phone_number">Phone Number</label>
      <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

      <label for="role">Role</label>
      <select id="role" name="role" required>
        <option value="">-- Select Role --</option>
        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>Manager</option>
        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
      </select>

      <label for="status">Status</label>
      <select id="status" name="status" required>
        <option value="">-- Select Status --</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Add User</button>
    </form>
  </div>
</body>
</html>