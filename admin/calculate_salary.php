<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

$message = "";
$calculated_data = [];

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['employee_id'];
    $month_year = $_POST['month_year']; // Format: YYYY-MM
    
    // Get employee details - Only approved employees
    $emp_sql = "SELECT e.*, u.fullname, d.name as department_name 
                FROM employees e 
                JOIN users u ON e.user_id = u.id 
                LEFT JOIN departments d ON e.department_id = d.id 
                WHERE e.id = '$employee_id' AND u.is_approved = 1"; // Only approved
    $emp_result = $conn->query($emp_sql);
    
    if($emp_result->num_rows == 0) {
        $message = "Employee not found or not approved!";
    } else {
        $employee = $emp_result->fetch_assoc();
        $basic_salary = $employee['salary'];
        
        // Calculate attendance for the month
        $start_date = $month_year . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        $total_days_in_month = date('t', strtotime($start_date));
        
        // Get all dates in the month
        $current_date = $start_date;
        $all_dates = [];
        while ($current_date <= $end_date) {
            $all_dates[] = $current_date;
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        // Get marked attendance
        $att_sql = "SELECT date, status 
                    FROM attendance 
                    WHERE employee_id = '$employee_id' 
                    AND date BETWEEN '$start_date' AND '$end_date'";
        $att_result = $conn->query($att_sql);
        
        $present_days = 0;
        $absent_days = 0;
        $half_days = 0;
        $marked_dates = [];
        
        while($row = $att_result->fetch_assoc()) {
            $marked_dates[] = $row['date'];
            if($row['status'] == 'present') $present_days++;
            if($row['status'] == 'absent') $absent_days++;
            if($row['status'] == 'half_day') $half_days++;
        }
        
        // Calculate unmarked days (treated as absent)
        $unmarked_days = 0;
        foreach ($all_dates as $date) {
            // Skip weekends (Saturday=6, Sunday=7)
            $day_of_week = date('N', strtotime($date));
            if ($day_of_week >= 6) { // 6=Saturday, 7=Sunday
                continue; // Skip weekends
            }
            
            if (!in_array($date, $marked_dates)) {
                $unmarked_days++;
            }
        }
        
        $total_absent_days = $absent_days + $unmarked_days;
        
        // Calculate salary
        $working_days_in_month = 0;
        foreach ($all_dates as $date) {
            $day_of_week = date('N', strtotime($date));
            if ($day_of_week < 6) { // Monday to Friday
                $working_days_in_month++;
            }
        }
        
        if ($working_days_in_month == 0) {
            $per_day_salary = 0;
        } else {
            $per_day_salary = $basic_salary / $working_days_in_month;
        }
        
        $absent_deduction = $total_absent_days * $per_day_salary;
        $half_day_deduction = $half_days * ($per_day_salary / 2);
        $total_deduction = $absent_deduction + $half_day_deduction;
        $net_salary = $basic_salary - $total_deduction;
        
        // Ensure net salary is not negative
        if ($net_salary < 0) {
            $net_salary = 0;
        }
        
        // Check if payslip already exists
        $check_sql = "SELECT id FROM payslips WHERE employee_id = '$employee_id' AND month = '" . date('F', strtotime($start_date)) . "' AND year = '" . date('Y', strtotime($start_date)) . "'";
        $check_result = $conn->query($check_sql);
        
        if($check_result->num_rows == 0) {
            // Insert new payslip
            $insert_sql = "INSERT INTO payslips (employee_id, month, year, total_salary, deduction, net_salary, status) 
                          VALUES ('$employee_id', '" . date('F', strtotime($start_date)) . "', '" . date('Y', strtotime($start_date)) . "', '$basic_salary', '$total_deduction', '$net_salary', 'Unpaid')";
            
            if($conn->query($insert_sql)) {
                $message = "Salary calculated successfully for " . $employee['fullname'] . "!";
            } else {
                $message = "Error calculating salary: " . $conn->error;
            }
        } else {
            $message = "Payslip already exists for this month!";
        }
        
        // Store calculated data for display
        $calculated_data = [
            'employee_name' => $employee['fullname'],
            'basic_salary' => $basic_salary,
            'present_days' => $present_days,
            'absent_days' => $absent_days,
            'half_days' => $half_days,
            'unmarked_days' => $unmarked_days,
            'total_absent_days' => $total_absent_days,
            'working_days_in_month' => $working_days_in_month,
            'absent_deduction' => $absent_deduction,
            'half_day_deduction' => $half_day_deduction,
            'total_deduction' => $total_deduction,
            'net_salary' => $net_salary,
            'month' => date('F Y', strtotime($start_date))
        ];
    }
}

// Fetch only approved employees for dropdown
$employees_sql = "SELECT e.id, u.fullname, e.emp_code 
                  FROM employees e 
                  JOIN users u ON e.user_id = u.id 
                  WHERE u.is_approved = 1"; // Only approved
$employees_result = $conn->query($employees_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculate Salary</title>
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
             max-width: 800px;
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
        input, select { 
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
        .salary-breakdown { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .breakdown-item { 
            display: flex; 
            justify-content: space-between; 
            margin: 10px 0; 
            padding: 10px; 
            border-bottom: 1px solid #dee2e6; 
        }
        .breakdown-total {
             background: #e9ecef; 
             font-weight: bold; 
            }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
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
            <h1><i class="fas fa-calculator"></i> Calculate Salary</h1>
        </div>

        <div class="content">
            <?php if(!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="employee_id">Select Employee</label>
                    <select name="employee_id" id="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php while($emp = $employees_result->fetch_assoc()): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo $emp['fullname'] . ' (' . $emp['emp_code'] . ')'; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="month_year">Select Month</label>
                    <input type="month" name="month_year" id="month_year" value="<?php echo date('Y-m'); ?>" required>
                </div>

                <button type="submit">
                    <i class="fas fa-calculator"></i> Calculate Salary
                </button>
            </form>

            <?php if(!empty($calculated_data)): ?>
                <div class="salary-breakdown">
                    <h3>Salary Breakdown for <?php echo $calculated_data['employee_name']; ?> - <?php echo $calculated_data['month']; ?></h3>
                    
                    <?php if($calculated_data['unmarked_days'] > 0): ?>
                        <div class="warning">
                            <!-- <i class="fas fa-exclamation-triangle"></i>  -->
                            <?php echo $calculated_data['unmarked_days']; ?> unmarked days treated as absent.
                        </div>
                    <?php endif; ?>
                    
                    <div class="info">
                        <i class="fas fa-info-circle"></i> 
                        Total working days in month: <?php echo $calculated_data['working_days_in_month']; ?> days
                    </div>
                    
                    <div class="breakdown-item">
                        <span>Basic Salary:</span>
                        <span>₹<?php echo number_format($calculated_data['basic_salary'], 2); ?></span>
                    </div>
                    
                    <div class="breakdown-item">
                        <span>Present Days:</span>
                        <span><?php echo $calculated_data['present_days']; ?> days</span>
                    </div>
                    
                    <div class="breakdown-item">
                        <span>Half Days:</span>
                        <span><?php echo $calculated_data['half_days']; ?> days (Deduction: ₹<?php echo number_format($calculated_data['half_day_deduction'], 2); ?>)</span>
                    </div>
                    
                    <div class="breakdown-item">
                        <span>Marked Absent Days:</span>
                        <span><?php echo $calculated_data['absent_days']; ?> days</span>
                    </div>
                    
                    <?php if($calculated_data['unmarked_days'] > 0): ?>
                        <div class="breakdown-item">
                            <span>Unmarked Days (Treated as Absent):</span>
                            <span><?php echo $calculated_data['unmarked_days']; ?> days</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="breakdown-item">
                        <span>Total Absent Days:</span>
                        <span><?php echo $calculated_data['total_absent_days']; ?> days (Deduction: ₹<?php echo number_format($calculated_data['absent_deduction'], 2); ?>)</span>
                    </div>
                    
                    <div class="breakdown-item breakdown-total">
                        <span>Total Deduction:</span>
                        <span>₹<?php echo number_format($calculated_data['total_deduction'], 2); ?></span>
                    </div>
                    
                    <div class="breakdown-item breakdown-total" style="background: #d4edda;">
                        <span>Net Salary:</span>
                        <span style="color: #155724; font-size: 1.2em;">₹<?php echo number_format($calculated_data['net_salary'], 2); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <a href="../admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>