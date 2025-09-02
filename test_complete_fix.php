<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

echo "<h1>🔧 OTP Verification Fix - Complete Flow Test</h1>";

// Clear any existing session
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'pending') !== false || strpos($key, 'temp') !== false || strpos($key, 'verification') !== false) {
        unset($_SESSION[$key]);
    }
}

echo "<h2>Step 1: Simulate Complete Login Process</h2>";

// Simulate user_login_process.php
$user_name = 'john_doe';
$user_id_input = '1001';
$user_password = 'password123';

// Convert user_id to integer (as done in user_login_process.php)
$user_id = intval($user_id_input);

// Check user credentials
$sql = "SELECT * FROM user_registration WHERE User_Name = ? AND User_ID = ? AND User_Password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sis', $user_name, $user_id, $user_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
    echo "✅ User authenticated: {$user_data['First_Name']} {$user_data['Last_Name']}<br>";
    echo "📧 Email: {$user_data['Email']}<br>";
    echo "🆔 Original User_ID from DB: {$user_data['User_ID']} (type: " . gettype($user_data['User_ID']) . ")<br>";
    
    // Simulate 2FA process (exactly as in user_login_process.php)
    $email = $user_data['Email'];
    $otp = generateOTP();
    
    // Store OTP in database with string conversion
    $user_id_str = (string)$user_id;
    echo "🔄 Converting user_id $user_id to string '$user_id_str'<br>";
    
    createOTPTable($conn);
    
    if (storeOTPInDB($conn, $user_id_str, 'user', $email, $otp)) {
        echo "✅ OTP stored in database with user_id '$user_id_str'<br>";
        
        // Ensure user_data has string user_id for consistency (NEW FIX)
        $user_data['User_ID'] = $user_id_str;
        echo "🔧 Fixed: Updated user_data['User_ID'] to string '$user_id_str'<br>";
        
        // Store user info in session for 2FA process
        $_SESSION['user_pending_verification'] = true;
        $_SESSION['user_verification_email'] = $email;
        $_SESSION['user_temp_data'] = $user_data;
        $_SESSION['user_user_type'] = 'user';
        
        echo "✅ Session data stored for verification<br>";
        echo "🔑 Generated OTP: <strong>$otp</strong><br>";
        
        echo "<h2>Step 2: Verify Session Data</h2>";
        echo "Session user_temp_data['User_ID']: '{$_SESSION['user_temp_data']['User_ID']}' (type: " . gettype($_SESSION['user_temp_data']['User_ID']) . ")<br>";
        
        echo "<h2>Step 3: Simulate OTP Verification Process</h2>";
        
        // Simulate verify_otp_process.php logic
        $user_pending = isset($_SESSION['user_pending_verification']);
        $user_data_from_session = $_SESSION['user_temp_data'] ?? null;
        $user_id_from_session = $user_data_from_session['User_ID'] ?? '';
        $user_type = 'user';
        
        echo "✅ User pending verification: " . ($user_pending ? 'Yes' : 'No') . "<br>";
        echo "🆔 User ID from session: '$user_id_from_session' (type: " . gettype($user_id_from_session) . ")<br>";
        
        // Convert to string for consistency (as done in verify_otp_process.php)
        $user_id_str_for_verification = (string)$user_id_from_session;
        echo "🔄 User ID for verification: '$user_id_str_for_verification'<br>";
        
        // Test OTP verification
        echo "<h3>Testing OTP Verification with: '$otp'</h3>";
        
        // Check what's in the database
        $check_sql = "SELECT * FROM otp_verification WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('ss', $user_id_str_for_verification, $user_type);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $otp_row = $check_result->fetch_assoc();
            echo "📋 Found OTP record:<br>";
            echo "- Stored User ID: '{$otp_row['user_id']}'<br>";
            echo "- Stored OTP: '{$otp_row['otp']}'<br>";
            echo "- Input OTP: '$otp'<br>";
            echo "- OTP Match: " . ($otp_row['otp'] === $otp ? '✅ YES' : '❌ NO') . "<br>";
            echo "- User ID Match: " . ($otp_row['user_id'] === $user_id_str_for_verification ? '✅ YES' : '❌ NO') . "<br>";
            echo "- Expires: {$otp_row['expires_at']}<br>";
            echo "- Used: " . ($otp_row['is_used'] ? 'Yes' : 'No') . "<br>";
            
            // Test the actual verification function
            if (verifyOTPFromDB($conn, $user_id_str_for_verification, $user_type, $otp)) {
                echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS: OTP verification working correctly!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ FAILED: OTP verification still failing!</p>";
            }
        } else {
            echo "❌ No OTP record found with user_id '$user_id_str_for_verification' and type '$user_type'<br>";
        }
        
    } else {
        echo "❌ Failed to store OTP in database<br>";
    }
    
} else {
    echo "❌ User authentication failed. Please ensure test user exists.<br>";
    echo "Run <a href='create_test_users.php'>create_test_users.php</a> first.<br>";
}

echo "<h2>Step 4: Manual Test Form</h2>";
echo "<p>Use the OTP generated above: <strong>$otp</strong></p>";

if (isset($_SESSION['user_pending_verification'])) {
    echo '<form action="verify_otp_process.php" method="POST" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">';
    echo '<h3>Test Real Verification Process:</h3>';
    echo '<input type="text" name="otp" placeholder="Enter OTP" maxlength="6" value="' . ($otp ?? '') . '" required style="padding: 10px; font-size: 16px; width: 150px;">';
    echo '<button type="submit" style="padding: 10px 20px; margin-left: 10px;">Verify with Real Process</button>';
    echo '</form>';
    
    echo '<p><a href="verify_otp.php">Or go to the actual verification page</a></p>';
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1, h2, h3 { color: #2c3e50; }
</style>
