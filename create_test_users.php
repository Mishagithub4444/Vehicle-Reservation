<?php
include 'connection/db.php';

echo "<h2>Creating Sample Users for 2FA Testing</h2>";

// Create user_registration table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS user_registration (
    User_ID INT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    User_Name VARCHAR(50) UNIQUE NOT NULL,
    Phone_Number BIGINT,
    Date_of_Birth DATE,
    Gender VARCHAR(10),
    Email VARCHAR(100),
    Address TEXT,
    User_Type VARCHAR(20),
    User_Password VARCHAR(255) NOT NULL
)";

if ($conn->query($create_table_sql)) {
    echo "✅ User registration table created/verified<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
    exit();
}

// Sample users data
$sample_users = [
    [1001, 'John', 'Doe', 'john_doe', 1234567890, '1990-01-15', 'Male', 'john.doe@email.com', '123 Main St', 'Standard', 'password123'],
    [1002, 'Jane', 'Smith', 'jane_smith', 9876543210, '1992-03-22', 'Female', 'jane.smith@email.com', '456 Oak Ave', 'Premium', 'password456'],
    [1003, 'Mike', 'Johnson', 'mike_johnson', 5555551234, '1988-07-10', 'Male', 'mike.johnson@email.com', '789 Pine Rd', 'Standard', 'password789']
];

echo "<h3>Adding Sample Users:</h3>";

foreach ($sample_users as $user) {
    // Check if user already exists
    $check_sql = "SELECT User_ID FROM user_registration WHERE User_ID = ? OR User_Name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('is', $user[0], $user[2]);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo "⚠️ User {$user[2]} (ID: {$user[0]}) already exists<br>";
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO user_registration (User_ID, First_Name, Last_Name, User_Name, Phone_Number, Date_of_Birth, Gender, Email, Address, User_Type, User_Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('isssssssss', $user[0], $user[1], $user[2], $user[3], $user[4], $user[5], $user[6], $user[7], $user[8], $user[9], $user[10]);
        
        if ($insert_stmt->execute()) {
            echo "✅ User {$user[2]} (ID: {$user[0]}) created successfully<br>";
        } else {
            echo "❌ Error creating user {$user[2]}: " . $conn->error . "<br>";
        }
    }
}

echo "<h3>Test 2FA Login:</h3>";
echo "<p><strong>Test User Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Username:</strong> john_doe | <strong>User ID:</strong> 1001 | <strong>Password:</strong> password123</li>";
echo "<li><strong>Username:</strong> jane_smith | <strong>User ID:</strong> 1002 | <strong>Password:</strong> password456</li>";
echo "<li><strong>Username:</strong> mike_johnson | <strong>User ID:</strong> 1003 | <strong>Password:</strong> password789</li>";
echo "</ul>";

echo "<h4>Steps to Test 2FA:</h4>";
echo "<ol>";
echo "<li>Go to <a href='user_login.php' target='_blank'>User Login Page</a></li>";
echo "<li>Enter one of the test user credentials above</li>";
echo "<li><strong>✅ Check the 'Sign In With 2FA (Enhanced Security)' checkbox</strong></li>";
echo "<li>Click 'Sign In'</li>";
echo "<li>You should see an alert with the OTP code</li>";
echo "<li>Enter the OTP on the verification page</li>";
echo "</ol>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    li { margin: 5px 0; }
    strong { color: #2c3e50; }
</style>
