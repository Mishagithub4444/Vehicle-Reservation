<?php
// create_search_history_table.php
// Run this file once to create the search_history table

include 'connection/db.php';

// Create search_history table
$create_table_sql = "CREATE TABLE IF NOT EXISTS search_history (
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

if ($conn->query($create_table_sql) === TRUE) {
    echo "Search history table created successfully!<br>";
    echo "Table structure:<br>";
    echo "- id: Auto-increment primary key<br>";
    echo "- user_id: User ID (NULL for guest searches)<br>";
    echo "- search_term: What the user searched for<br>";
    echo "- vehicle_type: Selected vehicle type filter<br>";
    echo "- status: Selected status filter<br>";
    echo "- results_count: Number of results found<br>";
    echo "- search_timestamp: When the search was performed<br>";
    echo "- ip_address: User's IP address<br>";
    echo "- user_agent: Browser/device information<br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
