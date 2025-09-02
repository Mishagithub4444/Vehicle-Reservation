<?php
session_start();
header('Content-Type: application/json');

// Enhanced admin authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Driver Management API: Admin authentication failed");
    error_log("Session data: " . print_r($_SESSION, true));
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required',
        'debug' => [
            'session_exists' => isset($_SESSION['admin_logged_in']),
            'session_value' => $_SESSION['admin_logged_in'] ?? 'not set',
            'session_id' => session_id(),
            'all_sessions' => array_keys($_SESSION)
        ]
    ]);
    exit();
}

// Database connection
include_once 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all_drivers':
            getAllDrivers($conn);
            break;

        case 'get_driver_details':
            getDriverDetails($conn);
            break;

        case 'delete_driver':
            deleteDriver($conn);
            break;

        case 'get_statistics':
            getDriverStatistics($conn);
            break;

        case 'update_driver':
            updateDriver($conn);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getAllDrivers($conn)
{
    try {
        error_log("getAllDrivers: Starting function");

        // Get sorting parameter
        $sort_order = $_POST['sort'] ?? 'asc';
        $sort_order = ($sort_order === 'desc') ? 'DESC' : 'ASC';

        // First, get all available columns from the driver_registration table
        $columnsResult = $conn->query("SHOW COLUMNS FROM driver_registration");
        $availableColumns = [];
        
        if ($columnsResult) {
            while ($row = $columnsResult->fetch_assoc()) {
                $availableColumns[] = $row['Field'];
            }
        }
        
        error_log("getAllDrivers: Available columns: " . implode(', ', $availableColumns));

        // Define the columns we want to select with fallbacks
        $desiredColumns = [
            'Driver_ID' => ['column' => 'Driver_ID', 'fallback' => null],
            'First_Name' => ['column' => 'First_Name', 'fallback' => null],
            'Last_Name' => ['column' => 'Last_Name', 'fallback' => null],
            'Email' => ['column' => 'Email', 'fallback' => null],
            'Phone_Number' => ['column' => 'Phone_Number', 'fallback' => null],
            'License_Number' => ['column' => 'License_Number', 'fallback' => null],
            'Status' => ['column' => 'Status', 'fallback' => "'Active' as Status"],
            'Years_of_Experience' => ['column' => 'Years_of_Experience', 'fallback' => "'0' as Years_of_Experience"],
            'Rating' => ['column' => 'Rating', 'fallback' => "'0.0' as Rating"],
            'Address' => ['column' => 'Address', 'fallback' => "'Not provided' as Address"],
            'Created_At' => ['column' => 'Created_At', 'fallback' => "NULL as Created_At"]
        ];

        $selectColumns = [];
        foreach ($desiredColumns as $alias => $config) {
            if (in_array($config['column'], $availableColumns)) {
                // Column exists, use it directly
                $selectColumns[] = $config['column'];
            } else if ($config['fallback']) {
                // Column doesn't exist, use fallback
                $selectColumns[] = $config['fallback'];
            }
            // If no fallback is defined and column doesn't exist, skip it
        }

        $sql = "SELECT " . implode(", ", $selectColumns) . " FROM driver_registration ORDER BY First_Name $sort_order, Last_Name $sort_order";

        error_log("getAllDrivers: Executing query with sort order $sort_order: " . $sql);

        $result = $conn->query($sql);

        if (!$result) {
            error_log("getAllDrivers: Query failed: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Database query failed: ' . $conn->error
            ]);
            return;
        }

        $drivers = [];
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }

        error_log("getAllDrivers: Found " . count($drivers) . " drivers");

        echo json_encode([
            'success' => true,
            'drivers' => $drivers,
            'total_count' => count($drivers)
        ]);

    } catch (Exception $e) {
        error_log("getAllDrivers: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error getting all drivers: ' . $e->getMessage()
        ]);
    }
}

function getDriverDetails($conn)
{
    try {
        $driverId = $_POST['driver_id'] ?? '';

        if (empty($driverId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Driver ID is required'
            ]);
            return;
        }

        error_log("getDriverDetails: Getting details for driver ID: " . $driverId);

        $sql = "SELECT * FROM driver_registration WHERE Driver_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $driverId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $driver = $result->fetch_assoc();
            
            // Add default values for columns that might not exist
            $driver['Status'] = $driver['Status'] ?? 'Active';
            $driver['Years_of_Experience'] = $driver['Years_of_Experience'] ?? '0';
            $driver['Rating'] = $driver['Rating'] ?? '0.0';
            $driver['Address'] = $driver['Address'] ?? 'Not provided';
            $driver['Created_At'] = $driver['Created_At'] ?? null;
            
            echo json_encode([
                'success' => true,
                'driver' => $driver
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Driver not found'
            ]);
        }

    } catch (Exception $e) {
        error_log("getDriverDetails: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error getting driver details: ' . $e->getMessage()
        ]);
    }
}

function deleteDriver($conn)
{
    try {
        $driverId = $_POST['driver_id'] ?? '';

        if (empty($driverId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Driver ID is required'
            ]);
            return;
        }

        error_log("deleteDriver: Attempting to delete driver ID: " . $driverId);

        // First check if driver exists
        $checkSql = "SELECT Driver_ID, First_Name, Last_Name FROM driver_registration WHERE Driver_ID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $driverId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Driver not found'
            ]);
            return;
        }

        $driverData = $checkResult->fetch_assoc();

        // Delete related data first (if any foreign key constraints exist)
        // Note: You may need to add more tables here based on your database schema
        
        // Delete from driver_locations if table exists
        $conn->query("DELETE FROM driver_locations WHERE driver_id = '" . $conn->real_escape_string($driverId) . "'");
        
        // Delete from driver_earnings if table exists
        $conn->query("DELETE FROM driver_earnings WHERE driver_id = '" . $conn->real_escape_string($driverId) . "'");

        // Finally delete the driver
        $deleteSql = "DELETE FROM driver_registration WHERE Driver_ID = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("s", $driverId);

        if ($deleteStmt->execute()) {
            if ($deleteStmt->affected_rows > 0) {
                error_log("deleteDriver: Successfully deleted driver ID: " . $driverId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Driver deleted successfully',
                    'deleted_driver' => $driverData['First_Name'] . ' ' . $driverData['Last_Name']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No driver was deleted (driver may not exist)'
                ]);
            }
        } else {
            error_log("deleteDriver: Failed to delete driver: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete driver: ' . $conn->error
            ]);
        }

    } catch (Exception $e) {
        error_log("deleteDriver: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting driver: ' . $e->getMessage()
        ]);
    }
}

function getDriverStatistics($conn)
{
    try {
        error_log("getDriverStatistics: Starting function");

        // Get total drivers
        $totalResult = $conn->query("SELECT COUNT(*) as count FROM driver_registration");
        $totalDrivers = $totalResult ? $totalResult->fetch_assoc()['count'] : 0;

        // Check if Status column exists before using it
        $statusColumnExists = false;
        $columnsResult = $conn->query("SHOW COLUMNS FROM driver_registration LIKE 'Status'");
        if ($columnsResult && $columnsResult->num_rows > 0) {
            $statusColumnExists = true;
        }

        // Get active drivers (only if Status column exists)
        if ($statusColumnExists) {
            $activeResult = $conn->query("SELECT COUNT(*) as count FROM driver_registration WHERE Status = 'Active'");
            $activeDrivers = $activeResult ? $activeResult->fetch_assoc()['count'] : 0;
        } else {
            $activeDrivers = $totalDrivers; // Assume all drivers are active if no Status column
        }

        // Check if Rating column exists before using it
        $ratingColumnExists = false;
        $ratingColumnsResult = $conn->query("SHOW COLUMNS FROM driver_registration LIKE 'Rating'");
        if ($ratingColumnsResult && $ratingColumnsResult->num_rows > 0) {
            $ratingColumnExists = true;
        }

        // Get average rating (only if Rating column exists)
        if ($ratingColumnExists) {
            $ratingResult = $conn->query("SELECT AVG(Rating) as avg_rating FROM driver_registration WHERE Rating IS NOT NULL");
            $avgRating = $ratingResult ? $ratingResult->fetch_assoc()['avg_rating'] : 0;
        } else {
            $avgRating = 0; // Default rating if no Rating column
        }

        // Check if Created_At column exists before using it
        $createdAtColumnExists = false;
        $createdAtColumnsResult = $conn->query("SHOW COLUMNS FROM driver_registration LIKE 'Created_At'");
        if ($createdAtColumnsResult && $createdAtColumnsResult->num_rows > 0) {
            $createdAtColumnExists = true;
        }

        // Get new drivers this month (only if Created_At column exists)
        if ($createdAtColumnExists) {
            $newResult = $conn->query("SELECT COUNT(*) as count FROM driver_registration WHERE Created_At >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
            $newDrivers = $newResult ? $newResult->fetch_assoc()['count'] : 0;
        } else {
            $newDrivers = 0; // Can't determine new drivers without Created_At column
        }

        $stats = [
            'total_drivers' => intval($totalDrivers),
            'active_drivers' => intval($activeDrivers),
            'avg_rating' => floatval($avgRating),
            'new_drivers' => intval($newDrivers)
        ];

        error_log("getDriverStatistics: Stats calculated: " . json_encode($stats));

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);

    } catch (Exception $e) {
        error_log("getDriverStatistics: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error getting statistics: ' . $e->getMessage(),
            'stats' => [
                'total_drivers' => 0,
                'active_drivers' => 0,
                'avg_rating' => 0,
                'new_drivers' => 0
            ]
        ]);
    }
}

// Update driver information
function updateDriver($conn) {
    try {
        // Validate required fields
        if (!isset($_POST['driver_id']) || empty($_POST['driver_id'])) {
            throw new Exception('Driver ID is required');
        }

        $driver_id = $_POST['driver_id'];
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $license_number = $_POST['license_number'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        $years_of_experience = $_POST['years_of_experience'] ?? null;
        $address = $_POST['address'] ?? '';

        // Validate email format
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email already exists for another driver
        if (!empty($email)) {
            $email_check_sql = "SELECT Driver_ID FROM driver_registration WHERE Email = ? AND Driver_ID != ?";
            $email_check_stmt = $conn->prepare($email_check_sql);
            $email_check_stmt->bind_param("si", $email, $driver_id);
            $email_check_stmt->execute();
            $email_result = $email_check_stmt->get_result();
            
            if ($email_result->num_rows > 0) {
                throw new Exception('Email already exists for another driver');
            }
        }

        // Check if license number already exists for another driver
        if (!empty($license_number)) {
            $license_check_sql = "SELECT Driver_ID FROM driver_registration WHERE License_Number = ? AND Driver_ID != ?";
            $license_check_stmt = $conn->prepare($license_check_sql);
            $license_check_stmt->bind_param("si", $license_number, $driver_id);
            $license_check_stmt->execute();
            $license_result = $license_check_stmt->get_result();
            
            if ($license_result->num_rows > 0) {
                throw new Exception('License number already exists for another driver');
            }
        }

        // Get existing columns in the table
        $column_query = "SHOW COLUMNS FROM driver_registration";
        $column_result = $conn->query($column_query);
        $existing_columns = [];
        
        while ($column = $column_result->fetch_assoc()) {
            $existing_columns[] = $column['Field'];
        }

        // Build dynamic update query based on existing columns
        $update_fields = [];
        $param_types = "";
        $param_values = [];

        // Core fields that should always exist
        if (in_array('First_Name', $existing_columns)) {
            $update_fields[] = "First_Name = ?";
            $param_types .= "s";
            $param_values[] = $first_name;
        }

        if (in_array('Last_Name', $existing_columns)) {
            $update_fields[] = "Last_Name = ?";
            $param_types .= "s";
            $param_values[] = $last_name;
        }

        if (in_array('Email', $existing_columns)) {
            $update_fields[] = "Email = ?";
            $param_types .= "s";
            $param_values[] = $email;
        }

        if (in_array('Phone_Number', $existing_columns)) {
            $update_fields[] = "Phone_Number = ?";
            $param_types .= "s";
            $param_values[] = $phone_number;
        }

        if (in_array('License_Number', $existing_columns)) {
            $update_fields[] = "License_Number = ?";
            $param_types .= "s";
            $param_values[] = $license_number;
        }

        // Optional fields
        if (in_array('Status', $existing_columns)) {
            $update_fields[] = "Status = ?";
            $param_types .= "s";
            $param_values[] = $status;
        }

        if (in_array('Years_of_Experience', $existing_columns) && !empty($years_of_experience)) {
            $update_fields[] = "Years_of_Experience = ?";
            $param_types .= "i";
            $param_values[] = intval($years_of_experience);
        }

        if (in_array('Address', $existing_columns)) {
            $update_fields[] = "Address = ?";
            $param_types .= "s";
            $param_values[] = $address;
        }

        if (empty($update_fields)) {
            throw new Exception('No valid fields to update');
        }

        // Add driver ID to parameters
        $param_types .= "i";
        $param_values[] = $driver_id;

        // Build and execute the update query
        $update_sql = "UPDATE driver_registration SET " . implode(", ", $update_fields) . " WHERE Driver_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($param_types, ...$param_values);

        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Driver information updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No changes were made or driver not found'
                ]);
            }
        } else {
            throw new Exception('Failed to update driver information');
        }

    } catch (Exception $e) {
        error_log("updateDriver: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error updating driver: ' . $e->getMessage()
        ]);
    }
}

// Close database connection
$conn->close();
?>
