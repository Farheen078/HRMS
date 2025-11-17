<?php
session_start();
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Debug information
        error_log("Login attempt - Email: $email, User ID: {$user['id']}, Role: {$user['role']}, Approved: {$user['is_approved']}");
        
        // Check password (plain text comparison since we're storing plain text)
        if ($password === $user['password']) {
            // Check if user is approved (for employees)
            if ($user['role'] == 'employee' && $user['is_approved'] == 0) {
                $error = "Your account is pending approval. Please wait for admin approval.";
                error_log("Employee login blocked - User ID: {$user['id']} is not approved");
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];

                // Check if employee has profile setup
                if ($user['role'] == 'employee') {
                    $emp_sql = "SELECT e.*, d.name as department_name FROM employees e 
                               LEFT JOIN departments d ON e.department_id = d.id 
                               WHERE e.user_id='{$user['id']}'";
                    $emp_result = $conn->query($emp_sql);
                    $_SESSION['profile_setup'] = $emp_result->num_rows > 0;
                    
                    if ($emp_result->num_rows > 0) {
                        $emp_data = $emp_result->fetch_assoc();
                        $_SESSION['emp_id'] = $emp_data['id'];
                        $_SESSION['designation'] = $emp_data['designation'];
                        $_SESSION['department'] = $emp_data['department_name'];
                    }
                }

                error_log("Login successful - User ID: {$user['id']}, Role: {$user['role']}");

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: employee_dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Invalid password!";
            error_log("Login failed - Invalid password for email: $email");
        }
    } else {
        $error = "No user found with this email!";
        error_log("Login failed - Email not found: $email");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="body_deg" background="background.jpg">
<div class="container">
    <div class="left_side"></div>
    <div class="right_side">
        <div class="form_deg">
            <form method="POST" class="login_form">
                <div class="title_deg">Login Form</div>
                <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
                <?php if(isset($_SESSION['success'])): ?>
                    <p style='color:green;'><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                <?php endif; ?>

                <div>
                    <label class="email">Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label class="label_deg">Password</label>
                    <input type="password" name="password" required>
                </div>
                <div>
                    <input type="submit" value="Login" class="login_btn">
                </div>
                <p style="color:white;margin-top:10px;">Don't have an account? 
                    <a href="signup.php" style="color:#76b900;">Sign Up</a>
                </p>
            </form>
        </div>
    </div>
</div>
</body>
</html>