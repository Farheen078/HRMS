<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

// Handle approve/reject actions
if(isset($_POST['action']) && isset($_POST['leave_id'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action']; // 'Approved' or 'Rejected'

    $sql = "UPDATE leave_requests SET status='$action' WHERE id='$leave_id'";
    if($conn->query($sql)) {
        $message = "Leave request updated successfully!";
    } else {
        $message = "Error updating leave request!";
    }
}

// Fetch all leave requests with employee names
$sql = "SELECT lr.*, u.fullname, e.emp_code 
        FROM leave_requests lr 
        JOIN employees e ON lr.employee_id = e.id 
        JOIN users u ON e.user_id = u.id 
        ORDER BY lr.applied_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests</title>
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
        
        .leave-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .leave-table th, 
        .leave-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .leave-table th {
            background: #2c3e50;
            color: white;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        
        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            margin: 2px;
        }
        
        .btn-approve {
            background: #27ae60;
        }
        
        .btn-reject {
            background: #e74c3c;
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
        
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
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
        
        .action-forms {
            display: flex;
            gap: 5px;
        }
        
        .action-forms form {
            margin: 0;
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
                        <li><a href="view_leaves.php" style="background: #1abc9c; color: white;">📄 View Leave Requests</a></li>
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
                    <h1><i class="fas fa-tasks"></i> Manage Leave Requests</h1>
                </div>

                <div class="content-container">
                    <?php if(isset($message)): ?>
                        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($result->num_rows > 0): ?>
                        <table class="leave-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Emp Code</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['fullname']; ?></td>
                                        <td><?php echo $row['emp_code']; ?></td>
                                        <td><?php echo $row['start_date']; ?></td>
                                        <td><?php echo $row['end_date']; ?></td>
                                        <td><?php echo $row['reason']; ?></td>
                                        <td class="status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </td>
                                        <td><?php echo $row['applied_at']; ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <div class="action-forms">
                                                    <form method="POST">
                                                        <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="action" value="Approved">
                                                        <button type="submit" class="btn btn-approve">Approve</button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="action" value="Rejected">
                                                        <button type="submit" class="btn btn-reject">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #7f8c8d;">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-times" style="font-size: 48px;"></i>
                            <h3>No Leave Requests Found</h3>
                            <p>There are no leave requests to display.</p>
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