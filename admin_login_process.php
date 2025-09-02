<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php'; // Include 2FA functions

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id_input = trim($_POST['admin_id']);
    $admin_username = trim($_POST['admin_name']);
    $admin_password = $_POST['password'];
    $enable_2fa = isset($_POST['enable_2fa']) ? true : false; // Check if 2FA is enabled

    // Validate input
    if (empty($admin_id_input) || empty($admin_username) || empty($admin_password)) {
        echo '<script>alert("Please fill in all fields."); window.history.back();</script>';
        exit();
    }

    // Keep admin_id as string since it's stored as VARCHAR in database
    $admin_id = trim($admin_id_input);

    // Use the existing database connection from db.php
    // The $conn variable is already available from the included file

    // Check admin credentials (using password verification for hashed passwords)
    $sql = "SELECT * FROM admin_registration WHERE Admin_ID = ? AND Admin_UserName = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('ss', $admin_id, $admin_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin_data = $result->fetch_assoc();

            // Compare password directly (plain text comparison)
            if ($admin_password === $admin_data['Admin_Password']) {
                // Credentials are valid

                if ($enable_2fa) {
                    // 2FA is enabled - Generate and send OTP
                    $otp = generateOTP();
                    $admin_email = getUserEmail($conn, $admin_data['Admin_ID'], 'admin', $admin_username);
                    
                    if ($admin_email) {
                        // Store OTP in database
                        if (storeOTPInDB($conn, $admin_data['Admin_ID'], 'admin', $admin_email, $otp)) {
                            // Store admin data in session for verification
                            $_SESSION['admin_pending_verification'] = true;
                            $_SESSION['admin_verification_email'] = $admin_email;
                            $_SESSION['admin_temp_data'] = $admin_data;
                            $_SESSION['admin_user_type'] = 'admin';
                            
                            // Simulate sending OTP via email (in production, you would actually send an email)
                            echo '<script>alert("Verification code sent to your email: ' . $admin_email . '\\nOTP: ' . $otp . '"); window.location.href = "verify_otp.php";</script>';
                        } else {
                            echo '<script>alert("Error generating verification code. Please try again."); window.history.back();</script>';
                        }
                    } else {
                        echo '<script>alert("Email not found for this account. Please contact administrator."); window.history.back();</script>';
                    }
                } else {
                    // Regular login without 2FA
                    // Store admin information in session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin_data['Admin_ID'];
                    $_SESSION['admin_name'] = $admin_data['Admin_UserName'];
                    $_SESSION['first_name'] = $admin_data['First_Name'];
                    $_SESSION['last_name'] = $admin_data['Last_Name'];
                    $_SESSION['email'] = $admin_data['Email'];
                    $_SESSION['phone'] = $admin_data['Phone_Number'];
                    $_SESSION['dob'] = $admin_data['Date_of_Birth'];
                    $_SESSION['gender'] = $admin_data['Gender'];
                    $_SESSION['address'] = $admin_data['Address'];
                    $_SESSION['role'] = $admin_data['Admin_Role'];
                    $_SESSION['admin_level'] = 'Standard'; // Default since this field doesn't exist in table
                    $_SESSION['department'] = 'General'; // Default since this field doesn't exist in table

                    echo '<script>alert("Login successful! Welcome Admin ' . $admin_data['First_Name'] . '!"); window.location.href = "admin_portal.php";</script>';
                }
            } else {
                // Password doesn't match
                echo '<script>alert("Invalid admin ID, username, or password. Please try again."); window.history.back();</script>';
            }
        } else {
            // No admin found with the provided ID and username
            echo '<script>alert("Invalid admin ID or username. Please try again."); window.history.back();</script>';
        }

        $stmt->close();
    } else {
        echo '<script>alert("Database error: ' . $conn->error . '"); window.history.back();</script>';
    }

    $conn->close();
} else {
    // Redirect to login page if accessed directly
    header('Location: admin_login.php');
    exit();
}
