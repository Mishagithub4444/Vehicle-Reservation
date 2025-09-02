<?php
include 'config.php';

echo "Driver Registration Table Structure:\n";
$result = $conn->query('DESCRIBE driver_registration');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nSample driver data:\n";
$result = $conn->query('SELECT * FROM driver_registration LIMIT 1');
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        echo "$key: $value\n";
    }
}
?>
