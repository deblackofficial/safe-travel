<?php
session_start();
include 'conn.php';

// Retrieve session data
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

$routes = [];
$sql = "SELECT CONCAT(start_point, ' - ', middle_point, ' - ', end_point) AS route FROM routes WHERE status = 'active'";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $routes[] = $row['route'];
    }
} else {
    die("Error fetching routes: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket = mysqli_real_escape_string($conn, $_POST['ticket']);
    $agency = mysqli_real_escape_string($conn, $_POST['agency']);
    $plate = mysqli_real_escape_string($conn, $_POST['plate']);
    $place = mysqli_real_escape_string($conn, $_POST['place']);
    $datetime = mysqli_real_escape_string($conn, $_POST['datetime']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);

    $_SESSION['form_data'] = [
        'ticket' => $ticket,
        'agency' => $agency,
        'plate' => $plate,
        'place' => $place,
        'datetime' => $datetime,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ];

    $permitFileName = $_FILES['upload']['name'];
    $permitTempName = $_FILES['upload']['tmp_name'];
    $permitFolder = "uploads/" . basename($permitFileName);

    if (move_uploaded_file($permitTempName, $permitFolder)) {
        $_SESSION['form_data']['upload'] = $permitFileName;
        header("Location: passengerfp.php");
        exit();
    } else {
        echo "<script>alert('Failed to upload permit file.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Passenger Registration | Transport System</title>
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
      background-color: #f5f7fa;
      color: var(--dark);
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      width: 100%;
      max-width: 800px;
    }

    .card {
      background: white;
      border-radius: 16px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .card-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 20px;
      text-align: center;
    }

    .card-header h1 {
      font-size: 24px;
      font-weight: 600;
    }

    .card-body {
      padding: 30px;
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
      font-size: 15px;
      transition: var(--transition);
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
      font-size: 15px;
      background-color: white;
      cursor: pointer;
      transition: var(--transition);
    }

    .form-select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .btn {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
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

    .btn-group {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 30px;
    }

    .btn-group .btn {
      flex: 1;
      min-width: 150px;
    }

    .file-upload {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }

    .file-upload-btn {
      background: var(--light);
      border: 1px dashed var(--gray);
      border-radius: 8px;
      padding: 30px;
      text-align: center;
      width: 100%;
      cursor: pointer;
      transition: var(--transition);
    }

    .file-upload-btn:hover {
      border-color: var(--primary);
      background: rgba(67, 97, 238, 0.05);
    }

    .file-upload-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .location-status {
      font-size: 14px;
      color: var(--gray);
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .location-status.active {
      color: var(--success);
    }

    .location-status.error {
      color: var(--warning);
    }

    @media (max-width: 768px) {
      .card-body {
        padding: 20px;
      }
      
      .btn-group {
        flex-direction: column;
      }
      
      .btn-group .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-header">
        <h1><i class="fas fa-user-plus"></i> Passenger Registration</h1>
      </div>
      
      <div class="card-body">
        <form id="passengerForm" method="POST" enctype="multipart/form-data" action="passenger.php">
          <div class="form-group">
            <label for="ticket" class="form-label">Ticket ID</label>
            <input type="tel" id="ticket" name="ticket" class="form-control" 
                   placeholder="Enter your ticket number (e.g., 1234 567 890)" 
                   value="<?php echo isset($formData['ticket']) ? $formData['ticket'] : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="agency" class="form-label">Agency Name</label>
            <input type="text" id="agency" name="agency" class="form-control" 
                   placeholder="Enter agency name (e.g., Ritco, Volcano)" 
                   value="<?php echo isset($formData['agency']) ? $formData['agency'] : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="plate" class="form-label">Plate Number</label>
            <input type="text" id="plate" name="plate" class="form-control" 
                   placeholder="Enter plate number (e.g., RAF 123 X)" 
                   value="<?php echo isset($formData['plate']) ? $formData['plate'] : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="place" class="form-label">Route</label>
            <select id="place" name="place" class="form-select" required>
              <option value="">Select a route</option>
              <?php foreach ($routes as $route): ?>
                <option value="<?php echo htmlspecialchars($route); ?>" <?php echo (isset($formData['place']) && $formData['place'] === $route) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($route); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="datetime" class="form-label">Date & Time</label>
            <input type="datetime-local" id="datetime" name="datetime" class="form-control" 
                   value="<?php echo isset($formData['datetime']) ? $formData['datetime'] : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Upload Your Ticket</label>
            <div class="file-upload">
              <div class="file-upload-btn">
                <i class="fas fa-cloud-upload-alt" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p>Click to upload ticket (Image or PDF)</p>
                <input type="file" id="upload" name="upload" class="file-upload-input" accept="image/*,.pdf" required>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Location</label>
            <button type="button" id="getLocationBtn" class="btn">
              <i class="fas fa-map-marker-alt"></i> Capture Current Location
            </button>
            <div id="locationStatus" class="location-status">
              <i class="fas fa-info-circle"></i> Location not captured yet
            </div>
            <input type="text" id="latitude" name="latitude" class="form-control" 
                   placeholder="Latitude" 
                   value="<?php echo isset($formData['latitude']) ? $formData['latitude'] : ''; ?>" readonly required>
            <input type="text" id="longitude" name="longitude" class="form-control" 
                   placeholder="Longitude" 
                   value="<?php echo isset($formData['longitude']) ? $formData['longitude'] : ''; ?>" readonly required>
          </div>
          
          <div class="btn-group">
            <button type="submit" class="btn">
              <i class="fas fa-paper-plane"></i> Submit Registration
            </button>
            <a href="choose.php" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Capture GPS location
    document.getElementById("getLocationBtn").addEventListener("click", function() {
      const statusElement = document.getElementById("locationStatus");
      
      statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
      statusElement.className = 'location-status';
      
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(position) {
            const lat = position.coords.latitude.toFixed(6);
            const lon = position.coords.longitude.toFixed(6);
            
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lon;
            
            statusElement.innerHTML = `<i class="fas fa-check-circle"></i> Location captured: ${lat}, ${lon}`;
            statusElement.className = 'location-status active';
          },
          function(error) {
            let errorMessage = "Error getting location: ";
            switch(error.code) {
              case error.PERMISSION_DENIED:
                errorMessage += "User denied the request for Geolocation.";
                break;
              case error.POSITION_UNAVAILABLE:
                errorMessage += "Location information is unavailable.";
                break;
              case error.TIMEOUT:
                errorMessage += "The request to get user location timed out.";
                break;
              case error.UNKNOWN_ERROR:
                errorMessage += "An unknown error occurred.";
                break;
            }
            
            statusElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
            statusElement.className = 'location-status error';
          },
          { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
      } else {
        statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Geolocation is not supported by this browser.';
        statusElement.className = 'location-status error';
      }
    });

    // File upload display
    const fileInput = document.querySelector('.file-upload-input');
    const uploadBtn = document.querySelector('.file-upload-btn');
    
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        uploadBtn.innerHTML = `
          <i class="fas fa-file-alt" style="font-size: 24px; margin-bottom: 10px;"></i>
          <p>${this.files[0].name}</p>
          <small>${(this.files[0].size / 1024).toFixed(2)} KB</small>
        `;
      }
    });
  </script>
</body>
</html>