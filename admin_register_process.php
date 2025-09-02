<?php
// admin_register_process.php processes admin registration form and saves data to the database

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Create admin_registration table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS admin_registration (
    Admin_ID VARCHAR(10) PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Admin_UserName VARCHAR(50) NOT NULL UNIQUE,
    Date_of_Birth DATE,
    Phone_Number VARCHAR(20),
    Gender VARCHAR(10),
    Email VARCHAR(100) UNIQUE,
    Address VARCHAR(255),
    Admin_Role VARCHAR(50),
    Admin_Password VARCHAR(100) NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

function register_admin($conn, $data)
{
    // Debug: Show received data
    echo "<h3>Debug - Received Data:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $admin_username = trim($data['admin_username']);
    $admin_id = trim($data['admin_id']); // treat as string
    $dob = $data['dob'];
    $gender = $data['gender'];
    $phone = trim($data['phone']); // Keep as string
    $email = trim($data['email']);
    $address = trim($data['address']);
    $role = $data['role'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];

    // Debug: Show processed data
    echo "<h3>Debug - Processed Data:</h3>";
    echo "First Name: " . $first_name . "<br>";
    echo "Last Name: " . $last_name . "<br>";
    echo "Username: " . $admin_username . "<br>";
    echo "Admin ID: " . $admin_id . "<br>";
    echo "DOB: " . $dob . "<br>";
    echo "Gender: " . $gender . "<br>";
    echo "Phone: " . $phone . "<br>";
    echo "Email: " . $email . "<br>";
    echo "Address: " . $address . "<br>";
    echo "Role: " . $role . "<br>";

    // Simple validation
    if ($password !== $confirm_password) {
        echo '<script>alert("Passwords do not match."); window.history.back();</script>';
        exit();
    }

    echo "<h3>Debug - Checking for existing Admin ID and Username...</h3>";

    // Check if Admin_ID already exists in admin_registration table
    $check_sql = "SELECT Admin_ID FROM admin_registration WHERE Admin_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param('s', $admin_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<script>alert("Admin ID already exists. Please choose a different one."); window.history.back();</script>';
            exit();
        }
        $check_stmt->close();
        echo "Admin ID is unique, proceeding...<br>";
    } else {
        echo "Error preparing check statement: " . $conn->error . "<br>";
        exit();
    }

    // Check if Admin_UserName already exists
    $check_username_sql = "SELECT Admin_UserName FROM admin_registration WHERE Admin_UserName = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    if ($check_username_stmt) {
        $check_username_stmt->bind_param('s', $admin_username);
        $check_username_stmt->execute();
        $username_result = $check_username_stmt->get_result();
        if ($username_result->num_rows > 0) {
            echo '<script>alert("Username already exists. Please choose a different username."); window.history.back();</script>';
            exit();
        }
        $check_username_stmt->close();
        echo "Username is unique, proceeding...<br>";
    } else {
        echo "Error preparing username check statement: " . $conn->error . "<br>";
        exit();
    }

    // Check if Email already exists
    $check_email_sql = "SELECT Email FROM admin_registration WHERE Email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    if ($check_email_stmt) {
        $check_email_stmt->bind_param('s', $email);
        $check_email_stmt->execute();
        $email_result = $check_email_stmt->get_result();
        if ($email_result->num_rows > 0) {
            echo '<script>alert("Email already exists. Please choose a different email."); window.history.back();</script>';
            exit();
        }
        $check_email_stmt->close();
        echo "Email is unique, proceeding...<br>";
    } else {
        echo "Error preparing email check statement: " . $conn->error . "<br>";
        exit();
    }

    // Store password in plain text format (as requested)
    $stored_password = $password;
    echo "<h3>Debug - Password stored in plain text format</h3>";

    // Prepare SQL for admin_registration table
    echo "<h3>Debug - Inserting into database...</h3>";
    $sql = "INSERT INTO admin_registration (First_Name, Last_Name, Admin_UserName, Admin_ID, Date_of_Birth, Phone_Number, Gender, Email, Address, Admin_Role, Admin_Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sssssssssss', $first_name, $last_name, $admin_username, $admin_id, $dob, $phone, $gender, $email, $address, $role, $stored_password);
        if ($stmt->execute()) {
            echo "<h3>Debug - Registration successful!</h3>";
            echo '<script>alert("Admin registration successful! You can now login with your credentials."); window.location.href = "admin_login.php";</script>';
        } else {
            echo "<h3>Debug - Database execution error:</h3>";
            echo "Error: " . $stmt->error . "<br>";
            echo '<script>alert("Error: ' . $stmt->error . '"); window.history.back();</script>';
        }
        $stmt->close();
    } else {
        echo "<h3>Debug - Statement preparation error:</h3>";
        echo "Database error: " . $conn->error . "<br>";
        echo '<script>alert("Database error: ' . $conn->error . '"); window.history.back();</script>';
    }
}

echo "<h3>Debug - Starting registration process...</h3>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Debug - POST request received</h3>";
    register_admin($conn, $_POST);
    $conn->close();
} else {
    echo "<h3>Debug - Not a POST request, redirecting...</h3>";
    header('Location: admin_register.html');
    exit();
}
