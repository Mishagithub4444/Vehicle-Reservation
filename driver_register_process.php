<?php
// driver_register_process.php processes driver registration form and saves data to the database

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vrms";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Create driver_registration table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS driver_registration (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Driver_UserName VARCHAR(50) UNIQUE NOT NULL,
    Driver_ID VARCHAR(6) UNIQUE NOT NULL,
    License_Number VARCHAR(20) NOT NULL,
    Date_of_Birth DATE NOT NULL,
    Phone_Number VARCHAR(15) NOT NULL,
    Gender VARCHAR(10) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Address TEXT NOT NULL,
    Driver_Status VARCHAR(20) DEFAULT 'Active',
    Availability VARCHAR(20) DEFAULT 'Available',
    Driver_Password VARCHAR(255) NOT NULL,
    Registration_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

function register_driver($conn, $data)
{
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $driver_username = trim($data['driver_username']);
    $driver_id = trim($data['driver_id']); // Keep as string for 6-digit format
    $license_number = trim($data['license_number']); // Keep as string
    $dob = $data['dob'];
    $phone = trim($data['phone']); // Keep as string
    $gender = $data['gender'];
    $email = trim($data['email']);
    $address = trim($data['address']);
    $status = isset($data['status']) ? $data['status'] : 'Active';
    $availability = isset($data['availability']) ? $data['availability'] : 'Available';
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];

    // Validate Driver ID format (must be exactly 6 digits)
    if (!preg_match('/^\d{6}$/', $driver_id)) {
        echo '<script>alert("Driver ID must be exactly 6 digits (e.g., 123456)."); window.history.back();</script>';
        exit();
    }

    // Simple validation
    if ($password !== $confirm_password) {
        echo '<script>alert("Passwords do not match."); window.history.back();</script>';
        exit();
    }

    // Check if Driver_ID already exists
    $check_sql = "SELECT Driver_ID FROM driver_registration WHERE Driver_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param('s', $driver_id); // Use 's' for string
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<script>alert("Driver ID already exists. Please choose a different one."); window.history.back();</script>';
            exit();
        }
        $check_stmt->close();
    }

    // Check if username already exists
    $check_username_sql = "SELECT Driver_UserName FROM driver_registration WHERE Driver_UserName = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    if ($check_username_stmt) {
        $check_username_stmt->bind_param('s', $driver_username);
        $check_username_stmt->execute();
        $result = $check_username_stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<script>alert("Username already exists. Please choose a different one."); window.history.back();</script>';
            exit();
        }
        $check_username_stmt->close();
    }

    // Check if email already exists
    $check_email_sql = "SELECT Email FROM driver_registration WHERE Email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    if ($check_email_stmt) {
        $check_email_stmt->bind_param('s', $email);
        $check_email_stmt->execute();
        $result = $check_email_stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<script>alert("Email already exists. Please use a different email."); window.history.back();</script>';
            exit();
        }
        $check_email_stmt->close();
    }

    // Store password as plain text string (no hashing)
    $stored_password = $password;

    // Prepare SQL for driver_registration table
    $sql = "INSERT INTO driver_registration (First_Name, Last_Name, Driver_UserName, Driver_ID, License_Number, Date_of_Birth, Phone_Number, Gender, Email, Address, Driver_Status, Availability, Driver_Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sssssssssssss', $first_name, $last_name, $driver_username, $driver_id, $license_number, $dob, $phone, $gender, $email, $address, $status, $availability, $stored_password);
        if ($stmt->execute()) {
            echo '<script>alert("Driver registration successful! You can now login with your credentials."); window.location.href = "driver_login.php";</script>';
        } else {
            echo '<script>alert("Error: ' . $stmt->error . '"); window.history.back();</script>';
        }
        $stmt->close();
    } else {
        echo '<script>alert("Database error: ' . $conn->error . '"); window.history.back();</script>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    register_driver($conn, $_POST);
    $conn->close();
} else {
    header('Location: driver_register.php');
    exit();
}
