<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

// Database connection
include_once 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$searchType = $_POST['search_type'] ?? '';
$searchQuery = trim($_POST['search_query'] ?? '');

if (empty($searchQuery)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit();
}

try {
    // Base query
    $sql = "SELECT User_ID, First_Name, Last_Name, User_Name, Email, Phone_Number, User_Type, Date_of_Birth, Gender, Address";

    // Check if Created_At column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM user_registration LIKE 'Created_At'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $sql .= ", Created_At";
    }

    $sql .= " FROM user_registration";

    // Build WHERE clause based on search type
    $whereClause = "";
    $params = [];
    $types = "";

    switch ($searchType) {
        case 'user_id':
            $whereClause = " WHERE User_ID = ?";
            $params[] = intval($searchQuery);
            $types .= "i";
            break;

        case 'name':
            $whereClause = " WHERE (First_Name LIKE ? OR Last_Name LIKE ? OR CONCAT(First_Name, ' ', Last_Name) LIKE ?)";
            $searchPattern = '%' . $searchQuery . '%';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= "sss";
            break;

        case 'email':
            $whereClause = " WHERE Email LIKE ?";
            $params[] = '%' . $searchQuery . '%';
            $types .= "s";
            break;

        case 'username':
            $whereClause = " WHERE User_Name LIKE ?";
            $params[] = '%' . $searchQuery . '%';
            $types .= "s";
            break;

        case 'phone':
            $whereClause = " WHERE Phone_Number LIKE ?";
            $params[] = '%' . $searchQuery . '%';
            $types .= "s";
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid search type']);
            exit();
    }

    $sql .= $whereClause . " ORDER BY User_ID ASC LIMIT 20";

    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!$stmt->bind_param($types, ...$params)) {
        throw new Exception("Bind failed: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // Fetch results
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    // Return results
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users),
        'search_params' => [
            'type' => $searchType,
            'query' => $searchQuery
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Search error: ' . $e->getMessage(),
        'users' => [],
        'count' => 0
    ]);
}

$conn->close();
