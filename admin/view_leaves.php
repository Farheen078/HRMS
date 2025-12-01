<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

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
            max-width: 1200px; 
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Manage Leave Requests</h1>
        </div>

        <div class="content">
            <?php if(isset($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($result->num_rows > 0): ?>
                <table>
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
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="Approved">
                                            <button type="submit" class="btn btn-approve">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="Rejected">
                                            <button type="submit" class="btn btn-reject">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span>No actions</span>
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

            <a href="../admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>