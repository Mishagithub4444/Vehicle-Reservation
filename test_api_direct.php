<?php
// Test the user location API endpoint directly
include 'connection/db.php';

echo "🧪 Testing User Location API Endpoint\n\n";

// Test the database query directly first
echo "1. Testing direct database query...\n";
$query = "SELECT ul.*, ur.First_Name, ur.Last_Name, ur.User_Name, ur.Phone_Number, ur.Email 
          FROM user_locations ul 
          JOIN user_registration ur ON ul.User_ID = ur.User_ID 
          WHERE ul.Status = 'Active' 
          ORDER BY ul.Last_Updated DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    $locations = [];
    while ($location = mysqli_fetch_assoc($result)) {
        $locations[] = [
            'user_id' => $location['User_ID'],
            'user_name' => $location['First_Name'] . ' ' . $location['Last_Name'],
            'username' => $location['User_Name'],
            'phone' => $location['Phone_Number'],
            'email' => $location['Email'],
            'main_location' => $location['Main_Location'],
            'city' => $location['City'],
            'landmark' => $location['Landmark'],
            'latitude' => $location['Latitude'],
            'longitude' => $location['Longitude'],
            'last_updated' => $location['Last_Updated']
        ];
    }

    echo "   ✅ Query successful, found " . count($locations) . " locations\n";

    if (count($locations) > 0) {
        echo "   📋 Sample data:\n";
        foreach ($locations as $location) {
            echo "      - {$location['user_name']} ({$location['username']}): {$location['main_location']}\n";
        }
    }

    echo "\n2. Simulating API response...\n";
    $api_response = [
        'success' => true,
        'count' => count($locations),
        'data' => $locations
    ];
    echo "   📤 API Response: " . json_encode($api_response, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Query failed: " . mysqli_error($conn) . "\n";
}

echo "\n3. Testing API URL accessibility...\n";
echo "   🔗 API URL: http://localhost/VRMS/user_location_api.php?action=get_all_user_locations\n";
echo "   💡 You can test this URL directly in browser while logged in as driver\n\n";

echo "✅ Database query test complete!\n";
echo "🎯 If this works but driver portal doesn't, the issue is in:\n";
echo "   - Session handling\n";
echo "   - JavaScript fetch request\n";
echo "   - CORS/headers\n";

mysqli_close($conn);
