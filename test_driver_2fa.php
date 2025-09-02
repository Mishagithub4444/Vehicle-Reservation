<?php
session_start();
include 'config.php';
include 'two_factor_auth.php';

echo "<h2>Driver 2FA System Test</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    exit;
}

// Test driver table structure
echo "<h3>2. Driver Table Structure Test</h3>";
$sql = "DESCRIBE driver_registration";
$result = $conn->query($sql);
if ($result) {
    echo "✅ Driver registration table exists<br>";
    echo "Columns: ";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " ";
    }
    echo "<br>";
} else {
    echo "❌ Driver registration table not found<br>";
}

// Test OTP table
echo "<h3>3. OTP Verification Table Test</h3>";
$sql = "DESCRIBE otp_verification";
$result = $conn->query($sql);
if ($result) {
    echo "✅ OTP verification table exists<br>";
} else {
    echo "❌ OTP verification table not found<br>";
}

// Test getUserEmail function
echo "<h3>4. getUserEmail Function Test</h3>";
echo "Testing getUserEmail function for drivers...<br>";

// Get a sample driver for testing
$sql = "SELECT Driver_ID, Driver_UserName, Email, Driver_Email FROM driver_registration LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $driver = $result->fetch_assoc();
    echo "Sample driver found: ID={$driver['Driver_ID']}, Username={$driver['Driver_UserName']}<br>";
    
    $email = getUserEmail($conn, $driver['Driver_ID'], 'driver', $driver['Driver_UserName']);
    if ($email) {
        echo "✅ getUserEmail returned: $email<br>";
    } else {
        echo "❌ getUserEmail failed to get email<br>";
    }
} else {
    echo "❌ No drivers found in database<br>";
}

// Test OTP generation
echo "<h3>5. OTP Generation Test</h3>";
$test_otp = generateOTP();
if ($test_otp && strlen($test_otp) == 6 && is_numeric($test_otp)) {
    echo "✅ OTP generation successful: $test_otp<br>";
} else {
    echo "❌ OTP generation failed<br>";
}

echo "<h3>6. Form Testing</h3>";
echo '<p>To test the complete 2FA flow:</p>';
echo '<ol>';
echo '<li>Go to <a href="driver_login.html" target="_blank">driver_login.html</a></li>';
echo '<li>Check the "Sign In With 2FA (Enhanced Security)" checkbox</li>';
echo '<li>Enter valid driver credentials</li>';
echo '<li>You should see an OTP alert and be redirected to verification page</li>';
echo '</ol>';

$conn->close();
?>
