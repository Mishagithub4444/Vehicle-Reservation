<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required'
    ]);
    exit();
}

// Database connection
include_once 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_all_reservations':
            getAllReservations($conn);
            break;

        case 'get_reservation_stats':
            getReservationStats($conn);
            break;

        case 'delete_reservation':
            deleteReservation($conn);
            break;

        case 'export_reservations':
            exportReservations($conn);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Reservation Reports API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}

// Get all reservations with optional filtering
function getAllReservations($conn) {
    try {
        // Base query - we'll check which tables exist and adapt accordingly
        $tables_to_check = [
            'reservations',
            'user_reservations', 
            'vehicle_reservations',
            'booking',
            'bookings'
        ];
        
        $reservation_table = null;
        foreach ($tables_to_check as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $reservation_table = $table;
                break;
            }
        }
        
        if (!$reservation_table) {
            // Create a sample response if no reservation table exists
            echo json_encode([
                'success' => true,
                'reservations' => [],
                'message' => 'No reservation table found. Sample data structure ready.'
            ]);
            return;
        }
        
        // Get columns from the reservation table
        $columns_query = "SHOW COLUMNS FROM $reservation_table";
        $columns_result = $conn->query($columns_query);
        $available_columns = [];
        
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        
        // Build dynamic query based on available columns
        $select_fields = [];
        $join_clauses = [];
        
        // Map common field variations for reservations table
        $field_mapping = [
            'reservation_id' => ['Reservation_ID', 'reservation_id', 'booking_id', 'id'],
            'user_id' => ['User_ID', 'user_id', 'customer_id'],
            'vehicle_id' => ['Vehicle_ID', 'vehicle_id'],
            'driver_id' => ['Driver_ID', 'driver_id'],
            'start_date' => ['Start_Date', 'start_date', 'pickup_date', 'from_date'],
            'end_date' => ['End_Date', 'end_date', 'return_date', 'to_date'],
            'total_cost' => ['Total_Cost', 'total_cost', 'amount', 'price', 'cost'],
            'status' => ['Status', 'status', 'booking_status'],
            'created_at' => ['Created_At', 'created_at', 'date_created', 'booking_date']
        ];
        
        // Store actual field names found in reservation table
        $found_fields = [];
        
        foreach ($field_mapping as $alias => $possible_fields) {
            foreach ($possible_fields as $field) {
                if (in_array($field, $available_columns)) {
                    $select_fields[] = "r.$field as $alias";
                    $found_fields[$alias] = $field; // Store the actual field name
                    break;
                }
            }
            // If no field found, add NULL placeholder
            if (!isset($found_fields[$alias])) {
                $select_fields[] = "NULL as $alias";
            }
        }
        
        // Add JOINs if user and vehicle tables exist
        $user_tables = ['user_registration', 'users', 'customers'];
        $vehicle_tables = ['vehicle_registration', 'vehicles'];
        $driver_tables = ['driver_registration', 'drivers'];
        
        $user_table = null;
        $vehicle_table = null;
        $driver_table = null;
        
        foreach ($user_tables as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $user_table = $table;
                break;
            }
        }
        
        foreach ($vehicle_tables as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $vehicle_table = $table;
                break;
            }
        }
        
        foreach ($driver_tables as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $driver_table = $table;
                break;
            }
        }
        
        // Add user information
        if ($user_table && isset($found_fields['user_id'])) {
            $user_columns_query = "SHOW COLUMNS FROM $user_table";
            $user_columns_result = $conn->query($user_columns_query);
            $user_available_columns = [];
            
            while ($column = $user_columns_result->fetch_assoc()) {
                $user_available_columns[] = $column['Field'];
            }
            
            // Find user ID field
            $user_id_field = null;
            foreach (['User_ID', 'user_id', 'id', 'customer_id'] as $field) {
                if (in_array($field, $user_available_columns)) {
                    $user_id_field = $field;
                    break;
                }
            }
            
            if ($user_id_field) {
                if (in_array('First_Name', $user_available_columns) && in_array('Last_Name', $user_available_columns)) {
                    $select_fields[] = "CONCAT(COALESCE(u.First_Name, ''), ' ', COALESCE(u.Last_Name, '')) as User_Name";
                } elseif (in_array('name', $user_available_columns)) {
                    $select_fields[] = "u.name as User_Name";
                } elseif (in_array('username', $user_available_columns)) {
                    $select_fields[] = "u.username as User_Name";
                } else {
                    $select_fields[] = "'Unknown User' as User_Name";
                }
                
                if (in_array('Email', $user_available_columns)) {
                    $select_fields[] = "u.Email as User_Email";
                } elseif (in_array('email', $user_available_columns)) {
                    $select_fields[] = "u.email as User_Email";
                } else {
                    $select_fields[] = "'' as User_Email";
                }
                
                $join_clauses[] = "LEFT JOIN $user_table u ON r.{$found_fields['user_id']} = u.$user_id_field";
            }
        } else {
            $select_fields[] = "'Unknown User' as User_Name";
            $select_fields[] = "'' as User_Email";
        }
        
        // Add vehicle information
        if ($vehicle_table && isset($found_fields['vehicle_id'])) {
            $vehicle_columns_query = "SHOW COLUMNS FROM $vehicle_table";
            $vehicle_columns_result = $conn->query($vehicle_columns_query);
            $vehicle_available_columns = [];
            
            while ($column = $vehicle_columns_result->fetch_assoc()) {
                $vehicle_available_columns[] = $column['Field'];
            }
            
            // Find vehicle ID field
            $vehicle_id_field = null;
            foreach (['Vehicle_ID', 'vehicle_id', 'id'] as $field) {
                if (in_array($field, $vehicle_available_columns)) {
                    $vehicle_id_field = $field;
                    break;
                }
            }
            
            if ($vehicle_id_field) {
                if (in_array('Make', $vehicle_available_columns) && in_array('Model', $vehicle_available_columns)) {
                    if (in_array('Year', $vehicle_available_columns)) {
                        $select_fields[] = "CONCAT(COALESCE(v.Make, ''), ' ', COALESCE(v.Model, ''), ' (', COALESCE(v.Year, ''), ')') as Vehicle_Info";
                    } else {
                        $select_fields[] = "CONCAT(COALESCE(v.Make, ''), ' ', COALESCE(v.Model, '')) as Vehicle_Info";
                    }
                } elseif (in_array('vehicle_name', $vehicle_available_columns)) {
                    $select_fields[] = "v.vehicle_name as Vehicle_Info";
                } else {
                    $select_fields[] = "'Unknown Vehicle' as Vehicle_Info";
                }
                
                $join_clauses[] = "LEFT JOIN $vehicle_table v ON r.{$found_fields['vehicle_id']} = v.$vehicle_id_field";
            }
        } else {
            $select_fields[] = "'Unknown Vehicle' as Vehicle_Info";
        }
        
        // Add driver information
        if ($driver_table && isset($found_fields['driver_id'])) {
            $driver_columns_query = "SHOW COLUMNS FROM $driver_table";
            $driver_columns_result = $conn->query($driver_columns_query);
            $driver_available_columns = [];
            
            while ($column = $driver_columns_result->fetch_assoc()) {
                $driver_available_columns[] = $column['Field'];
            }
            
            // Find driver ID field
            $driver_id_field = null;
            foreach (['Driver_ID', 'driver_id', 'id'] as $field) {
                if (in_array($field, $driver_available_columns)) {
                    $driver_id_field = $field;
                    break;
                }
            }
            
            if ($driver_id_field) {
                if (in_array('First_Name', $driver_available_columns) && in_array('Last_Name', $driver_available_columns)) {
                    $select_fields[] = "CONCAT(COALESCE(d.First_Name, ''), ' ', COALESCE(d.Last_Name, '')) as Driver_Name";
                } elseif (in_array('name', $driver_available_columns)) {
                    $select_fields[] = "d.name as Driver_Name";
                } else {
                    $select_fields[] = "'Unknown Driver' as Driver_Name";
                }
                
                $join_clauses[] = "LEFT JOIN $driver_table d ON r.{$found_fields['driver_id']} = d.$driver_id_field";
            }
        } else {
            $select_fields[] = "'Self-Drive' as Driver_Name";
        }
        
        // Build the complete query
        $query = "SELECT " . implode(', ', $select_fields) . " FROM $reservation_table r";
        
        // Add all JOIN clauses
        if (!empty($join_clauses)) {
            $query .= " " . implode(" ", $join_clauses);
        }
        
        // Add filters
        $where_conditions = [];
        $params = [];
        $param_types = "";
        
        if (!empty($_POST['start_date']) && isset($found_fields['start_date'])) {
            $where_conditions[] = "r.{$found_fields['start_date']} >= ?";
            $params[] = $_POST['start_date'];
            $param_types .= "s";
        }
        
        if (!empty($_POST['end_date']) && isset($found_fields['end_date'])) {
            $where_conditions[] = "r.{$found_fields['end_date']} <= ?";
            $params[] = $_POST['end_date'];
            $param_types .= "s";
        }
        
        if (!empty($_POST['status']) && isset($found_fields['status'])) {
            $where_conditions[] = "r.{$found_fields['status']} = ?";
            $params[] = $_POST['status'];
            $param_types .= "s";
        }
        
        if (!empty($_POST['user_search']) && $user_table && isset($found_fields['user_id'])) {
            $where_conditions[] = "(u.First_Name LIKE ? OR u.Last_Name LIKE ? OR u.Email LIKE ?)";
            $search_term = "%" . $_POST['user_search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= "sss";
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Order by created_at if it exists, otherwise by the first available field
        if (isset($found_fields['created_at'])) {
            $query .= " ORDER BY r.{$found_fields['created_at']} DESC LIMIT 100";
        } else {
            $query .= " ORDER BY 1 DESC LIMIT 100"; // Order by first column
        }
        
        // Execute query
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        $reservations = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Map the dynamic fields back to standard names
                $reservation = [
                    'Reservation_ID' => $row['reservation_id'] ?? 'N/A',
                    'User_Name' => $row['User_Name'] ?? 'Unknown User',
                    'User_Email' => $row['User_Email'] ?? '',
                    'Vehicle_Info' => $row['Vehicle_Info'] ?? 'Unknown Vehicle',
                    'Driver_Name' => $row['Driver_Name'] ?? 'Self-Drive',
                    'Start_Date' => $row['start_date'] ?? '',
                    'End_Date' => $row['end_date'] ?? '',
                    'Total_Cost' => $row['total_cost'] ?? 0,
                    'Status' => $row['status'] ?? 'Pending',
                    'Created_At' => $row['created_at'] ?? ''
                ];
                $reservations[] = $reservation;
            }
        }
        
        echo json_encode([
            'success' => true,
            'reservations' => $reservations,
            'total_count' => count($reservations)
        ]);
        
    } catch (Exception $e) {
        error_log("getAllReservations: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error loading reservations: ' . $e->getMessage()
        ]);
    }
}

// Get reservation statistics
function getReservationStats($conn) {
    try {
        // Check for reservation table
        $tables_to_check = ['reservations', 'user_reservations', 'vehicle_reservations', 'booking', 'bookings'];
        $reservation_table = null;
        
        foreach ($tables_to_check as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $reservation_table = $table;
                break;
            }
        }
        
        if (!$reservation_table) {
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_reservations' => 0,
                    'active_reservations' => 0,
                    'completed_reservations' => 0,
                    'total_revenue' => 0
                ]
            ]);
            return;
        }
        
        // Get columns
        $columns_query = "SHOW COLUMNS FROM $reservation_table";
        $columns_result = $conn->query($columns_query);
        $available_columns = [];
        
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        
        // Find status and cost columns
        $status_field = null;
        $cost_field = null;
        
        foreach (['Status', 'status', 'booking_status'] as $field) {
            if (in_array($field, $available_columns)) {
                $status_field = $field;
                break;
            }
        }
        
        foreach (['Total_Cost', 'total_cost', 'amount', 'price', 'cost'] as $field) {
            if (in_array($field, $available_columns)) {
                $cost_field = $field;
                break;
            }
        }
        
        $stats = [
            'total_reservations' => 0,
            'active_reservations' => 0,
            'completed_reservations' => 0,
            'total_revenue' => 0
        ];
        
        // Total reservations
        $total_query = "SELECT COUNT(*) as total FROM $reservation_table";
        $total_result = $conn->query($total_query);
        if ($total_result) {
            $stats['total_reservations'] = $total_result->fetch_assoc()['total'];
        }
        
        // Status-based counts
        if ($status_field) {
            // Active reservations (Confirmed, In-Progress, etc.)
            $active_query = "SELECT COUNT(*) as active FROM $reservation_table WHERE $status_field IN ('Confirmed', 'confirmed', 'In-Progress', 'in-progress', 'Active', 'active')";
            $active_result = $conn->query($active_query);
            if ($active_result) {
                $stats['active_reservations'] = $active_result->fetch_assoc()['active'];
            }
            
            // Completed reservations
            $completed_query = "SELECT COUNT(*) as completed FROM $reservation_table WHERE $status_field IN ('Completed', 'completed', 'Finished', 'finished')";
            $completed_result = $conn->query($completed_query);
            if ($completed_result) {
                $stats['completed_reservations'] = $completed_result->fetch_assoc()['completed'];
            }
        }
        
        // Total revenue
        if ($cost_field) {
            $revenue_query = "SELECT SUM($cost_field) as revenue FROM $reservation_table WHERE $status_field IN ('Completed', 'completed', 'Confirmed', 'confirmed')";
            $revenue_result = $conn->query($revenue_query);
            if ($revenue_result) {
                $revenue_row = $revenue_result->fetch_assoc();
                $stats['total_revenue'] = $revenue_row['revenue'] ?? 0;
            }
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        error_log("getReservationStats: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error loading statistics',
            'stats' => [
                'total_reservations' => 0,
                'active_reservations' => 0,
                'completed_reservations' => 0,
                'total_revenue' => 0
            ]
        ]);
    }
}

// Delete reservation
function deleteReservation($conn) {
    try {
        if (!isset($_POST['reservation_id']) || empty($_POST['reservation_id'])) {
            throw new Exception('Reservation ID is required');
        }
        
        $reservation_id = $_POST['reservation_id'];
        
        // Find reservation table
        $tables_to_check = ['reservations', 'user_reservations', 'vehicle_reservations', 'booking', 'bookings'];
        $reservation_table = null;
        
        foreach ($tables_to_check as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $reservation_table = $table;
                break;
            }
        }
        
        if (!$reservation_table) {
            throw new Exception('No reservation table found');
        }
        
        // Get columns to find ID field
        $columns_query = "SHOW COLUMNS FROM $reservation_table";
        $columns_result = $conn->query($columns_query);
        $available_columns = [];
        
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        
        $id_field = null;
        foreach (['Reservation_ID', 'reservation_id', 'booking_id', 'id'] as $field) {
            if (in_array($field, $available_columns)) {
                $id_field = $field;
                break;
            }
        }
        
        if (!$id_field) {
            throw new Exception('Cannot identify reservation ID field');
        }
        
        // Delete reservation
        $delete_query = "DELETE FROM $reservation_table WHERE $id_field = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservation deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Reservation not found'
                ]);
            }
        } else {
            throw new Exception('Failed to delete reservation');
        }
        
    } catch (Exception $e) {
        error_log("deleteReservation: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting reservation: ' . $e->getMessage()
        ]);
    }
}

// Export reservations
function exportReservations($conn) {
    try {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reservation_reports_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, [
            'Reservation ID',
            'User Name',
            'User Email', 
            'Vehicle Info',
            'Driver Name',
            'Start Date',
            'End Date',
            'Total Cost',
            'Status',
            'Created Date'
        ]);
        
        // Get reservations (using similar logic as getAllReservations but simplified)
        $tables_to_check = ['reservations', 'user_reservations', 'vehicle_reservations', 'booking', 'bookings'];
        $reservation_table = null;
        
        foreach ($tables_to_check as $table) {
            $check_query = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_query);
            if ($result && $result->num_rows > 0) {
                $reservation_table = $table;
                break;
            }
        }
        
        if ($reservation_table) {
            $query = "SELECT * FROM $reservation_table ORDER BY created_at DESC";
            $result = $conn->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['Reservation_ID'] ?? $row['reservation_id'] ?? $row['id'] ?? 'N/A',
                        'User Info', // Simplified for export
                        'user@example.com',
                        'Vehicle Info',
                        'Driver Info',
                        $row['Start_Date'] ?? $row['start_date'] ?? '',
                        $row['End_Date'] ?? $row['end_date'] ?? '',
                        $row['Total_Cost'] ?? $row['total_cost'] ?? 0,
                        $row['Status'] ?? $row['status'] ?? 'Pending',
                        $row['Created_At'] ?? $row['created_at'] ?? ''
                    ]);
                }
            }
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        error_log("exportReservations: Exception caught: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error exporting data: ' . $e->getMessage()
        ]);
    }
}
?>
