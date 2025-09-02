<?php
// Quick test to verify the data type fix
include 'connection/db.php';
include 'two_factor_auth.php';

echo "<h2>Testing Data Type Fix for OTP Verification</h2>";

// Test with integer vs string user_id
$test_user_id_int = 1001;
$test_user_id_str = "1001";
$test_otp = "123456";

// Create test OTP with string user_id
createOTPTable($conn);

echo "<h3>Test 1: Store OTP with string user_id</h3>";
if (storeOTPInDB($conn, $test_user_id_str, 'user', 'test@example.com', $test_otp)) {
    echo "✅ OTP stored with string user_id: '$test_user_id_str'<br>";
    
    // Test verification with string user_id
    if (verifyOTPFromDB($conn, $test_user_id_str, 'user', $test_otp)) {
        echo "✅ OTP verified with string user_id: '$test_user_id_str'<br>";
    } else {
        echo "❌ OTP verification failed with string user_id<br>";
    }
} else {
    echo "❌ Failed to store OTP with string user_id<br>";
}

echo "<h3>Test 2: Store OTP with integer user_id</h3>";
if (storeOTPInDB($conn, $test_user_id_int, 'user', 'test2@example.com', $test_otp)) {
    echo "✅ OTP stored with integer user_id: $test_user_id_int<br>";
    
    // Test verification with integer user_id
    if (verifyOTPFromDB($conn, $test_user_id_int, 'user', $test_otp)) {
        echo "✅ OTP verified with integer user_id: $test_user_id_int<br>";
    } else {
        echo "❌ OTP verification failed with integer user_id<br>";
    }
    
    // Test cross-verification (store as int, verify as string)
    if (verifyOTPFromDB($conn, $test_user_id_str, 'user', $test_otp)) {
        echo "✅ Cross-verification successful (stored as int, verified as string)<br>";
    } else {
        echo "❌ Cross-verification failed (stored as int, verified as string)<br>";
    }
} else {
    echo "❌ Failed to store OTP with integer user_id<br>";
}

// Show current OTP records
echo "<h3>Current OTP Records:</h3>";
$sql = "SELECT user_id, user_type, otp, email, created_at, expires_at FROM otp_verification ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Type</th><th>OTP</th><th>Email</th><th>Created</th><th>Expires</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['otp']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['expires_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>
