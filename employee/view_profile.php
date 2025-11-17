<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get employee profile with user details
$sql = "SELECT u.*, e.*, d.name as department_name
        FROM users u 
        LEFT JOIN employees e ON u.id = e.user_id 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE u.id = '$user_id'";
$result = $conn->query($sql);
$profile = $result->fetch_assoc();

// Check if profile is set up
$profile_setup = !empty($profile['emp_code']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
        .profile-header {
             text-align: center; 
             margin-bottom: 30px; 
            }
        .profile-avatar{
             width: 120px; 
             height: 120px; 
             border-radius: 50%; 
             background: #3498db; 
             color: white;
              display: flex;
               align-items: center;
                justify-content: center;
                 font-size: 48px; 
                 margin: 0 auto 15px;
                 }
        .profile-info { 
            display: grid;
            grid-template-columns: 1fr 1fr; 
            gap: 20px;
             margin-bottom: 30px;
             }
        .info-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px;
             border-left: 4px solid #3498db;
             }
        .info-card h3 { 
            color: #2c3e50; 
            margin-bottom: 10px;
             font-size: 16px;
             }
        .info-card p {
             font-size: 18px;
              font-weight: bold;
               color: #34495e; 
            }
        .pending-message {
             background: #fff3cd; 
             color: #856404; 
             padding: 30px;
              border-radius: 8px;
               text-align: center;
                margin: 20px 0; 
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
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        </div>

        <div class="content">
            <?php if($profile_setup): ?>
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2><?php echo $profile['fullname']; ?></h2>
                    <p><?php echo $profile['designation']; ?> | <?php echo $profile['department_name']; ?></p>
                    <p>Employee Code: <?php echo $profile['emp_code']; ?></p>
                </div>

                <div class="profile-info">
                    <div class="info-card">
                        <h3><i class="fas fa-envelope"></i> Email</h3>
                        <p><?php echo $profile['email']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-phone"></i> Phone</h3>
                        <p><?php echo $profile['phone'] ?: 'Not provided'; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-building"></i> Department</h3>
                        <p><?php echo $profile['department_name'] ?: 'Not assigned'; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-briefcase"></i> Designation</h3>
                        <p><?php echo $profile['designation']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-money-bill-wave"></i> Salary</h3>
                        <p>₹<?php echo number_format($profile['salary'], 2); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-calendar-day"></i> Date of Join</h3>
                        <p><?php echo $profile['date_of_join']; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="pending-message">
                    <i class="fas fa-clock" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h2>Profile Setup Pending</h2>
                    <p>Your employee profile is being set up by the administrator.</p>
                    <p>Once your profile is set up, you will be able to see your:</p>
                    <ul style="text-align: left; display: inline-block; margin: 15px 0;">
                        <li>Employee Code</li>
                        <li>Designation</li>
                        <li>Department</li>
                        <li>Salary Details</li>
                        <li>Date of Joining</li>
                    </ul>
                    <p><strong>Please check back later or contact your administrator.</strong></p>
                </div>
            <?php endif; ?>

            <a href="../employee_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>