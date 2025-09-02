<?php
// Check what registration tables exist
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vrms";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Show all tables
$result = mysqli_query($conn, "SHOW TABLES");
echo "Available tables:\n";
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "\n";
}

mysqli_close($conn);
?>
