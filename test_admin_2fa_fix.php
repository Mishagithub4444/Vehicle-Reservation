<?php
include 'config.php';
include 'two_factor_auth.php';

echo "<h2>Admin Login 2FA Test</h2>";

// Test getUserEmail function for admin
echo "<h3>1. Testing getUserEmail for Admin</h3>";

// Get a sample admin for testing
$sql = "SELECT Admin_ID, Admin_UserName, Email FROM admin_registration LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "Sample admin found:<br>";
    echo "Admin_ID: " . $admin['Admin_ID'] . "<br>";
    echo "Admin_UserName: " . $admin['Admin_UserName'] . "<br>";
    echo "Email: " . $admin['Email'] . "<br><br>";
    
    // Test getUserEmail function
    echo "<h3>2. Testing getUserEmail Function</h3>";
    $email = getUserEmail($conn, $admin['Admin_ID'], 'admin', $admin['Admin_UserName']);
    
    if ($email) {
        echo "✅ SUCCESS: getUserEmail returned: " . $email . "<br>";
        
        // Test OTP generation and storage
        echo "<h3>3. Testing OTP Generation and Storage</h3>";
        $otp = generateOTP();
        echo "Generated OTP: " . $otp . "<br>";
        
        if (storeOTPInDB($conn, $admin['Admin_ID'], 'admin', $email, $otp)) {
            echo "✅ SUCCESS: OTP stored in database<br>";
        } else {
            echo "❌ FAILED: Could not store OTP in database<br>";
        }
        
    } else {
        echo "❌ FAILED: getUserEmail returned false<br>";
        echo "This means the admin email could not be retrieved.<br>";
    }
} else {
    echo "❌ No admin records found in database<br>";
}

echo "<h3>4. Test Admin Login Form</h3>";
echo '<p>To test the complete admin 2FA flow:</p>';
echo '<ol>';
echo '<li>Go to <a href="admin_login.php" target="_blank">admin_login.php</a></li>';
echo '<li>Check the "Sign In With 2FA (Enhanced Security)" checkbox</li>';
echo '<li>Enter valid admin credentials</li>';
echo '<li>You should now see an OTP alert instead of "Email not found" error</li>';
echo '</ol>';

$conn->close();
?>
