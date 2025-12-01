<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-user-edit"></i> 
                <?php echo $editing ? 'Edit Employee' : 'Add New Employee'; ?>
                <?php if($editing && $employee_data): ?>
                    : <?php echo $employee_data['fullname']; ?>
                <?php endif; ?>
            </h1>
        </div>

        <div class="content">
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
                    <h3 style="margin: 25px 0 15px 0; color: #2c3e50;"><i class="fas fa-briefcase"></i> Employment Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" required>
                                <option value="">Select Department</option>
                                <?php while($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($employee_data['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" value="<?php echo $employee_data['designation']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Join</label>
                            <input type="date" name="date_of_join" value="<?php echo $employee_data['date_of_join']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Salary</label>
                            <input type="number" name="salary" step="0.01" value="<?php echo $employee_data['salary']; ?>" required>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="is_approved" id="is_approved" value="1" 
                            <?php echo $employee_data['is_approved'] ? 'checked' : ''; ?>>
                        <label for="is_approved" style="margin-bottom: 0; font-weight: bold;">
                            Approve this employee (allow login)
                        </label>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Employee
                        </button>
                        <a href="view_employee.php" class="btn btn-secondary">
                            <i ></i> Back
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
                <a href="view_employee.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Employee List
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Set default date to today if empty
        document.querySelector('input[name="date_of_join"]').valueAsDate = new Date();
    </script>
</body>
</html>