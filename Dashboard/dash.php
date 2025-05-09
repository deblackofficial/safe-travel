<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // If not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background-color: #f1f1f1; display: flex; min-height: 100vh; }
    
    .sidebar {
      width: 250px;
      background-color: #3b47f1;
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .sidebar h2 { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
    .sidebar nav a {
      color: white;
      text-decoration: none;
      font-size: 18px;
      padding: 10px 0;
      margin: 10px 0;
      display: block;
      transition: background 0.3s;
    }
    .sidebar nav a:hover { background-color: #1a2bcf; border-radius: 5px; }

    .main-content {
      flex-grow: 1;
      padding: 20px;
      background-color: #ffffff;
      overflow-y: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #3b47f1;
      color: white;
      padding: 15px;
      border-radius: 5px;
    }

    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .card:hover { transform: translateY(-5px); }
    .card h3 { font-size: 18px; margin-bottom: 10px; }
    .card p { font-size: 15px; color: #555; }

    .btn {
      background-color: #3b47f1;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 25px;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      margin-top: 15px;
    }

    .btn:hover { background-color: #1a2bcf; }

    #loginBox {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      padding: 30px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      border-radius: 10px;
    }

    #loginBox input {
      display: block;
      margin: 10px 0;
      padding: 10px;
      width: 100%;
      font-size: 16px;
    }

    #loginBox button {
      background-color: #3b47f1;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      width: 100%;
      cursor: pointer;
    }

    #loginBox button:hover { background-color: #1a2bcf; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><strong>Dashboard</strong></h2>
    <nav>
      <a href="userdetails.php">Users</a>
      <a href="driverdetails.php" class="btn">View Driver Reports</a>
      <a href="passengerdetails.php" class="btn">View Passenger Reports</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
    <div class="main-content">
      <div class="dashboard-cards">
        <div class="card">
          <h3></h3>
          <p></p>
        </div>
      </div>
    </div>
</body>
<script>
  const getPassengerCount = () => {
  try {
    // Simulated/mock response
    const data = [
      { id: 1, name: "KIGALI-KARONGI-RUSIZI" },
      { id: 2, name: "KIGALI-HUYE-RUSIZI" },
      { id: 3, name: "KIGALI-KARONGI-RUSIZI" },
      { id: 4, name: "KIGALI-KARONGI-RUSIZI" },
      { id: 5, name: "KIGALI-KARONGI-RUSIZI" },
      { id: 6, name: "KIGALI-KARONGI-RUSIZI" },
    ];

    const dashboardCards = document.querySelector('.dashboard-cards');
    dashboardCards.innerHTML = ''; // Clear existing cards

    for (const element of data) {
      const card = document.createElement('div');
      card.className = 'card';

      const h3 = document.createElement('h3');
      h3.textContent = `${element.id}`;

      const p = document.createElement('p');
      p.textContent = `Name: ${element.name}`;

      const button = document.createElement('button');
      button.className = 'btn';
      button.textContent = 'View Details';
      button.onclick = () => {
        window.location.href = `passenger_details.html?id=${element.id}`;
      };

      card.appendChild(button);

      card.appendChild(h3);
      card.appendChild(p);
      dashboardCards.appendChild(card);
    }

  } catch (error) {
    console.error('Error fetching passenger count:', error);
  }
};

getPassengerCount();
// window.onload = () => {
//   document.getElementById('dashboard').style.display = 'flex';
// };
 // Call the function to fetch data
</script>
</html>
