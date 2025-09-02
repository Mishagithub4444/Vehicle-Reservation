<?php
session_start();

// Check if admin is logged in (you can modify this based on your admin system)
// For now, allowing access to see search history

include 'connection/db.php';

// Create search_history table if it doesn't exist
$create_history_table = "CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    search_term VARCHAR(255) NOT NULL,
    vehicle_type VARCHAR(50) DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    results_count INT DEFAULT 0,
    search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_search_term (search_term),
    INDEX idx_timestamp (search_timestamp)
)";
$conn->query($create_history_table);

// Get search history with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM search_history";
$count_result = $conn->query($count_sql);
$total_searches = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_searches / $per_page);

// Get search history with user information
$history_sql = "SELECT sh.*, ur.User_Name, ur.First_Name, ur.Last_Name 
                FROM search_history sh 
                LEFT JOIN user_registration ur ON sh.user_id = ur.User_ID 
                ORDER BY sh.search_timestamp DESC 
                LIMIT ? OFFSET ?";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("ii", $per_page, $offset);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Get popular search terms
$popular_sql = "SELECT search_term, COUNT(*) as search_count, AVG(results_count) as avg_results 
                FROM search_history 
                WHERE search_term != 'All Vehicles' 
                GROUP BY search_term 
                ORDER BY search_count DESC 
                LIMIT 10";
$popular_result = $conn->query($popular_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search History - VRMS Analytics</title>
    <link rel="stylesheet" href="user_portal.css">
    <style>
        .analytics-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .history-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; border-radius: 10px; overflow: hidden; }
        .history-table th, .history-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .history-table th { background-color: #007bff; color: white; }
        .pagination { text-align: center; margin: 20px 0; }
        .pagination a { display: inline-block; padding: 8px 12px; margin: 0 5px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .pagination .current { background: #28a745; }
        .popular-searches { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 20px 0; }
    </style>
</head>
<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="vehicle.php">Vehicle</a></li>
                <li><a href="admin_portal.php">Admin Portal</a></li>
                <li><a href="search_history.php" class="active">Search Analytics</a></li>
            </ul>
        </nav>
    </header>

    <section class="portal-section">
        <div class="analytics-container">
            <h1>Search History & Analytics</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_searches; ?></div>
                    <div>Total Searches</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $today_sql = "SELECT COUNT(*) as today_count FROM search_history WHERE DATE(search_timestamp) = CURDATE()";
                        $today_result = $conn->query($today_sql);
                        echo $today_result->fetch_assoc()['today_count'];
                        ?>
                    </div>
                    <div>Today's Searches</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $unique_users_sql = "SELECT COUNT(DISTINCT user_id) as unique_users FROM search_history WHERE user_id IS NOT NULL";
                        $unique_users_result = $conn->query($unique_users_sql);
                        echo $unique_users_result->fetch_assoc()['unique_users'];
                        ?>
                    </div>
                    <div>Unique Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $avg_results_sql = "SELECT AVG(results_count) as avg_results FROM search_history WHERE results_count >= 0";
                        $avg_results_result = $conn->query($avg_results_sql);
                        echo round($avg_results_result->fetch_assoc()['avg_results'], 1);
                        ?>
                    </div>
                    <div>Avg Results</div>
                </div>
            </div>

            <div class="popular-searches">
                <h2>Popular Search Terms</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; text-align: left;">Search Term</th>
                            <th style="padding: 10px; text-align: center;">Times Searched</th>
                            <th style="padding: 10px; text-align: center;">Avg Results</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($popular = $popular_result->fetch_assoc()): ?>
                        <tr>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($popular['search_term']); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo $popular['search_count']; ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo round($popular['avg_results'], 1); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <h2>Recent Search History</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Search Term</th>
                        <th>Vehicle Type</th>
                        <th>Status</th>
                        <th>Results</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($search = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($search['search_timestamp'])); ?></td>
                        <td>
                            <?php 
                            if ($search['User_Name']) {
                                echo htmlspecialchars($search['First_Name'] . ' ' . $search['Last_Name'] . ' (' . $search['User_Name'] . ')');
                            } else {
                                echo 'Guest User';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($search['search_term']); ?></td>
                        <td><?php echo htmlspecialchars($search['vehicle_type'] ?: 'All'); ?></td>
                        <td><?php echo htmlspecialchars($search['status'] ?: 'All'); ?></td>
                        <td><?php echo $search['results_count'] >= 0 ? $search['results_count'] : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($search['ip_address']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'current' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>
</body>
</html>
