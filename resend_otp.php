<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    
    // Check if user is in 2FA process
    if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_user_type']) || !isset($_SESSION['2fa_username'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid session']);
        exit();
    }
    
    $user_id = $_SESSION['2fa_user_id'];
    $user_type = $_SESSION['2fa_user_type'];
    $username = $_SESSION['2fa_username'];
    
    // Create OTP table if not exists
    createOTPTable($conn);
    
    // Get user email
    $email = getUserEmail($conn, $user_id, $user_type, $username);
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit();
    }
    
    // Generate new OTP
    $otp = generateOTP();
    
    // Store OTP in database
    if (storeOTPInDB($conn, $user_id, $user_type, $email, $otp)) {
        // Update session with new email
        $_SESSION['2fa_email'] = $email;
        
        // Log the OTP for demonstration (remove in production)
        error_log("New OTP for $email ($username): $otp");
        
        echo json_encode(['success' => true, 'message' => 'New verification code sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate new code']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
