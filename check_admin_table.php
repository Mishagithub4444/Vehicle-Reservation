<?php
include 'config.php';

echo "Admin Registration Table Structure:\n";
echo "==================================\n";

$result = $conn->query('DESCRIBE admin_registration');
if ($result) {
    echo "Columns in admin_registration table:\n";
    while($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nSample admin data:\n";
echo "==================\n";
$result = $conn->query('SELECT Admin_ID, Admin_UserName, Email FROM admin_registration LIMIT 1');
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Sample Admin ID: " . $row['Admin_ID'] . "\n";
    echo "Sample Username: " . $row['Admin_UserName'] . "\n";
    echo "Sample Email: " . $row['Email'] . "\n";
} else {
    echo "No admin records found.\n";
}

$conn->close();
?>
