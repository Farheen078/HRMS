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
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            background: #f8f9fb;
            min-height: 100vh;
        }
        
        /* Profile Page Styles */
        .page-wrapper {
            max-width: 1000px;
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
        
        /* Dashboard Cards Layout */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .card-title {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .card-value {
            font-size: 20px;
            font-weight: bold;
            color: #34495e;
        }
        
        /* Profile Header */
        .profile-header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
            border: 3px solid white;
        }
        
        .profile-name {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .profile-role {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .profile-code {
            font-size: 14px;
            margin-top: 10px;
            opacity: 0.8;
        }
        
        /* Pending Message */
        .pending-message {
            background: #fff3cd;
            color: #856404;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .content-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Employee Sidebar - EXACTLY SAME AS ATTENDANCE PAGE -->
        <nav class="sidebar">
            <div>
                <h2>Employee Panel</h2>
                <ul>
                    <li><a href="../employee_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li><a href="view_profile.php" style="background: #00d084; color: #fff;"><i class="fa-solid fa-user"></i> View My Profile</a></li>
                    <li><a href="apply_leave.php"><i class="fa-solid fa-calendar-plus"></i> Apply for Leave</a></li>
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
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                </div>

                <div class="content-container">
                    <?php if($profile_setup): ?>
                        <!-- Profile Header Section -->
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-name"><?php echo $profile['fullname']; ?></div>
                            <div class="profile-role"><?php echo $profile['designation']; ?> | <?php echo $profile['department_name']; ?></div>
                            <div class="profile-code">Employee Code: <?php echo $profile['emp_code']; ?></div>
                        </div>

                        <!-- Dashboard Cards - 2 cards per row -->
                        <div class="dashboard-cards">
                            <!-- Email Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="card-title">Email Address</div>
                                <div class="card-value"><?php echo $profile['email']; ?></div>
                            </div>
                            
                            <!-- Phone Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="card-title">Phone Number</div>
                                <div class="card-value"><?php echo $profile['phone'] ?: 'Not provided'; ?></div>
                            </div>
                            
                            <!-- Department Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="card-title">Department</div>
                                <div class="card-value"><?php echo $profile['department_name'] ?: 'Not assigned'; ?></div>
                            </div>
                            
                            <!-- Designation Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="card-title">Designation</div>
                                <div class="card-value"><?php echo $profile['designation']; ?></div>
                            </div>
                            
                            <!-- Salary Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="card-title">Monthly Salary</div>
                                <div class="card-value">Rs.<?php echo number_format($profile['salary'], 2); ?></div>
                            </div>
                            
                            <!-- Date of Join Card -->
                            <div class="dashboard-card">
                                <div class="card-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="card-title">Date of Joining</div>
                                <div class="card-value"><?php echo $profile['date_of_join']; ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Pending Setup Message -->
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

                    <!-- Back Button -->
                    <div style="text-align: center;">
                        <a href="../employee_dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>