<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

require_once "../connect.php";

$message = "";
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payslip_id'])) {
    $payslip_id = (int)$_POST['payslip_id'];

    if(!isset($_FILES['payslip_pdf']) || $_FILES['payslip_pdf']['error'] != 0) {
        $message = "No file selected or upload error.";
    } else {
        $file = $_FILES['payslip_pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if($ext !== 'pdf') {
            $message = "Only PDF files are allowed.";
        } else {
            $target_dir = "../payslips/";
            if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $filename = "payslip_" . $payslip_id . "_" . time() . ".pdf";
            $target_file = $target_dir . $filename;

            if(move_uploaded_file($file['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("UPDATE payslips SET pdf_path = ? WHERE id = ?");
                $stmt->bind_param("si", $filename, $payslip_id);
                if($stmt->execute()) {
                    $message = "Payslip PDF uploaded successfully!";
                } else {
                    $message = "Database update failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Error moving uploaded file.";
            }
        }
    }
}

// If payslip_id provided in GET, fetch that single payslip
$payslip_id_get = isset($_GET['payslip_id']) ? (int)$_GET['payslip_id'] : null;

if($payslip_id_get) {
    $payslip_sql = "SELECT p.*, u.fullname, e.emp_code
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    JOIN users u ON e.user_id = u.id
                    WHERE p.id = ? AND p.status = 'Paid'"; // Only paid payslips
    $s = $conn->prepare($payslip_sql);
    $s->bind_param("i", $payslip_id_get);
    $s->execute();
    $p_result = $s->get_result();
    $s->close();
} else {
    // fetch ONLY PAID payslips that need PDF upload
    $p_result = $conn->query("SELECT p.*, u.fullname, e.emp_code 
                             FROM payslips p 
                             JOIN employees e ON p.employee_id = e.id 
                             JOIN users u ON e.user_id = u.id 
                             WHERE (p.pdf_path IS NULL OR p.pdf_path = '') 
                             AND p.status = 'Paid'  -- Only show paid payslips
                             ORDER BY p.year DESC, STR_TO_DATE(CONCAT(p.month,' 01 ',p.year),'%M %d %Y') DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payslip PDF</title>
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
            max-width: 1200px;
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
            padding: 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .payslip-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .payslip-table th,
        .payslip-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .payslip-table th {
            background: #2c3e50;
            color: white;
        }
        
        .upload-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .upload-btn {
            padding: 8px 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .upload-btn:hover {
            background: #2980b9;
        }
        
        .back-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #95a5a6;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            margin-right: 10px;
            transition: 0.3s;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
        }
        
        .dashboard-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #3498db;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .dashboard-btn:hover {
            background: #2980b9;
        }
        
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 6px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .status-paid {
            color: #27ae60;
            font-weight: bold;
        }
        
        .button-group {
            margin-top: 20px;
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
        
        .file-input {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .salary-amount {
            font-weight: bold;
            color: #2c3e50;
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
                    <h1><i class="fas fa-file-pdf"></i> Upload Payslip PDF</h1>
                </div>

                <div class="content-container">
                    <?php if($message): ?>
                        <div class="message <?php echo strpos($message,'successfully')!==false ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if(isset($p_result) && $p_result->num_rows > 0): ?>
                        <table class="payslip-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Emp Code</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th>Upload</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = $p_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($p['emp_code']); ?></td>
                                        <td><?php echo htmlspecialchars($p['month']); ?></td>
                                        <td><?php echo htmlspecialchars($p['year']); ?></td>
                                        <td class="salary-amount">Rs.<?php echo number_format($p['net_salary'],2); ?></td>
                                        <td class="status-paid"><?php echo htmlspecialchars($p['status']); ?></td>
                                        <td>
                                            <form method="POST" enctype="multipart/form-data" class="upload-form">
                                                <input type="hidden" name="payslip_id" value="<?php echo $p['id']; ?>">
                                                <input type="file" name="payslip_pdf" accept=".pdf" required class="file-input">
                                                <button type="submit" class="upload-btn"><i class="fas fa-upload"></i> Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-file-pdf" style="font-size:48px"></i>
                            <h3>No Paid Payslips Require PDFs</h3>
                            <p>All paid payslips already have PDF files uploaded.</p>
                        </div>
                    <?php endif; ?>

                    <div class="button-group">
                        <a href="mark_salary_paid.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Mark Salary Paid
                        </a>
                        <a href="../admin_dashboard.php" class="dashboard-btn">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>