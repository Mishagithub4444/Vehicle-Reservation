<?php

/**
 * Quick Driver Location Insert - Manually add specific drivers
 * This will add Muktar Ali and Riyad Rahman directly to the database
 */

session_start();
include 'connection/db.php';

echo "<h1>➕ Add Specific Drivers</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.result { background: #d4edda; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #28a745; }
.error { background: #f8d7da; border-left-color: #dc3545; }
</style>";

// Specific drivers to add
$drivers_to_add = [
    [
        'name' => 'Muktar Ali',
        'email' => 'muktar.ali@vrms.com',
        'phone' => '+8801712345001',
        'location' => 'Bashundhara Residential Area, Road 27',
        'city' => 'Dhaka',
        'landmark' => 'Near Bashundhara City Shopping Mall',
        'status' => 'Available'
    ],
    [
        'name' => 'Riyad Rahman',
        'email' => 'riyad.rahman@vrms.com',
        'phone' => '+8801812345002',
        'location' => 'Dhanmondi 32, Road 2/A',
        'city' => 'Dhaka',
        'landmark' => 'Near Dhanmondi Lake Park',
        'status' => 'Available'
    ],
    [
        'name' => 'Sabbir Hossain',
        'email' => 'sabbir.hossain@vrms.com',
        'phone' => '+8801912345003',
        'location' => 'Gulshan 2, Road 53',
        'city' => 'Dhaka',
        'landmark' => 'Near Gulshan Lake Park',
        'status' => 'Available'
    ],
    [
        'name' => 'Fahim Ahmed',
        'email' => 'fahim.ahmed@vrms.com',
        'phone' => '+8801612345004',
        'location' => 'Uttara Sector 10, Road 17',
        'city' => 'Dhaka',
        'landmark' => 'Near Uttara Central Park',
        'status' => 'Available'
    ]
];

// First, clean up any existing test entries
$cleanup_sql = "DELETE FROM driver_locations WHERE Driver_Email IN (
    'muktar.ali@vrms.com', 
    'riyad.rahman@vrms.com', 
    'sabbir.hossain@vrms.com', 
    'fahim.ahmed@vrms.com'
)";
$conn->query($cleanup_sql);

echo "<p>Cleaned up existing entries...</p>";

// Add each driver
$success_count = 0;
foreach ($drivers_to_add as $driver) {
    $sql = "INSERT INTO driver_locations (Driver_Name, Driver_Email, Driver_Phone, Current_Location, Current_City, Landmark, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "sssssss",
            $driver['name'],
            $driver['email'],
            $driver['phone'],
            $driver['location'],
            $driver['city'],
            $driver['landmark'],
            $driver['status']
        );

        if ($stmt->execute()) {
            $success_count++;
            echo "<div class='result'>";
            echo "<strong>✅ Added:</strong> {$driver['name']} at {$driver['location']}<br>";
            echo "<strong>📧 Email:</strong> {$driver['email']}<br>";
            echo "<strong>📞 Phone:</strong> {$driver['phone']}<br>";
            echo "<strong>🏢 Landmark:</strong> {$driver['landmark']}<br>";
            echo "<strong>📊 Status:</strong> {$driver['status']}";
            echo "</div>";
        } else {
            echo "<div class='result error'>";
            echo "<strong>❌ Failed to add:</strong> {$driver['name']} - " . $stmt->error;
            echo "</div>";
        }
        $stmt->close();
    }
}

echo "<hr>";
echo "<h2>📊 Summary</h2>";
echo "<p>Successfully added <strong>$success_count</strong> out of " . count($drivers_to_add) . " drivers.</p>";

// Show current database state
$sql = "SELECT Driver_Name, Current_Location, Status, Last_Updated FROM driver_locations ORDER BY Last_Updated DESC LIMIT 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Current Drivers in Database (Latest 10):</h3>";
    echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #ddd;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Driver Name</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Location</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Last Updated</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        $status_color = $row['Status'] == 'Available' ? '#28a745' : '#ffc107';
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>{$row['Driver_Name']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['Current_Location']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; color: $status_color;'><strong>{$row['Status']}</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$row['Last_Updated']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h2>🔗 Quick Links</h2>";
echo "<p>";
echo "<a href='user_portal.php' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View in User Portal</a>";
echo "<a href='debug_driver_locations.php' target='_blank' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Debug Info</a>";
echo "<a href='get_driver_locations.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Test API</a>";
echo "</p>";

echo "<div class='result' style='margin-top: 20px;'>";
echo "<h3>✅ Success!</h3>";
echo "<p><strong>Muktar Ali</strong> and <strong>Riyad Rahman</strong> (plus 2 additional drivers) have been added to the database.</p>";
echo "<p>They should now appear in the <strong>\"Available Drivers Near You\"</strong> section of the User Portal.</p>";
echo "<p>The system will auto-refresh every 30 seconds, or you can click the refresh button.</p>";
echo "</div>";

$conn->close();
