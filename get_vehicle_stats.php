<?php
header('Content-Type: application/json');
include 'connection/db.php';

// Initialize response array
$response = [
    'total' => 0,
    'available' => 0,
    'reserved' => 0,
    'maintenance' => 0
];

try {
    // Check if vehicle_registration table exists
    $table_check = "SHOW TABLES LIKE 'vehicle_registration'";
    $table_result = $conn->query($table_check);
    
    if ($table_result && $table_result->num_rows > 0) {
        // Get total vehicles
        $total_query = "SELECT COUNT(*) as total FROM vehicle_registration";
        $total_result = $conn->query($total_query);
        if ($total_result) {
            $total_row = $total_result->fetch_assoc();
            $response['total'] = (int)$total_row['total'];
        }
        
        // Get available vehicles
        $available_query = "SELECT COUNT(*) as available FROM vehicle_registration WHERE Status = 'Available'";
        $available_result = $conn->query($available_query);
        if ($available_result) {
            $available_row = $available_result->fetch_assoc();
            $response['available'] = (int)$available_row['available'];
        }
        
        // Get reserved vehicles
        $reserved_query = "SELECT COUNT(*) as reserved FROM vehicle_registration WHERE Status = 'Reserved'";
        $reserved_result = $conn->query($reserved_query);
        if ($reserved_result) {
            $reserved_row = $reserved_result->fetch_assoc();
            $response['reserved'] = (int)$reserved_row['reserved'];
        }
        
        // Get maintenance vehicles
        $maintenance_query = "SELECT COUNT(*) as maintenance FROM vehicle_registration WHERE Status = 'Maintenance'";
        $maintenance_result = $conn->query($maintenance_query);
        if ($maintenance_result) {
            $maintenance_row = $maintenance_result->fetch_assoc();
            $response['maintenance'] = (int)$maintenance_row['maintenance'];
        }
    }
} catch (Exception $e) {
    // If there's an error, return default values
    error_log("Error getting vehicle stats: " . $e->getMessage());
}

// Close connection
$conn->close();

// Return JSON response
echo json_encode($response);
?>
