<?php
session_start();
include "../connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../login.php");
    exit();
}

// Fetch all employees with user and department details
$sql = "SELECT e.*, u.fullname, u.email, u.phone, u.created_at, u.is_approved, d.name as department_name
        FROM employees e 
        JOIN users u ON e.user_id = u.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Employees</title>
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
            max-width: 1200px; 
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
        .no-data { 
            text-align: center; 
            padding: 40px; 
            color: #7f8c8d; 
        }
        .employee-count {
            margin: 15px 0; 
            color: #34495e; 
            font-weight: bold;
        }
        .edit-btn {
            background: #27ae60; 
            color: white; 
            padding: 8px 12px;
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none;
            font-size: 14px; 
            display: inline-block; 
            text-align: center;
        }
        .delete-btn {
            background: #e74c3c; 
            color: white; 
            padding: 8px 12px;
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none;
            font-size: 14px; 
            display: inline-block; 
            text-align: center;
        }
        .status-approved {
            color: #27ae60; 
            font-weight: bold;
        }
        .status-pending {
            color: #e67e22; 
            font-weight: bold;
        }
        .action-buttons {
            display: flex; 
            gap: 8px;
        }
    </style>
    <script>
        function confirmDelete(empId, empName) {
            if (confirm('Are you sure you want to delete ' + empName + '?')) {
                window.location.href = 'edit_employee.php?delete_emp=' + empId;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> View All Employees</h1>
        </div>

        <div class="content">
            <div class="employee-count">
                Total Employees: <?php echo $result->num_rows; ?>
            </div>

            <?php if($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Emp Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($employee = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $employee['emp_code']; ?></strong></td>
                                <td><?php echo $employee['fullname']; ?></td>
                                <td><?php echo $employee['email']; ?></td>
                                <td><?php echo $employee['phone']; ?></td>
                                <td><?php echo $employee['department_name'] ?: 'N/A'; ?></td>
                                <td><?php echo $employee['designation']; ?></td>
                                <td>Rs.<?php echo number_format($employee['salary'], 2); ?></td>
                                <td class="<?php echo $employee['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                    <?php echo $employee['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_employee.php?emp_id=<?php echo $employee['id']; ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" onclick="confirmDelete(<?php echo $employee['id']; ?>, '<?php echo $employee['fullname']; ?>')" class="delete-btn">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users" style="font-size: 48px;"></i>
                    <h3>No Employees Found</h3>
                    <p>No employees have been added yet.</p>
                </div>
            <?php endif; ?>

            <a href="../admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>