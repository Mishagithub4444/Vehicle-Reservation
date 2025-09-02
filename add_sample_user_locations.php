<?php
// Add some sample user location data for testing
include 'connection/db.php';

echo "🌟 Adding sample user location data...\n\n";

// Get some users from the database
$users_query = "SELECT User_ID, First_Name, Last_Name FROM user_registration LIMIT 3";
$users_result = mysqli_query($conn, $users_query);

if ($users_result && mysqli_num_rows($users_result) > 0) {
    $sample_locations = [
        [
            'location' => 'Downtown Chicago',
            'city' => 'Chicago',
            'landmark' => 'Near Willis Tower',
            'lat' => 41.8781,
            'lng' => -87.6298
        ],
        [
            'location' => 'Manhattan NYC',
            'city' => 'New York',
            'landmark' => 'Near Central Park',
            'lat' => 40.7829,
            'lng' => -73.9654
        ],
        [
            'location' => 'Beverly Hills',
            'city' => 'Los Angeles',
            'landmark' => 'Rodeo Drive Area',
            'lat' => 34.0736,
            'lng' => -118.4004
        ]
    ];

    $i = 0;
    while (($user = mysqli_fetch_assoc($users_result)) && $i < count($sample_locations)) {
        $location_data = $sample_locations[$i];

        // Insert or update location for this user
        $insert_query = "INSERT INTO user_locations (User_ID, Main_Location, City, Landmark, Latitude, Longitude, Status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Active')
                        ON DUPLICATE KEY UPDATE 
                        Main_Location = VALUES(Main_Location),
                        City = VALUES(City),
                        Landmark = VALUES(Landmark),
                        Latitude = VALUES(Latitude),
                        Longitude = VALUES(Longitude),
                        Last_Updated = NOW(),
                        Status = 'Active'";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "isssdd",
            $user['User_ID'],
            $location_data['location'],
            $location_data['city'],
            $location_data['landmark'],
            $location_data['lat'],
            $location_data['lng']
        );

        if ($stmt->execute()) {
            echo "✅ Added location for {$user['First_Name']} {$user['Last_Name']}: {$location_data['location']}\n";
        } else {
            echo "❌ Failed to add location for {$user['First_Name']} {$user['Last_Name']}: " . $stmt->error . "\n";
        }

        $stmt->close();
        $i++;
    }
} else {
    echo "❌ No users found in the database. Please register some users first.\n";
}

// Check the results
echo "\n📊 Current user locations:\n";
$result = mysqli_query($conn, "SELECT ul.*, ur.First_Name, ur.Last_Name 
                               FROM user_locations ul 
                               JOIN user_registration ur ON ul.User_ID = ur.User_ID 
                               WHERE ul.Status = 'Active'
                               ORDER BY ul.Last_Updated DESC");

if ($result && mysqli_num_rows($result) > 0) {
    while ($location = mysqli_fetch_assoc($result)) {
        echo "   👤 {$location['First_Name']} {$location['Last_Name']}: {$location['Main_Location']}, {$location['City']}\n";
    }
} else {
    echo "   No active user locations found.\n";
}

echo "\n✅ Sample data setup complete! Now you can:\n";
echo "   1. Go to driver portal to see user locations\n";
echo "   2. Test the refresh functionality\n";
echo "   3. Try the contact and map features\n\n";

mysqli_close($conn);
