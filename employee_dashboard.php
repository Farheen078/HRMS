<?php
session_start();
include "connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "employee"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if employee profile is set up
$sql = "SELECT e.id FROM employees e WHERE e.user_id='$user_id'";
$result = $conn->query($sql);
$profile_setup = $result->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<div class="dashboard-container">
  <!-- Sidebar -->
  <nav class="sidebar">
    <div>
      <h2>Employee Panel</h2>
      <ul>
        <li><a href="employee_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>

        <?php if($profile_setup): ?>
          <li><a href="employee/view_profile.php"><i class="fa-solid fa-user"></i> View My Profile</a></li>
          <li><a href="employee/apply_leave.php"><i class="fa-solid fa-calendar-plus"></i> Apply for Leave</a></li>
          <li><a href="employee/view_my_leaves.php"><i class="fa-solid fa-calendar-check"></i> My Leaves</a></li>
          <li><a href="employee/view_attendance.php"><i class="fa-solid fa-clock"></i> Attendance</a></li>
          <li><a href="employee/view_salary.php"><i class="fa-solid fa-money-bill"></i> Salary Slip</a></li>
        <?php else: ?>
          <li><a href="#" style="color:#ffb703;"><i class="fa-solid fa-hourglass-half"></i> Profile Pending</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="logout">
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </nav>

  <!-- Main content -->
  <main class="main-content">
    <header>
      <h1>Welcome, <?php echo $_SESSION['fullname']; ?> 👋</h1>
      <?php if($profile_setup): ?>
        <p>Manage your profile, leave requests, attendance, and salary information all in one place.</p>
      <?php else: ?>
        <p>Your account has been created successfully! Please wait for the admin to complete your profile setup.</p>
      <?php endif; ?>
    </header>

    <?php if(!$profile_setup): ?>
      <div class="pending-message">
        <h3><i class="fa-solid fa-circle-info"></i> Profile Setup Pending</h3>
        <p>Your employee profile is being prepared by the administrator. Once completed, you’ll be able to:</p>
        <ul>
          <li>Access your personal details and salary info</li>
          <li>Apply for leaves</li>
          <li>View attendance records</li>
          <li>Download salary slips</li>
        </ul>
        <p><strong>Check back later or contact HR for updates.</strong></p>
      </div>
    <?php else: ?>
      <section class="content">
        <div class="card">
          <h3><i class="fa-solid fa-user-circle"></i> Profile</h3>
          <p>Click “View My Profile” to view your personal info, designation, and salary details.</p>
        </div>

        <div class="card">
          <h3><i class="fa-solid fa-calendar-check"></i> Leave Management</h3>
          <p>Apply for leaves easily and track your requests in real time.</p>
        </div>

        <div class="card">
          <h3><i class="fa-solid fa-clock-rotate-left"></i> Attendance</h3>
          <p>Monitor your daily attendance records and working hours.</p>
        </div>

        <div class="card">
          <h3><i class="fa-solid fa-money-check-dollar"></i> Salary</h3>
          <p>View or download your monthly salary slips securely.</p>
        </div>
      </section>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
