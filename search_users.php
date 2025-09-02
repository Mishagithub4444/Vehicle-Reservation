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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_type = $_POST['search_type'] ?? '';
    $search_value = $_POST['search_value'] ?? '';

    if (empty($search_type) || empty($search_value)) {
        echo json_encode(['success' => false, 'message' => 'Search type and value are required']);
        exit();
    }

    try {
        $sql = '';
        $param = '';

        switch ($search_type) {
            case 'user_id':
                $sql = "SELECT * FROM user_registration WHERE User_ID = ?";
                $param = intval($search_value);
                break;

            case 'email':
                $sql = "SELECT * FROM user_registration WHERE Email LIKE ?";
                $param = '%' . $search_value . '%';
                break;

            case 'name':
                $sql = "SELECT * FROM user_registration WHERE CONCAT(First_Name, ' ', Last_Name) LIKE ? OR First_Name LIKE ? OR Last_Name LIKE ?";
                $param = '%' . $search_value . '%';
                break;

            case 'phone':
                $sql = "SELECT * FROM user_registration WHERE Phone_Number LIKE ?";
                $param = '%' . $search_value . '%';
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid search type']);
                exit();
        }

        $stmt = $conn->prepare($sql);

        if ($search_type === 'name') {
            $stmt->bind_param('sss', $param, $param, $param);
        } else {
            if ($search_type === 'user_id') {
                $stmt->bind_param('i', $param);
            } else {
                $stmt->bind_param('s', $param);
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
