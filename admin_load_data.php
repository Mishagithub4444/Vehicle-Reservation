<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in (optional - as per request, admin can update without additional login)
// Uncomment the following lines if you want to enforce admin login for this functionality
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
//     exit();
// }

// Database connection
include_once 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';

    if (empty($type) || empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Type and ID are required']);
        exit();
    }

    try {
        switch ($type) {
            case 'user':
                $stmt = $conn->prepare("SELECT * FROM user_registration WHERE User_ID = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $data = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $data]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                break;

            case 'driver':
                // Check driver_registration table first
                $stmt = $conn->prepare("SELECT * FROM driver_registration WHERE Driver_ID = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $data = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $data]);
                } else {
                    // Fallback to driver table if driver_registration doesn't exist
                    $stmt = $conn->prepare("SELECT * FROM driver WHERE Driver_ID = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $data = $result->fetch_assoc();
                        // Map old column names to new ones for consistency
                        $mappedData = [
                            'Driver_ID' => $data['Driver_ID'],
                            'First_Name' => $data['F_Name'],
                            'Last_Name' => $data['L_Name'],
                            'License_Number' => $data['Licence_No'],
                            'Phone_Number' => $data['Phone_No'],
                            'Status' => $data['D_Status'],
                            'Email' => '' // Not available in old table
                        ];
                        echo json_encode(['success' => true, 'data' => $mappedData]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Driver not found']);
                    }
                }
                break;

            case 'vehicle':
                $stmt = $conn->prepare("SELECT * FROM vehicle WHERE Vehicle_ID = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $data = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $data]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                break;
        }

        if (isset($stmt)) {
            $stmt->close();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
