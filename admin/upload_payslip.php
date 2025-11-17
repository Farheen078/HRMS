<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle file upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['payslip_pdf'])) {
    $payslip_id = $_POST['payslip_id'];
    
    // File upload configuration
    $target_dir = "../payslips/";
    if(!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = "payslip_" . $payslip_id . "_" . time() . ".pdf";
    $target_file = $target_dir . $file_name;
    
    // Check if file is a PDF
    $file_type = strtolower(pathinfo($_FILES["payslip_pdf"]["name"], PATHINFO_EXTENSION));
    if($file_type != "pdf") {
        $message = "Only PDF files are allowed.";
    } else {
        if(move_uploaded_file($_FILES["payslip_pdf"]["tmp_name"], $target_file)) {
            // Update payslip record with PDF path
            $update_sql = "UPDATE payslips SET pdf_path = '$file_name' WHERE id = '$payslip_id'";
            if($conn->query($update_sql)) {
                $message = "Payslip PDF uploaded successfully!";
            } else {
                $message = "Error updating record: " . $conn->error;
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch payslips without PDF
$payslips_sql = "SELECT p.*, u.fullname, e.emp_code 
                 FROM payslips p 
                 JOIN employees e ON p.employee_id = e.id 
                 JOIN users u ON e.user_id = u.id 
                 WHERE (p.pdf_path IS NULL OR p.pdf_path = '') AND p.status = 'Paid'
                 ORDER BY p.year DESC, p.month DESC";
$payslips_result = $conn->query($payslips_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payslip PDF</title>
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
            padding: 20px; 
            border-radius: 0 0 10px 10px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        table {
             width: 100%; 
             border-collapse: collapse; 
             margin-top: 20px; 
            }
        th, td {
             padding: 12px; 
             text-align: left; 
             border-bottom: 1px solid #ddd; 
            }
        th { 
            background: #2c3e50; 
            color: white; 
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
        .no-data { 
            text-align: center; 
            padding: 40px; 
            color: #7f8c8d; 
        }
        .upload-form {
             display: flex;
              gap: 10px; 
              align-items: center; 
            }
        .file-input {
             flex: 1; 
            }
        .upload-btn {
             background: #3498db; 
             color: white; 
             padding: 8px 15px; 
             border: none; 
             border-radius: 4px; 
             cursor: pointer; 
            }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-pdf"></i> Upload Payslip PDF</h1>
        </div>

        <div class="content">
            <?php if(!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($payslips_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Emp Code</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Net Salary</th>
                            <th>Upload PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($payslip = $payslips_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $payslip['fullname']; ?></td>
                                <td><?php echo $payslip['emp_code']; ?></td>
                                <td><?php echo $payslip['month']; ?></td>
                                <td><?php echo $payslip['year']; ?></td>
                                <td>₹<?php echo number_format($payslip['net_salary'], 2); ?></td>
                                <td>
                                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                                        <input type="hidden" name="payslip_id" value="<?php echo $payslip['id']; ?>">
                                        <input type="file" name="payslip_pdf" accept=".pdf" class="file-input" required>
                                        <button type="submit" class="upload-btn">
                                            <i class="fas fa-upload"></i> Upload
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-file-pdf" style="font-size: 48px;"></i>
                    <h3>No Payslips Need PDF Upload</h3>
                    <p>All paid salaries already have PDFs or no paid salaries found.</p>
                </div>
            <?php endif; ?>

            <a href="../admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>