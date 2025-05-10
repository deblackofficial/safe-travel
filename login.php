<?php
session_start();
include 'conn.php';

$inactiveMessage = ''; // Variable to store the inactive user message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Fetch the user from the database
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                // User is inactive
                $inactiveMessage = "Your account has been deactivated. Please contact an admin for assistance.";
            } else {
                // User is active, proceed with login
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Set the role in the session
                header('Location: Dashboard/dash.php');
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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

    .login-container {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #3b47f1;
    }

    .login-container input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .login-container button {
      width: 100%;
      padding: 10px;
      background-color: #3b47f1;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }

    .login-container button:hover {
      background-color: #1a2bcf;
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
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal-content h3 {
      color: #f44336;
      margin-bottom: 10px;
    }

    .modal-content p {
      font-size: 16px;
      color: #333;
      margin-bottom: 20px;
    }

    .modal-content button {
      padding: 10px 20px;
      background-color: #3b47f1;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }

    .modal-content button:hover {
      background-color: #1a2bcf;
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
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Login</h2>
    <form method="POST" action="login.php" onsubmit="return validateForm()">
      <input type="text" id="username" name="username" placeholder="Username" required />
      <input type="password" id="password" name="password" placeholder="Password" required />
      <button type="submit">Login</button>
      <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Modal -->
  <?php if (!empty($inactiveMessage)): ?>
    <div id="inactiveModal" class="modal" style="display: flex;">
      <div class="modal-content">
        <h3>Account Deactivated</h3>
        <p><?php echo $inactiveMessage; ?></p>
        <button onclick="closeModal()">Close</button>
      </div>
    </div>
  <?php endif; ?>

  <script>
    function validateForm() {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();

      if (username === '' || password === '') {
        alert('Please fill in all fields.');
        return false;
      }

      return true;
    }

    function closeModal() {
      document.getElementById('inactiveModal').style.display = 'none';
    }
  </script>
</body>
</html>