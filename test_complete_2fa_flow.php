<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

echo "<h1>🔍 Complete 2FA Flow Test</h1>";

// Test 1: Simulate login with 2FA
echo "<h2>Step 1: Simulate User Login with 2FA</h2>";

// Clear any existing session data
session_unset();
session_start();

$test_user_name = 'john_doe';
$test_user_id = 1001;
$test_password = 'password123';

// Check if user exists
$sql = "SELECT * FROM user_registration WHERE User_Name = ? AND User_ID = ? AND User_Password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sis', $test_user_name, $test_user_id, $test_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
    echo "✅ Test user found: {$user_data['First_Name']} {$user_data['Last_Name']}<br>";
    echo "📧 Email: {$user_data['Email']}<br>";
    
    // Generate OTP
    $otp = generateOTP();
    echo "🔑 Generated OTP: $otp<br>";
    
    // Store OTP in database
    createOTPTable($conn);
    
    if (storeOTPInDB($conn, $test_user_id, 'user', $user_data['Email'], $otp)) {
        echo "✅ OTP stored in database<br>";
        
        // Set session data (simulate user_login_process.php)
        $_SESSION['user_pending_verification'] = true;
        $_SESSION['user_verification_email'] = $user_data['Email'];
        $_SESSION['user_temp_data'] = $user_data;
        $_SESSION['user_user_type'] = 'user';
        
        echo "✅ Session data set for verification<br>";
        
        // Display current session data
        echo "<h3>Current Session Data:</h3>";
        echo "<pre>";
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'user_') === 0) {
                if (is_array($value)) {
                    echo "$key: " . print_r($value, true);
                } else {
                    echo "$key: $value\n";
                }
            }
        }
        echo "</pre>";
        
        // Test Step 2: Verify OTP
        echo "<h2>Step 2: Test OTP Verification</h2>";
        
        // Check what's in OTP table
        $check_sql = "SELECT * FROM otp_verification WHERE user_id = ? AND user_type = 'user' ORDER BY created_at DESC LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $test_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $otp_row = $check_result->fetch_assoc();
            echo "✅ OTP found in database:<br>";
            echo "- OTP: {$otp_row['otp']}<br>";
            echo "- User ID: {$otp_row['user_id']}<br>";
            echo "- User Type: {$otp_row['user_type']}<br>";
            echo "- Email: {$otp_row['email']}<br>";
            echo "- Expires: {$otp_row['expires_at']}<br>";
            echo "- Used: " . ($otp_row['is_used'] ? 'Yes' : 'No') . "<br>";
            echo "- Current Time: " . date('Y-m-d H:i:s') . "<br>";
            
            // Test verification
            echo "<h3>Testing OTP Verification:</h3>";
            if (verifyOTPFromDB($conn, $test_user_id, 'user', $otp)) {
                echo "✅ OTP verification successful!<br>";
                
                // Test the complete verification process
                echo "<h3>Testing Complete Verification Process:</h3>";
                
                // Simulate verify_otp_process.php logic
                $user_pending = isset($_SESSION['user_pending_verification']);
                $user_data_from_session = $_SESSION['user_temp_data'] ?? null;
                $user_id_from_session = $user_data_from_session['User_ID'] ?? '';
                
                echo "- User pending verification: " . ($user_pending ? 'Yes' : 'No') . "<br>";
                echo "- User data in session: " . ($user_data_from_session ? 'Yes' : 'No') . "<br>";
                echo "- User ID from session: $user_id_from_session<br>";
                
                if ($user_pending && $user_data_from_session && $user_id_from_session == $test_user_id) {
                    echo "✅ All verification conditions met!<br>";
                    echo "✅ 2FA flow would be successful!<br>";
                } else {
                    echo "❌ Verification conditions not met<br>";
                }
                
            } else {
                echo "❌ OTP verification failed!<br>";
                
                // Try to understand why
                echo "<h4>Debugging verification failure:</h4>";
                $debug_sql = "SELECT *, NOW() as current_time FROM otp_verification WHERE user_id = ? AND user_type = 'user' AND otp = ?";
                $debug_stmt = $conn->prepare($debug_sql);
                $debug_stmt->bind_param('is', $test_user_id, $otp);
                $debug_stmt->execute();
                $debug_result = $debug_stmt->get_result();
                
                if ($debug_result->num_rows > 0) {
                    $debug_row = $debug_result->fetch_assoc();
                    echo "- OTP found but failed verification<br>";
                    echo "- Expires at: {$debug_row['expires_at']}<br>";
                    echo "- Current time: {$debug_row['current_time']}<br>";
                    echo "- Is expired: " . (strtotime($debug_row['expires_at']) < time() ? 'Yes' : 'No') . "<br>";
                    echo "- Is used: " . ($debug_row['is_used'] ? 'Yes' : 'No') . "<br>";
                } else {
                    echo "- OTP not found with exact match<br>";
                }
            }
        } else {
            echo "❌ No OTP found in database<br>";
        }
        
    } else {
        echo "❌ Failed to store OTP in database<br>";
    }
    
} else {
    echo "❌ Test user not found. Please run create_test_users.php first<br>";
}

echo "<hr>";
echo "<h2>Manual Test Forms</h2>";

// Create a test form for manual OTP entry
if (isset($_SESSION['user_pending_verification'])) {
    echo "<h3>🧪 Test OTP Verification Form</h3>";
    echo "<p>Current OTP for testing: <strong>$otp</strong></p>";
    
    echo '<form action="debug_otp_verification.php" method="POST" style="margin: 20px 0;">';
    echo '<input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required style="padding: 8px; font-size: 16px; width: 120px; text-align: center;">';
    echo '<button type="submit" style="padding: 8px 16px; margin-left: 10px;">Test Verify</button>';
    echo '</form>';
    
    echo '<p><a href="verify_otp.php">Go to actual verification page</a></p>';
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1, h2, h3 { color: #2c3e50; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
</style>
