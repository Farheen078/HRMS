<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "hrms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Check if function already exists to prevent redeclaration
if (!function_exists('createAdminAccount')) {
    function createAdminAccount($conn) {
        $admin_email = "SubinaThapa@hrms.com";
        $admin_password = "admin123"; 
        $admin_fullname = "Subina Thapa";
        $admin_phone = "1234567890";
        
        $check_sql = "SELECT id FROM users WHERE email = '$admin_email' AND role = 'admin'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows == 0) {
            $insert_sql = "INSERT INTO users (fullname, email, phone, password, role) 
                          VALUES ('$admin_fullname', '$admin_email', '$admin_phone', '$admin_password', 'admin')";
            
            if ($conn->query($insert_sql)) {
                error_log("Admin account created automatically");
            }
        }
    }
}

// Only call the function if we're in the main scope
if (!defined('INCLUDE_ONLY')) {
    createAdminAccount($conn);
}
?>