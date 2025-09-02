<?php
include 'connection/db.php';

echo "<h2>Setting up Driver Earnings Database Tables</h2>";

// Create driver_earnings table
$driver_earnings_sql = "CREATE TABLE IF NOT EXISTS driver_earnings (
    earning_id int(11) NOT NULL AUTO_INCREMENT,
    driver_id varchar(50) NOT NULL,
    user_id varchar(50) DEFAULT NULL,
    vehicle_id varchar(50) DEFAULT NULL,
    trip_start_date date NOT NULL,
    trip_end_date date DEFAULT NULL,
    trip_duration_days int(11) NOT NULL DEFAULT 1,
    driver_cost decimal(10,2) NOT NULL DEFAULT 1500.00,
    payment_status enum('Pending','Confirmed','Paid','Cancelled') NOT NULL DEFAULT 'Pending',
    user_contact varchar(20) DEFAULT NULL,
    trip_route text DEFAULT NULL,
    notes text DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (earning_id),
    KEY idx_driver_id (driver_id),
    KEY idx_user_id (user_id),
    KEY idx_payment_status (payment_status),
    KEY idx_trip_date (trip_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($driver_earnings_sql) === TRUE) {
    echo "✅ driver_earnings table created successfully<br>";
} else {
    echo "❌ Error creating driver_earnings table: " . $conn->error . "<br>";
}

// Check if driver_registration table exists, if not create it
$driver_registration_sql = "CREATE TABLE IF NOT EXISTS driver_registration (
    Driver_ID varchar(50) NOT NULL,
    First_Name varchar(50) NOT NULL,
    Last_Name varchar(50) NOT NULL,
    Phone_Number varchar(20) DEFAULT NULL,
    Email varchar(100) DEFAULT NULL,
    Driver_Status enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    Availability enum('Available','Unavailable','On Trip') NOT NULL DEFAULT 'Available',
    License_Number varchar(50) DEFAULT NULL,
    Experience_Years int(11) DEFAULT NULL,
    Preferred_Vehicle_Type varchar(50) DEFAULT NULL,
    Current_Location varchar(255) DEFAULT NULL,
    Password varchar(255) NOT NULL,
    Created_At timestamp NOT NULL DEFAULT current_timestamp(),
    Updated_At timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (Driver_ID),
    UNIQUE KEY Email (Email),
    KEY idx_status (Driver_Status),
    KEY idx_availability (Availability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($driver_registration_sql) === TRUE) {
    echo "✅ driver_registration table created successfully<br>";
} else {
    echo "❌ Error creating driver_registration table: " . $conn->error . "<br>";
}

// Check if user_registration table exists, if not create it
$user_registration_sql = "CREATE TABLE IF NOT EXISTS user_registration (
    User_ID varchar(50) NOT NULL,
    First_Name varchar(50) NOT NULL,
    Last_Name varchar(50) NOT NULL,
    Phone_Number varchar(20) DEFAULT NULL,
    Email varchar(100) DEFAULT NULL,
    Password varchar(255) NOT NULL,
    Date_of_Birth date DEFAULT NULL,
    Gender varchar(10) DEFAULT NULL,
    Address varchar(255) DEFAULT NULL,
    City varchar(50) DEFAULT NULL,
    Account_Status enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    Created_At timestamp NOT NULL DEFAULT current_timestamp(),
    Updated_At timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (User_ID),
    UNIQUE KEY Email (Email),
    KEY idx_status (Account_Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($user_registration_sql) === TRUE) {
    echo "✅ user_registration table verified/created successfully<br>";
} else {
    echo "❌ Error with user_registration table: " . $conn->error . "<br>";
}

// Add sample data to driver_earnings if empty
$check_earnings = "SELECT COUNT(*) as count FROM driver_earnings";
$result = $conn->query($check_earnings);
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    echo "<br><h3>Adding sample earnings data...</h3>";
    
    $sample_earnings = [
        ['DRV-001', 'USR-001', 'VEH-001', '2025-08-15', '2025-08-17', 3, 4500.00, 'Paid', '01712345678', 'Dhaka to Chittagong', 'Smooth trip, customer satisfied'],
        ['DRV-001', 'USR-002', 'VEH-002', '2025-08-20', '2025-08-22', 2, 3000.00, 'Pending', '01812345678', 'Dhaka to Sylhet', 'Waiting for payment confirmation'],
        ['DRV-001', 'USR-003', 'VEH-003', '2025-09-01', '2025-09-01', 1, 1500.00, 'Confirmed', '01912345678', 'Local Dhaka tour', 'Day trip completed successfully'],
        ['DRV-002', 'USR-001', 'VEH-001', '2025-08-25', '2025-08-27', 2, 3000.00, 'Paid', '01712345678', 'Dhaka to Cox\'s Bazar', 'Long distance trip completed'],
        ['DRV-002', 'USR-003', 'VEH-002', '2025-09-02', '2025-09-02', 1, 1500.00, 'Pending', '01912345678', 'Airport pickup', 'Airport transfer service']
    ];
    
    $insert_sql = "INSERT INTO driver_earnings (driver_id, user_id, vehicle_id, trip_start_date, trip_end_date, trip_duration_days, driver_cost, payment_status, user_contact, trip_route, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    foreach ($sample_earnings as $earning) {
        $stmt->bind_param('sssssidsssss', $earning[0], $earning[1], $earning[2], $earning[3], $earning[4], $earning[5], $earning[6], $earning[7], $earning[8], $earning[9], $earning[10]);
        if ($stmt->execute()) {
            echo "✅ Added sample earning record for {$earning[0]}<br>";
        } else {
            echo "❌ Failed to add earning record: " . $stmt->error . "<br>";
        }
    }
    $stmt->close();
} else {
    echo "<br>📊 driver_earnings table already has $count records<br>";
}

// Add some sample drivers if needed
$check_drivers = "SELECT COUNT(*) as count FROM driver_registration";
$result = $conn->query($check_drivers);
$driver_count = $result->fetch_assoc()['count'];

if ($driver_count == 0) {
    echo "<br><h3>Adding sample driver data...</h3>";
    
    $sample_drivers = [
        ['DRV-001', 'Ahmed', 'Rahman', '01712345678', 'ahmed.driver@vrms.com', 'Active', 'Available', 'DL123456789', 5, password_hash('driver123', PASSWORD_DEFAULT)],
        ['DRV-002', 'Mohammad', 'Ali', '01812345678', 'ali.driver@vrms.com', 'Active', 'Available', 'DL987654321', 8, password_hash('driver123', PASSWORD_DEFAULT)],
        ['DRV-003', 'Karim', 'Uddin', '01912345678', 'karim.driver@vrms.com', 'Active', 'On Trip', 'DL456789123', 3, password_hash('driver123', PASSWORD_DEFAULT)]
    ];
    
    $driver_insert_sql = "INSERT INTO driver_registration (Driver_ID, First_Name, Last_Name, Phone_Number, Email, Driver_Status, Availability, License_Number, Experience_Years, Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $driver_stmt = $conn->prepare($driver_insert_sql);
    
    foreach ($sample_drivers as $driver) {
        $driver_stmt->bind_param('ssssssssss', $driver[0], $driver[1], $driver[2], $driver[3], $driver[4], $driver[5], $driver[6], $driver[7], $driver[8], $driver[9]);
        if ($driver_stmt->execute()) {
            echo "✅ Added sample driver: {$driver[1]} {$driver[2]}<br>";
        } else {
            echo "❌ Failed to add driver: " . $driver_stmt->error . "<br>";
        }
    }
    $driver_stmt->close();
} else {
    echo "<br>👨‍💼 driver_registration table already has $driver_count records<br>";
}

// Add some sample users if needed
$check_users = "SELECT COUNT(*) as count FROM user_registration";
$result = $conn->query($check_users);
$user_count = $result->fetch_assoc()['count'];

if ($user_count == 0) {
    echo "<br><h3>Adding sample user data...</h3>";
    
    $sample_users = [
        ['USR-001', 'Rahman', 'Khan', '01712345678', 'rahman@email.com', password_hash('user123', PASSWORD_DEFAULT), 'Active'],
        ['USR-002', 'Fatima', 'Ahmed', '01812345678', 'fatima@email.com', password_hash('user123', PASSWORD_DEFAULT), 'Active'],
        ['USR-003', 'Arif', 'Hassan', '01912345678', 'arif@email.com', password_hash('user123', PASSWORD_DEFAULT), 'Active']
    ];
    
    $user_insert_sql = "INSERT INTO user_registration (User_ID, First_Name, Last_Name, Phone_Number, Email, Password, Account_Status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $user_stmt = $conn->prepare($user_insert_sql);
    
    foreach ($sample_users as $user) {
        $user_stmt->bind_param('sssssss', $user[0], $user[1], $user[2], $user[3], $user[4], $user[5], $user[6]);
        if ($user_stmt->execute()) {
            echo "✅ Added sample user: {$user[1]} {$user[2]}<br>";
        } else {
            echo "❌ Failed to add user: " . $user_stmt->error . "<br>";
        }
    }
    $user_stmt->close();
} else {
    echo "<br>👥 user_registration table already has $user_count records<br>";
}

echo "<br><h3>✅ Database setup completed!</h3>";
echo "<p><a href='driver_portal.php' style='background: #4a90e2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Driver Portal</a></p>";
echo "<p><a href='driver_earnings_report.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Test Earnings Report</a></p>";

$conn->close();
?>
