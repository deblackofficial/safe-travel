<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Passenger Monitoring Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(to right, #e3f2fd, #ffffff);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .status-ok {
      color: #28a745;
      font-weight: bold;
    }
    .status-overload {
      color: #dc3545;
      font-weight: bold;
    }
    h1 {
      font-size: 2.5rem;
      font-weight: bold;
      text-align: center;
      margin-bottom: 30px;
    }
    .table th, .table td {
      vertical-align: middle;
    }

    /* Sidebar styling */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 250px;
      background-color: #343a40;
      padding: 20px;
    }
    .sidebar h2 {
      font-size: 1.8rem;
      margin-bottom: 1rem;
      color: white;
    }
    .sidebar .nav-link {
      margin-bottom: 10px;
      font-size: 1.1rem;
      color: white;
    }
    .sidebar .nav-link:hover {
      background-color: #495057;
      border-radius: 5px;
    }
    /* Main content styling */
    .main-content {
      margin-left: 250px;
      padding: 20px;
    }
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <nav class="nav flex-column">
        <a class="nav-link text-white" href="iotdash.html"><strong>IoT Dashboard</strong></a>
        <a class="nav-link text-white" href="dash.html"><strong>Manual Dashboard</strong></a>
        <!-- <a class="nav-link text-white" href="login.html" onclick="logout()"><strong>Log Out</strong></a> -->
      </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <h1>🚌 Passenger Monitoring Dashboard</h1>

      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="card text-white bg-primary h-100">
            <div class="card-body">
              <h5 class="card-title">Bus ID</h5>
              <p class="card-text">001</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-success h-100">
            <div class="card-body">
              <h5 class="card-title">Total Capacity</h5>
              <p class="card-text" id="max-passengers">30</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-info h-100">
            <div class="card-body">
              <h5 class="card-title">Current Count</h5>
              <p class="card-text" id="current-count">-</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Recent RFID Scans</h5>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead class="table-dark">
                <tr>
                  <th>Time</th>
                  <th>RFID Tag</th>
                  <th>Passenger ID</th>
                  <th>Count</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="logs-table-body">
                <tr><td colspan="5">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    async function fetchLogs() {
      try {
        const response = await fetch('/api/logs'); // Your REST API endpoint
        const logs = await response.json();

        const tableBody = document.getElementById('logs-table-body');
        tableBody.innerHTML = '';

        let latestCount = 0;
        let maxPassengers = 30;

        logs.forEach(log => {
          latestCount = log.count;
          maxPassengers = log.max_passengers;
          const statusClass = log.count > log.max_passengers ? 'status-overload' : 'status-ok';
          const statusText = log.count > log.max_passengers ? 'Overload' : 'OK';

          const row = `
            <tr>
              <td>${log.timestamp}</td>
              <td>${log.tag}</td>
              <td>${log.passenger_id || 'Unknown'}</td>
              <td>${log.count}</td>
              <td><span class="${statusClass}">${statusText}</span></td>
            </tr>`;
          tableBody.innerHTML += row;
        });

        document.getElementById('current-count').innerText = latestCount;
        document.getElementById('max-passengers').innerText = maxPassengers;
      } catch (err) {
        console.error('Error fetching data:', err);
        document.getElementById('logs-table-body').innerHTML = '<tr><td colspan="5">Failed to load data</td></tr>';
      }
    }

    fetchLogs();
    setInterval(fetchLogs, 10000); // Refresh every 10 seconds

    // Logout function
    function logout() {
      alert("Logging out...");
      window.location.href = "login.html"; // Or wherever your login page is
    }
  </script>
</body>
</html>
