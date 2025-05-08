<?php
session_start(); // Start the session
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $agency = mysqli_real_escape_string($conn, $_POST['agency']);
    $plate = mysqli_real_escape_string($conn, $_POST['plate']);
    $place = mysqli_real_escape_string($conn, $_POST['place']);
    $datetime = mysqli_real_escape_string($conn, $_POST['datetime']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);

    // Save data in session
    $_SESSION['form_data'] = [
        'phone' => $phone,
        'agency' => $agency,
        'plate' => $plate,
        'place' => $place,
        'datetime' => $datetime,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ];

    // Handle file upload
    $permitFileName = $_FILES['permit']['name'];
    $permitTempName = $_FILES['permit']['tmp_name'];
    $permitFolder = "uploads/" . basename($permitFileName);

    if (move_uploaded_file($permitTempName, $permitFolder)) {
        $_SESSION['form_data']['permit'] = $permitFileName; // Save permit file name in session

        // Redirect to driverfp.php
        header("Location: driverfp.php");
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
  <title>Driver Form</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #e4eee4;
      display: flex;
      justify-content: center;
      align-items: start;
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      max-width: 1200px; /* increased max-width */
      padding: 10px;
      box-sizing: border-box;
    }

    form {
      background-color: white;
      padding: 40px; /* increased padding */
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 100%;
    }

    label {
      display: block;
      font-weight: bold;
      color: #1a1a6f;
      margin-bottom: 5px;
      font-size: 16px;
    }

    input, select {
      width: 100%;
      height: 45px;
      padding: 0 16px;
      border: 2px solid #0c85d0;
      border-radius: 25px;
      margin-bottom: 15px;
      font-size: 15px;
      box-shadow: 2px 3px 6px rgba(0, 0, 0, 0.2);
      outline: none;
      color: #555;
    }

    input::placeholder, select option {
      color: #999;
      font-style: italic;
    }

    .buttons {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 20px;
      margin-top: 20px;
    }

    .btn {
      background-color: #3b47f1;
      color: white;
      padding: 14px 30px;
      border: none;
      border-radius: 25px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.25);
      transition: transform 0.2s ease;
      flex: 1;
      text-align: center;
      text-decoration: none;
    }

    .btn:hover {
      transform: scale(1.03);
    }

    .divider {
      text-align: center;
      width: 100%;
    }

    @media (max-width: 600px) {
      .btn {
        width: 100%;
      }

      .buttons {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <form id="driverForm" method="POST" enctype="multipart/form-data" action="driver.php">
      <label for="ticket">Phone Number</label>
      <input id="ticket" name="phone" type="tel" placeholder="Tel: 1234 567 890" 
             value="<?php echo isset($formData['phone']) ? $formData['phone'] : ''; ?>" required />

      <label for="agency">Agency name</label>
      <input id="agency" name="agency" type="text" placeholder="Eg: Ritco, Volcano,......." 
             value="<?php echo isset($formData['agency']) ? $formData['agency'] : ''; ?>" required />

      <label for="plate">Plate number</label>
      <input id="plate" name="plate" type="text" placeholder="Eg: RAF 123,,,,,," 
             value="<?php echo isset($formData['plate']) ? $formData['plate'] : ''; ?>" required />

      <label for="place">Place</label>
      <select id="place" name="place" required>
        <option value="">Select a Road</option>
        <option value="KIGALI_KARONGI_RUSIZI" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI_KARONGI_RUSIZI') ? 'selected' : ''; ?>>KIGALI_KARONGI_RUSIZI</option>
        <option value="KIGALI_HUYE_RUSIZI" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI_HUYE_RUSIZI') ? 'selected' : ''; ?>>KIGALI_HUYE_RUSIZI</option>
        <option value="KIGALI-MUSANZE_RUBAVU" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI-MUSANZE_RUBAVU') ? 'selected' : ''; ?>>KIGALI-MUSANZE_RUBAVU</option>
        <option value="KIGALI_KAYONZA_RUSUMO" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI_KAYONZA_RUSUMO') ? 'selected' : ''; ?>>KIGALI_KAYONZA_RUSUMO</option>
        <option value="KIGALI_KAYONZA_KAGITUMBA" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI_KAYONZA_KAGITUMBA') ? 'selected' : ''; ?>>KIGALI_KAYONZA_KAGITUMBA</option>
        <option value="KIGALI_NGARAMA_NYAGATARE" <?php echo (isset($formData['place']) && $formData['place'] === 'KIGALI_NGARAMA_NYAGATARE') ? 'selected' : ''; ?>>KIGALI_NGARAMA_NYAGATARE</option>
      </select>

      <label for="datetime">Date & Time</label>
      <input id="datetime" name="datetime" type="datetime-local" 
             value="<?php echo isset($formData['datetime']) ? $formData['datetime'] : ''; ?>" required />

      <label for="permit">Upload Your <strong><u>PERMIT</u></strong> here</label>
      <input id="permit" name="permit" type="file" accept="image/*,.pdf" required />

      <label>Capture Location</label>
      <button type="button" class="btn" id="getLocationBtn">Get Current Location</button>
      <br>
      <input id="latitude" name="latitude" type="text" placeholder="Latitude" 
             value="<?php echo isset($formData['latitude']) ? $formData['latitude'] : ''; ?>" readonly required />
      <input id="longitude" name="longitude" type="text" placeholder="Longitude" 
             value="<?php echo isset($formData['longitude']) ? $formData['longitude'] : ''; ?>" readonly required />

      <div class="buttons">
        <button class="btn" type="submit">Continue</button>
        <div class="divider"><h2>If not</h2></div>
        <a href="choose.html" class="btn"><strong>BACK</strong></a>
      </div>
    </form>
  </div>
  <script>
    // Save permit file to localStorage
    document.getElementById('permit').addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function () {
        localStorage.setItem('driverPermit', reader.result);
        alert('Permit file saved to localStorage.');
      };
      reader.readAsDataURL(file);
    });

    // Capture GPS location
    document.getElementById("getLocationBtn").addEventListener("click", function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function (position) {
            const lat = position.coords.latitude.toFixed(6);
            const lon = position.coords.longitude.toFixed(6);
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lon;

            // Optional: store to localStorage
            localStorage.setItem("driverLatitude", lat);
            localStorage.setItem("driverLongitude", lon);
            alert("Location captured: " + lat + ", " + lon);
          },
          function (error) {
            alert("Error getting location: " + error.message);
          }
        );
      } else {
        alert("Geolocation is not supported by this browser.");
      }
    });

    // Redirect on form submit
    // document.getElementById("driverForm").addEventListener("submit", function (e) {
    //   e.preventDefault();
    //   window.location.href = "driverfp.html";
    // });
  </script>
</body>
</html>