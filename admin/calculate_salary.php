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
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    $month_year = $conn->real_escape_string($_POST['month_year']); // format: YYYY-MM

    // basic validations
    if(empty($employee_id) || empty($month_year)){
        $message = "Please select employee and month.";
    } else {
        // parse start and end date for selected month
        $start_date = $month_year . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // fetch employee + paid_salary
        $emp_sql = "SELECT e.*, u.fullname, COALESCE(e.paid_salary, 0) AS paid_salary
                    FROM employees e
                    JOIN users u ON e.user_id = u.id
                    WHERE e.id = ?";
        $stmt = $conn->prepare($emp_sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $emp_result = $stmt->get_result();
        $stmt->close();

        if($emp_result->num_rows == 0) {
            $message = "Employee not found!";
        } else {
            $employee = $emp_result->fetch_assoc();
            $basic_salary = (float)$employee['salary'];
            $paid_salary = (float)$employee['paid_salary'];

            // get last paid payslip date (created_at) for this employee
            $lp_sql = "SELECT MAX(created_at) AS last_paid_at FROM payslips WHERE employee_id = ? AND status = 'Paid'";
            $s2 = $conn->prepare($lp_sql);
            $s2->bind_param("i", $employee_id);
            $s2->execute();
            $r2 = $s2->get_result()->fetch_assoc();
            $s2->close();

            $last_paid_at = $r2['last_paid_at']; // may be null

            // Decide attendance period: from day after last paid (if exists) or from start_date of selected month
            if($last_paid_at) {
                // convert last_paid_at to date (Y-m-d) then +1 day
                $period_start = date('Y-m-d', strtotime($last_paid_at . ' +1 day'));
                // but ensure period_start is not before current month start (we only calculate within selected month)
                if($period_start < $start_date) $period_start = $start_date;
            } else {
                $period_start = $start_date;
            }
            $period_end = $end_date;

            // If period_start > period_end then no new period to calculate
            if(strtotime($period_start) > strtotime($period_end)) {
                // nothing to calculate
                $present_days = $absent_days = $half_days = 0;
                $total_working_days = 0;
                $unmarked_days = 0;
                $total_deduction = 0;
                $salary_for_period = 0;
                $remaining_salary = max(0, $basic_salary - $paid_salary);
                $net_salary = 0;
            } else {
                // calculate total working days (Mon-Fri) within period_start..period_end
                $total_working_days = 0;
                $cd = $period_start;
                while(strtotime($cd) <= strtotime($period_end)) {
                    $dow = date('N', strtotime($cd));
                    if($dow < 6) $total_working_days++;
                    $cd = date('Y-m-d', strtotime($cd . ' +1 day'));
                }

                // fetch attendance only for dates > last_paid_at (or for full month if no last paid)
                $att_sql = "SELECT date, status FROM attendance
                            WHERE employee_id = ?
                              AND date BETWEEN ? AND ?";
                $s3 = $conn->prepare($att_sql);
                $s3->bind_param("iss", $employee_id, $period_start, $period_end);
                $s3->execute();
                $att_res = $s3->get_result();
                $s3->close();

                $present_days = 0; $absent_days = 0; $half_days = 0; $marked_days = 0;
                $marked_dates = [];
                while($row = $att_res->fetch_assoc()){
                    $marked_days++;
                    $marked_dates[] = $row['date'];
                    if($row['status'] === 'present') $present_days++;
                    if($row['status'] === 'absent') $absent_days++;
                    if($row['status'] === 'half_day') $half_days++;
                }

                // unmarked days treated as absent for the period
                $unmarked_days = max(0, $total_working_days - $marked_days);
                $total_absent_days = $absent_days + $unmarked_days;

                // per-day salary computed on basic salary (full monthly) prorated by working days in the period
                $per_day_salary = ($total_working_days > 0) ? ($basic_salary / $total_working_days) : 0;

                // Deductions
                $absent_deduction = $total_absent_days * $per_day_salary;
                $half_day_deduction = $half_days * ($per_day_salary / 2);
                $total_deduction = $absent_deduction + $half_day_deduction;

                // Salary for this period based on actual attendance (earned amount)
                $earned = ($present_days + ($half_days * 0.5)) * $per_day_salary;
                $salary_for_period = max(0, $earned); // earned money for this period

                // remaining salary after previous payments
                $remaining_salary = max(0, $basic_salary - $paid_salary);

                // If there are no new attendance days at all (marked_days == 0 and unmarked_days == total_working_days),
                // but you want net_salary to be 0 when admin already paid for these days:
                // Rule: if marked_days == 0 and $last_paid_at is today or later (meaning we've already paid these days), net = 0.
                // Simpler: If there are NO attendance records in the period and present_days == 0 and half_days == 0 then net_salary = 0
                if($present_days == 0 && $half_days == 0) {
                    // no new presence -> nothing to pay for this period
                    $net_salary = 0;
                } else {
                    // net is min(salary_for_period, remaining_salary)
                    $net_salary = min($salary_for_period, $remaining_salary);
                    if($net_salary < 0) $net_salary = 0;
                }
            }

            // Month name and year for payslip record
            $month_name = date('F', strtotime($start_date));
            $year = date('Y', strtotime($start_date));

            // Insert or update payslip for this employee & month
            $check_sql = "SELECT id FROM payslips WHERE employee_id = ? AND month = ? AND year = ?";
            $s4 = $conn->prepare($check_sql);
            $s4->bind_param("isi", $employee_id, $month_name, $year);
            $s4->execute();
            $cr = $s4->get_result();
            $s4->close();

            if($cr->num_rows == 0) {
                // insert
                $ins_sql = "INSERT INTO payslips (employee_id, month, year, total_salary, deduction, net_salary, status, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, 'Unpaid', NOW())";
                $s5 = $conn->prepare($ins_sql);
              $s5->bind_param("isiddd", $employee_id, $month_name, $year, $basic_salary, $total_deduction, $net_salary);


                // simpler insert using escaped values
                $esc_basic = $basic_salary;
                $esc_ded = isset($total_deduction) ? $total_deduction : 0;
                $esc_net = isset($net_salary) ? $net_salary : 0;
                $insert_sql = "INSERT INTO payslips (employee_id, month, year, total_salary, deduction, net_salary, status, created_at)
                               VALUES ('$employee_id', '$month_name', '$year', '$esc_basic', '$esc_ded', '$esc_net', 'Unpaid', NOW())";
                if($conn->query($insert_sql)){
                    $message = "Salary calculated successfully for " . $employee['fullname'] . "!";
                } else {
                    $message = "Error calculating salary: " . $conn->error;
                }
            } else {
                // update
                $existing = $cr->fetch_assoc();
                $esc_basic = $basic_salary;
                $esc_ded = isset($total_deduction) ? $total_deduction : 0;
                $esc_net = isset($net_salary) ? $net_salary : 0;
                $update_sql = "UPDATE payslips SET total_salary = '$esc_basic', deduction = '$esc_ded', net_salary = '$esc_net' WHERE id = '" . $existing['id'] . "'";
                if($conn->query($update_sql)){
                    $message = "Salary updated successfully for " . $employee['fullname'] . "!";
                } else {
                    $message = "Error updating salary: " . $conn->error;
                }
            }

            // Prepare calculated_data for display
            $calculated_data = [
                'employee_name' => $employee['fullname'],
                'basic_salary' => $basic_salary,
                'paid_salary' => $paid_salary,
                'remaining_salary' => max(0, $basic_salary - $paid_salary),
                'present_days' => isset($present_days) ? $present_days : 0,
                'half_days' => isset($half_days) ? $half_days : 0,
                'absent_days' => isset($absent_days) ? $absent_days : 0,
                'unmarked_days' => isset($unmarked_days) ? $unmarked_days : 0,
                'total_working_days' => isset($total_working_days) ? $total_working_days : 0,
                'total_deduction' => isset($total_deduction) ? $total_deduction : 0,
                'salary_for_period' => isset($salary_for_period) ? $salary_for_period : 0,
                'net_salary' => isset($net_salary) ? $net_salary : 0,
                'month' => date('F Y', strtotime($start_date)),
                'last_paid_at' => $last_paid_at
            ];
        }
    }
}

// Fetch ONLY APPROVED employees for dropdown
$employees_sql = "SELECT e.id, u.fullname, e.emp_code FROM employees e JOIN users u ON e.user_id = u.id WHERE u.is_approved = 1";
$employees_result = $conn->query($employees_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calculate Salary</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* your original styling kept unchanged */
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
.btn { 
    padding: 12px 25px; 
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    font-size: 16px; 
    text-decoration: none; 
    display: inline-block; 
    margin: 5px; 
}
.btn-primary {
     background: #3498db; 
     color: white; 
    }
.btn-success { 
    background: #27ae60; 
    color: white; 
}
.btn-secondary {
     background: #95a5a6; 
     color: white; 
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
.button-group { 
    display: flex;
     gap: 10px;
      margin-top: 20px; 
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

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Calculate Salary
                </button>
            </form>

            <?php if(!empty($calculated_data)): ?>
                <div class="salary-breakdown">
                    <h3>Salary Breakdown for <?php echo $calculated_data['employee_name']; ?> - <?php echo $calculated_data['month']; ?></h3>
                    
                    <?php if($calculated_data['last_paid_at']): ?>
                        <div class="info">
                            <i class="fas fa-info-circle"></i> 
                            Calculating period start: <?php echo date('d M Y', strtotime($calculated_data['last_paid_at'].' +1 day')); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($calculated_data['unmarked_days'] > 0): ?>
                        <div class="warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php echo $calculated_data['unmarked_days']; ?> unmarked days treated as absent.
                        </div>
                    <?php endif; ?>
                    
                    <?php if($calculated_data['paid_salary'] > 0): ?>
                        <div class="info">
                            <i class="fas fa-info-circle"></i> 
                            Already Paid: Rs.<?php echo number_format($calculated_data['paid_salary'], 2); ?> | 
                            Remaining Salary: Rs.<?php echo number_format($calculated_data['remaining_salary'], 2); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="breakdown-item"><span>Working Days in Period:</span><span><?php echo $calculated_data['total_working_days']; ?> days</span></div>
                    <div class="breakdown-item"><span>Present Days:</span><span><?php echo $calculated_data['present_days']; ?> days</span></div>
                    <div class="breakdown-item"><span>Half Days:</span><span><?php echo $calculated_data['half_days']; ?> days</span></div>
                    <div class="breakdown-item"><span>Marked Absent Days:</span><span><?php echo $calculated_data['absent_days']; ?> days</span></div>
                    <?php if($calculated_data['unmarked_days'] > 0): ?>
                        <div class="breakdown-item"><span>Unmarked Days (treated absent):</span><span><?php echo $calculated_data['unmarked_days']; ?> days</span></div>
                    <?php endif; ?>
                    
                    <div class="breakdown-item"><span>Basic Salary:</span><span>Rs.<?php echo number_format($calculated_data['basic_salary'], 2); ?></span></div>
                    <div class="breakdown-item"><span>Total Deduction:</span><span>Rs.<?php echo number_format($calculated_data['total_deduction'], 2); ?></span></div>
                    <div class="breakdown-item"><span>Salary for this period (earned):</span><span>Rs.<?php echo number_format($calculated_data['salary_for_period'], 2); ?></span></div>

                    <div class="breakdown-item breakdown-total" style="background: #d4edda;">
                        <span>Net Payable Salary (this calculation):</span>
                        <span style="color: #155724; font-size: 1.2em;">Rs.<?php echo number_format($calculated_data['net_salary'], 2); ?></span>
                    </div>
                </div>

                <div class="button-group">
                    <a href="../admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    <?php if($calculated_data['net_salary'] > 0): ?>
                        <a href="mark_salary_paid.php?employee_id=<?php echo $employee_id; ?>&month=<?php echo urlencode($calculated_data['month']); ?>" class="btn btn-success"><i class="fas fa-money-check"></i> Mark Salary Paid</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
