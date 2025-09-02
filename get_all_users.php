<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
    exit();
}

// Database connection
include_once 'connection/db.php';

try {
    // Get all users from user_registration table
    $sql = "SELECT User_ID, First_Name, Last_Name, Email, Phone_Number, User_Type, Date_of_Birth, Gender, Address, User_Name 
            FROM user_registration 
            ORDER BY User_ID ASC";

    $result = $conn->query($sql);

    if ($result) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching users: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
