<?php
header('Content-Type: application/json');
include 'connection/db.php';

// Initialize response array
$response = [
    'vehicles' => []
];

try {
    // Check if vehicle_registration table exists
    $table_check = "SHOW TABLES LIKE 'vehicle_registration'";
    $table_result = $conn->query($table_check);
    
    if ($table_result && $table_result->num_rows > 0) {
        // Get recent vehicles (last 5)
        $recent_query = "SELECT Vehicle_ID, Make, Model, Year, Status, Registration_Date 
                        FROM vehicle_registration 
                        ORDER BY Registration_Date DESC 
                        LIMIT 5";
        $recent_result = $conn->query($recent_query);
        
        if ($recent_result && $recent_result->num_rows > 0) {
            while ($row = $recent_result->fetch_assoc()) {
                $response['vehicles'][] = [
                    'vehicle_id' => $row['Vehicle_ID'],
                    'make' => $row['Make'],
                    'model' => $row['Model'],
                    'year' => $row['Year'],
                    'status' => $row['Status'],
                    'registration_date' => $row['Registration_Date']
                ];
            }
        }
    }
} catch (Exception $e) {
    // If there's an error, return empty array
    error_log("Error getting recent vehicles: " . $e->getMessage());
}

// Close connection
$conn->close();

// Return JSON response
echo json_encode($response);
?>
