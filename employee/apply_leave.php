<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get employee ID with error handling
$sql = "SELECT e.id FROM employees e WHERE e.user_id='$user_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $employee_id = $employee['id'];

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = $_POST['reason'];

        // Insert leave request
        $sql = "INSERT INTO leave_requests (employee_id, start_date, end_date, reason) 
                VALUES ('$employee_id', '$start_date', '$end_date', '$reason')";
        
        if($conn->query($sql)) {
            $message = "Leave application submitted successfully!";
        } else {
            $error = "Error submitting leave application!";
        }
    }
} else {
    $error = "Employee profile not found! Please contact admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave</title>
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
            max-width: 600px; 
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
              padding: 30px; 
              border-radius: 0 0 10px 10px; 
              box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            }
        .form-group {
             margin-bottom: 20px; 
            }
        label {
             display: block; 
             margin-bottom: 8px; 
             font-weight: bold; 
             color: #333; 
            }
        input, textarea, select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px; 
        }
        button {
             background: #3498db; 
             color: white; 
             padding: 15px 30px; 
             border: none; 
             border-radius: 5px; 
             cursor: pointer; 
             font-size: 16px; 
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
        .back-btn {
             display: inline-block; 
             margin-top: 20px; 
             padding: 10px 20px; 
             background: #95a5a6; 
             color: white; 
             text-decoration: none; 
             border-radius: 5px; 
            }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-plus"></i> Apply for Leave</h1>
        </div>

        <div class="content">
            <?php if(!empty($error)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if(empty($error) || !empty($message)): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" required>
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason for Leave</label>
                        <textarea name="reason" id="reason" rows="4" placeholder="Enter reason for leave..." required></textarea>
                    </div>

                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Submit Leave Application
                    </button>
                </form>
            <?php endif; ?>

            <a href="../employee_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>