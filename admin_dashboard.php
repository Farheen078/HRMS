<?php
session_start();
include "connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit();
}

// Get real data from database - Only approved employees
// Total Approved Employees Only
$total_employees_sql = "SELECT COUNT(*) as total FROM employees e 
                        JOIN users u ON e.user_id = u.id 
                        WHERE u.is_approved = 1";
$total_employees_result = $conn->query($total_employees_sql);
$total_employees = $total_employees_result->fetch_assoc()['total'];

// Active Employees (only approved)
$active_employees = $total_employees;

// Attendance Today (only for approved employees)
$today = date('Y-m-d');
$attendance_today_sql = "SELECT COUNT(*) as today_count FROM attendance a 
                         JOIN employees e ON a.employee_id = e.id 
                         JOIN users u ON e.user_id = u.id 
                         WHERE a.date = '$today' AND a.status = 'present' AND u.is_approved = 1";
$attendance_today_result = $conn->query($attendance_today_sql);
$attendance_today = $attendance_today_result->fetch_assoc()['today_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="css/admindash.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
/* Quick CSS for dropdown menus */
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
</style>
</head>
<body>
    <header class="header">
        <h2 class="u-name">Admin Dashboard</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <div class="container">
      <nav class="side-bar">
    <div class="user-p">
        <img src="img/user.jpeg" alt="User">
        <h4><?php echo $_SESSION['fullname']; ?></h4>
        <span>(Admin)</span>
    </div>
   <ul>
        <li><a href="admin_dashboard.php"><i class="fa fa-desktop"></i><span>Dashboard</span></a></li>
        <li>
            <a href="#"><i class="fa fa-users"></i><span>Manage Employee</span></a>
            <ul>
                <li><a href="admin/view_employee.php">👥 View Employee</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class="fa fa-calendar"></i><span>Manage Leaves</span></a>
            <ul>
                <li><a href="admin/view_leaves.php">📄 View Leave Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class="fa fa-check-square"></i><span>Manage Attendance</span></a>
            <ul>
                <li><a href="admin/mark_attendance.php">📝 Mark Daily Attendance</a></li>
                <li><a href="admin/attendance_report.php">📊 View Attendance Report</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class="fa fa-file-invoice"></i><span>Manage Payslip</span></a>
            <ul>
                <li><a href="admin/calculate_salary.php">💰 Calculate Salary</a></li>
                <li><a href="admin/mark_salary_paid.php">✔ Mark Salary as Paid</a></li>
                <li><a href="admin/upload_payslip.php">📂 Upload Payslip PDF</a></li>
            </ul>
        </li>
    </ul>
</nav>
        <main class="main-content">
            <h1>Welcome, <?php echo $_SESSION['fullname']; ?>!</h1>
            <p>This is your admin panel. From here you can manage employees, leaves, attendance, and payslips.</p>
            
            <div class="cards">
                <div class="card">
                    <h3>Total Employees</h3>
                    <p><?php echo $total_employees; ?></p>
                </div>
                <div class="card">
                    <h3>Active Employees</h3>
                    <p><?php echo $active_employees; ?></p>
                </div>
                <div class="card">
                    <h3>Attendance Today</h3>
                    <p><?php echo $attendance_today; ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>