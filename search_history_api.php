<?php

/**
 * Search History API
 * Handles recording and retrieving search information
 */

session_start();
include 'connection/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get request method and action
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'record_search':
        recordSearch();
        break;
    case 'get_user_history':
        getUserSearchHistory();
        break;
    case 'get_popular_searches':
        getPopularSearches();
        break;
    case 'get_recent_searches':
        getRecentSearches();
        break;
    case 'get_search_stats':
        getSearchStats();
        break;
    case 'delete_search_history':
        deleteSearchHistory();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function recordSearch()
{
    global $conn;

    try {
        // Get search parameters
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $search_term = trim($_POST['search_term'] ?? '');
        $vehicle_type = trim($_POST['vehicle_type'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $results_count = intval($_POST['results_count'] ?? 0);
        $search_type = trim($_POST['search_type'] ?? 'vehicle'); // vehicle, driver, user, etc.
        $filters = json_encode($_POST['filters'] ?? []); // Store filters as JSON

        // Get IP address and user agent
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (empty($search_term)) {
            echo json_encode(['success' => false, 'message' => 'Search term is required']);
            return;
        }

        // Insert search record with enhanced fields
        $sql = "INSERT INTO search_history (user_id, search_term, vehicle_type, status, results_count, search_type, filters, ip_address, user_agent, search_timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssisss", $user_id, $search_term, $vehicle_type, $status, $results_count, $search_type, $filters, $ip_address, $user_agent);

        if ($stmt->execute()) {
            $search_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Search recorded successfully',
                'search_id' => $search_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to record search']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getUserSearchHistory()
{
    global $conn;

    try {
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            return;
        }

        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);

        $sql = "SELECT id, search_term, vehicle_type, status, results_count, search_type, filters, search_timestamp 
                FROM search_history 
                WHERE user_id = ? 
                ORDER BY search_timestamp DESC 
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $searches = [];
        while ($row = $result->fetch_assoc()) {
            $row['filters'] = json_decode($row['filters'], true);
            $searches[] = $row;
        }

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM search_history WHERE user_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];

        echo json_encode([
            'success' => true,
            'searches' => $searches,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getPopularSearches()
{
    global $conn;

    try {
        $limit = intval($_GET['limit'] ?? 10);
        $days = intval($_GET['days'] ?? 30); // Last 30 days by default

        $sql = "SELECT search_term, COUNT(*) as search_count, AVG(results_count) as avg_results,
                       MAX(search_timestamp) as last_searched
                FROM search_history 
                WHERE search_timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY search_term 
                HAVING search_count > 1
                ORDER BY search_count DESC, last_searched DESC 
                LIMIT ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $days, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $popular_searches = [];
        while ($row = $result->fetch_assoc()) {
            $popular_searches[] = [
                'search_term' => $row['search_term'],
                'search_count' => intval($row['search_count']),
                'avg_results' => round($row['avg_results'], 1),
                'last_searched' => $row['last_searched']
            ];
        }

        echo json_encode([
            'success' => true,
            'popular_searches' => $popular_searches,
            'period_days' => $days
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getRecentSearches()
{
    global $conn;

    try {
        $limit = intval($_GET['limit'] ?? 10);
        $search_type = $_GET['search_type'] ?? '';

        $sql = "SELECT sh.search_term, sh.vehicle_type, sh.results_count, sh.search_timestamp, sh.search_type,
                       ur.User_Name, CONCAT(ur.First_Name, ' ', ur.Last_Name) as full_name
                FROM search_history sh 
                LEFT JOIN user_registration ur ON sh.user_id = ur.User_ID";

        if ($search_type) {
            $sql .= " WHERE sh.search_type = ?";
        }

        $sql .= " ORDER BY sh.search_timestamp DESC LIMIT ?";

        $stmt = $conn->prepare($sql);
        if ($search_type) {
            $stmt->bind_param("si", $search_type, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $recent_searches = [];
        while ($row = $result->fetch_assoc()) {
            $recent_searches[] = $row;
        }

        echo json_encode([
            'success' => true,
            'recent_searches' => $recent_searches
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getSearchStats()
{
    global $conn;

    try {
        $user_id = $_SESSION['user_id'] ?? null;

        // Overall stats
        $stats = [];

        // Total searches
        $total_sql = "SELECT COUNT(*) as total FROM search_history" . ($user_id ? " WHERE user_id = ?" : "");
        $total_stmt = $conn->prepare($total_sql);
        if ($user_id) {
            $total_stmt->bind_param("i", $user_id);
        }
        $total_stmt->execute();
        $stats['total_searches'] = $total_stmt->get_result()->fetch_assoc()['total'];

        // Searches today
        $today_sql = "SELECT COUNT(*) as today FROM search_history WHERE DATE(search_timestamp) = CURDATE()" . ($user_id ? " AND user_id = ?" : "");
        $today_stmt = $conn->prepare($today_sql);
        if ($user_id) {
            $today_stmt->bind_param("i", $user_id);
        }
        $today_stmt->execute();
        $stats['searches_today'] = $today_stmt->get_result()->fetch_assoc()['today'];

        // Searches this week
        $week_sql = "SELECT COUNT(*) as week FROM search_history WHERE search_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)" . ($user_id ? " AND user_id = ?" : "");
        $week_stmt = $conn->prepare($week_sql);
        if ($user_id) {
            $week_stmt->bind_param("i", $user_id);
        }
        $week_stmt->execute();
        $stats['searches_this_week'] = $week_stmt->get_result()->fetch_assoc()['week'];

        // Average results per search
        $avg_sql = "SELECT AVG(results_count) as avg_results FROM search_history WHERE results_count > 0" . ($user_id ? " AND user_id = ?" : "");
        $avg_stmt = $conn->prepare($avg_sql);
        if ($user_id) {
            $avg_stmt->bind_param("i", $user_id);
        }
        $avg_stmt->execute();
        $avg_result = $avg_stmt->get_result()->fetch_assoc();
        $stats['avg_results_per_search'] = round($avg_result['avg_results'] ?? 0, 1);

        // Most searched vehicle type
        $vehicle_type_sql = "SELECT vehicle_type, COUNT(*) as count FROM search_history WHERE vehicle_type IS NOT NULL AND vehicle_type != ''" . ($user_id ? " AND user_id = ?" : "") . " GROUP BY vehicle_type ORDER BY count DESC LIMIT 1";
        $vehicle_type_stmt = $conn->prepare($vehicle_type_sql);
        if ($user_id) {
            $vehicle_type_stmt->bind_param("i", $user_id);
        }
        $vehicle_type_stmt->execute();
        $vehicle_result = $vehicle_type_stmt->get_result()->fetch_assoc();
        $stats['most_searched_vehicle_type'] = $vehicle_result ? $vehicle_result['vehicle_type'] : 'None';

        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'user_specific' => $user_id ? true : false
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteSearchHistory()
{
    global $conn;

    try {
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            return;
        }

        $search_id = intval($_POST['search_id'] ?? 0);

        if ($search_id > 0) {
            // Delete specific search
            $sql = "DELETE FROM search_history WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $search_id, $user_id);
        } else {
            // Delete all user's search history
            $sql = "DELETE FROM search_history WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        }

        if ($stmt->execute()) {
            $deleted_count = $stmt->affected_rows;
            echo json_encode([
                'success' => true,
                'message' => 'Search history deleted successfully',
                'deleted_count' => $deleted_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete search history']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
