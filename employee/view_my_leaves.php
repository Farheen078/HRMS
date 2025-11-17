<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$leave_result = null;

// Get employee ID with proper error handling
$sql = "SELECT e.id FROM employees e WHERE e.user_id='$user_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $employee_id = $employee['id'];
    
    // Fetch leave requests for this employee
    $sql = "SELECT * FROM leave_requests WHERE employee_id='$employee_id' ORDER BY applied_at DESC";
    $leave_result = $conn->query($sql);
} else {
    $error = "Employee profile not found! Please contact admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave Applications</title>
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
        .error {
             background: #f8d7da;
              color: #721c24; 
              padding: 15px; 
              border-radius: 5px; 
              margin: 20px 0; 
            }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> My Leave Applications</h1>
        </div>

        <div class="content">
            <?php if(!empty($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(empty($error)): ?>
                <?php if($leave_result && $leave_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $leave_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['start_date']; ?></td>
                                    <td><?php echo $row['end_date']; ?></td>
                                    <td><?php echo $row['reason']; ?></td>
                                    <td class="status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </td>
                                    <td><?php echo $row['applied_at']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times" style="font-size: 48px;"></i>
                        <h3>No Leave Applications Found</h3>
                        <p>You haven't applied for any leaves yet.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <a href="../employee_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>