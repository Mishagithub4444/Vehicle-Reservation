<?php
include 'db_connection.php';

echo "<h2>Adding Sample Data</h2>";

// Check if we have vehicles
$vehicle_query = "SELECT COUNT(*) as vehicle_count FROM vehicle_registration";
$vehicle_result = $conn->query($vehicle_query);
$vehicle_count = $vehicle_result->fetch_assoc()['vehicle_count'];

if ($vehicle_count == 0) {
    echo "<p>Adding sample vehicles...</p>";
    
    $vehicles = [
        ['VEH-001', 'Toyota Corolla 2023', 'Sedan', 5, 'Automatic', 2500],
        ['VEH-002', 'Honda Civic 2022', 'Sedan', 5, 'Manual', 2200],
        ['VEH-003', 'Toyota Hiace 2023', 'Van', 12, 'Manual', 4000],
        ['VEH-004', 'Nissan X-Trail 2022', 'SUV', 7, 'Automatic', 3500],
        ['VEH-005', 'Honda Freed 2021', 'MPV', 8, 'Automatic', 3000]
    ];
    
    foreach ($vehicles as $vehicle) {
        $insert_vehicle = "INSERT INTO vehicle_registration (Vehicle_ID, Vehicle_Model, Vehicle_Type, Seating_Capacity, Transmission_Type, Cost_Per_Day, Availability_Status) VALUES (?, ?, ?, ?, ?, ?, 'Available')";
        $stmt = $conn->prepare($insert_vehicle);
        $stmt->bind_param("sssisd", $vehicle[0], $vehicle[1], $vehicle[2], $vehicle[3], $vehicle[4], $vehicle[5]);
        
        if ($stmt->execute()) {
            echo "✓ Added vehicle: {$vehicle[1]}<br>";
        } else {
            echo "✗ Failed to add vehicle: {$vehicle[1]} - " . $stmt->error . "<br>";
        }
    }
} else {
    echo "<p>Vehicles already exist in database ($vehicle_count found).</p>";
}

// Check if we have drivers
$driver_query = "SELECT COUNT(*) as driver_count FROM driver_registration WHERE Driver_Status = 'Active'";
$driver_result = $conn->query($driver_query);
$driver_count = $driver_result->fetch_assoc()['driver_count'];

if ($driver_count == 0) {
    echo "<p>Adding sample drivers...</p>";
    
    $drivers = [
        ['DRV-001', 'Ahmed', 'Rahman', '01712345678', 'ahmed@email.com'],
        ['DRV-002', 'Mohammad', 'Ali', '01812345678', 'ali@email.com'],
        ['DRV-003', 'Karim', 'Uddin', '01912345678', 'karim@email.com'],
        ['DRV-004', 'Rahim', 'Khan', '01612345678', 'rahim@email.com'],
        ['DRV-005', 'Salim', 'Ahmed', '01512345678', 'salim@email.com']
    ];
    
    foreach ($drivers as $driver) {
        $insert_driver = "INSERT INTO driver_registration (Driver_ID, First_Name, Last_Name, Phone_Number, Email, Driver_Status, Availability) VALUES (?, ?, ?, ?, ?, 'Active', 'Available')";
        $stmt = $conn->prepare($insert_driver);
        $stmt->bind_param("sssss", $driver[0], $driver[1], $driver[2], $driver[3], $driver[4]);
        
        if ($stmt->execute()) {
            echo "✓ Added driver: {$driver[1]} {$driver[2]}<br>";
        } else {
            echo "✗ Failed to add driver: {$driver[1]} {$driver[2]} - " . $stmt->error . "<br>";
        }
    }
} else {
    echo "<p>Drivers already exist in database ($driver_count found).</p>";
}

echo "<h3>Final Data Summary:</h3>";

// Final count check
$final_vehicle_count = $conn->query("SELECT COUNT(*) as count FROM vehicle_registration")->fetch_assoc()['count'];
$final_driver_count = $conn->query("SELECT COUNT(*) as count FROM driver_registration WHERE Driver_Status = 'Active'")->fetch_assoc()['count'];

echo "<p>Total vehicles: <strong>$final_vehicle_count</strong></p>";
echo "<p>Total active drivers: <strong>$final_driver_count</strong></p>";

echo "<p><a href='user_reservations.php'>Go to User Reservations</a></p>";

$conn->close();
?>
