<?php
include 'connection/db.php';

echo "<h2>Vehicle Registration Table Structure</h2>";

// Check table structure
$structure_query = "DESCRIBE vehicle_registration";
$structure_result = $conn->query($structure_query);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($column = $structure_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$column['Field']}</strong></td>";
    echo "<td>{$column['Type']}</td>";
    echo "<td>{$column['Null']}</td>";
    echo "<td>{$column['Key']}</td>";
    echo "<td>{$column['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample data
echo "<h3>Sample Data:</h3>";
$sample_query = "SELECT * FROM vehicle_registration LIMIT 3";
$sample_result = $conn->query($sample_query);

if ($sample_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    $first_row = true;
    while ($row = $sample_result->fetch_assoc()) {
        if ($first_row) {
            echo "<tr>";
            foreach (array_keys($row) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            $first_row = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . ($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found in vehicle_registration table.</p>";
}

$conn->close();
?>
