<?php
session_start();

// Check if admin is accessing or regular driver is logged in
$is_admin_access = isset($_GET['admin_access']) && $_GET['admin_access'] === 'true' &&
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_driver_access = isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true;

// If neither admin nor driver access, redirect to driver login
if (!$is_admin_access && !$is_driver_access) {
    header('Location: driver_login.php');
    exit();
}

// Database connection
include 'connection/db.php';

// If admin is accessing, load first driver or specific driver for viewing
if ($is_admin_access && !$is_driver_access) {
    // Admin accessing driver portal - get first driver or specific driver
    $selected_driver_id = isset($_GET['view_driver_id']) ? $_GET['view_driver_id'] : null;

    if ($selected_driver_id) {
        $query = "SELECT * FROM driver_registration WHERE Driver_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $selected_driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $query = "SELECT * FROM driver_registration ORDER BY ID ASC LIMIT 1";
        $result = mysqli_query($conn, $query);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $driver_data = mysqli_fetch_assoc($result);
        // Set session variables for display purposes (without interfering with admin session)
        $driver_info = [
            'driver_id' => $driver_data['Driver_ID'],
            'first_name' => $driver_data['First_Name'],
            'last_name' => $driver_data['Last_Name'],
            'driver_username' => $driver_data['Driver_UserName'],
            'email' => $driver_data['Email'],
            'phone' => $driver_data['Phone_Number'],
            'dob' => $driver_data['Date_of_Birth'],
            'gender' => $driver_data['Gender'],
            'address' => $driver_data['Address'],
            'license_number' => $driver_data['License_Number'],
            'driver_status' => $driver_data['Driver_Status'],
            'availability' => $driver_data['Availability']
        ];
    } else {
        // No drivers found
        $driver_info = [
            'driver_id' => 'No Drivers',
            'first_name' => 'No Drivers',
            'last_name' => 'Found',
            'driver_username' => 'N/A',
            'email' => 'N/A',
            'phone' => 'N/A',
            'dob' => 'N/A',
            'gender' => 'N/A',
            'address' => 'N/A',
            'license_number' => 'N/A',
            'driver_status' => 'N/A',
            'availability' => 'N/A'
        ];
    }
}

// Handle AJAX requests for vehicle data
if (isset($_GET['action']) && $_GET['action'] === 'get_vehicles') {
    header('Content-Type: application/json');

    try {
        // Modified query to fetch ALL registered vehicles, not just available ones
        $sql = "SELECT Vehicle_ID, License_Plate, Make, Model, Vehicle_Type, Rental_Rate, Year, Fuel_Type, Transmission, Seating_Capacity, Status FROM vehicle_registration ORDER BY Registration_Date DESC";
        $result = $conn->query($sql);

        $vehicles = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vehicles[] = array(
                    'id' => $row['Vehicle_ID'],
                    'vehicleId' => $row['Vehicle_ID'],
                    'name' => $row['Make'] . ' ' . $row['Model'],
                    'type' => mapVehicleType($row['Vehicle_Type']),
                    'price' => intval($row['Rental_Rate']),
                    'transmission' => strtolower($row['Transmission'] ?: 'automatic'),
                    'fuel' => $row['Fuel_Type'] ?: 'Gasoline',
                    'seats' => intval($row['Seating_Capacity'] ?: 5),
                    'image' => getVehicleIcon($row['Vehicle_Type']),
                    'features' => ['AC', 'GPS'],
                    'available' => ($row['Status'] === 'Available'), // Set availability based on status
                    'licensePlate' => $row['License_Plate'],
                    'year' => intval($row['Year']),
                    'status' => $row['Status'] // Include status for display
                );
            }
        }
        echo json_encode($vehicles);
    } catch (Exception $e) {
        echo json_encode(array());
    }
    exit();
}

// Handle AJAX requests for specific vehicle details by ID (for bookings)
if (isset($_GET['action']) && $_GET['action'] === 'get_vehicle_by_id' && isset($_GET['vehicle_id'])) {
    header('Content-Type: application/json');

    try {
        $vehicle_id = $_GET['vehicle_id'];
        $sql = "SELECT Vehicle_ID, License_Plate, Make, Model, Vehicle_Type, Rental_Rate, Year, Fuel_Type, Transmission, Seating_Capacity, Features FROM vehicle_registration WHERE Vehicle_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $vehicle = array(
                'id' => $row['Vehicle_ID'],
                'vehicleId' => $row['Vehicle_ID'],
                'name' => $row['Make'] . ' ' . $row['Model'],
                'make' => $row['Make'],
                'model' => $row['Model'],
                'type' => mapVehicleType($row['Vehicle_Type']),
                'price' => intval($row['Rental_Rate']),
                'transmission' => $row['Transmission'] ?: 'automatic',
                'fuel' => $row['Fuel_Type'] ?: 'Gasoline',
                'seats' => intval($row['Seating_Capacity'] ?: 5),
                'image' => getVehicleIcon($row['Vehicle_Type']),
                'features' => $row['Features'] ? explode(', ', $row['Features']) : ['AC', 'GPS'],
                'licensePlate' => $row['License_Plate'],
                'year' => intval($row['Year'])
            );
            echo json_encode($vehicle);
        } else {
            echo json_encode(null);
        }
    } catch (Exception $e) {
        echo json_encode(null);
    }
    exit();
}

// Handle AJAX requests for driver location information
if (isset($_POST['action']) && $_POST['action'] === 'get_driver_location') {
    header('Content-Type: application/json');

    try {
        // Create drivers table if it doesn't exist
        $create_drivers_table = "CREATE TABLE IF NOT EXISTS driver_locations (
            Driver_ID INT AUTO_INCREMENT PRIMARY KEY,
            Driver_Name VARCHAR(100) NOT NULL,
            Driver_Email VARCHAR(100),
            Driver_Phone VARCHAR(20),
            Current_Location VARCHAR(255),
            Current_City VARCHAR(100),
            Landmark VARCHAR(255),
            Status VARCHAR(20) DEFAULT 'Available',
            Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (Status)
        )";
        $conn->query($create_drivers_table);

        // Get current driver's location
        $driver_email = $_SESSION['email'] ?? '';
        $sql = "SELECT * FROM driver_locations WHERE Driver_Email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $driver_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'location' => [
                    'name' => $row['Driver_Name'],
                    'address' => $row['Current_Location'],
                    'city' => $row['Current_City'],
                    'landmark' => $row['Landmark'],
                    'status' => $row['Status'],
                    'lastUpdated' => $row['Last_Updated']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No location found']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(array());
    }
    exit();
}

// Handle driver location updates
if (isset($_POST['action']) && $_POST['action'] === 'update_driver_location') {
    header('Content-Type: application/json');

    try {
        // Check if session data exists
        if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit();
        }

        $driver_name = $_POST['name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        $driver_email = $_SESSION['email'];
        $driver_phone = $_SESSION['phone'] ?? '';
        $location = $_POST['address'] ?? '';
        $coordinates = $_POST['coordinates'] ?? '';
        $landmark = ''; // Initialize landmark as empty
        $status = 'Available';

        // Validate required fields
        if (empty($driver_name)) {
            echo json_encode(['success' => false, 'message' => 'Driver name is required']);
            exit();
        }

        if (empty($location)) {
            echo json_encode(['success' => false, 'message' => 'Address is required']);
            exit();
        }

        // Ensure the driver_locations table exists
        $create_table_sql = "CREATE TABLE IF NOT EXISTS driver_locations (
            Driver_ID INT AUTO_INCREMENT PRIMARY KEY,
            Driver_Name VARCHAR(100) NOT NULL,
            Driver_Email VARCHAR(100),
            Driver_Phone VARCHAR(20),
            Current_Location VARCHAR(255),
            Current_City VARCHAR(100),
            Landmark VARCHAR(255),
            Status VARCHAR(20) DEFAULT 'Available',
            Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (Status)
        )";
        
        if (!$conn->query($create_table_sql)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create table: ' . $conn->error]);
            exit();
        }

        // Check if driver already exists
        $check_sql = "SELECT Driver_ID FROM driver_locations WHERE Driver_Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit();
        }

        $check_stmt->bind_param('s', $driver_email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing driver - use coordinates in Current_City field for now
            $sql = "UPDATE driver_locations 
                    SET Driver_Name = ?, Driver_Phone = ?, Current_Location = ?, 
                        Current_City = ?, Landmark = ?, Status = ?, Last_Updated = NOW()
                    WHERE Driver_Email = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param('sssssss', $driver_name, $driver_phone, $location, $coordinates, $landmark, $status, $driver_email);
        } else {
            // Insert new driver
            $sql = "INSERT INTO driver_locations 
                    (Driver_Name, Driver_Email, Driver_Phone, Current_Location, Current_City, Landmark, Status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param('sssssss', $driver_name, $driver_email, $driver_phone, $location, $coordinates, $landmark, $status);
        }

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Driver location updated successfully',
                'location' => $location,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update driver location: ' . $stmt->error]);
        }
        $stmt->close();
        $check_stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle driver location deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete_driver_location') {
    header('Content-Type: application/json');

    try {
        // Check if session data exists
        if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit();
        }

        $driver_email = $_SESSION['email'];

        // Delete driver location from database
        $sql = "DELETE FROM driver_locations WHERE Driver_Email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param('s', $driver_email);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Driver location deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete driver location: ' . $stmt->error]);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Function to map vehicle types from database to display types
function mapVehicleType($dbType)
{
    $type = strtolower($dbType);
    switch ($type) {
        case 'car':
            return 'sedan';
        case 'suv':
            return 'suv';
        case 'van':
            return 'van';
        case 'truck':
            return 'truck';
        case 'motorcycle':
            return 'economy';
        default:
            return 'sedan';
    }
}

// Function to get vehicle icon
function getVehicleIcon($type)
{
    $type = strtolower($type);
    switch ($type) {
        case 'car':
            return '🚗';
        case 'suv':
            return '🚙';
        case 'van':
            return '🚐';
        case 'truck':
            return '🚚';
        case 'motorcycle':
            return '🏍️';
        default:
            return '🚗';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin_access ? 'Admin - Driver Management Portal' : 'Driver Portal'; ?> - Vehicle Reservation</title>
    <link rel="stylesheet" href="./user_portal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="user_portal.css" type="text/css">
    <style>
        /* Fallback styles in case CSS file doesn't load */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: #004080;
            color: white;
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        nav a:hover,
        nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .portal-section {
            padding: 40px 20px;
        }

        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-header h1 {
            color: #004080;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .user-type {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 0 5px;
        }

        .portal-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .profile-section,
        .actions-section,
        .account-status {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-section h2,
        .actions-section h2,
        .account-status h2 {
            color: #004080;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dc3545;
            animation: pulse 2s infinite;
        }

        .status-dot.active {
            background: #28a745;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #004080;
            color: white;
        }

        .btn-primary:hover {
            background: #003366;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #333;
            padding: 8px 0;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .badge.active {
            background: #e8f5e8;
            color: #4caf50;
        }

        .badge.available {
            background: #e8f5e8;
            color: #4caf50;
        }

        .badge.busy {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge.offline {
            background: #ffebee;
            color: #f44336;
        }

        .badge.on-duty {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge.off-duty {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .status-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .status-item label {
            font-weight: bold;
            color: #555;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .welcome-header h1 {
                font-size: 2rem;
            }

            .portal-content {
                grid-template-columns: 1fr;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 15px;
            cursor: pointer;
            z-index: 1001;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }

        .location-content {
            margin-top: 20px;
        }

        .location-info {
            margin-bottom: 20px;
        }

        .location-info h3 {
            color: #004080;
            margin-bottom: 10px;
        }

        .location-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .location-details p {
            margin: 5px 0;
        }

        #map-placeholder {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border: 2px dashed #ddd;
            color: #666;
        }

        /* Manual Location Input Styles */
        .manual-input-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .input-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .input-group input {
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #004080;
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
        }

        .input-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn.secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .action-btn.secondary:hover {
            background: #5a6268;
        }

        /* Pickup Location Styles */
        .pickup-location-button {
            transition: all 0.3s ease;
        }

        .pickup-location-button:hover {
            background: rgba(255, 255, 255, 0.4) !important;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        #carHirePickupDisplay {
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .input-actions {
                flex-direction: column;
            }
        }

        /* User Locations Styles */
        .user-locations-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .user-locations-section h2 {
            color: #004080;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .users-container {
            max-width: 100%;
        }

        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .users-header p {
            color: #666;
            margin: 0;
            flex: 1;
            min-width: 200px;
        }

        .users-list {
            margin-bottom: 20px;
            min-height: 200px;
        }

        .user-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }

        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .user-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #004080;
        }

        .user-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            background: #d4edda;
            color: #155724;
        }

        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .user-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .user-detail-item i {
            width: 16px;
            color: #004080;
        }

        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .user-action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .user-action-btn.primary {
            background: #007bff;
            color: white;
        }

        .user-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .users-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .users-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-details {
                grid-template-columns: 1fr;
            }

            .user-actions,
            .users-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve <?php
                                            if ($is_admin_access) {
                                                echo '<span style="font-size: 0.6em; background: #28a745; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">DRIVER MANAGEMENT</span>';
                                            }
                                            ?></div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="<?php echo $is_admin_access ? 'driver_portal.php?admin_access=true' : 'driver_portal.php'; ?>" class="active">
                        <?php echo $is_admin_access ? 'Driver Management' : 'My Portal'; ?>
                    </a></li>
                <li><a href="<?php echo $is_admin_access ? 'admin_portal.php' : 'driver_logout.php'; ?>">
                        <?php echo $is_admin_access ? 'Back to Admin' : 'Logout'; ?>
                    </a></li>
            </ul>
        </nav>
    </header>

    <section class="portal-section">
        <div class="portal-container">
            <div class="welcome-header">
                <?php if ($is_admin_access): ?>
                    <h1>🚗 Admin View - Driver: <?php echo htmlspecialchars($driver_info['first_name'] . ' ' . $driver_info['last_name']); ?></h1>
                    <p>Driver Status: <span class="user-type"><?php echo htmlspecialchars($driver_info['driver_status']); ?></span> |
                        Availability: <span class="user-type"><?php echo htmlspecialchars($driver_info['availability']); ?></span></p>
                    <div style="background: #28a745; color: white; padding: 10px; border-radius: 8px; margin: 15px 0; text-align: center;">
                        <strong>🚗 Admin Driver Management Mode</strong> - You are viewing driver information as an administrator
                    </div>
                <?php else: ?>
                    <h1>Welcome, Driver <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</h1>
                    <p>Status: <span class="user-type"><?php echo htmlspecialchars($_SESSION['driver_status']); ?></span> |
                        Availability: <span class="user-type"><?php echo htmlspecialchars($_SESSION['availability']); ?></span></p>
                <?php endif; ?>
            </div>

            <div class="portal-content">
                <?php if ($is_admin_access): ?>
                    <!-- Admin Driver Selection Section -->
                    <div class="profile-section" style="border-left: 4px solid #28a745;">
                        <h2>🚗 Select Driver to View</h2>
                        <div style="margin-bottom: 20px;">
                            <form method="GET" action="driver_portal.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                <input type="hidden" name="admin_access" value="true">
                                <select name="view_driver_id" style="padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; min-width: 200px;">
                                    <option value="">Select a driver to view</option>
                                    <?php
                                    // Get all drivers for admin selection
                                    $all_drivers_query = "SELECT Driver_ID, First_Name, Last_Name, Driver_UserName, Email, Driver_Status, Availability FROM driver_registration ORDER BY First_Name, Last_Name";
                                    $all_drivers_result = mysqli_query($conn, $all_drivers_query);

                                    if ($all_drivers_result) {
                                        while ($driver = mysqli_fetch_assoc($all_drivers_result)) {
                                            $selected = (isset($_GET['view_driver_id']) && $_GET['view_driver_id'] == $driver['Driver_ID']) ? 'selected' : '';
                                            $status_icon = $driver['Driver_Status'] == 'Active' ? '✅' : '❌';
                                            $availability_icon = $driver['Availability'] == 'Available' ? '🟢' : '🔴';
                                            echo "<option value='" . $driver['Driver_ID'] . "' $selected>";
                                            echo htmlspecialchars($driver['First_Name'] . ' ' . $driver['Last_Name'] . ' (' . $driver['Driver_UserName'] . ') ' . $status_icon . ' ' . $availability_icon);
                                            echo "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-size: 16px; cursor: pointer;">
                                    🚗 View Driver
                                </button>
                                <a href="admin_portal.php" style="background: #6c757d; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 16px;">
                                    ← Back to Admin Portal
                                </a>
                            </form>
                        </div>

                        <?php
                        // If admin selected a specific driver, show confirmation message
                        if (isset($_GET['view_driver_id']) && !empty($_GET['view_driver_id'])) {
                            echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 15px; color: #0c5460;'>";
                            echo "<strong>✅ Now viewing driver:</strong> " . htmlspecialchars($driver_info['first_name'] . ' ' . $driver_info['last_name']);
                            echo " (ID: " . $driver_info['driver_id'] . ")";
                            echo "</div>";
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php
                // Show driver profile information only when viewing a specific driver (admin) or for regular drivers
                if (($is_admin_access && isset($_GET['view_driver_id']) && !empty($_GET['view_driver_id'])) || $is_driver_access):
                ?>
                    <div class="profile-section">
                        <h2>Driver Profile Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>First Name:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['first_name'] : $_SESSION['first_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Last Name:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['last_name'] : $_SESSION['last_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Driver UserName:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['driver_username'] : $_SESSION['driver_username']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Driver ID:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['driver_id'] : $_SESSION['driver_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>License Number:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['license_number'] : $_SESSION['license_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Date of Birth:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['dob'] : $_SESSION['dob']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Phone Number:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['phone'] : $_SESSION['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['gender'] : $_SESSION['gender']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['email'] : $_SESSION['email']); ?></span>
                            </div>
                            <div class="info-item full-width">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['address'] : $_SESSION['address']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Driver Status:</label>
                                <span class="badge <?php echo strtolower($is_admin_access ? $driver_info['driver_status'] : $_SESSION['driver_status']); ?>"><?php echo htmlspecialchars($is_admin_access ? $driver_info['driver_status'] : $_SESSION['driver_status']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Availability:</label>
                                <span class="badge <?php echo strtolower(str_replace(' ', '-', $is_admin_access ? $driver_info['availability'] : $_SESSION['availability'])); ?>"><?php echo htmlspecialchars($is_admin_access ? $driver_info['availability'] : $_SESSION['availability']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="actions-section">
                    <h2>Driver Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="driver_earnings_report.php" class="action-btn">Earnings Report</a>
                        <a href="driver_profile_update.php" class="action-btn">Update Profile</a>
                        <a href="javascript:void(0)" onclick="showLocationModal()" class="action-btn">📍 Set Location</a>
                    </div>
                </div>

                <div class="account-status">
                    <h2>Driver Status</h2>
                    <div class="status-info">
                        <div class="status-item">
                            <label>Current Status:</label>
                            <span class="badge <?php echo strtolower($is_admin_access ? $driver_info['driver_status'] : $_SESSION['driver_status']); ?>"><?php echo htmlspecialchars($is_admin_access ? $driver_info['driver_status'] : $_SESSION['driver_status']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>Availability:</label>
                            <span class="badge <?php echo strtolower(str_replace(' ', '-', $is_admin_access ? $driver_info['availability'] : $_SESSION['availability'])); ?>"><?php echo htmlspecialchars($is_admin_access ? $driver_info['availability'] : $_SESSION['availability']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>License Number:</label>
                            <span><?php echo htmlspecialchars($is_admin_access ? $driver_info['license_number'] : $_SESSION['license_number']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>Member Since:</label>
                            <span>Today</span>
                        </div>
                        <div class="status-item">
                            <label>Current Location:</label>
                            <span id="current-location-display" style="color: #666; font-style: italic;">Not set</span>
                        </div>
                    </div>
                </div>

                <!-- User Locations Section -->
                <div class="user-locations-section">
                    <h2>📍 Registered User Locations</h2>
                    <div class="users-container">
                        <div class="users-header">
                            <p>View locations of registered users for pickup and delivery services</p>
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <button class="btn btn-primary" onclick="loadUserLocations()" style="background: #28a745;">
                                    <i class="fa fa-refresh"></i> Refresh User Locations
                                </button>
                                <div id="userLastUpdateTime" style="font-size: 12px; color: #666; font-style: italic;"></div>
                            </div>
                        </div>

                        <div id="userLocationsList" class="users-list">
                            <div class="loading-state">
                                <i class="fa fa-spinner fa-spin"></i> Loading user locations...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>

    <!-- Modals -->
    <div id="tripsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 800px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #4a90e2;">🚗 Car Hire System</h3>
                <button onclick="closeModal('tripsModal')" style="background: none; border: none; font-size: 24px; color: #666; cursor: pointer;">&times;</button>
            </div>

            <!-- Tab Navigation -->
            <div style="display: flex; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
                <button onclick="showTripTab('browse')" id="browseTab" class="trip-tab active-tab" style="flex: 1; padding: 10px; border: none; background: none; cursor: pointer; border-bottom: 2px solid #4a90e2; color: #4a90e2; font-weight: bold;">Browse Cars</button>
                <button onclick="showTripTab('bookings')" id="bookingsTab" class="trip-tab" style="flex: 1; padding: 10px; border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent; color: #666;">My Bookings</button>
            </div>

            <!-- Browse Cars Tab -->
            <div id="browseTrips" class="trip-content">
                <div style="margin-bottom: 15px;">
                    <h4 style="color: #007bff; margin-bottom: 15px;">🚗 Available Cars for Hire</h4>

                    <!-- Current Pickup Location Display -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size: 14px; opacity: 0.9;">📍 Current Pickup Location:</span>
                            <span id="carHirePickupDisplay" style="font-weight: bold; margin-left: 8px;"><?php echo htmlspecialchars($_SESSION['address']); ?></span>
                        </div>
                        <button class="pickup-location-button" onclick="changePickupLocationForHire()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 6px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.3s ease;">
                            Change Location
                        </button>
                    </div>

                    <!-- Available Cars Grid -->
                    <div id="carsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <!-- Car cards will be populated here -->
                    </div>
                </div>
            </div>

            <!-- My Bookings Tab -->
            <div id="bookingsTrips" class="trip-content" style="display: none;">
                <div style="margin-bottom: 15px;">
                    <h4 style="color: #28a745; margin-bottom: 15px;">📋 My Car Bookings</h4>

                    <!-- Booking Status Filter -->
                    <div style="margin-bottom: 20px;">
                        <label style="margin-right: 15px;">Filter by status:</label>
                        <button onclick="filterBookings('all')" class="booking-filter active-filter" style="background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 4px; margin-right: 5px; cursor: pointer;">All</button>
                        <button onclick="filterBookings('active')" class="booking-filter" style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; margin-right: 5px; cursor: pointer;">Active</button>
                        <button onclick="filterBookings('cancelled')" class="booking-filter" style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Cancelled</button>
                    </div>

                    <!-- Bookings List -->
                    <div id="bookingsList">
                        <div style="text-align: center; padding: 40px; color: #666; font-style: italic;">
                            No bookings yet. Browse cars and make your first booking!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trip Reports Tab -->
    <div id="reportsTrips" class="trip-content" style="display: none;">
        <div style="margin-bottom: 15px;">
            <h4 style="color: #6f42c1; margin-bottom: 10px;">📊 Trip Reports & Analytics</h4>

            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold;">3</div>
                    <div style="font-size: 12px; opacity: 0.9;">Total Trips</div>
                </div>
                <div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold;">$77.50</div>
                    <div style="font-size: 12px; opacity: 0.9;">Total Earnings</div>
                </div>
                <div style="background: linear-gradient(135deg, #ffc107, #e0a800); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold;">44.0</div>
                    <div style="font-size: 12px; opacity: 0.9;">Miles Driven</div>
                </div>
                <div style="background: linear-gradient(135deg, #6f42c1, #5a3aa7); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold;">2h</div>
                    <div style="font-size: 12px; opacity: 0.9;">Drive Time</div>
                </div>
            </div>

            <!-- Report Generation -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <h5 style="margin-bottom: 10px;">Generate Custom Report</h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-size: 12px;">From Date:</label>
                        <input type="date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-size: 12px;">To Date:</label>
                        <input type="date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button style="background: #6f42c1; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">📊 Generate Report</button>
                    <button style="background: #17a2b8; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">📄 Export PDF</button>
                    <button style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">📊 Export Excel</button>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div style="background: #e9f7ef; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                <h5 style="color: #155724; margin-bottom: 10px;">Performance Metrics</h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="color: #155724; font-size: 12px;">Average Earnings per Trip:</div>
                        <div style="font-size: 18px; font-weight: bold; color: #28a745;">$25.83</div>
                    </div>
                    <div>
                        <div style="color: #155724; font-size: 12px;">Average Trip Duration:</div>
                        <div style="font-size: 18px; font-weight: bold; color: #28a745;">40 min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <div id="earningsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 20px; color: #4a90e2;">Earnings Report</h3>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #666;">This Month:</span>
                    <span style="color: #28a745; font-weight: bold;">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #666;">Total Earnings:</span>
                    <span style="color: #28a745; font-weight: bold;">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #666;">Trips Completed:</span>
                    <span style="color: #4a90e2; font-weight: bold;">0</span>
                </div>
            </div>
            <p style="margin-bottom: 20px; color: #666; font-style: italic;">Start taking trips to see your earnings here!</p>
            <button onclick="closeModal('earningsModal')" style="background: #4a90e2; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Close</button>
        </div>
    </div>

    <script>
        function showTripsModal() {
            document.getElementById('tripsModal').style.display = 'block';
            showTripTab('browse'); // Default to browse cars tab
            loadAvailableCars(); // Load cars when modal opens
        }

        function showTripTab(tabName) {
            // Hide all trip content
            const contents = document.querySelectorAll('.trip-content');
            contents.forEach(content => content.style.display = 'none');

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.trip-tab');
            tabs.forEach(tab => {
                tab.style.borderBottom = '2px solid transparent';
                tab.style.color = '#666';
                tab.style.fontWeight = 'normal';
            });

            // Show selected content and activate tab
            document.getElementById(tabName + 'Trips').style.display = 'block';
            const activeTab = document.getElementById(tabName + 'Tab');
            activeTab.style.borderBottom = '2px solid #4a90e2';
            activeTab.style.color = '#4a90e2';
            activeTab.style.fontWeight = 'bold';

            // Load specific content based on tab
            if (tabName === 'browse') {
                loadAvailableCars();
            } else if (tabName === 'bookings') {
                loadMyBookings();
            } else if (tabName === 'profile') {
                loadDriverPreferences();
            }
        }

        function showEarningsModal() {
            document.getElementById('earningsModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function submitVehicleReservation() {
            const vehicleType = document.getElementById('vehicleType').value;
            const pickupDate = document.getElementById('pickupDate').value;
            const pickupTime = document.getElementById('pickupTime').value;
            const duration = document.getElementById('duration').value;
            const tripPurpose = document.getElementById('tripPurpose').value;

            // Validate form
            if (!vehicleType || !pickupDate || !pickupTime || !duration) {
                alert('Please fill in all required fields');
                return;
            }

            // Create reservation object
            const reservation = {
                id: 'RES' + Date.now(),
                vehicleType: vehicleType,
                pickupDate: pickupDate,
                pickupTime: pickupTime,
                duration: duration,
                purpose: tripPurpose,
                status: 'Pending',
                createdAt: new Date().toLocaleString()
            };

            // Get existing reservations from localStorage
            let reservations = JSON.parse(localStorage.getItem('vehicleReservations') || '[]');
            reservations.push(reservation);
            localStorage.setItem('vehicleReservations', JSON.stringify(reservations));

            // Update the reservations display
            updateReservationsList();

            // Clear form
            clearReservationForm();

            alert('Vehicle reservation submitted successfully!');
        }

        function clearReservationForm() {
            document.getElementById('vehicleType').value = '';
            document.getElementById('pickupDate').value = '';
            document.getElementById('pickupTime').value = '';
            document.getElementById('duration').value = '';
            document.getElementById('tripPurpose').value = '';
        }

        function updateReservationsList() {
            const reservations = JSON.parse(localStorage.getItem('vehicleReservations') || '[]');
            const reservationsList = document.getElementById('reservationsList');

            if (reservations.length === 0) {
                reservationsList.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #666; font-style: italic;">
                        No vehicle reservations yet. Reserve a vehicle above to get started!
                    </div>
                `;
                return;
            }

            let html = '';
            reservations.forEach(reservation => {
                const statusColor = reservation.status === 'Confirmed' ? '#28a745' :
                    reservation.status === 'Pending' ? '#ffc107' : '#dc3545';

                html += `
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${statusColor};">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <strong>${reservation.id}</strong>
                                <div style="color: #666; font-size: 14px;">${reservation.vehicleType.charAt(0).toUpperCase() + reservation.vehicleType.slice(1)}</div>
                            </div>
                            <span style="background: ${statusColor}; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                ${reservation.status}
                            </span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                            <div>
                                <strong>Pickup:</strong> ${reservation.pickupDate} at ${reservation.pickupTime}
                            </div>
                            <div>
                                <strong>Duration:</strong> ${reservation.duration} hours
                            </div>
                        </div>
                        ${reservation.purpose ? `<div style="margin-top: 10px; font-size: 14px;"><strong>Purpose:</strong> ${reservation.purpose}</div>` : ''}
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">
                            Created: ${reservation.createdAt}
                        </div>
                        <div style="margin-top: 10px;">
                            <button onclick="cancelReservation('${reservation.id}')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Cancel</button>
                        </div>
                    </div>
                `;
            });

            reservationsList.innerHTML = html;
        }

        function cancelReservation(reservationId) {
            if (!confirm('Are you sure you want to cancel this reservation?')) {
                return;
            }

            let reservations = JSON.parse(localStorage.getItem('vehicleReservations') || '[]');
            reservations = reservations.filter(res => res.id !== reservationId);
            localStorage.setItem('vehicleReservations', JSON.stringify(reservations));

            updateReservationsList();
            alert('Reservation cancelled successfully');
        }

        // Car Hire System Functions
        function loadAvailableCars() {
            const carsGrid = document.getElementById('carsGrid');

            // Show loading state
            carsGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;"><i class="fas fa-spinner fa-spin"></i> Loading vehicles...</div>';

            // Fetch vehicles from database
            fetch('?action=get_vehicles')
                .then(response => response.json())
                .then(cars => {
                    if (cars && cars.length > 0) {
                        // Store globally for filtering
                        window.availableCars = cars;
                        displayCars(cars);
                    } else {
                        carsGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666; font-style: italic;">No vehicles available for booking. Please check back later or contact support.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading vehicles:', error);
                    carsGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Error loading vehicles. Please refresh the page or try again later.</div>';
                });
        }

        function displayCars(cars) {
            const carsGrid = document.getElementById('carsGrid');
            let html = '';

            cars.forEach(car => {
                // Enhanced status display to show all registered vehicles
                let statusBadge = '';
                let statusStyle = '';
                let buttonHtml = '';
                
                if (car.available) {
                    statusBadge = '<span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;">✓ Available for Hire</span>';
                    statusStyle = 'cursor: pointer; border-color: #28a745;';
                    buttonHtml = `<button onclick="event.stopPropagation(); bookCar('${car.id}')" style="width: 100%; background: #007bff; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer;">Book Now</button>`;
                } else {
                    statusBadge = `<span style="background: #ffc107; color: #000; padding: 4px 8px; border-radius: 12px; font-size: 11px;">📋 ${car.status || 'Not Available'}</span>`;
                    statusStyle = 'cursor: pointer; border-color: #ffc107; opacity: 0.85;';
                    buttonHtml = `<button onclick="event.stopPropagation(); requestVehicle('${car.id}')" style="width: 100%; background: #ffc107; color: #000; border: none; padding: 8px; border-radius: 4px; cursor: pointer;">Request Availability</button>`;
                }

                html += `
                    <div style="background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 15px; transition: all 0.3s; ${statusStyle}" onclick="showCarDetails('${car.id}')">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div style="font-size: 30px;">${car.image}</div>
                            ${statusBadge}
                        </div>
                        <div style="margin-bottom: 8px;">
                            <h5 style="margin-bottom: 2px; color: #333;">${car.name}</h5>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 12px; color: #007bff; font-weight: 600;">🆔 ${car.vehicleId}</span>
                                <span style="font-size: 12px; color: #666;">📋 ${car.licensePlate}</span>
                            </div>
                            <p style="color: #666; font-size: 13px; margin-bottom: 8px; text-transform: capitalize;">${car.type} • ${car.transmission} • ${car.seats} seats • ${car.year}</p>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 18px; font-weight: bold; color: #007bff;">৳${car.price}/day</span>
                            <span style="font-size: 12px; color: #666;">${car.fuel}</span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Features:</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                ${car.features.map(feature => `<span style="background: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-size: 10px;">${feature}</span>`).join('')}
                            </div>
                        </div>
                        ${buttonHtml}
                    </div>
                `;
            });

            if (cars.length === 0) {
                html = '<div style="text-align: center; padding: 40px; color: #666; font-style: italic;">No vehicles are registered in the system yet.</div>';
            }

            carsGrid.innerHTML = html;
        }

        function showCarDetails(carId) {
            // Find the car details from the globally stored vehicle data
            const allCars = window.availableCars || [];
            const car = allCars.find(c => c.id === carId);

            if (car) {
                const statusIcon = car.status === 'Available' ? '✅ AVAILABLE' : '⚠️ CURRENTLY UNAVAILABLE';
                const statusInfo = car.status === 'Available' ? 
                    'This vehicle is available for immediate booking.' : 
                    `Status: ${car.status}. You can request availability through the admin.`;
                
                alert(`🚗 REGISTERED VEHICLE DETAILS\n\n🆔 Vehicle ID: ${car.vehicleId}\n📋 License Plate: ${car.licensePlate}\n\n🚙 Vehicle: ${car.name} (${car.year})\n🚗 Type: ${car.type}\n👥 Seats: ${car.seats}\n⚙️ Transmission: ${car.transmission}\n⛽ Fuel: ${car.fuel}\n💰 Rate: ৳${car.price}/day\n\n✨ Features: ${car.features.join(', ')}\n\n${statusIcon}\n${statusInfo}`);
            } else {
                alert(`🚗 Vehicle Details\n\nVehicle ID: ${carId}\n\nDetailed information loading... In a full system, this would show comprehensive vehicle information, photos, and booking options.`);
            }
        }

        function bookCar(carId) {
            // Create booking modal
            const modal = document.createElement('div');
            modal.className = 'booking-modal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: flex; align-items: center; justify-content: center;';

            modal.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
                    <h3 style="margin-bottom: 20px; color: #007bff;">Book Car</h3>
                    <form id="bookingForm">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Pickup Date:</label>
                            <input type="date" id="bookingDate" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Pickup Time:</label>
                            <input type="time" id="bookingTime" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Rental Duration (days):</label>
                            <input type="number" id="bookingDuration" min="1" max="30" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Special Requirements:</label>
                            <textarea id="bookingNotes" rows="3" placeholder="Any special requirements or notes..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="this.closest('.booking-modal').remove()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">Cancel</button>
                            <button type="button" onclick="confirmBooking('${carId}', this.closest('.booking-modal'))" style="flex: 1; background: #28a745; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">Confirm Booking</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);
        }

        function confirmBooking(carId, modal) {
            const date = document.getElementById('bookingDate').value;
            const time = document.getElementById('bookingTime').value;
            const duration = document.getElementById('bookingDuration').value;
            const notes = document.getElementById('bookingNotes').value;

            if (!date || !time || !duration) {
                alert('Please fill in all required fields');
                return;
            }

            // Get car details from the globally stored vehicle data
            const allCars = window.availableCars || [];
            const selectedCar = allCars.find(car => car.id === carId);

            // Create booking object
            const booking = {
                id: 'BOOK' + Date.now(),
                carId: carId, // This is the actual Vehicle_ID from database (like VEH-146798-8180)
                carName: selectedCar ? selectedCar.name : 'Unknown Vehicle',
                licensePlate: selectedCar ? selectedCar.licensePlate : 'N/A',
                vehicleId: selectedCar ? selectedCar.vehicleId : carId, // Store the registered Vehicle ID explicitly
                make: selectedCar ? selectedCar.name.split(' ')[0] : 'Unknown',
                model: selectedCar ? selectedCar.name.split(' ').slice(1).join(' ') : 'Model',
                year: selectedCar ? selectedCar.year : 'N/A',
                date: date,
                time: time,
                duration: duration,
                notes: notes,
                status: 'Active',
                createdAt: new Date().toLocaleString(),
                totalCost: 0 // Would be calculated based on car price and duration
            };

            // Save booking
            let bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            bookings.push(booking);
            localStorage.setItem('carBookings', JSON.stringify(bookings));

            modal.remove();

            // Show confirmation with Vehicle ID
            alert(`✅ Booking confirmed successfully!\n\n🚗 Vehicle ID: ${booking.carId}\n📋 Vehicle: ${booking.carName}\n📅 Date: ${booking.date}\n⏰ Duration: ${booking.duration} day(s)\n\nYou can view this booking in "My Bookings" tab.`);
        }

        function requestVehicle(carId) {
            // Create request modal
            const modal = document.createElement('div');
            modal.className = 'request-modal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: flex; align-items: center; justify-content: center;';

            modal.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
                    <h3 style="margin-bottom: 20px; color: #ffc107;">Request Vehicle Availability</h3>
                    <form id="requestForm">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Desired Date:</label>
                            <input type="date" id="requestDate" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Duration (days):</label>
                            <input type="number" id="requestDuration" min="1" max="30" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Message to Admin:</label>
                            <textarea id="requestMessage" rows="4" placeholder="Please explain why you need this vehicle and any specific requirements..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="this.closest('.request-modal').remove()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">Cancel</button>
                            <button type="button" onclick="submitRequest('${carId}', this.closest('.request-modal'))" style="flex: 1; background: #ffc107; color: #333; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">Send Request</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);
        }

        function submitRequest(carId, modal) {
            const date = document.getElementById('requestDate').value;
            const duration = document.getElementById('requestDuration').value;
            const message = document.getElementById('requestMessage').value;

            if (!date || !duration || !message) {
                alert('Please fill in all required fields');
                return;
            }

            // Get car details from the globally stored vehicle data
            const allCars = window.availableCars || [];
            const selectedCar = allCars.find(car => car.id === carId);

            // Create request object
            const request = {
                id: 'REQ' + Date.now(),
                carId: carId,
                carName: selectedCar ? selectedCar.name : 'Unknown Vehicle',
                licensePlate: selectedCar ? selectedCar.licensePlate : 'N/A',
                date: date,
                duration: duration,
                message: message,
                status: 'Pending',
                createdAt: new Date().toLocaleString(),
                type: 'availability_request'
            };

            // Save request
            let requests = JSON.parse(localStorage.getItem('vehicleRequests') || '[]');
            requests.push(request);
            localStorage.setItem('vehicleRequests', JSON.stringify(requests));

            modal.remove();

            // Show confirmation
            alert(`📋 Request submitted successfully!\n\n🚗 Vehicle: ${request.carName}\n📅 Requested Date: ${request.date}\n⏰ Duration: ${request.duration} day(s)\n\nYour request has been sent to the admin for review. You will be notified once a decision is made.`);
        }

        function loadMyBookings() {
            const bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            const requests = JSON.parse(localStorage.getItem('vehicleRequests') || '[]');
            
            // Combine bookings and requests for display
            const allItems = [
                ...bookings.map(b => ({...b, itemType: 'booking'})),
                ...requests.map(r => ({...r, itemType: 'request'}))
            ];
            
            // Sort by creation date (newest first)
            allItems.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
            
            displayBookings(allItems);
        }

        function displayBookings(items) {
            const bookingsList = document.getElementById('bookingsList');

            if (items.length === 0) {
                bookingsList.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #666; font-style: italic;">
                        No bookings or requests yet. Browse cars and make your first booking!
                    </div>
                `;
                return;
            }

            let html = '';
            let pendingRequests = 0;

            items.forEach((item, index) => {
                const isRequest = item.itemType === 'request';
                const statusColor = item.status === 'Active' ? '#ffc107' :
                    item.status === 'Completed' ? '#28a745' : 
                    item.status === 'Pending' ? '#17a2b8' : '#dc3545';

                const itemId = `item-${index}`;
                const title = isRequest ? `Request ${item.id}` : `Booking ${item.id}`;
                const icon = isRequest ? '📋' : '🚗';

                html += `
                    <div id="${itemId}" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <h6 style="margin: 0; color: #333;">${icon} ${title}</h6>
                            <span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;">${item.status}</span>
                        </div>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                            <div id="vehicle-info-${index}" style="text-align: center; color: #666;">
                                <i class="fas fa-spinner fa-spin"></i> Loading vehicle details...
                            </div>
                        </div>
                        ${item.pickupLocation ? `<div style="margin-bottom: 10px; font-size: 14px; color: #007bff;"><strong>📍 Pickup Location:</strong> ${item.pickupLocation}</div>` : ''}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; font-size: 14px;">
                            <div><strong>Date:</strong> ${item.date}</div>
                            ${!isRequest ? `<div><strong>Time:</strong> ${item.time}</div>` : ''}
                            <div><strong>Duration:</strong> ${item.duration} day(s)</div>
                            <div><strong>Created:</strong> ${new Date(item.createdAt).toLocaleDateString()}</div>
                        </div>
                        ${item.notes ? `<div style="margin-bottom: 10px; font-size: 14px;"><strong>Notes:</strong> ${item.notes}</div>` : ''}
                        ${item.message ? `<div style="margin-bottom: 10px; font-size: 14px;"><strong>Message:</strong> ${item.message}</div>` : ''}
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button onclick="viewItemDetails('${item.id}', '${item.itemType}')" style="background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">View Details</button>
                            ${(item.status === 'Active' || item.status === 'Pending') ? `<button onclick="cancelItem('${item.id}', '${item.itemType}')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">Cancel</button>` : ''}
                        </div>
                    </div>
                `;

                // Fetch vehicle details for each item
                pendingRequests++;
                fetch(`?action=get_vehicle_by_id&vehicle_id=${encodeURIComponent(item.carId)}`)
                    .then(response => response.json())
                    .then(vehicle => {
                        const vehicleInfoDiv = document.getElementById(`vehicle-info-${index}`);
                        if (vehicle) {
                            vehicleInfoDiv.innerHTML = `
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 600; color: #007bff;">🚗 Vehicle ID: ${item.carId}</span>
                                </div>
                                <div style="margin-bottom: 8px;">
                                    <div style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 4px;">
                                        ${vehicle.image} ${vehicle.name} (${vehicle.year})
                                    </div>
                                    <div style="font-size: 12px; color: #666; margin-bottom: 6px;">
                                        License: ${vehicle.licensePlate}
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; font-size: 12px; color: #555;">
                                    <div style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; text-align: center;">
                                        👥 ${vehicle.seats} Seats
                                    </div>
                                    <div style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; text-align: center;">
                                        ⚙️ ${vehicle.transmission}
                                    </div>
                                    <div style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; text-align: center;">
                                        ⛽ ${vehicle.fuel}
                                    </div>
                                </div>
                                <div style="margin-top: 8px; font-size: 14px; font-weight: 600; color: #28a745;">
                                    💰 Rate: ৳${vehicle.price}/day
                                </div>
                            `;
                            // Store vehicle data globally for detail view
                            if (!window.bookingVehicleDetails) {
                                window.bookingVehicleDetails = {};
                            }
                            window.bookingVehicleDetails[item.carId] = vehicle;
                        } else {
                            vehicleInfoDiv.innerHTML = `
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 600; color: #007bff;">🚗 Vehicle ID: ${item.carId}</span>
                                </div>
                                ${item.carName ? `
                                    <div style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 4px;">
                                        🚗 ${item.carName}
                                    </div>
                                ` : ''}
                                ${item.licensePlate ? `
                                    <div style="font-size: 12px; color: #666;">
                                        License: ${item.licensePlate}
                                    </div>
                                ` : ''}
                                <div style="font-size: 12px; color: #dc3545; margin-top: 4px;">
                                    ⚠️ Vehicle details not found in database
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching vehicle details:', error);
                        const vehicleInfoDiv = document.getElementById(`vehicle-info-${index}`);
                        vehicleInfoDiv.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #007bff;">🚗 Vehicle ID: ${item.carId}</span>
                                ${item.carName ? `<span style="font-size: 12px; color: #666;">${item.carName}</span>` : ''}
                            </div>
                            <div style="font-size: 12px; color: #dc3545; margin-top: 4px;">
                                ⚠️ Error loading vehicle details
                            </div>
                        `;
                    });
            });

            bookingsList.innerHTML = html;
        }

        function filterBookings(status) {
            // Update filter buttons
            document.querySelectorAll('.booking-filter').forEach(btn => {
                btn.style.background = '#6c757d';
            });
            event.target.style.background = '#007bff';

            // Get both bookings and requests
            const allBookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            const allRequests = JSON.parse(localStorage.getItem('vehicleRequests') || '[]');
            
            // Combine and mark item types
            const allItems = [
                ...allBookings.map(b => ({...b, itemType: 'booking'})),
                ...allRequests.map(r => ({...r, itemType: 'request'}))
            ];

            // Filter based on status
            const filteredItems = status === 'all' ? allItems : allItems.filter(item => item.status.toLowerCase() === status);
            
            // Sort by creation date (newest first)
            filteredItems.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
            
            displayBookings(filteredItems);
        }

        function filterCars() {
            const typeFilter = document.getElementById('carTypeFilter').value;
            const priceFilter = document.getElementById('priceRangeFilter').value;
            const transmissionFilter = document.getElementById('transmissionFilter').value;

            // Use globally stored vehicle data from database
            const allCars = window.availableCars || [];

            // Apply filters
            let filteredCars = allCars.filter(car => {
                // Type filter
                if (typeFilter && car.type !== typeFilter) return false;

                // Price filter (BDT ranges)
                if (priceFilter) {
                    if (priceFilter === '0-3000' && (car.price < 0 || car.price > 3000)) return false;
                    if (priceFilter === '3000-6000' && (car.price < 3000 || car.price > 6000)) return false;
                    if (priceFilter === '6000-12000' && (car.price < 6000 || car.price > 12000)) return false;
                    if (priceFilter === '12000+' && car.price < 12000) return false;
                }

                // Transmission filter
                if (transmissionFilter && car.transmission !== transmissionFilter) return false;

                return true;
            });

            // Display filtered cars
            displayCars(filteredCars);

            // Show filter results
            const resultsMessage = filteredCars.length > 0 ?
                `Found ${filteredCars.length} car(s) matching your criteria.` :
                'No cars found matching your criteria. Try adjusting your filters.';

            alert(resultsMessage);
        }

        function cancelBooking(bookingId) {
            if (!confirm('Are you sure you want to cancel this booking?')) {
                return;
            }

            let bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            const booking = bookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = 'Cancelled';
            }
            localStorage.setItem('carBookings', JSON.stringify(bookings));

            loadMyBookings();
            alert('Booking cancelled successfully');
        }

        function viewBookingDetails(bookingId) {
            const bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            const booking = bookings.find(b => b.id === bookingId);

            if (booking) {
                // Check if we have detailed vehicle information stored
                const vehicleDetails = window.bookingVehicleDetails ? window.bookingVehicleDetails[booking.carId] : null;

                if (vehicleDetails) {
                    // Show detailed information from database
                    let detailsMessage = `🚗 REGISTERED VEHICLE BOOKING DETAILS\n\n`;
                    detailsMessage += `📋 Booking ID: ${booking.id}\n`;
                    detailsMessage += `🆔 Vehicle ID: ${booking.carId}\n\n`;

                    detailsMessage += `🚙 VEHICLE DETAILS (from vehicle_register.php):\n`;
                    detailsMessage += `• Vehicle: ${vehicleDetails.name} (${vehicleDetails.year})\n`;
                    detailsMessage += `• Make: ${vehicleDetails.make}\n`;
                    detailsMessage += `• Model: ${vehicleDetails.model}\n`;
                    detailsMessage += `• License Plate: ${vehicleDetails.licensePlate}\n`;
                    detailsMessage += `• Vehicle Type: ${vehicleDetails.type}\n`;
                    detailsMessage += `• Seating Capacity: ${vehicleDetails.seats} passengers\n`;
                    detailsMessage += `• Transmission: ${vehicleDetails.transmission}\n`;
                    detailsMessage += `• Fuel Type: ${vehicleDetails.fuel}\n`;
                    detailsMessage += `• Daily Rate: ৳${vehicleDetails.price}\n`;

                    if (vehicleDetails.features && vehicleDetails.features.length > 0) {
                        detailsMessage += `• Features: ${vehicleDetails.features.join(', ')}\n`;
                    }

                    detailsMessage += `\n📅 BOOKING INFORMATION:\n`;
                    detailsMessage += `• Date: ${booking.date}\n`;
                    detailsMessage += `• Time: ${booking.time}\n`;
                    detailsMessage += `• Duration: ${booking.duration} day(s)\n`;
                    detailsMessage += `• Status: ${booking.status}\n`;
                    detailsMessage += `• Created: ${booking.createdAt}\n`;

                    if (booking.pickupLocation) {
                        detailsMessage += `• Pickup Location: ${booking.pickupLocation}\n`;
                    }

                    if (booking.notes) {
                        detailsMessage += `• Notes: ${booking.notes}\n`;
                    }

                    // Calculate total cost
                    const totalCost = vehicleDetails.price * parseInt(booking.duration);
                    detailsMessage += `\n💰 COST CALCULATION:\n`;
                    detailsMessage += `• Daily Rate: ৳${vehicleDetails.price}\n`;
                    detailsMessage += `• Duration: ${booking.duration} day(s)\n`;
                    detailsMessage += `• Total Estimated Cost: ৳${totalCost}\n`;

                    detailsMessage += `\n✅ Vehicle details loaded from VRMS Registration Database`;

                    alert(detailsMessage);
                } else {
                    // Fetch vehicle details if not already loaded
                    fetch(`?action=get_vehicle_by_id&vehicle_id=${encodeURIComponent(booking.carId)}`)
                        .then(response => response.json())
                        .then(vehicle => {
                            if (vehicle) {
                                // Store for future use
                                if (!window.bookingVehicleDetails) {
                                    window.bookingVehicleDetails = {};
                                }
                                window.bookingVehicleDetails[booking.carId] = vehicle;

                                // Show detailed information
                                let detailsMessage = `🚗 REGISTERED VEHICLE BOOKING DETAILS\n\n`;
                                detailsMessage += `📋 Booking ID: ${booking.id}\n`;
                                detailsMessage += `🆔 Vehicle ID: ${booking.carId}\n\n`;

                                detailsMessage += `🚙 VEHICLE DETAILS (from vehicle_register.php):\n`;
                                detailsMessage += `• Vehicle: ${vehicle.name} (${vehicle.year})\n`;
                                detailsMessage += `• Make: ${vehicle.make}\n`;
                                detailsMessage += `• Model: ${vehicle.model}\n`;
                                detailsMessage += `• License Plate: ${vehicle.licensePlate}\n`;
                                detailsMessage += `• Seating Capacity: ${vehicle.seats} passengers\n`;
                                detailsMessage += `• Transmission: ${vehicle.transmission}\n`;
                                detailsMessage += `• Fuel Type: ${vehicle.fuel}\n`;
                                detailsMessage += `• Daily Rate: ৳${vehicle.price}\n`;

                                if (vehicle.features && vehicle.features.length > 0) {
                                    detailsMessage += `• Features: ${vehicle.features.join(', ')}\n`;
                                }

                                detailsMessage += `\n📅 BOOKING INFORMATION:\n`;
                                detailsMessage += `• Date: ${booking.date}\n`;
                                detailsMessage += `• Time: ${booking.time}\n`;
                                detailsMessage += `• Duration: ${booking.duration} day(s)\n`;
                                detailsMessage += `• Status: ${booking.status}\n`;
                                detailsMessage += `• Created: ${booking.createdAt}\n`;

                                if (booking.pickupLocation) {
                                    detailsMessage += `• Pickup Location: ${booking.pickupLocation}\n`;
                                }

                                if (booking.notes) {
                                    detailsMessage += `• Notes: ${booking.notes}\n`;
                                }

                                // Calculate total cost
                                const totalCost = vehicle.price * parseInt(booking.duration);
                                detailsMessage += `\n💰 COST CALCULATION:\n`;
                                detailsMessage += `• Daily Rate: ৳${vehicle.price}\n`;
                                detailsMessage += `• Duration: ${booking.duration} day(s)\n`;
                                detailsMessage += `• Total Estimated Cost: ৳${totalCost}\n`;

                                detailsMessage += `\n✅ Vehicle details loaded from VRMS Registration Database`;

                                alert(detailsMessage);
                            } else {
                                // Fallback to basic info
                                const carInfo = booking.carName ? `\nVehicle: ${booking.carName}` : '';
                                const plateInfo = booking.licensePlate ? `\nLicense Plate: ${booking.licensePlate}` : '';
                                const pickupInfo = booking.pickupLocation ? `\nPickup Location: ${booking.pickupLocation}` : '';
                                const notesInfo = booking.notes ? `\nNotes: ${booking.notes}` : '';

                                alert(`🚗 BOOKING DETAILS (Limited Info)\n\n📋 Booking ID: ${booking.id}\n🆔 Vehicle ID: ${booking.carId}${carInfo}${plateInfo}\n\n📅 Date: ${booking.date}\n🕐 Time: ${booking.time}\n📆 Duration: ${booking.duration} day(s)\n📊 Status: ${booking.status}${pickupInfo}\n📝 Created: ${booking.createdAt}${notesInfo}\n\n⚠️ Vehicle details not found in registration database.`);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching vehicle details:', error);
                            // Fallback to basic info
                            const carInfo = booking.carName ? `\nVehicle: ${booking.carName}` : '';
                            const plateInfo = booking.licensePlate ? `\nLicense Plate: ${booking.licensePlate}` : '';
                            const pickupInfo = booking.pickupLocation ? `\nPickup Location: ${booking.pickupLocation}` : '';
                            const notesInfo = booking.notes ? `\nNotes: ${booking.notes}` : '';

                            alert(`🚗 BOOKING DETAILS (Limited Info)\n\n📋 Booking ID: ${booking.id}\n🆔 Vehicle ID: ${booking.carId}${carInfo}${plateInfo}\n\n📅 Date: ${booking.date}\n🕐 Time: ${booking.time}\n📆 Duration: ${booking.duration} day(s)\n📊 Status: ${booking.status}${pickupInfo}\n📝 Created: ${booking.createdAt}${notesInfo}\n\n❌ Error loading vehicle details from database.`);
                        });
                }
            }
        }

        function viewItemDetails(itemId, itemType) {
            if (itemType === 'booking') {
                const bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
                const booking = bookings.find(b => b.id === itemId);
                if (booking) {
                    viewBookingDetails(itemId);
                }
            } else if (itemType === 'request') {
                const requests = JSON.parse(localStorage.getItem('vehicleRequests') || '[]');
                const request = requests.find(r => r.id === itemId);
                if (request) {
                    viewRequestDetails(request);
                }
            }
        }

        function viewRequestDetails(request) {
            // Check if we have detailed vehicle information stored
            const vehicleDetails = window.bookingVehicleDetails ? window.bookingVehicleDetails[request.carId] : null;

            if (vehicleDetails) {
                // Show detailed information from database
                let detailsMessage = `📋 VEHICLE REQUEST DETAILS\n\n`;
                detailsMessage += `📋 Request ID: ${request.id}\n`;
                detailsMessage += `🆔 Vehicle ID: ${request.carId}\n\n`;

                detailsMessage += `🚙 VEHICLE DETAILS (from vehicle_register.php):\n`;
                detailsMessage += `• Vehicle: ${vehicleDetails.name} (${vehicleDetails.year})\n`;
                detailsMessage += `• Make: ${vehicleDetails.make}\n`;
                detailsMessage += `• Model: ${vehicleDetails.model}\n`;
                detailsMessage += `• License Plate: ${vehicleDetails.licensePlate}\n`;
                detailsMessage += `• Vehicle Type: ${vehicleDetails.type}\n`;
                detailsMessage += `• Seating Capacity: ${vehicleDetails.seats} passengers\n`;
                detailsMessage += `• Transmission: ${vehicleDetails.transmission}\n`;
                detailsMessage += `• Fuel Type: ${vehicleDetails.fuel}\n`;
                detailsMessage += `• Daily Rate: ৳${vehicleDetails.price}\n`;

                if (vehicleDetails.features && vehicleDetails.features.length > 0) {
                    detailsMessage += `• Features: ${vehicleDetails.features.join(', ')}\n`;
                }

                detailsMessage += `\n📅 REQUEST INFORMATION:\n`;
                detailsMessage += `• Desired Date: ${request.date}\n`;
                detailsMessage += `• Duration: ${request.duration} day(s)\n`;
                detailsMessage += `• Status: ${request.status}\n`;
                detailsMessage += `• Created: ${request.createdAt}\n`;
                detailsMessage += `• Message: ${request.message}\n`;

                // Calculate estimated cost
                const totalCost = vehicleDetails.price * parseInt(request.duration);
                detailsMessage += `\n💰 ESTIMATED COST:\n`;
                detailsMessage += `• Daily Rate: ৳${vehicleDetails.price}\n`;
                detailsMessage += `• Duration: ${request.duration} day(s)\n`;
                detailsMessage += `• Estimated Total: ৳${totalCost}\n`;

                detailsMessage += `\n✅ Vehicle details loaded from VRMS Registration Database`;

                alert(detailsMessage);
            } else {
                // Fetch vehicle details if not already loaded
                fetch(`?action=get_vehicle_by_id&vehicle_id=${encodeURIComponent(request.carId)}`)
                    .then(response => response.json())
                    .then(vehicle => {
                        if (vehicle) {
                            // Store for future use
                            if (!window.bookingVehicleDetails) {
                                window.bookingVehicleDetails = {};
                            }
                            window.bookingVehicleDetails[request.carId] = vehicle;

                            // Show detailed information
                            let detailsMessage = `📋 VEHICLE REQUEST DETAILS\n\n`;
                            detailsMessage += `📋 Request ID: ${request.id}\n`;
                            detailsMessage += `🆔 Vehicle ID: ${request.carId}\n\n`;

                            detailsMessage += `🚙 VEHICLE DETAILS (from vehicle_register.php):\n`;
                            detailsMessage += `• Vehicle: ${vehicle.name} (${vehicle.year})\n`;
                            detailsMessage += `• Make: ${vehicle.make}\n`;
                            detailsMessage += `• Model: ${vehicle.model}\n`;
                            detailsMessage += `• License Plate: ${vehicle.licensePlate}\n`;
                            detailsMessage += `• Seating Capacity: ${vehicle.seats} passengers\n`;
                            detailsMessage += `• Transmission: ${vehicle.transmission}\n`;
                            detailsMessage += `• Fuel Type: ${vehicle.fuel}\n`;
                            detailsMessage += `• Daily Rate: ৳${vehicle.price}\n`;

                            if (vehicle.features && vehicle.features.length > 0) {
                                detailsMessage += `• Features: ${vehicle.features.join(', ')}\n`;
                            }

                            detailsMessage += `\n📅 REQUEST INFORMATION:\n`;
                            detailsMessage += `• Desired Date: ${request.date}\n`;
                            detailsMessage += `• Duration: ${request.duration} day(s)\n`;
                            detailsMessage += `• Status: ${request.status}\n`;
                            detailsMessage += `• Created: ${request.createdAt}\n`;
                            detailsMessage += `• Message: ${request.message}\n`;

                            // Calculate estimated cost
                            const totalCost = vehicle.price * parseInt(request.duration);
                            detailsMessage += `\n💰 ESTIMATED COST:\n`;
                            detailsMessage += `• Daily Rate: ৳${vehicle.price}\n`;
                            detailsMessage += `• Duration: ${request.duration} day(s)\n`;
                            detailsMessage += `• Estimated Total: ৳${totalCost}\n`;

                            detailsMessage += `\n✅ Vehicle details loaded from VRMS Registration Database`;

                            alert(detailsMessage);
                        } else {
                            // Fallback to basic info
                            const carInfo = request.carName ? `\nVehicle: ${request.carName}` : '';
                            const plateInfo = request.licensePlate ? `\nLicense Plate: ${request.licensePlate}` : '';

                            alert(`📋 REQUEST DETAILS (Limited Info)\n\n📋 Request ID: ${request.id}\n🆔 Vehicle ID: ${request.carId}${carInfo}${plateInfo}\n\n📅 Desired Date: ${request.date}\n📆 Duration: ${request.duration} day(s)\n📊 Status: ${request.status}\n💬 Message: ${request.message}\n📝 Created: ${request.createdAt}\n\n⚠️ Vehicle details not found in registration database.`);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching vehicle details:', error);
                        // Fallback to basic info
                        const carInfo = request.carName ? `\nVehicle: ${request.carName}` : '';
                        const plateInfo = request.licensePlate ? `\nLicense Plate: ${request.licensePlate}` : '';

                        alert(`📋 REQUEST DETAILS (Limited Info)\n\n📋 Request ID: ${request.id}\n🆔 Vehicle ID: ${request.carId}${carInfo}${plateInfo}\n\n📅 Desired Date: ${request.date}\n📆 Duration: ${request.duration} day(s)\n📊 Status: ${request.status}\n💬 Message: ${request.message}\n📝 Created: ${request.createdAt}\n\n❌ Error loading vehicle details from database.`);
                    });
            }
        }

        function cancelItem(itemId, itemType) {
            const action = itemType === 'booking' ? 'cancel this booking' : 'cancel this request';
            if (!confirm(`Are you sure you want to ${action}?`)) {
                return;
            }

            if (itemType === 'booking') {
                let bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
                const booking = bookings.find(b => b.id === itemId);
                if (booking) {
                    booking.status = 'Cancelled';
                }
                localStorage.setItem('carBookings', JSON.stringify(bookings));
                alert('Booking cancelled successfully');
            } else if (itemType === 'request') {
                let requests = JSON.parse(localStorage.getItem('vehicleRequests') || '[]');
                const request = requests.find(r => r.id === itemId);
                if (request) {
                    request.status = 'Cancelled';
                }
                localStorage.setItem('vehicleRequests', JSON.stringify(requests));
                alert('Request cancelled successfully');
            }

            loadMyBookings();
        }

        function updatePreferences() {
            const carType = document.getElementById('preferredCarType').value;
            const pickupLocation = document.getElementById('pickupLocation').value;

            if (!pickupLocation.trim()) {
                alert('Please enter a pickup location');
                return;
            }

            // Save preferences to localStorage
            const preferences = {
                carType: carType,
                pickupLocation: pickupLocation,
                updatedAt: new Date().toLocaleString()
            };

            localStorage.setItem('driverPreferences', JSON.stringify(preferences));

            // Update the Car Hire pickup location display
            updateCarHirePickupDisplay(pickupLocation);

            // Show success message
            alert(`Preferences updated successfully!\n\nPreferred Car Type: ${carType}\nPickup Location: ${pickupLocation}\n\nYour preferences have been saved and updated in Car Hire section.`);

            // In a real system, this would also update the database
            console.log('Driver preferences updated:', preferences);
        }

        // Function to update Car Hire pickup location display
        function updateCarHirePickupDisplay(location) {
            const carHireDisplay = document.getElementById('carHirePickupDisplay');
            if (carHireDisplay) {
                carHireDisplay.textContent = location;

                // Add a brief highlight animation
                carHireDisplay.style.background = 'rgba(255, 255, 255, 0.3)';
                carHireDisplay.style.borderRadius = '4px';
                carHireDisplay.style.padding = '2px 6px';
                carHireDisplay.style.transition = 'all 0.3s ease';

                setTimeout(() => {
                    carHireDisplay.style.background = 'transparent';
                    carHireDisplay.style.padding = '0';
                }, 1500);

                console.log('Car Hire pickup location updated to:', location);
            }
        }

        // Function to change pickup location directly from Car Hire section
        function changePickupLocationForHire() {
            const currentLocation = document.getElementById('carHirePickupDisplay').textContent;
            const newLocation = prompt('Enter new pickup location:', currentLocation);

            if (newLocation && newLocation.trim() !== '') {
                // Update the Car Hire display immediately
                updateCarHirePickupDisplay(newLocation.trim());

                // Update the Driver Profile input as well
                const profilePickupInput = document.getElementById('pickupLocation');
                if (profilePickupInput) {
                    profilePickupInput.value = newLocation.trim();
                }

                // Save to localStorage
                const currentPreferences = JSON.parse(localStorage.getItem('driverPreferences') || '{}');
                const updatedPreferences = {
                    ...currentPreferences,
                    pickupLocation: newLocation.trim(),
                    updatedAt: new Date().toLocaleString()
                };

                localStorage.setItem('driverPreferences', JSON.stringify(updatedPreferences));

                // Show confirmation
                alert(`Pickup location updated to: ${newLocation.trim()}\n\nThis change is reflected in both Car Hire and Driver Profile sections.`);
                console.log('Pickup location changed from Car Hire section:', newLocation.trim());
            }
        }

        function viewFullProfile() {
            // Create a comprehensive profile view
            const preferences = JSON.parse(localStorage.getItem('driverPreferences') || '{}');

            let profileInfo = `DRIVER PROFILE SUMMARY\n\n`;
            profileInfo += `Personal Information:\n`;
            profileInfo += `• Name: <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>\n`;
            profileInfo += `• Email: <?php echo htmlspecialchars($_SESSION['email']); ?>\n`;
            profileInfo += `• Phone: <?php echo htmlspecialchars($_SESSION['phone']); ?>\n`;
            profileInfo += `• License: <?php echo htmlspecialchars($_SESSION['license_number']); ?>\n`;
            profileInfo += `• Driver ID: <?php echo htmlspecialchars($_SESSION['driver_id']); ?>\n\n`;

            profileInfo += `Status Information:\n`;
            profileInfo += `• Driver Status: <?php echo htmlspecialchars($_SESSION['driver_status']); ?>\n`;
            profileInfo += `• Availability: <?php echo htmlspecialchars($_SESSION['availability']); ?>\n`;
            profileInfo += `• Address: <?php echo htmlspecialchars($_SESSION['address']); ?>\n\n`;

            if (preferences.carType) {
                profileInfo += `Rental Preferences:\n`;
                profileInfo += `• Preferred Car Type: ${preferences.carType}\n`;
                profileInfo += `• Pickup Location: ${preferences.pickupLocation}\n`;
                profileInfo += `• Last Updated: ${preferences.updatedAt}\n\n`;
            }

            const bookings = JSON.parse(localStorage.getItem('carBookings') || '[]');
            profileInfo += `Booking Statistics:\n`;
            profileInfo += `• Total Bookings: ${bookings.length}\n`;
            profileInfo += `• Active Bookings: ${bookings.filter(b => b.status === 'Active').length}\n`;
            profileInfo += `• Completed Bookings: ${bookings.filter(b => b.status === 'Completed').length}\n`;

            alert(profileInfo);
        }

        function loadDriverPreferences() {
            // Load saved preferences from localStorage
            const preferences = JSON.parse(localStorage.getItem('driverPreferences') || '{}');

            if (preferences.carType) {
                const carTypeSelect = document.getElementById('preferredCarType');
                carTypeSelect.value = preferences.carType;
            }

            if (preferences.pickupLocation) {
                const pickupLocationInput = document.getElementById('pickupLocation');
                pickupLocationInput.value = preferences.pickupLocation;
            }
        }

        function showLocationModal() {
            // Remove any existing location modal first
            const existingModal = document.getElementById('locationModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create and show location modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'locationModal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('locationModal').style.display='none'">&times;</span>
                    <h2>Set Your Location</h2>
                    <div class="location-content">
                        <div class="location-info">
                            <h3>Current Location</h3>
                            <div id="current-saved-location" style="background: #e9f7ef; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                                <p style="margin: 0; color: #155724;"><strong>Saved Location:</strong> <span id="saved-location-text">Not set</span></p>
                                <button onclick="deleteSavedLocation()" class="action-btn secondary" style="margin-top: 10px; background: #dc3545; color: white; font-size: 12px; padding: 5px 10px;" id="delete-location-btn">Delete Saved Location</button>
                            </div>
                            <h3>Update Your Address</h3>
                            <p>Please provide your current address or location.</p>
                            <div class="manual-input-section">
                                <div class="input-group full-width">
                                    <label for="manual-address">Enter Address:</label>
                                    <input type="text" id="manual-address" placeholder="e.g., 123 Main St, City, State">
                                </div>
                                <div class="input-actions">
                                    <button onclick="setAddressLocation()" class="action-btn">Set Location</button>
                                    <button onclick="clearManualInputs()" class="action-btn secondary">Clear</button>
                                </div>
                            </div>
                            <div id="manual-location-display" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.style.display = 'block';

            // Load and display current saved location
            loadCurrentLocation();
        }

        function loadCurrentLocation() {
            // Load location from database first
            const formData = new FormData();
            formData.append('action', 'get_driver_location');

            fetch('driver_portal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Load location response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    return response.text().then(text => {
                        console.log('Load location raw response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error in loadCurrentLocation:', e);
                            throw new Error(`Invalid JSON response: ${text.substring(0, 200)}...`);
                        }
                    });
                })
                .then(data => {
                    console.log('Load location parsed data:', data);
                    const savedLocationText = document.getElementById('saved-location-text');
                    const currentLocationDisplay = document.getElementById('current-location-display');
                    const deleteButton = document.getElementById('delete-location-btn');

                    if (data.success && data.location && data.location.address) {
                        const address = data.location.address;

                        // Update displays
                        if (savedLocationText) {
                            savedLocationText.textContent = address;
                        }
                        if (currentLocationDisplay) {
                            currentLocationDisplay.textContent = address;
                            currentLocationDisplay.style.color = '#28a745';
                            currentLocationDisplay.style.fontStyle = 'normal';
                        }
                        // Show delete button when location exists
                        if (deleteButton) {
                            deleteButton.style.display = 'inline-block';
                        }

                        // Also save to localStorage for offline access
                        localStorage.setItem('driverCurrentLocation', address);
                    } else {
                        // No location in database, check localStorage as fallback
                        const savedLocation = localStorage.getItem('driverCurrentLocation');

                        if (savedLocation) {
                            if (savedLocationText) {
                                savedLocationText.textContent = savedLocation;
                            }
                            if (currentLocationDisplay) {
                                currentLocationDisplay.textContent = savedLocation;
                                currentLocationDisplay.style.color = '#28a745';
                                currentLocationDisplay.style.fontStyle = 'normal';
                            }
                            if (deleteButton) {
                                deleteButton.style.display = 'inline-block';
                            }
                        } else {
                            // No location found anywhere
                            if (savedLocationText) {
                                savedLocationText.textContent = 'Not set';
                            }
                            if (currentLocationDisplay) {
                                currentLocationDisplay.textContent = 'Not set';
                                currentLocationDisplay.style.color = '#666';
                                currentLocationDisplay.style.fontStyle = 'italic';
                            }
                            if (deleteButton) {
                                deleteButton.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading location from database:', error);

                    // Fallback to localStorage
                    const savedLocation = localStorage.getItem('driverCurrentLocation');
                    const savedLocationText = document.getElementById('saved-location-text');
                    const currentLocationDisplay = document.getElementById('current-location-display');
                    const deleteButton = document.getElementById('delete-location-btn');

                    if (savedLocation) {
                        if (savedLocationText) {
                            savedLocationText.textContent = savedLocation + ' (offline)';
                        }
                        if (currentLocationDisplay) {
                            currentLocationDisplay.textContent = savedLocation + ' (offline)';
                            currentLocationDisplay.style.color = '#ffc107';
                            currentLocationDisplay.style.fontStyle = 'normal';
                        }
                        if (deleteButton) {
                            deleteButton.style.display = 'inline-block';
                        }
                    } else {
                        if (savedLocationText) {
                            savedLocationText.textContent = 'Not set';
                        }
                        if (currentLocationDisplay) {
                            currentLocationDisplay.textContent = 'Error loading location';
                            currentLocationDisplay.style.color = '#dc3545';
                            currentLocationDisplay.style.fontStyle = 'italic';
                        }
                        if (deleteButton) {
                            deleteButton.style.display = 'none';
                        }
                    }
                });
        }

        function setAddressLocation() {
            const address = document.getElementById('manual-address').value.trim();
            const display = document.getElementById('manual-location-display');

            if (!address) {
                display.innerHTML = `
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <p><strong>Error:</strong> Please enter an address.</p>
                    </div>
                `;
                return;
            }

            // Show loading state
            display.innerHTML = `
                <div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; border-left: 4px solid #bee5eb;">
                    <p><i class="fas fa-spinner fa-spin"></i> Updating location...</p>
                </div>
            `;

            // Save location to database
            const formData = new FormData();
            formData.append('action', 'update_driver_location');
            formData.append('address', address);
            formData.append('name', '<?php echo isset($_SESSION["first_name"]) ? htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]) : "Driver"; ?>');

            fetch('driver_portal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error(`Invalid JSON response: ${text.substring(0, 200)}...`);
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed response data:', data);
                    if (data.success) {
                        // Save to localStorage as backup
                        localStorage.setItem('driverCurrentLocation', address);

                        // Update all location displays
                        const savedLocationText = document.getElementById('saved-location-text');
                        const currentLocationDisplay = document.getElementById('current-location-display');
                        const deleteButton = document.getElementById('delete-location-btn');

                        if (savedLocationText) {
                            savedLocationText.textContent = address;
                        }

                        if (currentLocationDisplay) {
                            currentLocationDisplay.textContent = address;
                            currentLocationDisplay.style.color = '#28a745';
                            currentLocationDisplay.style.fontStyle = 'normal';
                        }

                        // Show delete button when location is saved
                        if (deleteButton) {
                            deleteButton.style.display = 'inline-block';
                        }

                        // Display success message
                        display.innerHTML = `
                        <div class="location-details">
                            <h4 style="color: #28a745; margin-bottom: 10px;">✓ Location Set Successfully</h4>
                            <p><strong>Address:</strong> ${address}</p>
                            <p><strong>Status:</strong> Location saved to database and is now visible to users</p>
                            <p><small>Updated at: ${new Date().toLocaleTimeString()}</small></p>
                        </div>
                    `;
                    } else {
                        display.innerHTML = `
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <p><strong>Error:</strong> ${data.message || 'Failed to update location'}</p>
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error updating location:', error);
                    display.innerHTML = `
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <p><strong>Error:</strong> ${error.message}</p>
                        <p><small>Check browser console for more details.</small></p>
                    </div>
                `;
                });
        }

        function clearManualInputs() {
            document.getElementById('manual-address').value = '';
            document.getElementById('manual-location-display').innerHTML = '';
        }

        function deleteSavedLocation() {
            // Show loading message
            const display = document.getElementById('manual-location-display');
            display.innerHTML = `
                <div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; border-left: 4px solid #bee5eb;">
                    <p><i class="fas fa-spinner fa-spin"></i> Deleting location...</p>
                </div>
            `;

            // Delete from database
            const formData = new FormData();
            formData.append('action', 'delete_driver_location');

            fetch('driver_portal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove from localStorage
                        localStorage.removeItem('driverCurrentLocation');

                        // Update all location displays
                        const savedLocationText = document.getElementById('saved-location-text');
                        const currentLocationDisplay = document.getElementById('current-location-display');
                        const deleteButton = document.getElementById('delete-location-btn');

                        if (savedLocationText) {
                            savedLocationText.textContent = 'Not set';
                        }

                        if (currentLocationDisplay) {
                            currentLocationDisplay.textContent = 'Not set';
                            currentLocationDisplay.style.color = '#666';
                            currentLocationDisplay.style.fontStyle = 'italic';
                        }

                        // Hide the delete button when no location is saved
                        if (deleteButton) {
                            deleteButton.style.display = 'none';
                        }

                        // Show success message
                        display.innerHTML = `
                        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                            <p><strong>Location Deleted:</strong> Your location has been removed from the database and is no longer visible to users.</p>
                            <p><small>Deleted at: ${new Date().toLocaleTimeString()}</small></p>
                        </div>
                    `;
                    } else {
                        display.innerHTML = `
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <p><strong>Error:</strong> ${data.message || 'Failed to delete location'}</p>
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error deleting location:', error);
                    display.innerHTML = `
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <p><strong>Error:</strong> Failed to connect to server. Please try again.</p>
                    </div>
                `;
                });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['tripsModal', 'earningsModal', 'locationModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Load current location when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const savedLocation = localStorage.getItem('driverCurrentLocation');
            const currentLocationDisplay = document.getElementById('current-location-display');

            if (savedLocation && currentLocationDisplay) {
                currentLocationDisplay.textContent = savedLocation;
                currentLocationDisplay.style.color = '#28a745';
                currentLocationDisplay.style.fontStyle = 'normal';
            }

            // Load saved pickup location preferences
            loadPickupLocationPreferences();
        });

        // Function to load saved pickup location preferences
        function loadPickupLocationPreferences() {
            const preferences = JSON.parse(localStorage.getItem('driverPreferences') || '{}');

            if (preferences.pickupLocation) {
                // Update Driver Profile pickup location
                const profilePickupInput = document.getElementById('pickupLocation');
                if (profilePickupInput) {
                    profilePickupInput.value = preferences.pickupLocation;
                    console.log('Loaded pickup location to Driver Profile:', preferences.pickupLocation);
                }

                // Update Car Hire pickup location display
                const carHireDisplay = document.getElementById('carHirePickupDisplay');
                if (carHireDisplay) {
                    carHireDisplay.textContent = preferences.pickupLocation;
                    console.log('Loaded pickup location to Car Hire display:', preferences.pickupLocation);
                }

                // Update preferred car type if available
                if (preferences.carType) {
                    const carTypeSelect = document.getElementById('preferredCarType');
                    if (carTypeSelect) {
                        carTypeSelect.value = preferences.carType;
                        console.log('Loaded preferred car type:', preferences.carType);
                    }
                }
            }
        }

        // HTML escaping function for security
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, function(m) {
                return map[m];
            }) : '';
        }

        // Open location in Google Maps
        function openLocationInMaps(location) {
            const encodedLocation = encodeURIComponent(location);
            window.open(`https://www.google.com/maps/search/${encodedLocation}`, '_blank');
        }

        // User Locations Functions
        let userRefreshCounter = 30;
        let userRefreshInterval, userCountdownInterval;
        let lastUserCount = 0;

        function loadUserLocations() {
            const usersList = document.getElementById('userLocationsList');
            const lastUpdateElement = document.getElementById('userLastUpdateTime');

            // Show loading state
            usersList.innerHTML = `
                <div class="loading-state">
                    <i class="fa fa-spinner fa-spin"></i> Loading user locations...
                </div>
            `;

            // Fetch user locations from the database
            fetch('user_location_api.php?action=get_all_user_locations')
                .then(response => {
                    console.log('API Response Status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('User locations data received:', data);
                    if (data.success && data.data && data.data.length > 0) {
                        console.log('First user data sample:', data.data[0]);
                        displayUsers(data.data);

                        // Check for changes and show notification
                        if (lastUserCount > 0 && data.data.length !== lastUserCount) {
                            if (data.data.length > lastUserCount) {
                                showUserNotification(`📍 New user locations available! (${data.data.length - lastUserCount} new)`);
                            } else {
                                showUserNotification(`📍 User locations updated (${lastUserCount - data.data.length} changed)`);
                            }
                        }
                        lastUserCount = data.data.length;
                    } else {
                        console.log('No user locations found or error:', data);
                        showNoUsersMessage();
                        lastUserCount = 0;
                    }

                    // Update last refresh time
                    const now = new Date();
                    const timeString = now.toLocaleTimeString();

                    if (lastUpdateElement) {
                        lastUpdateElement.innerHTML = `Last updated: ${timeString}`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching user locations:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        name: error.name
                    });

                    // Show more detailed error message
                    showUsersErrorMessage(error.message);

                    // Still update timestamp even on error
                    const now = new Date();
                    const timeString = now.toLocaleTimeString();

                    if (lastUpdateElement) {
                        lastUpdateElement.innerHTML = `Last update failed: ${timeString} - ${error.message}`;
                    }
                });
        }

        function displayUsers(users) {
            const usersList = document.getElementById('userLocationsList');
            let html = '';

            users.forEach(user => {
                const lastUpdated = user.last_updated ? new Date(user.last_updated).toLocaleString() : 'Unknown';
                
                // Handle username display properly
                const fullName = user.user_name || `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Unknown User';
                let userName = user.username || user.User_Name || 'N/A';
                
                // Debug: Log the original username data
                console.log('Original username data:', {
                    'user.username': user.username,
                    'user.User_Name': user.User_Name,
                    'selected userName': userName
                });
                
                // Ensure username has proper @ formatting (don't add @ if it already exists)
                if (userName !== 'N/A' && !userName.startsWith('@')) {
                    userName = '@' + userName;
                } else if (userName !== 'N/A' && userName.startsWith('@')) {
                    // Username already has @, use as is
                    userName = userName;
                }
                
                console.log('Final userName:', userName);

                html += `
                    <div class="user-card">
                        <div class="user-card-header">
                            <div class="user-name">${escapeHtml(fullName)}</div>
                            <span class="user-status">Active</span>
                        </div>
                        
                        <div class="user-details">
                            <div class="user-detail-item">
                                <i class="fa fa-user"></i>
                                <span>${escapeHtml(userName)}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-map-marker"></i>
                                <span>${escapeHtml(user.main_location || 'Location not set')}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-city"></i>
                                <span>${escapeHtml(user.city || 'City not set')}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-landmark"></i>
                                <span>${escapeHtml(user.landmark || 'No landmark')}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-phone"></i>
                                <span>${escapeHtml(user.phone || 'Not available')}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-envelope"></i>
                                <span>${escapeHtml(user.email || 'Not available')}</span>
                            </div>
                            <div class="user-detail-item">
                                <i class="fa fa-clock"></i>
                                <span>Updated: ${lastUpdated}</span>
                            </div>
                        </div>
                        
                        <div class="user-actions">
                            <button class="user-action-btn primary" onclick="openLocationInMaps('${escapeHtml(user.main_location + (user.city ? ', ' + user.city : ''))}')" style="background: #17a2b8;">
                                <i class="fa fa-map"></i> View on Map
                            </button>
                        </div>
                    </div>
                `;
            });

            usersList.innerHTML = html;
        }

        function showNoUsersMessage() {
            const usersList = document.getElementById('userLocationsList');
            usersList.innerHTML = `
                <div class="loading-state">
                    <i class="fa fa-users" style="font-size: 2rem; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No User Locations Available</h3>
                    <p>No registered users have set their locations yet.</p>
                    <button class="btn btn-primary" onclick="loadUserLocations()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            `;
        }

        function showUsersErrorMessage(errorMessage = null) {
            const usersList = document.getElementById('userLocationsList');
            const errorDetail = errorMessage ? `<br><small style="color: #666;">Error: ${errorMessage}</small>` : '';

            usersList.innerHTML = `
                <div class="loading-state">
                    <i class="fa fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545; margin-bottom: 15px;"></i>
                    <h3>Error Loading User Locations</h3>
                    <p>Unable to load user locations. Please check your connection and try again.${errorDetail}</p>
                    <button class="btn btn-primary" onclick="loadUserLocations()">
                        <i class="fa fa-refresh"></i> Try Again
                    </button>
                </div>
            `;
        }

        function showUserNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                z-index: 10000;
                font-size: 14px;
                max-width: 300px;
                word-wrap: break-word;
                animation: slideIn 0.3s ease-out;
            `;
            notification.innerHTML = message;
            document.body.appendChild(notification);

            // Remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease-in';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 4000);
        }

        // Load user locations when page loads and set up auto-refresh
        document.addEventListener('DOMContentLoaded', function() {
            // Load user locations on page load
            setTimeout(() => {
                loadUserLocations();
            }, 1000); // Load after drivers load

            // Auto-refresh user locations every 45 seconds (different from driver refresh)
            userRefreshInterval = setInterval(function() {
                userRefreshCounter = 45;
                loadUserLocations();
            }, 45000);

            // Show countdown timer for users
            userCountdownInterval = setInterval(function() {
                userRefreshCounter--;
                const lastUpdateElement = document.getElementById('userLastUpdateTime');
                if (lastUpdateElement && userRefreshCounter > 0) {
                    const currentText = lastUpdateElement.innerHTML;
                    if (currentText && !currentText.includes('Next refresh in')) {
                        lastUpdateElement.innerHTML = currentText + ` • Next refresh in ${userRefreshCounter}s`;
                    }
                }

                if (userRefreshCounter <= 0) {
                    userRefreshCounter = 45;
                }
            }, 1000);
        });
    </script>
</body>

</html>