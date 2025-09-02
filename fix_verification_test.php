<!DOCTYPE html>
<html>
<head>
    <title>2FA Fix Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status-box { margin: 15px 0; padding: 15px; border-radius: 8px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .test-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; }
        input, button { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>🔧 2FA "Invalid or expired verification code" Fix</h1>

<div class="status-box info">
    <h3>🔍 Issue Analysis</h3>
    <p>The "Invalid or expired verification code" error was likely caused by:</p>
    <ul>
        <li><strong>Data Type Mismatch:</strong> User ID stored as integer but compared as string</li>
        <li><strong>Session Variable Mismatch:</strong> Different session variable names in different files</li>
        <li><strong>Checkbox Visibility:</strong> Custom CSS making checkbox unclickable</li>
    </ul>
</div>

<div class="status-box success">
    <h3>✅ Fixes Applied</h3>
    <ul>
        <li>✅ Fixed checkbox styling for all login forms (user, driver, admin)</li>
        <li>✅ Fixed session variable names to be consistent across all files</li>
        <li>✅ Fixed data type handling for user_id in OTP storage and verification</li>
        <li>✅ Added debug logging to track OTP verification process</li>
        <li>✅ Added OTP display in alert before redirect</li>
    </ul>
</div>

<?php
include 'connection/db.php';
include 'two_factor_auth.php';

// Clear any existing OTP session data
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'pending') !== false || strpos($key, 'temp') !== false || strpos($key, 'verification') !== false) {
        unset($_SESSION[$key]);
    }
}

echo "<div class='status-box info'>";
echo "<h3>🧪 Live Test</h3>";

// Create test users if they don't exist
$test_users = [
    [1001, 'John', 'Doe', 'john_doe', 1234567890, '1990-01-15', 'Male', 'john.doe@email.com', '123 Main St', 'Standard', 'password123'],
    [1002, 'Jane', 'Smith', 'jane_smith', 9876543210, '1992-03-22', 'Female', 'jane.smith@email.com', '456 Oak Ave', 'Premium', 'password456']
];

foreach ($test_users as $user) {
    $check_sql = "SELECT User_ID FROM user_registration WHERE User_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $user[0]);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $insert_sql = "INSERT INTO user_registration (User_ID, First_Name, Last_Name, User_Name, Phone_Number, Date_of_Birth, Gender, Email, Address, User_Type, User_Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('isssssssss', $user[0], $user[1], $user[2], $user[3], $user[4], $user[5], $user[6], $user[7], $user[8], $user[9], $user[10]);
        $insert_stmt->execute();
    }
}

echo "<p>✅ Test users ensured in database</p>";
echo "</div>";
?>

<div class="test-form">
    <h3>🚀 Step-by-Step Test Instructions</h3>
    <ol>
        <li><strong>Go to User Login:</strong> <a href="user_login.php" target="_blank">Click here to open user login</a></li>
        <li><strong>Enter Test Credentials:</strong>
            <ul>
                <li>Username: <code>john_doe</code></li>
                <li>User ID: <code>1001</code></li>
                <li>Password: <code>password123</code></li>
            </ul>
        </li>
        <li><strong>✅ Check the 2FA Checkbox:</strong> Make sure "Sign In With 2FA (Enhanced Security)" is checked</li>
        <li><strong>Click Sign In:</strong> You should see an alert with the 6-digit OTP code</li>
        <li><strong>Note the OTP:</strong> Write down the 6-digit code from the alert</li>
        <li><strong>Enter OTP:</strong> On the verification page, enter the exact OTP from the alert</li>
        <li><strong>Verify:</strong> Click "Verify & Continue"</li>
        <li><strong>Success:</strong> You should be redirected to the user portal</li>
    </ol>
</div>

<div class="status-box info">
    <h3>🔍 If Still Getting Error</h3>
    <p>If you still get "Invalid or expired verification code", try these debugging steps:</p>
    <ol>
        <li><a href="debug_otp_verification.php" target="_blank">Open OTP Debug Page</a> and submit the form to see detailed debug info</li>
        <li><a href="test_complete_2fa_flow.php" target="_blank">Run Complete Flow Test</a> to see where the process breaks</li>
        <li>Check browser console for JavaScript errors</li>
        <li>Ensure you're entering the OTP exactly as shown in the alert</li>
        <li>Try the process again with a fresh browser session</li>
    </ol>
</div>

<div class="test-form">
    <h3>🧪 Alternative Test Forms</h3>
    
    <h4>Test Other Login Types:</h4>
    <ul>
        <li><a href="driver_login.html" target="_blank">Driver Login</a> - Use driver credentials</li>
        <li><a href="admin_login.php" target="_blank">Admin Login</a> - Use admin credentials</li>
    </ul>
    
    <h4>Debug Tools:</h4>
    <ul>
        <li><a href="test_checkbox.php" target="_blank">Checkbox Test</a> - Verify checkbox submission</li>
        <li><a href="test_data_types.php" target="_blank">Data Types Test</a> - Check user_id handling</li>
        <li><a href="test_2fa_complete.php" target="_blank">System Status</a> - Overall system check</li>
    </ul>
</div>

<div class="status-box success">
    <h3>✅ Expected Behavior After Fix</h3>
    <p>The 2FA system should now work correctly:</p>
    <ul>
        <li>✅ Checkbox is visible and clickable</li>
        <li>✅ OTP is generated and displayed in alert</li>
        <li>✅ OTP verification page loads correctly</li>
        <li>✅ OTP verification succeeds with correct code</li>
        <li>✅ User is redirected to appropriate portal after verification</li>
    </ul>
</div>

</body>
</html>
