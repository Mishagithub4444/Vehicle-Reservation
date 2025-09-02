<?php
// Check user_registration table structure
session_start();
header('Content-Type: application/json');

// Database connection
include_once 'connection/db.php';

try {
    // Check if user_registration table exists
    $table_check = "SHOW TABLES LIKE 'user_registration'";
    $table_result = $conn->query($table_check);

    if ($table_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'user_registration table does not exist',
            'suggestion' => 'Please create the user_registration table first'
        ]);
        exit();
    }

    // Get table structure
    $structure_query = "DESCRIBE user_registration";
    $structure_result = $conn->query($structure_query);

    $columns = [];
    while ($row = $structure_result->fetch_assoc()) {
        $columns[] = $row;
    }

    // Get sample data
    $sample_query = "SELECT * FROM user_registration LIMIT 3";
    $sample_result = $conn->query($sample_query);

    $sample_data = [];
    while ($row = $sample_result->fetch_assoc()) {
        $sample_data[] = $row;
    }

    // Count total users
    $count_query = "SELECT COUNT(*) as total FROM user_registration";
    $count_result = $conn->query($count_query);
    $total_users = $count_result->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'total_users' => $total_users,
        'columns' => $columns,
        'sample_data' => $sample_data,
        'admin_session' => [
            'admin_logged_in' => $_SESSION['admin_logged_in'] ?? false,
            'first_name' => $_SESSION['first_name'] ?? 'N/A',
            'role' => $_SESSION['role'] ?? 'N/A'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
