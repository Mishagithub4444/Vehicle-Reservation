<?php
// create_admin_table.php - Creates the admin_registration table

// Database connection
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

// SQL to create admin_registration table
$sql = "CREATE TABLE IF NOT EXISTS admin_registration (
    Admin_ID VARCHAR(10) PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Admin_UserName VARCHAR(50) NOT NULL UNIQUE,
    Date_of_Birth DATE,
    Phone_Number VARCHAR(20),
    Gender VARCHAR(10),
    Email VARCHAR(100) UNIQUE,
    Address VARCHAR(255),
    Admin_Role VARCHAR(50),
    Admin_Password VARCHAR(100) NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table admin_registration created successfully or already exists.<br>";

    // Check if table structure is correct
    $result = mysqli_query($conn, "DESCRIBE admin_registration");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);

echo "<br><br><a href='admin_register.html'>Go to Admin Registration</a>";
