<?php
session_start();
include 'connection/db.php';
include 'two_factor_auth.php';

echo "<h1>🔍 OTP Verification Debug - Step by Step</h1>";

// Check current session state
echo "<h2>Step 1: Current Session State</h2>";
echo "<pre>";
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'pending') !== false || strpos($key, 'temp') !== false || strpos($key, 'verification') !== false || strpos($key, 'user') !== false) {
        if (is_array($value)) {
            echo "$key: " . print_r($value, true);
        } else {
            echo "$key: $value\n";
        }
    }
}
echo "</pre>";

// Check what's in the OTP table
echo "<h2>Step 2: Current OTP Records</h2>";
$sql = "SELECT user_id, user_type, otp, email, created_at, expires_at, is_used, 
        TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_until_expiry 
        FROM otp_verification 
        ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Type</th><th>OTP</th><th>Email</th><th>Created</th><th>Expires</th><th>Used</th><th>Seconds Left</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $expired = $row['seconds_until_expiry'] < 0;
        $rowClass = $expired ? 'style="background-color: #ffe6e6;"' : ($row['is_used'] ? 'style="background-color: #e6f3ff;"' : '');
        echo "<tr $rowClass>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td><strong>{$row['otp']}</strong></td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['expires_at']}</td>";
        echo "<td>" . ($row['is_used'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($expired ? 'EXPIRED' : $row['seconds_until_expiry'] . 's') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><small>Red = Expired, Blue = Used, White = Valid</small></p>";
} else {
    echo "<p>❌ No OTP records found in database!</p>";
}

// Manual OTP test form
echo "<h2>Step 3: Manual OTP Test</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_otp'])) {
    $input_otp = trim($_POST['test_otp']);
    echo "<h3>Testing OTP: '$input_otp'</h3>";
    
    // Check if user is in 2FA process
    $user_pending = isset($_SESSION['user_pending_verification']);
    $driver_pending = isset($_SESSION['driver_pending_verification']);
    $admin_pending = isset($_SESSION['admin_pending_verification']);
    
    echo "<p><strong>Session Status:</strong></p>";
    echo "<ul>";
    echo "<li>User pending: " . ($user_pending ? 'Yes' : 'No') . "</li>";
    echo "<li>Driver pending: " . ($driver_pending ? 'Yes' : 'No') . "</li>";
    echo "<li>Admin pending: " . ($admin_pending ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
    
    if ($user_pending || $driver_pending || $admin_pending) {
        // Determine user type and get data
        if ($user_pending) {
            $user_type = 'user';
            $user_data = $_SESSION['user_temp_data'] ?? null;
            $user_id = $user_data['User_ID'] ?? '';
        } elseif ($driver_pending) {
            $user_type = 'driver';
            $user_data = $_SESSION['driver_temp_data'] ?? null;
            $user_id = $user_data['Driver_ID'] ?? '';
        } elseif ($admin_pending) {
            $user_type = 'admin';
            $user_data = $_SESSION['admin_temp_data'] ?? null;
            $user_id = $user_data['Admin_ID'] ?? '';
        }
        
        echo "<p><strong>Verification Details:</strong></p>";
        echo "<ul>";
        echo "<li>User Type: $user_type</li>";
        echo "<li>User ID: $user_id</li>";
        echo "</ul>";
        
        // Convert to string for consistency
        $user_id_str = (string)$user_id;
        
        // Check what OTP records exist for this specific user
        echo "<h4>OTP Records for User ID '$user_id_str' Type '$user_type':</h4>";
        $user_sql = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_left FROM otp_verification WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param('ss', $user_id_str, $user_type);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>OTP</th><th>Created</th><th>Expires</th><th>Used</th><th>Seconds Left</th><th>Match?</th></tr>";
            while ($row = $user_result->fetch_assoc()) {
                $match = ($row['otp'] === $input_otp) ? '✅ YES' : '❌ No';
                $valid = (!$row['is_used'] && $row['seconds_left'] > 0) ? '✅ Valid' : '❌ Invalid';
                echo "<tr>";
                echo "<td><strong>{$row['otp']}</strong></td>";
                echo "<td>{$row['created_at']}</td>";
                echo "<td>{$row['expires_at']}</td>";
                echo "<td>" . ($row['is_used'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$row['seconds_left']}s</td>";
                echo "<td>$match</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ No OTP records found for this user!</p>";
        }
        
        // Test the actual verification function
        echo "<h4>Testing verifyOTPFromDB Function:</h4>";
        if (verifyOTPFromDB($conn, $user_id_str, $user_type, $input_otp)) {
            echo "<p style='color: green;'>✅ verifyOTPFromDB returned TRUE - OTP is valid!</p>";
        } else {
            echo "<p style='color: red;'>❌ verifyOTPFromDB returned FALSE - OTP verification failed!</p>";
            
            // Additional debugging
            echo "<h5>Detailed Debugging:</h5>";
            echo "<p>Checking verification query manually...</p>";
            $manual_sql = "SELECT id, otp, expires_at, is_used, NOW() as current_time FROM otp_verification WHERE user_id = ? AND user_type = ? AND otp = ? AND expires_at > NOW() AND is_used = FALSE ORDER BY created_at DESC LIMIT 1";
            $manual_stmt = $conn->prepare($manual_sql);
            $manual_stmt->bind_param('sss', $user_id_str, $user_type, $input_otp);
            $manual_stmt->execute();
            $manual_result = $manual_stmt->get_result();
            
            if ($manual_result->num_rows > 0) {
                $manual_row = $manual_result->fetch_assoc();
                echo "<p>✅ Found matching OTP record but verification still failed!</p>";
                echo "<p>Record details:</p>";
                echo "<ul>";
                echo "<li>ID: {$manual_row['id']}</li>";
                echo "<li>OTP: {$manual_row['otp']}</li>";
                echo "<li>Expires: {$manual_row['expires_at']}</li>";
                echo "<li>Current: {$manual_row['current_time']}</li>";
                echo "<li>Used: " . ($manual_row['is_used'] ? 'Yes' : 'No') . "</li>";
                echo "</ul>";
            } else {
                echo "<p>❌ No matching valid OTP found with manual query</p>";
                
                // Check each condition separately
                echo "<h6>Checking each condition:</h6>";
                $cond1_sql = "SELECT COUNT(*) as count FROM otp_verification WHERE user_id = ?";
                $cond1_stmt = $conn->prepare($cond1_sql);
                $cond1_stmt->bind_param('s', $user_id_str);
                $cond1_stmt->execute();
                $cond1_result = $cond1_stmt->get_result();
                $cond1_row = $cond1_result->fetch_assoc();
                echo "<p>Records with matching user_id: {$cond1_row['count']}</p>";
                
                $cond2_sql = "SELECT COUNT(*) as count FROM otp_verification WHERE user_id = ? AND user_type = ?";
                $cond2_stmt = $conn->prepare($cond2_sql);
                $cond2_stmt->bind_param('ss', $user_id_str, $user_type);
                $cond2_stmt->execute();
                $cond2_result = $cond2_stmt->get_result();
                $cond2_row = $cond2_result->fetch_assoc();
                echo "<p>Records with matching user_id + user_type: {$cond2_row['count']}</p>";
                
                $cond3_sql = "SELECT COUNT(*) as count FROM otp_verification WHERE user_id = ? AND user_type = ? AND otp = ?";
                $cond3_stmt = $conn->prepare($cond3_sql);
                $cond3_stmt->bind_param('sss', $user_id_str, $user_type, $input_otp);
                $cond3_stmt->execute();
                $cond3_result = $cond3_stmt->get_result();
                $cond3_row = $cond3_result->fetch_assoc();
                echo "<p>Records with matching user_id + user_type + otp: {$cond3_row['count']}</p>";
            }
        }
        
    } else {
        echo "<p>❌ No valid 2FA session found. Please start the login process first.</p>";
    }
}

echo '<form method="POST" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">';
echo '<h3>Test OTP Verification:</h3>';
echo '<input type="text" name="test_otp" placeholder="Enter 6-digit OTP" maxlength="6" required style="padding: 10px; font-size: 16px; width: 150px;">';
echo '<button type="submit" style="padding: 10px 20px; margin-left: 10px;">Test Verify</button>';
echo '</form>';

echo "<h2>Step 4: Start Fresh Test</h2>";
echo "<p>If you need to start a fresh test:</p>";
echo "<ol>";
echo "<li><a href='user_login.php' target='_blank'>Go to User Login</a></li>";
echo "<li>Use: john_doe / 1001 / password123</li>";
echo "<li>Check 2FA checkbox and submit</li>";
echo "<li>Note the OTP from the alert</li>";
echo "<li>Come back here and test that OTP</li>";
echo "</ol>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    h1, h2, h3 { color: #2c3e50; }
</style>
