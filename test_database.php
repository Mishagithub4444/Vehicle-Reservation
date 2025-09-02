<?php
include 'connection/db.php';

echo "<h2>Database Test - User Registration Table</h2>";

// Check if the table exists and get some sample data
$sql = "SELECT * FROM user_registration LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        echo "<h3>Sample Users Found:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>User_ID</th><th>User_Name</th><th>First_Name</th><th>Last_Name</th><th>Email</th><th>Password</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['User_ID'] . "</td>";
            echo "<td>" . $row['User_Name'] . "</td>";
            echo "<td>" . $row['First_Name'] . "</td>";
            echo "<td>" . $row['Last_Name'] . "</td>";
            echo "<td>" . $row['Email'] . "</td>";
            echo "<td>" . substr($row['User_Password'], 0, 10) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Test 2FA with any of these users:</h3>";
        echo "<p>1. Go to <a href='user_login.php' target='_blank'>user_login.php</a></p>";
        echo "<p>2. Use the credentials above</p>";
        echo "<p>3. Check the '2FA Enhanced Security' checkbox</p>";
        echo "<p>4. Click Sign In</p>";
    } else {
        echo "<p>No users found in the database.</p>";
        echo "<p>Please register a user first or run the sample data script.</p>";
    }
} else {
    echo "<p>Error: " . $conn->error . "</p>";
    echo "<p>The user_registration table might not exist yet.</p>";
}

// Check if OTP table exists
echo "<h3>OTP Table Status:</h3>";
$otp_table_check = "SHOW TABLES LIKE 'otp_verification'";
$otp_result = $conn->query($otp_table_check);

if ($otp_result && $otp_result->num_rows > 0) {
    echo "✅ OTP table exists<br>";
    
    // Check for any OTP records
    $otp_count = $conn->query("SELECT COUNT(*) as count FROM otp_verification");
    if ($otp_count) {
        $count_row = $otp_count->fetch_assoc();
        echo "OTP records in table: " . $count_row['count'] . "<br>";
    }
} else {
    echo "❌ OTP table does not exist yet<br>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>
