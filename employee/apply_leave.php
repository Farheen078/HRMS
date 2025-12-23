<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

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
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page specific styles */
        .page-wrapper {
            max-width: 600px;
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
        
        .submit-btn {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: 0.3s;
        }
        
        .submit-btn:hover {
            background: #2980b9;
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
            transition: 0.3s;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
        }
        
        /* Main content adjustment */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            background: #f8f9fb;
            min-height: 100vh;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .date-inputs {
                grid-template-columns: 1fr;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
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
                    <li><a href="apply_leave.php" style="background: #00d084; color: #fff;"><i class="fa-solid fa-calendar-plus"></i> Apply for Leave</a></li>
                    <li><a href="view_my_leaves.php"><i class="fa-solid fa-calendar-check"></i> My Leaves</a></li>
                    <li><a href="view_attendance.php"><i class="fa-solid fa-clock"></i> Attendance</a></li>
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
                    <h1><i class="fas fa-calendar-plus"></i> Apply for Leave</h1>
                </div>

                <div class="content-container">
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
                            <div class="date-inputs">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" required>
                                </div>

                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" id="end_date" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reason">Reason for Leave</label>
                                <textarea name="reason" id="reason" rows="4" placeholder="Enter reason for leave..." required></textarea>
                            </div>

                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane"></i> Submit Leave Application
                            </button>
                        </form>
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

    <script>
        // Set minimum date to today for start date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        document.getElementById('end_date').min = today;
        
        // Ensure end date is not before start date
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>