<?php
session_start();
include '../conn.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $sql = "UPDATE users 
            SET username = '$username', 
                first_name = '$first_name', 
                last_name = '$last_name', 
                phone_number = '$phone_number', 
                email = '$email', 
                role = '$role' 
            WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        header('Location: userdetails.php');
        exit();
    } else {
        $error = "Error updating user: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User</title>
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-color: #f9f9f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .form-container {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
    }

    .form-container h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #3b47f1;
    }

    .form-container input, .form-container select {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .form-container button {
      width: 100%;
      padding: 10px;
      background-color: #3b47f1;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }

    .form-container button:hover {
      background-color: #1a2bcf;
    }

    .error {
      color: red;
      text-align: center;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Edit User</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
      <input type="text" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
      <input type="text" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
      <input type="text" name="phone_number" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
      <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
      <select name="role" required>
        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
      </select>
      <button type="submit">Update</button>
      <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>