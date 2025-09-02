<?php
include 'connection/db.php';

echo "<h2>Adding Sample Vehicles with Correct Structure</h2>";

// Check current vehicle count
$count_query = "SELECT COUNT(*) as count FROM vehicle_registration";
$count_result = $conn->query($count_query);
$current_count = $count_result->fetch_assoc()['count'];

echo "<p>Current vehicles in database: <strong>$current_count</strong></p>";

if ($current_count == 0) {
    echo "<p>Adding sample vehicles...</p>";
    
    $vehicles = [
        ['VEH-001', 'ABC-1234', 'Toyota', 'Corolla', 2023, 'Sedan', 'White', 'Petrol', 1.8, 'Automatic', 5, 2500.00, 'AC, Power Steering, ABS', 'Reliable sedan for city driving', 'Available', 15000],
        ['VEH-002', 'XYZ-5678', 'Honda', 'Civic', 2022, 'Sedan', 'Silver', 'Petrol', 1.5, 'Manual', 5, 2200.00, 'AC, Bluetooth, USB', 'Fuel efficient compact car', 'Available', 12000],
        ['VEH-003', 'DEF-9012', 'Toyota', 'Hiace', 2023, 'Van', 'White', 'Diesel', 2.8, 'Manual', 12, 4000.00, 'AC, High Roof, GPS', 'Large van for group travel', 'Available', 8000],
        ['VEH-004', 'GHI-3456', 'Nissan', 'X-Trail', 2022, 'SUV', 'Black', 'Petrol', 2.5, 'Automatic', 7, 3500.00, 'AWD, AC, Sunroof', 'Family SUV with good comfort', 'Available', 18000],
        ['VEH-005', 'JKL-7890', 'Honda', 'Freed', 2021, 'MPV', 'Blue', 'Hybrid', 1.5, 'CVT', 8, 3000.00, 'Hybrid, AC, Sliding Doors', 'Eco-friendly family car', 'Available', 22000]
    ];
    
    foreach ($vehicles as $vehicle) {
        $insert_sql = "INSERT INTO vehicle_registration 
                      (Vehicle_ID, License_Plate, Make, Model, Year, Vehicle_Type, Color, Fuel_Type, Engine_Size, Transmission, Seating_Capacity, Rental_Rate, Features, Description, Status, Mileage) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssssississdssssi", 
            $vehicle[0], $vehicle[1], $vehicle[2], $vehicle[3], $vehicle[4], 
            $vehicle[5], $vehicle[6], $vehicle[7], $vehicle[8], $vehicle[9], 
            $vehicle[10], $vehicle[11], $vehicle[12], $vehicle[13], $vehicle[14], $vehicle[15]);
        
        if ($stmt->execute()) {
            echo "✓ Added vehicle: {$vehicle[2]} {$vehicle[3]} {$vehicle[4]}<br>";
        } else {
            echo "✗ Failed to add vehicle: {$vehicle[2]} {$vehicle[3]} - " . $stmt->error . "<br>";
        }
    }
} else {
    echo "<p>Vehicles already exist in database.</p>";
}

// Final count
$final_count_result = $conn->query($count_query);
$final_count = $final_count_result->fetch_assoc()['count'];
echo "<h3>Final vehicle count: <strong>$final_count</strong></h3>";

echo "<p><a href='user_reservations.php'>Test Vehicle Loading</a></p>";

$conn->close();
?>
