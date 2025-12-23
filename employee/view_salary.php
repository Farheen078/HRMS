<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get employee ID
$sql = "SELECT e.id FROM employees e WHERE e.user_id='$user_id'";
$result = $conn->query($sql);
$employee = $result->fetch_assoc();
$employee_id = $employee['id'];

// Fetch ONLY PAID payslips for this employee
$payslips_sql = "SELECT * FROM payslips WHERE employee_id = '$employee_id' AND status = 'Paid' ORDER BY year DESC, month DESC";
$payslips_result = $conn->query($payslips_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Salary Slips</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page specific styles */
        .page-wrapper {
            max-width: 1000px;
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
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .salary-table th,
        .salary-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .salary-table th {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .status-paid {
            color: #27ae60;
            font-weight: bold;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-download {
            background: #3498db;
            transition: 0.3s;
        }
        
        .btn-download:hover {
            background: #2980b9;
        }
        
        .btn-disabled {
            background: #95a5a6;
            cursor: not-allowed;
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
        
        /* Column widths */
        .month-column {
            width: 100px;
        }
        
        .year-column {
            width: 80px;
        }
        
        .salary-column {
            width: 120px;
        }
        
        .action-column {
            width: 140px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .salary-table {
                font-size: 14px;
            }
            
            .salary-table th,
            .salary-table td {
                padding: 10px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Employee Sidebar - EXACTLY SAME AS ATTENDANCE PAGE -->
        <nav class="sidebar">
            <div>
                <h2>Employee Panel</h2>
                <ul>
                    <li><a href="../employee_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li><a href="view_profile.php"><i class="fa-solid fa-user"></i> View My Profile</a></li>
                    <li><a href="apply_leave.php"><i class="fa-solid fa-calendar-plus"></i> Apply for Leave</a></li>
                    <li><a href="view_my_leaves.php"><i class="fa-solid fa-calendar-check"></i> My Leaves</a></li>
                    <li><a href="view_attendance.php"><i class="fa-solid fa-clock"></i> Attendance</a></li>
                    <li><a href="view_salary.php" style="background: #00d084; color: #fff;"><i class="fa-solid fa-money-bill"></i> Salary Slip</a></li>
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
                    <h1><i class="fas fa-money-bill-wave"></i> My Salary Slips</h1>
                </div>

                <div class="content-container">
                    <?php if($payslips_result->num_rows > 0): ?>
                        <table class="salary-table">
                            <thead>
                                <tr>
                                    <th class="month-column">Month</th>
                                    <th class="year-column">Year</th>
                                    <th class="salary-column">Total Salary</th>
                                    <th class="salary-column">Deduction</th>
                                    <th class="salary-column">Net Salary</th>
                                    <th class="status-column">Status</th>
                                    <th class="action-column">Payslip</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($payslip = $payslips_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $payslip['month']; ?></td>
                                        <td><?php echo $payslip['year']; ?></td>
                                        <td>Rs.<?php echo number_format($payslip['total_salary'], 2); ?></td>
                                        <td>Rs.<?php echo number_format($payslip['deduction'], 2); ?></td>
                                        <td>Rs.<?php echo number_format($payslip['net_salary'], 2); ?></td>
                                        <td class="status-paid">
                                            <?php echo $payslip['status']; ?>
                                        </td>
                                        <td>
                                            <?php if($payslip['pdf_path']): ?>
                                                <a href="../payslips/<?php echo $payslip['pdf_path']; ?>" 
                                                   class="btn btn-download" 
                                                   target="_blank"
                                                   download="payslip_<?php echo $payslip['month'] . '_' . $payslip['year']; ?>.pdf">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-disabled">PDF Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div class="records-count">
                            <p>Total Payslips: <?php echo $payslips_result->num_rows; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 48px;"></i>
                            <h3>No Salary Records Found</h3>
                            <p>Your paid salary slips will appear here once they are processed by admin.</p>
                        </div>
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