<?php
// Simplified user data retrieval for testing (REMOVE IN PRODUCTION)
header('Content-Type: application/json');

// Database connection
include_once 'connection/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'test';

try {
    if ($action === 'test') {
        // Simple test query
        $sql = "SELECT User_ID, First_Name, Last_Name, User_Name, Email, Phone_Number, User_Type FROM user_registration ORDER BY User_ID ASC LIMIT 10";
        $result = $conn->query($sql);

        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users),
            'message' => 'Direct database query successful',
            'sql_used' => $sql
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action for test API']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}

$conn->close();
