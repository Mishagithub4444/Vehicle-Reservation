<!DOCTYPE html>
<html>
<head>
    <title>2FA System Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status-box { margin: 15px 0; padding: 15px; border-radius: 8px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>🔐 Two-Factor Authentication System Status</h1>

<?php
include 'connection/db.php';
include 'two_factor_auth.php';

// Test database connection
echo "<div class='status-box info'>";
echo "<h3>📊 Database Connection</h3>";
if ($conn) {
    echo "✅ Database connected successfully<br>";
    echo "Server: localhost | Database: vrms<br>";
} else {
    echo "❌ Database connection failed<br>";
}
echo "</div>";

// Check required tables
echo "<div class='status-box info'>";
echo "<h3>🗃️ Database Tables</h3>";

$tables_to_check = ['user_registration', 'driver_registration', 'admin_registration'];
foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ $table table exists<br>";
    } else {
        echo "⚠️ $table table missing<br>";
    }
}

// Check OTP table
if (createOTPTable($conn)) {
    echo "✅ otp_verification table created/verified<br>";
} else {
    echo "❌ Failed to create otp_verification table<br>";
}
echo "</div>";

// Test 2FA functions
echo "<div class='status-box info'>";
echo "<h3>⚙️ 2FA Functions Test</h3>";

// Test OTP generation
$test_otp = generateOTP();
if ($test_otp && strlen($test_otp) == 6) {
    echo "✅ OTP generation working: $test_otp<br>";
} else {
    echo "❌ OTP generation failed<br>";
}

// Test OTP storage
if (storeOTPInDB($conn, 'TEST001', 'test', 'test@example.com', $test_otp)) {
    echo "✅ OTP storage working<br>";
    
    // Test OTP verification
    if (verifyOTPFromDB($conn, 'TEST001', 'test', $test_otp)) {
        echo "✅ OTP verification working<br>";
    } else {
        echo "❌ OTP verification failed<br>";
    }
} else {
    echo "❌ OTP storage failed<br>";
}
echo "</div>";

// Check login files
echo "<div class='status-box info'>";
echo "<h3>📁 Login Files Status</h3>";

$login_files = [
    'user_login.php' => 'User Login',
    'user_login_process.php' => 'User Login Process',
    'driver_login.html' => 'Driver Login',
    'driver_login_process.php' => 'Driver Login Process',
    'admin_login.php' => 'Admin Login',
    'admin_login_process.php' => 'Admin Login Process',
    'verify_otp.php' => 'OTP Verification Page',
    'verify_otp_process.php' => 'OTP Verification Process',
    'resend_otp.php' => 'OTP Resend Handler'
];

foreach ($login_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ <a href='$file' target='_blank'>$description</a><br>";
    } else {
        echo "❌ $description ($file) missing<br>";
    }
}
echo "</div>";

// Check for sample users
echo "<div class='status-box info'>";
echo "<h3>👥 Test Users</h3>";

$user_query = "SELECT User_ID, User_Name, First_Name, Email FROM user_registration LIMIT 3";
$user_result = $conn->query($user_query);

if ($user_result && $user_result->num_rows > 0) {
    echo "✅ Test users available:<br>";
    echo "<table>";
    echo "<tr><th>User ID</th><th>Username</th><th>Name</th><th>Email</th></tr>";
    while ($row = $user_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['User_ID']}</td>";
        echo "<td>{$row['User_Name']}</td>";
        echo "<td>{$row['First_Name']}</td>";
        echo "<td>{$row['Email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "⚠️ No test users found. <a href='create_test_users.php'>Create test users</a><br>";
}
echo "</div>";

$conn->close();
?>

<div class="status-box success">
    <h3>🚀 Test 2FA System</h3>
    <p><strong>Ready to test!</strong> Follow these steps:</p>
    <ol>
        <li>Go to <a href="user_login.php" target="_blank">User Login</a></li>
        <li>Use credentials: <strong>john_doe / 1001 / password123</strong></li>
        <li><strong>✅ Check the "Sign In With 2FA" checkbox</strong></li>
        <li>Click "Sign In"</li>
        <li>You should see an alert with the OTP code</li>
        <li>Enter the OTP on the verification page</li>
        <li>Complete login successfully!</li>
    </ol>
</div>

<div class="status-box warning">
    <h3>🔧 Troubleshooting</h3>
    <p>If the 2FA checkbox is not working:</p>
    <ul>
        <li>Check if JavaScript is enabled in your browser</li>
        <li>Ensure the checkbox is clickable (we simplified the styling)</li>
        <li>Check browser console for any JavaScript errors</li>
        <li>Try the <a href="test_checkbox.php" target="_blank">checkbox test page</a></li>
    </ul>
</div>

</body>
</html>
