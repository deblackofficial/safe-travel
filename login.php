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
  <title>Login | RFID System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --danger: #f72585;
      --success: #4cc9f0;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --white: #ffffff;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    
    .login-container {
      display: flex;
      width: 900px;
      max-width: 100%;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
      background: var(--white);
      animation: fadeIn 0.6s ease-out;
    }
    
    .login-left {
      flex: 1;
      padding: 60px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: var(--white);
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    
    .login-left::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }
    
    .login-left::after {
      content: '';
      position: absolute;
      bottom: -80px;
      left: -80px;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }
    
    .login-left h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      position: relative;
      z-index: 1;
    }
    
    .login-left p {
      font-size: 1rem;
      opacity: 0.9;
      margin-bottom: 30px;
      position: relative;
      z-index: 1;
    }
    
    .login-left .features {
      margin-top: 40px;
      position: relative;
      z-index: 1;
    }
    
    .login-left .feature-item {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .login-left .feature-icon {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 1rem;
    }
    
    .login-right {
      flex: 1;
      padding: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .login-right h2 {
      font-size: 2rem;
      color: var(--dark);
      margin-bottom: 10px;
      text-align: center;
    }
    
    .login-right .welcome-text {
      color: var(--gray);
      text-align: center;
      margin-bottom: 40px;
      font-size: 0.9rem;
    }
    
    .form-group {
      margin-bottom: 25px;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.9rem;
      color: var(--dark);
      font-weight: 500;
    }
    
    .form-control {
      width: 100%;
      padding: 15px 20px;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      background-color: var(--white);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .input-icon {
      position: absolute;
      right: 20px;
      top: 42px;
      color: var(--gray);
    }
    
    .btn {
      display: inline-block;
      padding: 15px 30px;
      background: var(--primary);
      color: var(--white);
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      text-align: center;
    }
    
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn:active {
      transform: translateY(0);
    }
    
    .forgot-password {
      text-align: right;
      margin-top: -15px;
      margin-bottom: 25px;
    }
    
    .forgot-password a {
      color: var(--gray);
      font-size: 0.8rem;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    
    .forgot-password a:hover {
      color: var(--primary);
    }
    
    .register-link {
      text-align: center;
      margin-top: 30px;
      font-size: 0.9rem;
      color: var(--gray);
    }
    
    .register-link a {
      color: var(--primary);
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .register-link a:hover {
      text-decoration: underline;
    }
    
    .error-message {
      color: var(--danger);
      font-size: 0.9rem;
      margin-top: 5px;
      display: block;
      text-align: center;
      animation: shake 0.5s ease;
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
      background-color: var(--white);
      padding: 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      animation: fadeInUp 0.4s ease-out;
      position: relative;
    }
    
    .modal-icon {
      font-size: 3rem;
      color: var(--danger);
      margin-bottom: 20px;
    }
    
    .modal-content h3 {
      color: var(--danger);
      margin-bottom: 15px;
      font-size: 1.5rem;
    }
    
    .modal-content p {
      font-size: 1rem;
      color: var(--gray);
      margin-bottom: 25px;
      line-height: 1.5;
    }
    
    .modal-content .btn {
      margin-top: 10px;
    }
    
    .close-modal {
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 1.5rem;
      color: var(--gray);
      cursor: pointer;
      transition: color 0.3s ease;
    }
    
    .close-modal:hover {
      color: var(--dark);
    }
    
    /* Animations */
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
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
      }
      
      .login-left, .login-right {
        padding: 40px 30px;
      }
      
      .login-left {
        text-align: center;
      }
      
      .login-left .feature-item {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-left">
      <h1>RFID Access System</h1>
      <p>Secure and efficient user management with RFID technology</p>
      
      <div class="features">
        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <span>Secure Authentication</span>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-id-card"></i>
          </div>
          <span>RFID Card Integration</span>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <span>Real-time Monitoring</span>
        </div>
      </div>
    </div>
    
    <div class="login-right">
      <h2>Welcome Back</h2>
      <p class="welcome-text">Please enter your credentials to access the system</p>
      
      <form method="POST" action="login.php" onsubmit="return validateForm()">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required />
          <i class="fas fa-user input-icon"></i>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required />
          <i class="fas fa-lock input-icon"></i>
        </div>
        
        <div class="forgot-password">
          <!-- <a href="#">Forgot password?</a> -->
        </div>
        
        <button type="submit" class="btn">Login</button>
        
        <?php if (isset($error)): ?>
          <span class="error-message"><?php echo $error; ?></span>
        <?php endif; ?>
      </form>
      
      <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <?php if (!empty($inactiveMessage)): ?>
    <div id="inactiveModal" class="modal" style="display: flex;">
      <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-icon">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Account Deactivated</h3>
        <p><?php echo $inactiveMessage; ?></p>
        <button class="btn" onclick="closeModal()">Close</button>
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
    
    // Add focus effects
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentNode.querySelector('label').style.color = 'var(--primary)';
      });
      
      input.addEventListener('blur', function() {
        this.parentNode.querySelector('label').style.color = 'var(--dark)';
      });
    });
  </script>
</body>
</html>