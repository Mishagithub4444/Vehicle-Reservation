<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php'; // Include 2FA functions

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_username = trim($_POST['driver_username']);
    $driver_id_input = trim($_POST['driver_id']);
    $driver_password = $_POST['password'];
    $enable_2fa = isset($_POST['enable_2fa']) ? true : false; // Check if 2FA is enabled

    // Validate input
    if (empty($driver_username) || empty($driver_id_input) || empty($driver_password)) {
        echo '<script>alert("Please fill in all fields."); window.history.back();</script>';
        exit();
    }

    // Keep driver_id as string to match database VARCHAR(6) format
    $driver_id = $driver_id_input;

    // Use the existing database connection from db.php
    // The $conn variable is already available from the included file

    // Check driver credentials (plain text password comparison)
    $sql = "SELECT * FROM driver_registration WHERE Driver_UserName = ? AND Driver_ID = ? AND Driver_Password = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('sss', $driver_username, $driver_id, $driver_password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // Credentials are valid
            $driver_data = $result->fetch_assoc();

            if ($enable_2fa) {
                // 2FA is enabled - Generate and send OTP
                $otp = generateOTP();
                $driver_email = getUserEmail($conn, $driver_data['Driver_ID'], 'driver', $driver_username);
                
                if ($driver_email) {
                    // Store OTP in database
                    if (storeOTPInDB($conn, $driver_data['Driver_ID'], 'driver', $driver_email, $otp)) {
                        // Store driver data in session for verification
                        $_SESSION['driver_pending_verification'] = true;
                        $_SESSION['driver_verification_email'] = $driver_email;
                        $_SESSION['driver_temp_data'] = $driver_data;
                        $_SESSION['driver_user_type'] = 'driver';
                        
                        // Simulate sending OTP via email (in production, you would actually send an email)
                        echo '<script>alert("Verification code sent to your email: ' . $driver_email . '\\nOTP: ' . $otp . '"); window.location.href = "verify_otp.php";</script>';
                    } else {
                        echo '<script>alert("Error generating verification code. Please try again."); window.history.back();</script>';
                    }
                } else {
                    echo '<script>alert("Email not found for this account. Please contact administrator."); window.history.back();</script>';
                }
            } else {
                // Regular login without 2FA
                // Store driver information in session
                $_SESSION['driver_logged_in'] = true;
                $_SESSION['driver_id'] = $driver_data['Driver_ID'];
                $_SESSION['driver_username'] = $driver_data['Driver_UserName'];
                $_SESSION['first_name'] = $driver_data['First_Name'];
                $_SESSION['last_name'] = $driver_data['Last_Name'];
                $_SESSION['license_number'] = $driver_data['License_Number'];
                $_SESSION['dob'] = $driver_data['Date_of_Birth'];
                $_SESSION['phone'] = $driver_data['Phone_Number'];
                $_SESSION['gender'] = $driver_data['Gender'];
                $_SESSION['email'] = $driver_data['Email'];
                $_SESSION['address'] = $driver_data['Address'];
                $_SESSION['driver_status'] = $driver_data['Driver_Status'];
                $_SESSION['availability'] = $driver_data['Availability'];

                echo '<script>alert("Login successful! Welcome Driver ' . $driver_data['First_Name'] . '!"); window.location.href = "driver_portal.php";</script>';
            }
        } else {
            // Login failed
            echo '<script>alert("Invalid driver username, driver ID, or password. Please try again."); window.history.back();</script>';
        }

        $stmt->close();
    } else {
        echo '<script>alert("Database error: ' . $conn->error . '"); window.history.back();</script>';
    }

    $conn->close();
} else {
    // Redirect to login page if accessed directly
    header('Location: driver_login.php');
    exit();
}
