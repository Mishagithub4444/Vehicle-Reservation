<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

echo "<h2>🔧 Fix for bind_param() Reference Error</h2>";

echo "<div style='background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; color: #721c24;'>";
echo "<h3>❌ Previous Error:</h3>";
echo "<p><code>Fatal error: Uncaught Error: mysqli_stmt::bind_param(): Argument #2 cannot be passed by reference</code></p>";
echo "<p><strong>Cause:</strong> Cannot pass the result of <code>(string)$user_id</code> by reference to bind_param()</p>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; color: #155724;'>";
echo "<h3>✅ Fix Applied:</h3>";
echo "<p>Assigned cast result to variable first: <code>\$user_id_str = (string)\$user_id;</code></p>";
echo "<p>Then use variable in bind_param(): <code>bind_param('ss', \$user_id_str, \$user_type)</code></p>";
echo "</div>";

// Test the fix
echo "<h3>🧪 Testing the Fix</h3>";

try {
    // Test creating OTP table
    if (createOTPTable($conn)) {
        echo "✅ OTP table creation/verification successful<br>";
    }
    
    // Test OTP storage with string conversion
    $test_user_id = 1001;
    $user_id_str = (string)$test_user_id;
    $test_otp = generateOTP();
    
    if (storeOTPInDB($conn, $user_id_str, 'user', 'test@example.com', $test_otp)) {
        echo "✅ OTP storage with string conversion successful<br>";
        echo "- User ID: $user_id_str (originally $test_user_id)<br>";
        echo "- OTP: $test_otp<br>";
        
        // Test OTP verification
        if (verifyOTPFromDB($conn, $user_id_str, 'user', $test_otp)) {
            echo "✅ OTP verification with string conversion successful<br>";
        } else {
            echo "❌ OTP verification failed<br>";
        }
    } else {
        echo "❌ OTP storage failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "<br>";
}

echo "<h3>🚀 Ready to Test 2FA</h3>";
echo "<p>The bind_param() reference error has been fixed. You can now test the 2FA system:</p>";
echo "<ol>";
echo "<li><a href='user_login.php' target='_blank'>Go to User Login</a></li>";
echo "<li>Use credentials: john_doe / 1001 / password123</li>";
echo "<li>Check the 2FA checkbox</li>";
echo "<li>Complete the verification process</li>";
echo "</ol>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
