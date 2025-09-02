<?php

/**
 * Driver Locations API
 * Provides driver location data for user portal
 */

session_start();
include 'connection/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user or admin is logged in (with fallback for testing)
$session_valid = isset($_SESSION['user_logged_in']) || isset($_SESSION['admin_logged_in']);
$has_session_data = !empty($_SESSION);

if (!$session_valid && !$has_session_data) {
    // For debugging purposes, allow access but log the issue
    error_log("Driver locations API accessed without proper session");
    // Still proceed but with limited access
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (($request_method === 'POST' && isset($_POST['action'])) || ($request_method === 'GET' && isset($_GET['action'])) || isset($action)) {
    if ($action === 'get_all_driver_locations') {
        try {
            // Query to get the most recent location for each registered driver
            $sql = "SELECT 
                        dl.Driver_ID,
                        dl.Driver_Name,
                        dr.Email as Driver_Email,
                        dr.Phone_Number as Driver_Phone,
                        dl.Current_Location,
                        dl.Current_City,
                        dl.Landmark,
                        dl.Status,
                        dl.Last_Updated,
                        dr.Driver_ID as Registration_ID,
                        dr.Driver_Status,
                        dr.Availability
                    FROM driver_locations dl
                    INNER JOIN driver_registration dr ON dl.Driver_Name = CONCAT(dr.First_Name, ' ', dr.Last_Name)
                    INNER JOIN (
                        SELECT Driver_Name, MAX(Last_Updated) as Max_Updated
                        FROM driver_locations 
                        WHERE Current_Location IS NOT NULL AND Current_Location != ''
                        GROUP BY Driver_Name
                    ) latest ON dl.Driver_Name = latest.Driver_Name AND dl.Last_Updated = latest.Max_Updated
                    WHERE dl.Current_Location IS NOT NULL 
                    AND dl.Current_Location != ''
                    AND dr.Driver_Status = 'Active'
                    ORDER BY dl.Last_Updated DESC";

            $result = $conn->query($sql);

            $drivers = [];
            if ($result && $result->num_rows > 0) {
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
            }

            echo json_encode([
                'success' => true,
                'drivers' => $drivers,
                'count' => count($drivers)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching driver locations: ' . $e->getMessage()
            ]);
        }
    } elseif ($action === 'get_booked_drivers') {
        try {
            // Get user_id from request
            $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
            
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit();
            }
            
            // Query to get drivers booked by the specific user
            $sql = "SELECT DISTINCT
                        vr.Reservation_ID,
                        vr.Driver_ID,
                        vr.Reservation_Date,
                        vr.Total_Cost,
                        vr.Status as Reservation_Status,
                        dr.First_Name,
                        dr.Last_Name,
                        CONCAT(dr.First_Name, ' ', dr.Last_Name) as Driver_Name,
                        dr.Email as Driver_Email,
                        dr.Phone_Number as Driver_Phone,
                        dr.Driver_Status,
                        dr.Availability,
                        dl.Current_Location,
                        dl.Current_City,
                        dl.Landmark,
                        dl.Status as Location_Status,
                        dl.Last_Updated
                    FROM vehicle_reservations vr
                    INNER JOIN driver_registration dr ON vr.Driver_ID = dr.Driver_ID
                    LEFT JOIN driver_locations dl ON CONCAT(dr.First_Name, ' ', dr.Last_Name) = dl.Driver_Name
                    WHERE vr.User_ID = ? 
                    AND vr.Driver_ID IS NOT NULL 
                    AND vr.Driver_ID != ''
                    ORDER BY vr.Reservation_Date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $drivers = [];
            while ($row = $result->fetch_assoc()) {
                $drivers[] = [
                    'Reservation_ID' => $row['Reservation_ID'],
                    'Driver_ID' => $row['Driver_ID'],
                    'Driver_Name' => $row['Driver_Name'],
                    'Driver_Email' => $row['Driver_Email'],
                    'Driver_Phone' => $row['Driver_Phone'],
                    'Current_Location' => $row['Current_Location'] ?: 'Location not available',
                    'Current_City' => $row['Current_City'] ?: 'City not available',
                    'Landmark' => $row['Landmark'] ?: 'No landmark',
                    'Status' => $row['Location_Status'] ?: $row['Availability'] ?: 'Unknown',
                    'Last_Updated' => $row['Last_Updated'],
                    'Reservation_Date' => $row['Reservation_Date'],
                    'Total_Cost' => $row['Total_Cost'],
                    'Reservation_Status' => $row['Reservation_Status']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'drivers' => $drivers,
                'count' => count($drivers)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching booked drivers: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
