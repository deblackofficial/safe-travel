<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Travel - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }
        
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        #sidebar .sidebar-header {
            padding: 1.5rem 1rem;
            background: rgba(0, 0, 0, 0.1);
        }
        
        #sidebar ul.components {
            padding: 0;
        }
        
        #sidebar ul li {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        #sidebar ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            padding: 0.5rem 0;
        }
        
        #sidebar ul li a:hover {
            color: white;
        }
        
        #sidebar ul li.active {
            background: rgba(0, 0, 0, 0.2);
        }
        
        #sidebar ul li.active a {
            color: white;
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        #content {
            margin-left: var(--sidebar-width);
            min-height: calc(100vh - var(--header-height));
            padding: 20px;
            transition: all 0.3s;
        }
        
        #header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .stat-card {
            border-left: 0.25rem solid var(--primary-color);
        }
        
        .stat-card .card-body {
            padding: 1rem;
        }
        
        .stat-card .text-primary {
            color: var(--primary-color) !important;
        }
        
        .stat-card .text-xs {
            font-size: 0.7rem;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(87deg, var(--primary-color) 0, #224abe 100%) !important;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 1rem 0;
        }
        
        .sidebar-heading {
            padding: 0 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .quick-actions .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                margin-left: 0;
            }
            #header {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-bus-front"></i> Safe Travel</h3>
        </div>
        
        <ul class="list-unstyled components">
            <li class="active">
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            
            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Core</div>
            
            <li>
                <a href="bus_boarding.php"><i class="bi bi-person-plus"></i> Passenger Boarding</a>
            </li>
            <li>
                <a href="register.php"><i class="bi bi-credit-card"></i> Register Passengers</a>
            </li>
            <li>
                <a href="register_bus.php"><i class="bi bi-bus-front"></i> Register Buses</a>
            </li>

            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Management</div>
            
            <li>
                <a href="routes.php"><i class="bi bi-signpost-split"></i> Manage Routes</a>
            </li>
            <li>
                <a href="adduser.php"><i class="bi bi-people"></i> User Management</a>
            </li>

            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Reports</div>
            
            <li>
                <a href="reports.php"><i class="bi bi-clipboard-data"></i> Trip Reports</a>
            </li>
            <li>
                <a href="dash.php"><i class="bi bi-exclamation-triangle"></i> Incident Analytics</a>
            </li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Top Header -->
        <header id="header">
            <button type="button" id="sidebarCollapse" class="btn btn-sm btn-primary d-md-none">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=Admin&background=random" alt="User">
                <div>
                    <div class="fw-bold">Administrator</div>
                    <div class="text-muted small"><strong>Admin</strong></div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container-fluid" style="margin-top: calc(var(--header-height) + 20px);">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="bi bi-download text-white-50"></i> Generate Report
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Active Buses</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        include 'conn.php';
                                        $query = "SELECT COUNT(*) as count FROM buses WHERE status = 'active'";
                                        $result = mysqli_query($conn, $query);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['count'];
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bus-front fs-1 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Registered Passengers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        $query = "SELECT COUNT(*) as count FROM rfid_cards";
                                        $result = mysqli_query($conn, $query);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['count'];
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fs-1 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Today's Trips</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        $query = "SELECT COUNT(*) as count FROM passenger_trips 
                                                 WHERE DATE(entry_time) = CURDATE()";
                                        $result = mysqli_query($conn, $query);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['count'];
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clipboard-data fs-1 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Over Limit Today</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        $query = "SELECT COUNT(*) as count FROM passenger_trips 
                                                 WHERE status = 'over_limit' AND DATE(entry_time) = CURDATE()";
                                        $result = mysqli_query($conn, $query);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['count'];
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-exclamation-triangle fs-1 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body quick-actions">
                            <a href="bus_boarding.php" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Board Passengers
                            </a>
                            <a href="register-card.php" class="btn btn-success">
                                <i class="bi bi-credit-card"></i> Register Passenger Card
                            </a>
                            <a href="register_bus.php" class="btn btn-info">
                                <i class="bi bi-bus-front"></i> Register New Bus
                            </a>
                            <a href="routes.php" class="btn btn-warning">
                                <i class="bi bi-signpost-split"></i> Manage Routes
                            </a>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="bi bi-clipboard-data"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Trips</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Passenger</th>
                                            <th>Bus</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT t.*, u.first_name, u.last_name, b.plate_number
                                                 FROM passenger_trips t
                                                 JOIN users u ON t.passenger_id = u.id
                                                 JOIN buses b ON t.bus_id = b.id
                                                 ORDER BY t.entry_time DESC LIMIT 5";
                                        $result = mysqli_query($conn, $query);
                                        
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $statusClass = $row['status'] === 'over_limit' ? 'badge-danger' : 
                                                         ($row['status'] === 'completed' ? 'badge-success' : 'badge-primary');
                                            echo "<tr>
                                                <td>{$row['first_name']} {$row['last_name']}</td>
                                                <td>{$row['plate_number']}</td>
                                                <td>" . date('H:i', strtotime($row['entry_time'])) . "</td>
                                                <td><span class='badge {$statusClass}'>" . ucfirst($row['status']) . "</span></td>
                                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">System Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i> RFID Reader: <strong>Online</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i> Database: <strong>Connected</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i> System: <strong>Operational</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Highlight active menu item
        const currentPage = window.location.pathname.split('/').pop();
        const menuItems = document.querySelectorAll('#sidebar ul li a');
        
        menuItems.forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.parentElement.classList.add('active');
            } else {
                item.parentElement.classList.remove('active');
            }
        });
        
        // Always highlight dashboard when on dashboard.php
        if (currentPage === 'dashboard.php') {
            document.querySelector('#sidebar ul li:first-child').classList.add('active');
        }
    </script>