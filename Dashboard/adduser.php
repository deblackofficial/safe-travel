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
  <title>Add New User | Transport System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a0ca3;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --success: #4cc9f0;
      --warning: #f72585;
      --danger: #f72585;
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    body { 
      font-family: 'Poppins', sans-serif; 
      background-color: #f5f7ff; 
      color: var(--dark);
    }

    .dashboard-layout {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 280px;
      background: linear-gradient(180deg, var(--primary), var(--primary-dark));
      color: white;
      padding: 25px 0;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 10;
    }
    
    .brand {
      padding: 0 25px 25px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .brand h2 { 
      font-size: 24px; 
      font-weight: 600; 
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .brand-icon {
      color: var(--success);
    }
    
    .sidebar nav {
      flex: 1;
      padding: 25px 0;
      overflow-y: auto;
    }
    
    .sidebar nav a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 15px;
      padding: 12px 25px;
      margin: 5px 0;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 3px solid transparent;
      transition: var(--transition);
    }
    
    .sidebar nav a:hover, 
    .sidebar nav a.active {
      color: white;
      background: rgba(255, 255, 255, 0.1);
      border-left: 3px solid var(--accent);
    }
    
    .sidebar nav a i {
      width: 20px;
      text-align: center;
    }
    
    .sidebar-footer {
      padding: 20px 25px 0;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .user-info small {
      display: block;
      color: rgba(255, 255, 255, 0.6);
      font-size: 12px;
    }

    .main-content {
      flex: 1;
      padding: 25px;
      overflow-y: auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    
    .page-title h1 {
      font-size: 28px;
      font-weight: 600;
      color: var(--dark);
    }
    
    .page-title p {
      color: var(--gray);
      font-size: 14px;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .btn {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
    }
    
    .btn:hover { 
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }
    
    .btn i {
      font-size: 12px;
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--primary);
      color: var(--primary);
    }

    .btn-outline:hover {
      background: var(--primary);
      color: white;
    }

    .card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
      transition: var(--transition);
      margin-bottom: 25px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .card-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--primary-dark);
    }

    .form-container {
      max-width: 600px;
      margin: 0 auto;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .form-select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      background-color: white;
      cursor: pointer;
      transition: border-color 0.3s;
    }

    .form-select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
      border-left: 4px solid #dc3545;
    }

    .alert-success {
      background-color: rgba(40, 167, 69, 0.1);
      color: #28a745;
      border-left: 4px solid #28a745;
    }

    .alert i {
      font-size: 18px;
    }

    .password-toggle {
      position: relative;
    }

    .password-toggle-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--gray);
    }

    @media (max-width: 768px) {
      .dashboard-layout {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <div class="sidebar">
      <div class="brand">
        <h2><i class="fas fa-bus-alt brand-icon"></i> Transport System</h2>
      </div>
      
      <nav>
        <a href="dash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="userdetails.php"><i class="fas fa-users"></i> User Management</a>
        <a href="routes.php"><i class="fas fa-route"></i> Route Management</a>
        <a href="driverdetails.php"><i class="fas fa-id-card"></i> Driver Reports</a>
        <a href="passengerdetails.php"><i class="fas fa-user-tag"></i> Passenger Reports</a>
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
          </div>
          <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <small>Admin</small>
          </div>
        </div>
        <a href="logout.php" class="btn" style="display: block; text-align: center; margin-top: 15px;">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>
    
    <div class="main-content">
      <div class="page-header">
        <div class="page-title">
          <h1>Add New User</h1>
          <p>Create a new user account in the system</p>
        </div>
        
        <div class="header-actions">
          <a href="userdetails.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Users
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">User Information</h2>
        </div>
        
        <?php if (!empty($error)): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
          </div>
        <?php endif; ?>

        <div class="form-container">
          <form method="POST" action="">
            <div class="form-group">
              <label for="username" class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-control" 
                     value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="row" style="display: flex; gap: 15px; margin-bottom: 20px;">
              <div class="form-group" style="flex: 1;">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" 
                       value="<?php echo htmlspecialchars($first_name); ?>" required>
              </div>
              
              <div class="form-group" style="flex: 1;">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" 
                       value="<?php echo htmlspecialchars($last_name); ?>" required>
              </div>
            </div>
            
            <div class="form-group">
              <label for="phone_number" class="form-label">Phone Number</label>
              <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                     value="<?php echo htmlspecialchars($phone_number); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" 
                     value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="row" style="display: flex; gap: 15px; margin-bottom: 20px;">
              <div class="form-group" style="flex: 1;">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select" required>
                  <option value="">-- Select Role --</option>
                  <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                  <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>Manager</option>
                  <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                </select>
              </div>
              
              <div class="form-group" style="flex: 1;">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                  <option value="">-- Select Status --</option>
                  <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                  <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
              </div>
            </div>
            
            <div class="form-group password-toggle">
              <label for="password" class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-control" required>
              <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
            </div>
            
            <div class="form-actions">
              <button type="reset" class="btn btn-outline">
                <i class="fas fa-redo"></i> Reset
              </button>
              <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Add User
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.classList.toggle('fa-eye');
      this.classList.toggle('fa-eye-slash');
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      
      if (password.length < 8) {
        alert('Password must be at least 8 characters long');
        e.preventDefault();
      }
    });
  </script>
</body>
</html>