<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle mark as paid
if(isset($_GET['mark_paid']) && isset($_GET['payslip_id'])) {
    $payslip_id = $_GET['payslip_id'];
    $update_sql = "UPDATE payslips SET status = 'Paid' WHERE id = '$payslip_id'";
    
    if($conn->query($update_sql)) {
        $message = "Salary marked as paid successfully!";
    } else {
        $message = "Error updating salary status!";
    }
}

// Fetch unpaid payslips
$payslips_sql = "SELECT p.*, u.fullname, e.emp_code 
                 FROM payslips p 
                 JOIN employees e ON p.employee_id = e.id 
                 JOIN users u ON e.user_id = u.id 
                 WHERE p.status = 'Unpaid' 
                 ORDER BY p.year DESC, p.month DESC";
$payslips_result = $conn->query($payslips_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Salary as Paid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { 
            margin: 0;
             padding: 0; 
             box-sizing: border-box; 
             font-family: Arial, sans-serif; 
            }
        body { 
            background: #f4f4f9; 
            padding: 20px; 
        }
        .container {
             max-width: 1000px; 
             margin: 0 auto; 
            }
        .header {
             background: #34495e; 
             color: white; 
             padding: 20px; 
             border-radius: 10px 10px 0 0; 
            }
        .content { 
            background: white; 
            padding: 20px; 
            border-radius: 0 0 10px 10px;
             box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th {
             background: #2c3e50; 
             color: white; 
            }
        .btn {
             padding: 8px 15px; 
             border: none; 
             border-radius: 4px; 
             cursor: pointer; 
             color: white; 
             text-decoration: none; 
            }
        .btn-paid {
             background: #27ae60; 
            }
        .back-btn {
             display: inline-block; 
             margin-top: 20px; 
             padding: 10px 20px; 
             background: #95a5a6; 
             color: white; 
             text-decoration: none; 
             border-radius: 5px; 
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-check"></i> Mark Salary as Paid</h1>
        </div>

        <div class="content">
            <?php if(!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($payslips_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Emp Code</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($payslip = $payslips_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $payslip['fullname']; ?></td>
                                <td><?php echo $payslip['emp_code']; ?></td>
                                <td><?php echo $payslip['month']; ?></td>
                                <td><?php echo $payslip['year']; ?></td>
                                <td>₹<?php echo number_format($payslip['net_salary'], 2); ?></td>
                                <td style="color: #e74c3c; font-weight: bold;"><?php echo $payslip['status']; ?></td>
                                <td>
                                    <a href="mark_salary_paid.php?mark_paid=1&payslip_id=<?php echo $payslip['id']; ?>" 
                                       class="btn btn-paid" 
                                       onclick="return confirm('Mark salary as paid for <?php echo $payslip['fullname']; ?>?')">
                                        <i class="fas fa-check"></i> Mark Paid
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-money-bill-wave" style="font-size: 48px;"></i>
                    <h3>No Unpaid Salaries Found</h3>
                    <p>All salaries are marked as paid or no salary records found.</p>
                </div>
            <?php endif; ?>

            <a href="../admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>