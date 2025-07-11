<?php
session_start();
include '../conn.php';

// --- Date Filter ---
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$years = [];
$yearQuery = "
    SELECT DISTINCT YEAR(datetime) as y FROM (
        SELECT datetime FROM passenger_report
        UNION ALL
        SELECT datetime FROM driver_report
    ) as all_reports
    ORDER BY y DESC
";
$yearRes = mysqli_query($conn, $yearQuery);
while ($row = mysqli_fetch_assoc($yearRes)) $years[] = $row['y'];

// --- Monthly counts (selected year) ---
$monthlyData = [];
$monthlyQuery = "
    SELECT DATE_FORMAT(datetime, '%Y-%m') as month, COUNT(*) as count
    FROM (
        SELECT datetime FROM passenger_report WHERE YEAR(datetime) = $year
        UNION ALL
        SELECT datetime FROM driver_report WHERE YEAR(datetime) = $year
    ) as all_reports
    GROUP BY month
    ORDER BY month ASC
";
$res = mysqli_query($conn, $monthlyQuery);
while ($row = mysqli_fetch_assoc($res)) $monthlyData[] = $row;

// --- Previous year for comparison ---
$prevYear = $year - 1;
$prevMonthlyData = [];
$prevMonthlyQuery = "
    SELECT DATE_FORMAT(datetime, '%Y-%m') as month, COUNT(*) as count
    FROM (
        SELECT datetime FROM passenger_report WHERE YEAR(datetime) = $prevYear
        UNION ALL
        SELECT datetime FROM driver_report WHERE YEAR(datetime) = $prevYear
    ) as all_reports
    GROUP BY month
    ORDER BY month ASC
";
$resPrev = mysqli_query($conn, $prevMonthlyQuery);
while ($row = mysqli_fetch_assoc($resPrev)) $prevMonthlyData[] = $row;

// --- Regional stats (by place, selected year) ---
$regionalData = [];
$regionalQuery = "
    SELECT place, COUNT(*) as count
    FROM (
        SELECT place, datetime FROM passenger_report WHERE YEAR(datetime) = $year
        UNION ALL
        SELECT place, datetime FROM driver_report WHERE YEAR(datetime) = $year
    ) as all_places
    GROUP BY place
    ORDER BY count DESC
    LIMIT 10
";
$res2 = mysqli_query($conn, $regionalQuery);
$totalReports = 0;
$mostActiveRegion = '';
$mostActiveCount = 0;
while ($row = mysqli_fetch_assoc($res2)) {
    $regionalData[] = $row;
    $totalReports += $row['count'];
    if ($row['count'] > $mostActiveCount) {
        $mostActiveRegion = $row['place'];
        $mostActiveCount = $row['count'];
    }
}

// --- Total reports (all time) ---
$totalReportsQuery = "
    SELECT COUNT(*) as total FROM (
        SELECT id FROM passenger_report
        UNION ALL
        SELECT id FROM driver_report
    ) as all_reports
";
$res3 = mysqli_query($conn, $totalReportsQuery);
$totalReportsAll = mysqli_fetch_assoc($res3)['total'] ?? 0;

// --- Last report date ---
$lastReportQuery = "
    SELECT MAX(datetime) as last_date FROM (
        SELECT datetime FROM passenger_report
        UNION ALL
        SELECT datetime FROM driver_report
    ) as all_reports
";
$res4 = mysqli_query($conn, $lastReportQuery);
$lastReportDate = mysqli_fetch_assoc($res4)['last_date'] ?? 'N/A';

// --- Pie chart: report type breakdown (selected year) ---
$typeQuery = "
    SELECT 'Passenger' as type, COUNT(*) as count FROM passenger_report WHERE YEAR(datetime) = $year
    UNION ALL
    SELECT 'Driver' as type, COUNT(*) as count FROM driver_report WHERE YEAR(datetime) = $year
";
$typeRes = mysqli_query($conn, $typeQuery);
$typeData = [];
while ($row = mysqli_fetch_assoc($typeRes)) $typeData[] = $row;

// --- Key Insights ---
$currentMonth = date('Y-m');
$currentMonthCount = 0;
foreach ($monthlyData as $row) {
    if ($row['month'] == $currentMonth) $currentMonthCount = $row['count'];
}
$prevMonth = date('Y-m', strtotime('-1 month'));
$prevMonthCount = 0;
foreach ($monthlyData as $row) {
    if ($row['month'] == $prevMonth) $prevMonthCount = $row['count'];
}
$change = $prevMonthCount ? round((($currentMonthCount - $prevMonthCount) / $prevMonthCount) * 100) : 0;
$insight = '';
if ($currentMonthCount > $prevMonthCount) {
    $insight = "Reports increased by $change% compared to last month.";
} elseif ($currentMonthCount < $prevMonthCount && $prevMonthCount > 0) {
    $insight = "Reports decreased by " . abs($change) . "% compared to last month.";
} else {
    $insight = "Reports are stable compared to last month.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Insights</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #e0ffe7 100%);
            font-family: 'Nunito', Arial, sans-serif;
            margin: 0;
        }
        nav {
            background: #3b47f1;
            color: #fff;
            padding: 20px 0 18px 0;
            text-align: center;
            font-size: 1.15em;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 16px #0002;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 22px;
            font-weight: bold;
            border-bottom: 2px solid transparent;
            padding-bottom: 3px;
            transition: color 0.2s, border-bottom 0.2s;
        }
        nav a:hover, nav a.active {
            color: #ffea00;
            border-bottom: 2.5px solid #ffea00;
        }
        .container {
            max-width: 1250px;
            margin: 44px auto 30px auto;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 32px #0002;
            padding: 48px 36px 36px 36px;
        }
        h2 {
            color: #3b47f1;
            font-size: 2.5em;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }
        .filter-bar {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .filter-bar select {
            padding: 7px 14px;
            border-radius: 7px;
            border: 1px solid #3b47f1;
            font-size: 1em;
            color: #3b47f1;
            background: #f8faff;
        }
        .insight-box {
            background: #e6eaff;
            color: #3b47f1;
            border-left: 6px solid #3b47f1;
            border-radius: 10px;
            padding: 18px 22px;
            font-size: 1.15em;
            margin-bottom: 28px;
            box-shadow: 0 2px 10px #0001;
        }
        .summary-cards {
            display: flex;
            gap: 36px;
            margin-bottom: 48px;
            flex-wrap: wrap;
        }
        .card {
            flex: 1;
            min-width: 240px;
            background: linear-gradient(120deg, #e4eee4 60%, #e6eaff 100%);
            border-radius: 18px;
            box-shadow: 0 2px 16px #0001;
            padding: 32px 26px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
        }
        .card:before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 80px; height: 80px;
            background: rgba(59,71,241,0.07);
            border-radius: 50%;
            z-index: 0;
        }
        .card-title {
            font-size: 1.13em;
            color: #3b47f1;
            margin-bottom: 10px;
            font-weight: 700;
            z-index: 1;
        }
        .card-value {
            font-size: 2.3em;
            font-weight: bold;
            color: #222;
            z-index: 1;
        }
        .card-desc {
            font-size: 1em;
            color: #666;
            margin-top: 8px;
            z-index: 1;
        }
        .chart-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto 48px auto;
            background: #f8faff;
            border-radius: 16px;
            box-shadow: 0 2px 10px #0001;
            padding: 36px 24px 18px 24px;
        }
        .pie-container {
            width: 370px;
            margin: 0 auto 48px auto;
            background: #f8faff;
            border-radius: 16px;
            box-shadow: 0 2px 10px #0001;
            padding: 36px 24px 18px 24px;
        }
        .regions-section {
            margin-top: 36px;
        }
        .regions-title {
            color: #3b47f1;
            font-size: 1.35em;
            margin-bottom: 22px;
            font-weight: bold;
        }
        .region-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            gap: 18px;
        }
        .region-name {
            min-width: 120px;
            font-weight: 600;
            color: #222;
        }
        .progress-bar-bg {
            flex: 1;
            background: #e6eaff;
            border-radius: 8px;
            height: 18px;
            overflow: hidden;
            margin-right: 10px;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b47f1 60%, #5ee7df 100%);
            border-radius: 8px;
            transition: width 0.7s;
        }
        .region-count {
            min-width: 40px;
            text-align: right;
            font-weight: 600;
            color: #3b47f1;
        }
        .last-report {
            font-size: 1em;
            color: #888;
            margin-top: 18px;
            text-align: right;
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
            background: #f8faff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px #0001;
        }
        .recent-table th, .recent-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e6eaff;
            text-align: left;
        }
        .recent-table th {
            background: #3b47f1;
            color: #fff;
            font-size: 1.08em;
        }
        .recent-table tr:last-child td {
            border-bottom: none;
        }
        .section-title {
            color: #3b47f1;
            margin-top: 48px;
            font-size: 1.25em;
            font-weight: bold;
        }
        .export-btn, .refresh-btn {
            display: inline-block;
            background: #3b47f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1em;
            margin: 0 8px 18px 0;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .export-btn:hover, .refresh-btn:hover {
            background: #1a2bcf;
        }
        @media (max-width: 1100px) {
            .container { padding: 18px 2vw 18px 2vw; }
            .summary-cards { flex-direction: column; gap: 18px; }
            .chart-container, .pie-container { padding: 10px 2vw 10px 2vw; }
        }
    </style>
</head>
<body>
    <nav>
        <a href="../index.php">Home</a>
        <a href="dash.php">Dashboard</a>
        <a href="incidents.php">Incidents</a>
        <a href="users.php">Users</a>
        <a href="analytics.php" class="active">Analytics</a>
        <a href="notifications.php">Notifications</a>
        <a href="agency.php">Agencies</a>
    </nav>
    <div class="container">
        <h2>Analytics & Insights</h2>
        <form method="get" class="filter-bar">
            <label for="year" style="color:#3b47f1;font-weight:bold;">Year:</label>
            <select name="year" id="year" onchange="this.form.submit()">
                <?php foreach($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php if($y == $year) echo 'selected'; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="insight-box">
            <strong>Key Insight:</strong> <?php echo $insight; ?>
        </div>
        <div style="margin-bottom:18px;">
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
            <a href="export_analytics.php?type=csv&year=<?php echo $year; ?>" class="export-btn">⬇️ Export CSV</a>
            <button class="export-btn" onclick="window.print()">🖨️ Print</button>
        </div>
        <div class="summary-cards">
            <div class="card">
                <div class="card-title">Total Reports</div>
                <div class="card-value"><?php echo number_format($totalReportsAll); ?></div>
                <div class="card-desc">All time, both passengers and drivers</div>
            </div>
            <div class="card">
                <div class="card-title">Most Active Region</div>
                <div class="card-value"><?php echo htmlspecialchars($mostActiveRegion ?: 'N/A'); ?></div>
                <div class="card-desc">With <?php echo $mostActiveCount; ?> reports</div>
            </div>
            <div class="card">
                <div class="card-title">Last Report Date</div>
                <div class="card-value"><?php echo $lastReportDate ? date('d M Y, H:i', strtotime($lastReportDate)) : 'N/A'; ?></div>
                <div class="card-desc">Most recent report submitted</div>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
        <div class="pie-container">
            <canvas id="typePie"></canvas>
        </div>
        <div class="regions-section">
            <div class="regions-title">Top 10 Regions by Reports</div>
            <?php
            $maxCount = 1;
            foreach ($regionalData as $row) {
                if ($row['count'] > $maxCount) $maxCount = $row['count'];
            }
            foreach ($regionalData as $row): 
                $percent = $maxCount ? round(($row['count'] / $maxCount) * 100) : 0;
            ?>
                <div class="region-row">
                    <span class="region-name"><?php echo htmlspecialchars($row['place']); ?></span>
                    <div class="progress-bar-bg">
                        <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <span class="region-count" title="Number of reports"><?php echo $row['count']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        // Monthly bar chart
        const rawLabels = <?php echo json_encode(array_column($monthlyData, 'month')); ?>;
        const labels = rawLabels.map(m => {
            const d = new Date(m + '-01');
            return d.toLocaleString('default', { month: 'short', year: 'numeric' });
        });

        const data = {
            labels: labels,
            datasets: [{
                label: 'Reports per Month',
                data: <?php echo json_encode(array_column($monthlyData, 'count')); ?>,
                backgroundColor: '#3b47f1',
                borderRadius: 10,
                maxBarThickness: 50
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#222',
                        font: { weight: 'bold', size: 16 }
                    },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Month', color: '#3b47f1', font: { weight: 'bold', size: 17 } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Reports', color: '#3b47f1', font: { weight: 'bold', size: 17 } },
                        ticks: { stepSize: 1 }
                    }
                }
            },
            plugins: [ChartDataLabels]
        };

        Chart.register(window.ChartDataLabels);
        new Chart(document.getElementById('monthlyChart'), config);

        // Pie chart for report types
        const pieData = {
            labels: <?php echo json_encode(array_column($typeData, 'type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($typeData, 'count')); ?>,
                backgroundColor: ['#3b47f1', '#5ee7df']
            }]
        };
        new Chart(document.getElementById('typePie'), {
            type: 'pie',
            data: pieData,
            options: {
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 15 } } },
                    datalabels: {
                        color: '#222',
                        font: { weight: 'bold', size: 15 },
                        formatter: (value, ctx) => value
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    </script>
</body>
</html>