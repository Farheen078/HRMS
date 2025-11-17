<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

// Fetch only approved employees
$emp_sql = "SELECT e.id, u.fullname, e.emp_code 
            FROM employees e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.is_approved = 1";
$emp_result = $conn->query($emp_sql);

$success = "";
$error = "";
$current_date = date('Y-m-d');

// Check if attendance already exists for today
$check_attendance_sql = "SELECT COUNT(*) as count FROM attendance WHERE date = '$current_date'";
$check_result = $conn->query($check_attendance_sql);
$attendance_exists = ($check_result->fetch_assoc()['count'] > 0);

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $date = $_POST['date'];
    
    // Check if attendance already exists for this date
    $check_date_sql = "SELECT COUNT(*) as count FROM attendance WHERE date = '$date'";
    $date_attendance_count = $conn->query($check_date_sql)->fetch_assoc()['count'];
    
    if($date_attendance_count > 0) {
        $error = "Attendance for this date already exists!";
    } else {
        if(isset($_POST['attendance']) && is_array($_POST['attendance'])) {
            $marked_count = 0;
            foreach($_POST['attendance'] as $emp_id => $status){
                $insert_sql = "INSERT INTO attendance (employee_id, date, status) VALUES ('$emp_id','$date','$status')";
                if($conn->query($insert_sql)) {
                    $marked_count++;
                }
            }
            if($marked_count > 0) {
                $success = "Attendance submitted successfully!";
                $attendance_exists = true;
            } else {
                $error = "Failed to mark attendance!";
            }
        } else {
            $error = "No employees selected!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
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
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #2c3e50;
            color: #fff;
        }
        .message {
            padding: 12px;
            margin: 15px 0;
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
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            margin-right: 10px;
        }
        input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .radio-group {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> Mark Attendance</h1>
        </div>

        <div class="content">
            <?php if(!empty($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" value="<?php echo $current_date; ?>" required 
                           <?php echo $attendance_exists ? 'disabled' : ''; ?>>
                </div>
                
                <?php if($emp_result && $emp_result->num_rows > 0): ?>
                    <?php if($attendance_exists): ?>
                        <div class="message error">Attendance already submitted for today.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Half Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $emp_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['fullname']; ?> (<?php echo $row['emp_code']; ?>)</td>
                                        <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="present" checked required></td>
                                        <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="absent"></td>
                                        <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="half_day"></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <button type="submit" class="btn btn-success">
                                Submit Attendance
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        No approved employees found.
                    </div>
                <?php endif; ?>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="../admin_dashboard.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>