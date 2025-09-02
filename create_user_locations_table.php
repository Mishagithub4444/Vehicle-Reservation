<?php
// Create user locations table for storing user location data
include 'connection/db.php';

// SQL to create user_locations table
$sql = "CREATE TABLE IF NOT EXISTS user_locations (
    Location_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Main_Location VARCHAR(255) NOT NULL,
    City VARCHAR(100),
    Landmark VARCHAR(255),
    Latitude DECIMAL(10, 8) NULL,
    Longitude DECIMAL(11, 8) NULL,
    Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    FOREIGN KEY (User_ID) REFERENCES user_registration(User_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_user_location (User_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ User locations table created successfully!\n";

    // Check if table exists and show structure
    $result = mysqli_query($conn, "DESCRIBE user_locations");
    if ($result) {
        echo "\n📋 Table Structure:\n";
        echo "+-----------------+------------------+------+-----+---------+----------------+\n";
        echo "| Field           | Type             | Null | Key | Default | Extra          |\n";
        echo "+-----------------+------------------+------+-----+---------+----------------+\n";

        while ($row = mysqli_fetch_assoc($result)) {
            printf(
                "| %-15s | %-16s | %-4s | %-3s | %-7s | %-14s |\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key'],
                $row['Default'] ?? 'NULL',
                $row['Extra']
            );
        }
        echo "+-----------------+------------------+------+-----+---------+----------------+\n";
    }

    // Check current count
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM user_locations");
    $count = mysqli_fetch_assoc($count_result)['count'];
    echo "\n📊 Current records in user_locations table: {$count}\n";
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
