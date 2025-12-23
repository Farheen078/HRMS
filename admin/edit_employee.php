<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

$message = "";

// Handle employee deletion
if(isset($_GET['delete_emp'])) {
    $emp_id = $_GET['delete_emp'];
    
    // Get user_id from employee record
    $get_user_sql = "SELECT user_id FROM employees WHERE id = '$emp_id'";
    $user_result = $conn->query($get_user_sql);
    
    if($user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_id_to_delete = $user_row['user_id'];
        
        // Delete employee record
        $delete_emp_sql = "DELETE FROM employees WHERE id = '$emp_id'";
        // Delete user record
        $delete_user_sql = "DELETE FROM users WHERE id = '$user_id_to_delete'";
        
        if($conn->query($delete_emp_sql) && $conn->query($delete_user_sql)) {
            $_SESSION['message'] = "Employee deleted successfully!";
            header("Location: view_employee.php");
            exit();
        } else {
            $message = "Error deleting employee: " . $conn->error;
        }
    }
}

// Check if editing existing employee
$editing = false;
$employee_data = null;

if(isset($_GET['emp_id'])) {
    $emp_id = $_GET['emp_id'];
    $editing = true;
    
    // Fetch employee data
    $sql = "SELECT e.*, u.fullname, u.email, u.phone, u.is_approved, d.name as department_name
            FROM employees e 
            JOIN users u ON e.user_id = u.id 
            LEFT JOIN departments d ON e.department_id = d.id 
            WHERE e.id = '$emp_id'";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        $employee_data = $result->fetch_assoc();
        
        // Check if all required fields are filled
        $salary_assigned = !empty($employee_data['salary']) && $employee_data['salary'] > 0;
        $designation_assigned = !empty($employee_data['designation']) && trim($employee_data['designation']) != '';
        $department_assigned = !empty($employee_data['department_id']);
        $can_be_approved = $salary_assigned && $designation_assigned && $department_assigned;
    } else {
        $message = "Employee not found!";
    }
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_id = $_POST['emp_id'];
    $department_id = $_POST['department_id'];
    $designation = $_POST['designation'];
    $date_of_join = $_POST['date_of_join'];
    $salary = $_POST['salary'];
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;
    
    // Check if trying to approve without all fields filled
    $salary_assigned = !empty($salary) && $salary > 0;
    $designation_assigned = !empty($designation) && trim($designation) != '';
    $department_assigned = !empty($department_id);
    $can_be_approved = $salary_assigned && $designation_assigned && $department_assigned;
    
    if($is_approved == 1 && !$can_be_approved) {
        $message = "Cannot approve employee. Please assign Department, Designation, and Salary first.";
    } else {
        // Update employee record
        $update_sql = "UPDATE employees SET 
                      department_id = '$department_id', 
                      designation = '$designation', 
                      date_of_join = '$date_of_join', 
                      salary = '$salary' 
                      WHERE id = '$emp_id'";
        
        if($conn->query($update_sql)) {
            // Update user approval status
            $user_id = $_POST['user_id'];
            $update_user_sql = "UPDATE users SET is_approved = '$is_approved' WHERE id = '$user_id'";
            $conn->query($update_user_sql);
            
            $_SESSION['message'] = "Employee updated successfully!";
            header("Location: view_employee.php");
            exit();
        } else {
            $message = "Error updating employee: " . $conn->error;
        }
    }
}

// Fetch departments
$dept_sql = "SELECT * FROM departments";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editing ? 'Edit Employee' : 'Add Employee'; ?></title>
    <link rel="stylesheet" href="../css/admindash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* SAME dropdown menu styles as admin_dashboard.php */
        .side-bar ul li {
            position: relative;
        }
        .side-bar ul li ul {
            display: none;
            list-style: none;
            padding-left: 20px;
            background: #2f323a;
            position: absolute;
            left: 100%;
            top: 0;
            width: 200px;
            z-index: 1000;
        }
        .side-bar ul li:hover ul {
            display: block;
        }
        .side-bar ul li ul li {
            padding: 10px;
        }
        .side-bar ul li ul li a {
            font-size: 14px;
        }
        
        /* Page specific styles */
        .page-wrapper {
            max-width: 800px;
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
            color: #2c3e50;
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
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-disabled {
            background: #95a5a6;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .message {
            margin: 15px 0;
            padding: 12px;
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
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .checkbox-group input {
            width: auto;
            transform: scale(1.3);
        }
        
        .checkbox-group input:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .user-info h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .readonly-field {
            background: #e9ecef !important;
            color: #495057;
            cursor: not-allowed;
        }
        
        /* Main content adjustment */
        .main-content {
            padding: 25px;
            background: #f4f6f9;
            min-height: calc(100vh - 60px);
        }
         .main-content h1{
            color:white;

        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .section-title {
            margin: 25px 0 15px 0;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <!-- EXACT SAME HEADER as admin_dashboard.php -->
    <header class="header">
        <h2 class="u-name">Admin Dashboard</h2>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- EXACT SAME CONTAINER STRUCTURE -->
    <div class="container">
        <!-- EXACT SAME SIDEBAR as admin_dashboard.php -->
        <nav class="side-bar">
            <div class="user-p">
                <img src="../img/user.jpeg" alt="User">
                <h4><?php echo $_SESSION['fullname']; ?></h4>
                <span>(Admin)</span>
            </div>
            <ul>
                <li><a href="../admin_dashboard.php"><i class="fa fa-desktop"></i><span>Dashboard</span></a></li>
                <li>
                    <a href="#"><i class="fa fa-users"></i><span>Manage Employee</span></a>
                    <ul>
                        <li><a href="view_employee.php">👥 View Employee</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-calendar"></i><span>Manage Leaves</span></a>
                    <ul>
                        <li><a href="view_leaves.php">📄 View Leave Requests</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-check-square"></i><span>Manage Attendance</span></a>
                    <ul>
                        <li><a href="mark_attendance.php">📝 Mark Daily Attendance</a></li>
                        <li><a href="attendance_report.php">📊 View Attendance Report</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fa fa-file-invoice"></i><span>Manage Payslip</span></a>
                    <ul>
                        <li><a href="calculate_salary.php">💰 Calculate Salary</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="page-wrapper">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-user-edit"></i> 
                        <?php echo $editing ? 'Edit Employee' : 'Add New Employee'; ?>
                        <?php if($editing && $employee_data): ?>
                            : <?php echo $employee_data['fullname']; ?>
                        <?php endif; ?>
                    </h1>
                </div>

                <div class="content-container">
                    <?php if(!empty($message)): ?>
                        <div class="message error"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                    <?php endif; ?>

                    <?php if($editing && $employee_data): ?>
                        <form method="POST">
                            <input type="hidden" name="emp_id" value="<?php echo $employee_data['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $employee_data['user_id']; ?>">

                            <!-- User Basic Information -->
                            <div class="user-info">
                                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" value="<?php echo $employee_data['fullname']; ?>" class="readonly-field" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" value="<?php echo $employee_data['email']; ?>" class="readonly-field" readonly>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" value="<?php echo $employee_data['phone']; ?>" class="readonly-field" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Employee Code</label>
                                        <input type="text" value="<?php echo $employee_data['emp_code']; ?>" class="readonly-field" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Details -->
                            <h3 class="section-title"><i class="fas fa-briefcase"></i> Employment Details</h3>
                            
                            <?php if(!$can_be_approved): ?>
                                <div class="warning-box">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Complete all required fields below to enable employee approval.</strong>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="required-field">Department</label>
                                    <select name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php 
                                        // Reset pointer
                                        $dept_result->data_seek(0);
                                        while($dept = $dept_result->fetch_assoc()): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo ($employee_data['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo $dept['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="required-field">Designation</label>
                                    <input type="text" name="designation" value="<?php echo $employee_data['designation']; ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date of Join</label>
                                    <input type="date" name="date_of_join" value="<?php echo $employee_data['date_of_join']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="required-field">Salary</label>
                                    <input type="number" name="salary" step="0.01" value="<?php echo $employee_data['salary']; ?>" required>
                                </div>
                            </div>

                            <div class="checkbox-group">
                                <?php if($can_be_approved): ?>
                                    <input type="checkbox" name="is_approved" id="is_approved" value="1" 
                                        <?php echo $employee_data['is_approved'] ? 'checked' : ''; ?>>
                                    <label for="is_approved" style="margin-bottom: 0; font-weight: bold;">
                                        Approve this employee (allow login)
                                    </label>
                                <?php else: ?>
                                    <input type="checkbox" name="is_approved" id="is_approved" value="1" disabled>
                                    <label for="is_approved" style="margin-bottom: 0; font-weight: bold; color: #95a5a6;">
                                        <i class="fas fa-lock"></i> Approval locked - Complete all fields above
                                    </label>
                                <?php endif; ?>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Update Employee
                                </button>
                                <a href="view_employee.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Employee List
                                </a>
                                <a href="edit_employee.php?delete_emp=<?php echo $employee_data['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete <?php echo $employee_data['fullname']; ?>?')">
                                    <i class="fas fa-trash"></i> Delete Employee
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="message error">
                            Employee not found or invalid employee ID.
                        </div>
                        <div class="action-buttons">
                            <a href="view_employee.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Employee List
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set default date to today if empty
        <?php if($editing && $employee_data && empty($employee_data['date_of_join'])): ?>
            document.querySelector('input[name="date_of_join"]').valueAsDate = new Date();
        <?php endif; ?>
        
        // Enable/disable approval checkbox based on required fields
        document.addEventListener('DOMContentLoaded', function() {
            const departmentSelect = document.querySelector('select[name="department_id"]');
            const designationInput = document.querySelector('input[name="designation"]');
            const salaryInput = document.querySelector('input[name="salary"]');
            const approvalCheckbox = document.querySelector('input[name="is_approved"]');
            
            function checkFields() {
                const departmentFilled = departmentSelect.value.trim() !== '';
                const designationFilled = designationInput.value.trim() !== '';
                const salaryFilled = salaryInput.value.trim() !== '' && parseFloat(salaryInput.value) > 0;
                
                if (approvalCheckbox) {
                    if (departmentFilled && designationFilled && salaryFilled) {
                        approvalCheckbox.disabled = false;
                    } else {
                        approvalCheckbox.disabled = true;
                        approvalCheckbox.checked = false;
                    }
                }
            }
            
            if (departmentSelect && designationInput && salaryInput) {
                departmentSelect.addEventListener('change', checkFields);
                designationInput.addEventListener('input', checkFields);
                salaryInput.addEventListener('input', checkFields);
                
                // Initial check
                checkFields();
            }
        });
    </script>
</body>
</html>