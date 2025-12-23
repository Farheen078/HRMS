<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

$user_id = $_SESSION['user_id'];
$error = "";
$att_result = null;

$sql = "SELECT e.id FROM employees e WHERE e.user_id='$user_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $emp_id = $employee['id'];
    
    // Get attendance records
    $sql = "SELECT * FROM attendance WHERE employee_id='$emp_id' ORDER BY date DESC";
    $att_result = $conn->query($sql);
} else {
    $error = "Employee profile not found! Please contact admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page specific styles */
        .page-wrapper {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
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
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        
        .attendance-table th {
            background: #2c3e50;
            color: white;
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
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        
        .date-column {
            width: 120px;
        }
        
        .day-column {
            width: 100px;
        }
        
        .status-column {
            width: 100px;
        }
        
        /* Main content adjustment */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            background: #f8f9fb;
            min-height: 100vh;
        }
        
        .records-count {
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
            color: #34495e;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .attendance-table {
                font-size: 14px;
            }
            
            .attendance-table th,
            .attendance-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Employee Sidebar -->
        <nav class="sidebar">
            <div>
                <h2>Employee Panel</h2>
                <ul>
                    <li><a href="../employee_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li><a href="view_profile.php"><i class="fa-solid fa-user"></i> View My Profile</a></li>
                    <li><a href="apply_leave.php"><i class="fa-solid fa-calendar-plus"></i> Apply for Leave</a></li>
                    <li><a href="view_my_leaves.php"><i class="fa-solid fa-calendar-check"></i> My Leaves</a></li>
                    <li><a href="view_attendance.php" style="background: #00d084; color: #fff;"><i class="fa-solid fa-clock"></i> Attendance</a></li>
                    <li><a href="view_salary.php"><i class="fa-solid fa-money-bill"></i> Salary Slip</a></li>
                </ul>
            </div>

            <div class="logout">
                <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-wrapper">
                <div class="page-header">
                    <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
                </div>

                <div class="content-container">
                    <?php if(!empty($error)): ?>
                        <div class="error">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if(empty($error)): ?>
                        <?php if($att_result && $att_result->num_rows > 0): ?>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th class="date-column">Date</th>
                                        <th class="day-column">Day</th>
                                        <th class="status-column">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $att_result->fetch_assoc()): ?>
                                        <tr>
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
                            
                            <div class="records-count">
                                <p><strong>Total Records: <?php echo $att_result->num_rows; ?></strong></p>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px;"></i>
                                <h3>No Attendance Records Found</h3>
                                <p>Your attendance records will appear here once they are marked by the admin.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div style="text-align: center;">
                        <a href="../employee_dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>