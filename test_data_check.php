<?php
include 'db_connection.php';

echo "<h2>Database Data Check</h2>";

// Check vehicles
$vehicle_query = "SELECT COUNT(*) as vehicle_count FROM vehicle_registration";
$vehicle_result = $conn->query($vehicle_query);
$vehicle_count = $vehicle_result->fetch_assoc()['vehicle_count'];
echo "<p>Total vehicles in database: <strong>$vehicle_count</strong></p>";

if ($vehicle_count > 0) {
    echo "<h3>Sample Vehicles:</h3>";
    $sample_vehicles = "SELECT Vehicle_ID, Vehicle_Model, Vehicle_Type, Cost_Per_Day FROM vehicle_registration LIMIT 5";
    $sample_result = $conn->query($sample_vehicles);
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Model</th><th>Type</th><th>Cost/Day</th></tr>";
    while ($vehicle = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$vehicle['Vehicle_ID']}</td>";
        echo "<td>{$vehicle['Vehicle_Model']}</td>";
        echo "<td>{$vehicle['Vehicle_Type']}</td>";
        echo "<td>BDT {$vehicle['Cost_Per_Day']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check drivers
$driver_query = "SELECT COUNT(*) as driver_count FROM driver_registration WHERE Driver_Status = 'Active'";
$driver_result = $conn->query($driver_query);
$driver_count = $driver_result->fetch_assoc()['driver_count'];
echo "<p>Total active drivers in database: <strong>$driver_count</strong></p>";

if ($driver_count > 0) {
    echo "<h3>Sample Drivers:</h3>";
    $sample_drivers = "SELECT Driver_ID, First_Name, Last_Name, Availability FROM driver_registration WHERE Driver_Status = 'Active' LIMIT 5";
    $sample_result = $conn->query($sample_drivers);
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Availability</th></tr>";
    while ($driver = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$driver['Driver_ID']}</td>";
        echo "<td>{$driver['First_Name']} {$driver['Last_Name']}</td>";
        echo "<td>{$driver['Availability']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check reservations table structure
echo "<h3>Reservations Table Columns:</h3>";
$columns_query = "DESCRIBE vehicle_reservations";
$columns_result = $conn->query($columns_query);
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($column = $columns_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$column['Field']}</td>";
    echo "<td>{$column['Type']}</td>";
    echo "<td>{$column['Null']}</td>";
    echo "<td>{$column['Key']}</td>";
    echo "<td>{$column['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
