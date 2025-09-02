<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Vehicle Search</h1>";

// Test database connection
include 'connection/db.php';

if ($conn) {
    echo "✅ Database connection successful<br><br>";
} else {
    echo "❌ Database connection failed<br><br>";
    exit;
}

// Test table existence
$table_check = $conn->query("SHOW TABLES LIKE 'vehicle_registration'");
if ($table_check->num_rows > 0) {
    echo "✅ vehicle_registration table exists<br><br>";
} else {
    echo "❌ vehicle_registration table does not exist<br><br>";
    exit;
}

// Count total vehicles
$count_result = $conn->query("SELECT COUNT(*) as total FROM vehicle_registration");
$count_row = $count_result->fetch_assoc();
echo "📊 Total vehicles in database: " . $count_row['total'] . "<br><br>";

// Test search functionality
$search_term = "Toyota";
echo "🔍 Testing search for: '$search_term'<br>";

$sql = "SELECT * FROM vehicle_registration WHERE Make LIKE ? OR Model LIKE ? OR Vehicle_Type LIKE ? OR Color LIKE ?";
$search_param = "%" . $search_term . "%";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "📝 Search results: " . $result->num_rows . " vehicles found<br><br>";

    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Vehicle ID</th><th>Make</th><th>Model</th><th>Year</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Vehicle_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Make']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Model']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Year']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    $stmt->close();
} else {
    echo "❌ Failed to prepare search statement<br>";
}

// Test all vehicles display
echo "<h2>All Vehicles in Database:</h2>";
$all_result = $conn->query("SELECT Vehicle_ID, Make, Model, Year, Status FROM vehicle_registration ORDER BY Make, Model");
if ($all_result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Vehicle ID</th><th>Make</th><th>Model</th><th>Year</th><th>Status</th></tr>";
    while ($row = $all_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Vehicle_ID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Make']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Model']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test GET parameters
echo "<h2>Testing GET Parameters:</h2>";
echo "Current GET parameters: ";
print_r($_GET);

$conn->close();

echo "<br><br><a href='vehicle_search.php?search=Toyota'>Test Search Page</a> | ";
echo "<a href='index.html'>Back to Homepage</a>";
