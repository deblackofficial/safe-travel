<?php
session_start();
include 'conn.php';

$success = '';
$error = '';

// Generate 4-digit ID
function generateRandomID($conn) {
    do {
        $id = rand(1000, 9999);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE id = $id");
    } while (mysqli_num_rows($check) > 0);
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = generateRandomID($conn);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (id, username, first_name, last_name, phone_number, email, password)
                    VALUES ('$id', '$username', '$first_name', '$last_name', '$phone_number', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                require 'send_email.php';
                if (sendRegistrationEmail($email, "$first_name $last_name", $username, $id, $phone_number)) {
                    $success = "Registration successful. Confirmation email sent.";
                } else {
                    $success = "Registration successful, but email failed to send.";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f1f1f1;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .register-container {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 450px;
    }

    .register-container h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #3b47f1;
    }

    .register-container input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .register-container button {
      width: 100%;
      padding: 10px;
      background-color: #3b47f1;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }

    .register-container button:hover {
      background-color: #1a2bcf;
    }

    .message {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .error { color: red; }
    .success { color: green; }

    a {
      display: block;
      text-align: center;
      margin-top: 10px;
      color: #3b47f1;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="register-container">
    <h2>Register new passenger</h2>
    <form method="POST" action="">
      <input type="text" name="username" placeholder="Username" required />
      <input type="text" name="first_name" placeholder="First Name" required />
      <input type="text" name="last_name" placeholder="Last Name" required />
      <input type="text" name="phone_number" placeholder="Phone Number" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <input type="password" name="confirm_password" placeholder="Confirm Password" required />
      <button type="submit">Register</button>
      <a href="login.php">Back to Login</a>
    </form>

    <?php if (!empty($error)): ?>
      <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
