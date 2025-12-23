<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

// Fetch all employees with user and department details
$sql = "SELECT e.*, u.fullname, u.email, u.phone, u.created_at, u.is_approved, d.name as department_name
        FROM employees e 
        JOIN users u ON e.user_id = u.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Employees</title>
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Dropdown menu styles - SAME as admin_dashboard.php */
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
        
        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .employee-table th, 
        .employee-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .employee-table th {
            background: #2c3e50;
            color: white;
        }
        
        .employee-count {
            margin: 15px 0;
            color: #34495e;
            font-weight: bold;
            font-size: 16px;
        }
        
        .edit-btn {
            background: #27ae60;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
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
        
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
    <script>
        function confirmDelete(empId, empName) {
            if (confirm('Are you sure you want to delete ' + empName + '?')) {
                window.location.href = 'edit_employee.php?delete_emp=' + empId;
            }
        }
    </script>
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
                        <li><a href="view_employee.php" style="background: #1abc9c; color: white;">👥 View Employee</a></li>
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
                        <li><a href="attendance_report.php">📊 View Attendance Report</a></li>
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
                    <h1><i class="fas fa-users"></i> View All Employees</h1>
                </div>

                <div class="content-container">
                    <div class="employee-count">
                        Total Employees: <?php echo $result->num_rows; ?>
                    </div>

                    <?php if($result->num_rows > 0): ?>
                        <table class="employee-table">
                            <thead>
                                <tr>
                                    <th>Emp Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($employee = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $employee['emp_code']; ?></strong></td>
                                        <td><?php echo $employee['fullname']; ?></td>
                                        <td><?php echo $employee['email']; ?></td>
                                        <td><?php echo $employee['phone']; ?></td>
                                        <td><?php echo $employee['department_name'] ?: 'N/A'; ?></td>
                                        <td><?php echo $employee['designation'] ?:'N/A'; ?></td>
                                        <td>Rs.<?php echo number_format($employee['salary'], 2)?:'N/A'; ?></td>
                                        <td class="<?php echo $employee['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                            <?php echo $employee['is_approved'] ? 'Approved' : 'Pending'; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_employee.php?emp_id=<?php echo $employee['id']; ?>" class="edit-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="#" onclick="confirmDelete(<?php echo $employee['id']; ?>, '<?php echo $employee['fullname']; ?>')" class="delete-btn">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-users" style="font-size: 48px;"></i>
                            <h3>No Employees Found</h3>
                            <p>No employees have been added yet.</p>
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
</body>
</html>