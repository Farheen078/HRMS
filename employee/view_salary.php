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
            }
        .btn-download { 
            background: #3498db; 
        }
        .btn-disabled {
             background: #95a5a6; 
             cursor: not-allowed; 
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
            <h1><i class="fas fa-money-bill-wave"></i> My Salary Slips</h1>
        </div>

        <div class="content">
            <?php if($payslips_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Total Salary</th>
                            <th>Deduction</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Payslip</th>
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
                                            <i class="fas fa-download"></i> Download PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-disabled">PDF Not Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 48px;"></i>
                    <h3>No Salary Records Found</h3>
                    <p>Your paid salary slips will appear here once they are processed by admin.</p>
                </div>
            <?php endif; ?>

            <a href="../employee_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>