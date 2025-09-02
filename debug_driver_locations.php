<?php

/**
 * Debug Driver Locations - Shows what data is in the database and what the API returns
 */

session_start();
include 'connection/db.php';

// Check if driver_locations table exists
$check_table = "SHOW TABLES LIKE 'driver_locations'";
$result = $conn->query($check_table);

echo "<h1>🔍 Driver Location Debug Info</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.debug-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
.success { border-left-color: #28a745; background: #d4edda; }
.error { border-left-color: #dc3545; background: #f8d7da; }
.warning { border-left-color: #ffc107; background: #fff3cd; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";

if ($result->num_rows == 0) {
    echo "<div class='debug-section error'>";
    echo "<h2>❌ Table Missing</h2>";
    echo "<p>The driver_locations table does not exist. You need to create it first.</p>";
    echo "</div>";
    exit();
}

// 1. Check raw database data
echo "<div class='debug-section'>";
echo "<h2>📊 Raw Database Data</h2>";

$sql = "SELECT * FROM driver_locations ORDER BY Last_Updated DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p>Found <strong>" . $result->num_rows . "</strong> records in driver_locations table:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Driver_ID</th><th>Driver_Name</th><th>Email</th><th>Phone</th><th>Location</th><th>City</th><th>Landmark</th><th>Status</th><th>Last_Updated</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ID'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Driver_ID'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Driver_Name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Driver_Email'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Driver_Phone'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Current_Location'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Current_City'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Landmark'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Status'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Last_Updated'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>No records found in driver_locations table.</div>";
}
echo "</div>";

// 2. Test the API that user portal uses
echo "<div class='debug-section'>";
echo "<h2>🔌 API Response Test</h2>";

// Simulate what the API returns
$sql = "SELECT 
            dl.Driver_ID,
            dl.Driver_Name,
            dl.Driver_Email,
            dl.Driver_Phone,
            dl.Current_Location,
            dl.Current_City,
            dl.Landmark,
            dl.Status,
            dl.Last_Updated
        FROM driver_locations dl
        WHERE dl.Current_Location IS NOT NULL 
        AND dl.Current_Location != ''
        ORDER BY dl.Last_Updated DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p>API would return <strong>" . $result->num_rows . "</strong> drivers:</p>";
    echo "<div style='background: #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>";

    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        // Parse coordinates if stored in Current_City field
        $coordinates = null;
        if ($row['Current_City'] && strpos($row['Current_City'], ',') !== false) {
            $coords = explode(',', $row['Current_City']);
            if (count($coords) == 2) {
                $coordinates = [
                    'lat' => trim($coords[0]),
                    'lng' => trim($coords[1])
                ];
            }
        }

        $drivers[] = [
            'Driver_ID' => $row['Driver_ID'],
            'Driver_Name' => $row['Driver_Name'],
            'Driver_Email' => $row['Driver_Email'],
            'Driver_Phone' => $row['Driver_Phone'],
            'Current_Location' => $row['Current_Location'],
            'Current_City' => $coordinates ? 'GPS Location Set' : $row['Current_City'],
            'Landmark' => $row['Landmark'],
            'Status' => $row['Status'],
            'Last_Updated' => $row['Last_Updated'],
            'coordinates' => $coordinates
        ];
    }

    echo json_encode(['success' => true, 'drivers' => $drivers, 'count' => count($drivers)], JSON_PRETTY_PRINT);
    echo "</div>";
} else {
    echo "<div class='warning'>API would return: No drivers found with locations set.</div>";
}
echo "</div>";

// 3. Check session status
echo "<div class='debug-section'>";
echo "<h2>👤 Session Status</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User Logged In:</strong> " . (isset($_SESSION['user_logged_in']) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Admin Logged In:</strong> " . (isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Driver Logged In:</strong> " . (isset($_SESSION['driver_logged_in']) ? 'Yes' : 'No') . "</p>";

if (isset($_SESSION['first_name'])) {
    echo "<p><strong>Current User:</strong> " . htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) . "</p>";
}
if (isset($_SESSION['email'])) {
    echo "<p><strong>Email:</strong> " . htmlspecialchars($_SESSION['email']) . "</p>";
}
echo "</div>";

// 4. Test API access
echo "<div class='debug-section'>";
echo "<h2>🧪 Live API Test</h2>";
echo "<p>Testing if get_driver_locations.php is accessible...</p>";

$can_access_api = isset($_SESSION['user_logged_in']) || isset($_SESSION['admin_logged_in']);
if ($can_access_api) {
    echo "<div class='success'>✅ API access should work (user or admin is logged in)</div>";

    echo "<iframe src='get_driver_locations.php?test=1' style='display: none;' onload='console.log(\"API loaded\")'></iframe>";
    echo "<p><a href='get_driver_locations.php' target='_blank'>Click here to test the API directly</a></p>";
} else {
    echo "<div class='error'>❌ API access will fail (no user or admin session)</div>";
}
echo "</div>";

// 5. Instructions
echo "<div class='debug-section success'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<ul>";
echo "<li><strong>If no drivers are shown above:</strong> Run the test integration script first</li>";
echo "<li><strong>If drivers exist but user portal doesn't show them:</strong> Check the browser console for JavaScript errors</li>";
echo "<li><strong>If API access fails:</strong> Make sure you're logged in as a user or admin</li>";
echo "<li><strong>If you see Muktar Ali and Riyad Rahman above:</strong> They should appear in the user portal now</li>";
echo "</ul>";
echo "<p><strong>Quick Actions:</strong></p>";
echo "<p><a href='test_driver_integration.php' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Run Integration Test</a>";
echo "<a href='user_portal.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Open User Portal</a></p>";
echo "</div>";

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Driver Location Debug</title>
    <script>
        // Auto-refresh every 10 seconds to see live changes
        setTimeout(function() {
            window.location.reload();
        }, 10000);

        console.log("Debug page loaded - will auto-refresh in 10 seconds");
    </script>
</head>

<body>
    <p style="color: #666; font-size: 12px; margin-top: 20px;">
        <em>This page auto-refreshes every 10 seconds to show live data changes.</em>
    </p>
</body>

</html>