<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

// Handle filters
$filter_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters - Only approved employees
$sql = "SELECT a.*, u.fullname, e.emp_code, d.name as department_name 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        JOIN users u ON e.user_id = u.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE u.is_approved = 1"; // Only approved employees

if(!empty($filter_employee)) {
    $sql .= " AND a.employee_id = '$filter_employee'";
}

if(!empty($filter_month)) {
    $start_date = $filter_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $sql .= " AND a.date BETWEEN '$start_date' AND '$end_date'";
}

if(!empty($filter_date)) {
    $sql .= " AND a.date = '$filter_date'";
}

$sql .= " ORDER BY a.date DESC, u.fullname ASC";
$result = $conn->query($sql);

// Fetch only approved employees for filter dropdown
$employees_sql = "SELECT e.id, u.fullname, e.emp_code 
                  FROM employees e 
                  JOIN users u ON e.user_id = u.id 
                  WHERE u.is_approved = 1"; // Only approved
$employees_result = $conn->query($employees_sql);

// Calculate statistics - Only for approved employees
$stats_sql = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as total_half_day
              FROM attendance a
              JOIN employees e ON a.employee_id = e.id
              JOIN users u ON e.user_id = u.id
              WHERE u.is_approved = 1"; // Only approved

if(!empty($filter_employee)) {
    $stats_sql .= " AND a.employee_id = '$filter_employee'";
}

if(!empty($filter_month)) {
    $start_date = $filter_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $stats_sql .= " AND a.date BETWEEN '$start_date' AND '$end_date'";
}

if(!empty($filter_date)) {
    $stats_sql .= " AND a.date = '$filter_date'";
}

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* SAME dropdown menu styles as admin_dashboard.php */
        .side-bar ul li {
            position: relative;
        }
        .side-bar ul li ul {
            display: none;
            list-style: none;
            padding-left: 20px;
            background: #2f323a;
            position: absolute;
            left: 100%;
            top: 0;
            width: 200px;
            z-index: 1000;
        }
        .side-bar ul li:hover ul {
            display: block;
        }
        .side-bar ul li ul li {
            padding: 10px;
        }
        .side-bar ul li ul li a {
            font-size: 14px;
        }
        
        /* Page specific styles */
        .page-wrapper {
            max-width: 1200px;
            width: 100%;
        }
        
        .page-header {
            background: #34495e;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
        
        .content-container {
            background: white;
            padding: 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: #34495e;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        
        .attendance-table th {
            background: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
            font-weight: bold;
        }
        
        .status-present {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-absent {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .status-half_day {
            color: #f39c12;
            font-weight: bold;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .clear-btn {
            background: #95a5a6;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            display: inline-block;
            margin-left: 10px;
            transition: 0.3s;
        }
        
        .clear-btn:hover {
            background: #7f8c8d;
        }
        
        .employee-name {
            text-align: left;
            padding-left: 20px;
        }
        
        /* Main content adjustment */
        .main-content {
            padding: 25px;
            background: #f4f6f9;
            min-height: calc(100vh - 60px);
        }
        .main-content h1{
            color:white;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .records-count {
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
            color: #34495e;
        }
    </style>
</head>
<body>
    <!-- EXACT SAME HEADER as admin_dashboard.php -->
    <header class="header">
        <h2 class="u-name">Admin Dashboard</h2>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- EXACT SAME CONTAINER STRUCTURE -->
    <div class="container">
        <!-- EXACT SAME SIDEBAR as admin_dashboard.php -->
        <nav class="side-bar">
            <div class="user-p">
                <img src="../img/user.jpeg" alt="User">
                <h4><?php echo $_SESSION['fullname']; ?></h4>
                <span>(Admin)</span>
            </div>
            <ul>
                <li><a href="../admin_dashboard.php"><i class="fa fa-desktop"></i><span>Dashboard</span></a></li>
                <li>
                    <a href="#"><i class="fa fa-users"></i><span>Manage Employee</span></a>
                    <ul>
                        <li><a href="view_employee.php">👥 View Employee</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-calendar"></i><span>Manage Leaves</span></a>
                    <ul>
                        <li><a href="view_leaves.php">📄 View Leave Requests</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-check-square"></i><span>Manage Attendance</span></a>
                    <ul>
                        <li><a href="mark_attendance.php">📝 Mark Daily Attendance</a></li>
                        <li><a href="attendance_report.php" style="background: #1abc9c; color: white;">📊 View Attendance Report</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-file-invoice"></i><span>Manage Payslip</span></a>
                    <ul>
                        <li><a href="calculate_salary.php">💰 Calculate Salary</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="page-wrapper">
                <div class="page-header">
                    <h1><i class="fas fa-chart-bar"></i> Attendance Report</h1>
                </div>

                <div class="content-container">
                    <!-- Filters -->
                    <div class="filters">
                        <form method="GET">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="employee_id">Filter by Employee</label>
                                    <select name="employee_id" id="employee_id">
                                        <option value="">All Employees</option>
                                        <?php 
                                        // Reset pointer to use employees_result again
                                        $employees_result->data_seek(0);
                                        while($emp = $employees_result->fetch_assoc()): ?>
                                            <option value="<?php echo $emp['id']; ?>" <?php echo $filter_employee == $emp['id'] ? 'selected' : ''; ?>>
                                                <?php echo $emp['fullname'] . ' (' . $emp['emp_code'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="month">Filter by Month</label>
                                    <input type="month" name="month" id="month" value="<?php echo $filter_month; ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <label for="date">Filter by Date</label>
                                    <input type="date" name="date" id="date" value="<?php echo $filter_date; ?>">
                                </div>
                                
                                <div class="filter-group filter-buttons">
                                    <button type="submit">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="attendance_report.php" class="clear-btn">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Statistics -->
                    <div class="stats">
                        <div class="stat-card">
                            <h3>Total Records</h3>
                            <p><?php echo $stats['total_records']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Present Days</h3>
                            <p style="color: #27ae60;"><?php echo $stats['total_present']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Absent Days</h3>
                            <p style="color: #e74c3c;"><?php echo $stats['total_absent']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Half Days</h3>
                            <p style="color: #f39c12;"><?php echo $stats['total_half_day']; ?></p>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <?php if($result->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th class="employee-name">Employee Name</th>
                                        <th>Emp Code</th>
                                        <th>Department</th>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="employee-name"><?php echo $row['fullname']; ?></td>
                                            <td><?php echo $row['emp_code']; ?></td>
                                            <td><?php echo $row['department_name'] ?: 'N/A'; ?></td>
                                            <td><?php echo $row['date']; ?></td>
                                            <td><?php echo date('l', strtotime($row['date'])); ?></td>
                                            <td class="status-<?php echo $row['status']; ?>">
                                                <?php 
                                                    if($row['status'] == 'half_day') {
                                                        echo 'Half Day';
                                                    } else {
                                                        echo ucfirst($row['status']);
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="records-count">
                            <p><strong>Showing <?php echo $result->num_rows; ?> records</strong></p>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h3>No Attendance Records Found</h3>
                            <p>No attendance records match your current filters.</p>
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center;">
                        <a href="../admin_dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Clear date when month is selected and vice versa
        document.getElementById('month').addEventListener('change', function() {
            if(this.value) {
                document.getElementById('date').value = '';
            }
        });
        
        document.getElementById('date').addEventListener('change', function() {
            if(this.value) {
                document.getElementById('month').value = '';
            }
        });
    </script>
</body>
</html>