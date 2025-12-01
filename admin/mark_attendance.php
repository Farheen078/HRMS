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

// Check if attendance already exists for today FOR ALL CURRENTLY APPROVED EMPLOYEES
$approved_employees_sql = "SELECT e.id FROM employees e JOIN users u ON e.user_id = u.id WHERE u.is_approved = 1";
$approved_employees_result = $conn->query($approved_employees_sql);

$all_marked = true;
$approved_employee_ids = [];

if($approved_employees_result->num_rows > 0) {
    while($emp = $approved_employees_result->fetch_assoc()) {
        $approved_employee_ids[] = $emp['id'];
        
        // Check if attendance exists for this employee for today
        $check_emp_attendance_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = '{$emp['id']}' AND date = '$current_date'";
        $check_emp_result = $conn->query($check_emp_attendance_sql);
        $emp_attendance_exists = ($check_emp_result->fetch_assoc()['count'] > 0);
        
        if(!$emp_attendance_exists) {
            $all_marked = false;
        }
    }
}

$attendance_exists = $all_marked;

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $date = $_POST['date'];
    
    // Check if attendance already exists for this date FOR THE EMPLOYEES BEING MARKED
    $employees_to_mark = isset($_POST['attendance']) ? array_keys($_POST['attendance']) : [];
    $can_proceed = true;
    $already_marked_employees = [];
    
    if(!empty($employees_to_mark)) {
        foreach($employees_to_mark as $emp_id) {
            $check_emp_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = '$emp_id' AND date = '$date'";
            $check_emp_result = $conn->query($check_emp_sql);
            $emp_exists = ($check_emp_result->fetch_assoc()['count'] > 0);
            
            if($emp_exists) {
                $can_proceed = false;
                // Get employee name for error message
                $emp_name_sql = "SELECT u.fullname FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = '$emp_id'";
                $emp_name_result = $conn->query($emp_name_sql);
                if($emp_name_result->num_rows > 0) {
                    $emp_name = $emp_name_result->fetch_assoc()['fullname'];
                    $already_marked_employees[] = $emp_name;
                }
            }
        }
    }
    
    if(!$can_proceed) {
        $error = "Attendance already exists for this date for: " . implode(", ", $already_marked_employees);
    } else {
        if(isset($_POST['attendance']) && is_array($_POST['attendance'])) {
            $marked_count = 0;
            foreach($_POST['attendance'] as $emp_id => $status){
                // Double check if attendance doesn't already exist for this employee-date combination
                $check_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = '$emp_id' AND date = '$date'";
                $check_result = $conn->query($check_sql);
                $exists = ($check_result->fetch_assoc()['count'] > 0);
                
                if(!$exists) {
                    $insert_sql = "INSERT INTO attendance (employee_id, date, status) VALUES ('$emp_id','$date','$status')";
                    if($conn->query($insert_sql)) {
                        $marked_count++;
                    }
                }
            }
            if($marked_count > 0) {
                $success = "Attendance submitted successfully for $marked_count employees!";
                // Recheck if all approved employees now have attendance
                $all_marked = true;
                foreach($approved_employee_ids as $emp_id) {
                    $check_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = '$emp_id' AND date = '$date'";
                    $check_result = $conn->query($check_sql);
                    $exists = ($check_result->fetch_assoc()['count'] > 0);
                    if(!$exists) {
                        $all_marked = false;
                    }
                }
                $attendance_exists = $all_marked;
            } else {
                $error = "Failed to mark attendance! All selected employees may already have attendance for this date.";
            }
        } else {
            $error = "No employees selected!";
        }
    }
}

// Re-fetch approved employees for display (in case new ones were approved)
$emp_sql = "SELECT e.id, u.fullname, e.emp_code 
            FROM employees e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.is_approved = 1";
$emp_result = $conn->query($emp_sql);
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
            padding: 15px;
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
            padding: 12px 25px;
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
            font-size: 16px;
        }
        input[type="date"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .radio-group {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .employee-name {
            text-align: left;
            padding-left: 20px;
        }
        .already-marked {
            background-color: #f8f9fa;
            opacity: 0.6;
        }
        .already-marked td {
            color: #6c757d;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
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
                    <input type="date" name="date" id="date" value="<?php echo $current_date; ?>" required>
                </div>
                
                <?php if($emp_result && $emp_result->num_rows > 0): ?>
                    <?php if($attendance_exists): ?>
                        <div class="message success">
                            <i class="fas fa-check-circle"></i> 
                            Attendance already submitted for all approved employees today.
                        </div>
                    <?php else: ?>
                        <?php
                        // Get employees who already have attendance for today
                        $marked_employees = [];
                        foreach($approved_employee_ids as $emp_id) {
                            $check_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = '$emp_id' AND date = '$current_date'";
                            $check_result = $conn->query($check_sql);
                            $exists = ($check_result->fetch_assoc()['count'] > 0);
                            if($exists) {
                                $marked_employees[] = $emp_id;
                            }
                        }
                        ?>
                        
                        <?php if(!empty($marked_employees)): ?>
                            <div class="info">
                                <i class="fas fa-info-circle"></i> 
                                Some employees already have attendance marked for today. You can mark attendance for the remaining employees.
                            </div>
                        <?php endif; ?>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th class="employee-name">Employee Name</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Half Day</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset pointer
                                $emp_result->data_seek(0);
                                while($row = $emp_result->fetch_assoc()): 
                                    $already_marked = in_array($row['id'], $marked_employees);
                                ?>
                                    <tr class="<?php echo $already_marked ? 'already-marked' : ''; ?>">
                                        <td class="employee-name">
                                            <?php echo $row['fullname']; ?> (<?php echo $row['emp_code']; ?>)
                                            <?php if($already_marked): ?>
                                                <br><small style="color: #28a745;">✓ Already marked</small>
                                            <?php endif; ?>
                                        </td>
                                        <?php if($already_marked): ?>
                                            <td colspan="3" style="color: #28a745; font-weight: bold;">
                                                Attendance Already Submitted
                                            </td>
                                            <td>
                                                <span style="color: #28a745;">✓ Completed</span>
                                            </td>
                                        <?php else: ?>
                                            <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="present" checked required></td>
                                            <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="absent"></td>
                                            <td><input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="half_day"></td>
                                            <td><span style="color: #dc3545;">Pending</span></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Submit Attendance for Pending Employees
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-users" style="font-size: 48px; color: #bdc3c7; margin-bottom: 15px;"></i>
                        <p>No approved employees found.</p>
                    </div>
                <?php endif; ?>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="../admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>