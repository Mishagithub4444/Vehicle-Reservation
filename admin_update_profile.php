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

    if (empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Type is required']);
        exit();
    }

    try {
        switch ($type) {
            case 'user':
                $user_id = $_POST['user_id'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $phone_number = $_POST['phone_number'] ?? '';
                $password = $_POST['password'] ?? '';

                if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number)) {
                    echo json_encode(['success' => false, 'message' => 'All fields except password are required']);
                    exit();
                }

                // Check if user exists
                $check_stmt = $conn->prepare("SELECT User_ID FROM user_registration WHERE User_ID = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit();
                }

                // Update user profile
                if (!empty($password)) {
                    // Update with password
                    $stmt = $conn->prepare("UPDATE user_registration SET First_Name = ?, Last_Name = ?, Email = ?, Phone_Number = ?, User_Password = ? WHERE User_ID = ?");
                    $stmt->bind_param("sssisi", $first_name, $last_name, $email, $phone_number, $password, $user_id);
                } else {
                    // Update without password
                    $stmt = $conn->prepare("UPDATE user_registration SET First_Name = ?, Last_Name = ?, Email = ?, Phone_Number = ? WHERE User_ID = ?");
                    $stmt->bind_param("sssii", $first_name, $last_name, $email, $phone_number, $user_id);
                }

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'User profile updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update user profile']);
                }
                break;

            case 'driver':
                $driver_id = $_POST['driver_id'] ?? '';
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $phone_number = $_POST['phone_number'] ?? '';
                $license_number = $_POST['license_number'] ?? '';
                $status = $_POST['status'] ?? '';

                if (empty($driver_id) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($license_number) || empty($status)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit();
                }

                // Check if driver_registration table exists and has this driver
                $check_stmt = $conn->prepare("SELECT Driver_ID FROM driver_registration WHERE Driver_ID = ?");
                $check_stmt->bind_param("s", $driver_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // Update in driver_registration table
                    $stmt = $conn->prepare("UPDATE driver_registration SET First_Name = ?, Last_Name = ?, Email = ?, Phone_Number = ?, License_Number = ?, Status = ? WHERE Driver_ID = ?");
                    $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone_number, $license_number, $status, $driver_id);
                } else {
                    // Check if driver exists in old driver table
                    $check_stmt2 = $conn->prepare("SELECT Driver_ID FROM driver WHERE Driver_ID = ?");
                    $check_stmt2->bind_param("i", $driver_id);
                    $check_stmt2->execute();
                    $check_result2 = $check_stmt2->get_result();

                    if ($check_result2->num_rows > 0) {
                        // Update in old driver table (map to old column names)
                        $stmt = $conn->prepare("UPDATE driver SET F_Name = ?, L_Name = ?, Phone_No = ?, Licence_No = ?, D_Status = ? WHERE Driver_ID = ?");
                        $stmt->bind_param("sssssi", $first_name, $last_name, $phone_number, $license_number, $status, $driver_id);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Driver not found']);
                        exit();
                    }
                }

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Driver profile updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update driver profile']);
                }
                break;

            case 'vehicle':
                $vehicle_id = $_POST['vehicle_id'] ?? '';
                $vehicle_name = $_POST['vehicle_name'] ?? '';
                $vehicle_type = $_POST['vehicle_type'] ?? '';
                $vehicle_model = $_POST['vehicle_model'] ?? '';
                $license_plate = $_POST['license_plate'] ?? '';
                $status = $_POST['status'] ?? '';

                if (empty($vehicle_id) || empty($vehicle_name) || empty($vehicle_type) || empty($vehicle_model) || empty($license_plate) || empty($status)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit();
                }

                // Check if vehicle exists
                $check_stmt = $conn->prepare("SELECT Vehicle_ID FROM vehicle WHERE Vehicle_ID = ?");
                $check_stmt->bind_param("i", $vehicle_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
                    exit();
                }

                // Update vehicle profile
                $stmt = $conn->prepare("UPDATE vehicle SET Vehicle_Name = ?, Vehicle_Type = ?, Vehicle_Model = ?, License_Plate = ?, Available = ? WHERE Vehicle_ID = ?");
                $stmt->bind_param("sssssi", $vehicle_name, $vehicle_type, $vehicle_model, $license_plate, $status, $vehicle_id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle profile updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update vehicle profile']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid type']);
                break;
        }

        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($check_stmt)) {
            $check_stmt->close();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
