<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>OTP Verification Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_otp = trim($_POST['otp']);
    
    echo "<h3>Debug Information:</h3>";
    echo "<p><strong>Input OTP:</strong> '$input_otp'</p>";
    echo "<p><strong>OTP Length:</strong> " . strlen($input_otp) . "</p>";
    echo "<p><strong>Is Numeric:</strong> " . (is_numeric($input_otp) ? 'Yes' : 'No') . "</p>";
    
    // Check session data
    echo "<h3>Session Data:</h3>";
    echo "<pre>";
    $relevant_sessions = [];
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'pending') !== false || strpos($key, 'temp') !== false || strpos($key, 'verification') !== false) {
            $relevant_sessions[$key] = $value;
        }
    }
    print_r($relevant_sessions);
    echo "</pre>";
    
    // Validate input
    if (empty($input_otp) || !preg_match('/^[0-9]{6}$/', $input_otp)) {
        echo "<p style='color: red;'>❌ Invalid OTP format. Must be 6 digits.</p>";
        exit();
    }
    
    // Check if user is in 2FA process
    $user_pending = isset($_SESSION['user_pending_verification']);
    $driver_pending = isset($_SESSION['driver_pending_verification']);
    $admin_pending = isset($_SESSION['admin_pending_verification']);
    
    echo "<h3>2FA Session Status:</h3>";
    echo "<p>User pending: " . ($user_pending ? 'Yes' : 'No') . "</p>";
    echo "<p>Driver pending: " . ($driver_pending ? 'Yes' : 'No') . "</p>";
    echo "<p>Admin pending: " . ($admin_pending ? 'Yes' : 'No') . "</p>";
    
    if (!$user_pending && !$driver_pending && !$admin_pending) {
        echo "<p style='color: red;'>❌ No valid verification session found.</p>";
        exit();
    }
    
    // Determine user type and get data
    if ($user_pending) {
        $user_type = 'user';
        $user_data = $_SESSION['user_temp_data'] ?? null;
        $user_id = $user_data['User_ID'] ?? '';
        $username = $user_data['User_Name'] ?? '';
    } elseif ($driver_pending) {
        $user_type = 'driver';
        $user_data = $_SESSION['driver_temp_data'] ?? null;
        $user_id = $user_data['Driver_ID'] ?? '';
        $username = $user_data['Driver_UserName'] ?? '';
    } elseif ($admin_pending) {
        $user_type = 'admin';
        $user_data = $_SESSION['admin_temp_data'] ?? null;
        $user_id = $user_data['Admin_ID'] ?? '';
        $username = $user_data['Admin_UserName'] ?? '';
    }
    
    echo "<h3>User Information:</h3>";
    echo "<p>User Type: $user_type</p>";
    echo "<p>User ID: $user_id</p>";
    echo "<p>Username: $username</p>";
    
    if (!$user_data || !$user_id) {
        echo "<p style='color: red;'>❌ Invalid user data in session.</p>";
        exit();
    }
    
    // Check OTP table existence
    createOTPTable($conn);
    
    // Debug: Check what's in the OTP table
    echo "<h3>OTP Table Contents:</h3>";
    $debug_sql = "SELECT * FROM otp_verification WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 3";
    $debug_stmt = $conn->prepare($debug_sql);
    $debug_stmt->bind_param('ss', $user_id, $user_type);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    
    if ($debug_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>User Type</th><th>OTP</th><th>Email</th><th>Created</th><th>Expires</th><th>Used</th></tr>";
        while ($row = $debug_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['otp']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>{$row['expires_at']}</td>";
            echo "<td>" . ($row['is_used'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No OTP records found for user $user_id ($user_type)</p>";
    }
    
    // Try to verify OTP
    echo "<h3>OTP Verification Attempt:</h3>";
    echo "<p>Checking OTP: '$input_otp' for user '$user_id' type '$user_type'</p>";
    
    if (verifyOTPFromDB($conn, $user_id, $user_type, $input_otp)) {
        echo "<p style='color: green;'>✅ OTP verification successful!</p>";
        echo "<p>Redirecting to appropriate portal...</p>";
        // Here you would normally redirect, but for debugging we'll just show success
    } else {
        echo "<p style='color: red;'>❌ OTP verification failed!</p>";
        
        // Additional debugging - check if OTP exists with different criteria
        $debug_otp_sql = "SELECT *, NOW() as current_time FROM otp_verification WHERE user_id = ? AND user_type = ? AND otp = ?";
        $debug_otp_stmt = $conn->prepare($debug_otp_sql);
        $debug_otp_stmt->bind_param('sss', $user_id, $user_type, $input_otp);
        $debug_otp_stmt->execute();
        $debug_otp_result = $debug_otp_stmt->get_result();
        
        if ($debug_otp_result->num_rows > 0) {
            $otp_row = $debug_otp_result->fetch_assoc();
            echo "<h4>OTP Found but verification failed:</h4>";
            echo "<p>OTP: {$otp_row['otp']}</p>";
            echo "<p>Created: {$otp_row['created_at']}</p>";
            echo "<p>Expires: {$otp_row['expires_at']}</p>";
            echo "<p>Current Time: {$otp_row['current_time']}</p>";
            echo "<p>Is Used: " . ($otp_row['is_used'] ? 'Yes' : 'No') . "</p>";
            echo "<p>Is Expired: " . (strtotime($otp_row['expires_at']) < time() ? 'Yes' : 'No') . "</p>";
        } else {
            echo "<p>No matching OTP found in database.</p>";
        }
    }
    
} else {
    echo "<p>This page is for debugging OTP verification. Please submit the OTP form to see debug information.</p>";
    echo "<p><a href='verify_otp.php'>← Back to OTP Verification</a></p>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>
