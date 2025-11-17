<?php
session_start();
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    // Role is automatically set to 'employee'

    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Store password as plain text, set role to employee, and is_approved to 0 (pending)
        $sql = "INSERT INTO users (fullname, email, phone, password, role, is_approved)
                VALUES ('$fullname','$email','$phone','$password','employee', 0)";
        
        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            
            // Create employee record
            $emp_code = 'EMP' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
            $emp_sql = "INSERT INTO employees (user_id, emp_code, designation, date_of_join, salary)
                       VALUES ('$user_id', '$emp_code', 'Employee', CURDATE(), 0)";
            $conn->query($emp_sql);
            
            $success = "Your information has been saved successfully! Please wait for admin approval before you can login.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Sign Up - HRMS</title>
    <style>
        * {
         margin:0; padding:0;
         box-sizing:border-box; 
         font-family: Arial, sans-serif; 
         }
        body { 
        background: #667eea; 
        height: 100vh;
         display: flex;
          justify-content: center; 
          align-items: center; }
        .signup-container { 
        background: white; 
        padding: 40px;
         border-radius: 10px; 
         box-shadow: 0 0 20px rgba(0,0,0,0.1);
          width: 450px;
           }
        .signup-form h1 {
             text-align: center; 
             margin-bottom: 30px;
              color: #333; 
            }
        .input-group {
             margin-bottom: 20px;
             }
        .input-group label {
             display: block;
              margin-bottom: 8px;
               color: #555;
                font-weight: bold;
             }
        .input-group input {
             width: 100%;
              padding: 12px;
               border: 1px solid #ddd; 
               border-radius: 5px; 
               font-size: 16px; 
            }
        .signup-form button {
             width: 100%;
              padding: 12px; 
              border: none;
               border-radius: 5px;
                background: #667eea;
                 color: white;
                  cursor: pointer; 
                  font-size: 16px; 
                  font-weight: bold; 
                }

        .error-msg { 
            color: #e74c3c; 
            text-align: center; 
            margin-bottom: 15px; 
            padding: 10px;
             background: #ffeaea; 
             border-radius: 5px;
             }
        .success-msg { 
            color: #27ae60; 
            text-align: center; 
            margin-bottom: 15px; 
            padding: 10px;
             background: #eafaf1; 
             border-radius: 5px;
             }
        
    </style>
</head>
<body>
    <div class="signup-container">
        <form action="" method="POST" class="signup-form">
            <h1>Create Employee Account</h1>
            
            <?php if(isset($error)): ?>
                <div class='error-msg'><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class='success-msg'><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(!isset($success)): ?>
            <div class="input-group">
                <label for="fullname">Full Name</label>
                <input type="text" name="fullname" id="fullname" required>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="input-group">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit">Sign Up as Employee</button>

            <p style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
            <?php else: ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Go to Login</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>