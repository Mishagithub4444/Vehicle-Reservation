<?php
// Debug script to check reservations
session_start();
include 'connection/db.php';

echo "<h2>Debugging Reservations Issue</h2>";

// Check if user_reservations table exists
$check_table = "SHOW TABLES LIKE 'vehicle_reservations'";
$result = $conn->query($check_table);
echo "<h3>1. Table Existence Check:</h3>";
if ($result->num_rows > 0) {
    echo "✅ vehicle_reservations table EXISTS<br>";
} else {
    echo "❌ vehicle_reservations table DOES NOT EXIST<br>";
}

// Check table structure
echo "<h3>2. Table Structure:</h3>";
$structure = $conn->query("DESCRIBE vehicle_reservations");
if ($structure) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Could not describe table<br>";
}

// Check if there are any reservations in the table
echo "<h3>3. Current Reservations Count:</h3>";
$count_query = "SELECT COUNT(*) as count FROM vehicle_reservations";
$count_result = $conn->query($count_query);
if ($count_result) {
    $count = $count_result->fetch_assoc();
    echo "Total reservations in database: " . $count['count'] . "<br>";
} else {
    echo "❌ Could not count reservations: " . $conn->error . "<br>";
}

// Show all reservations
echo "<h3>4. All Reservations:</h3>";
$all_reservations = $conn->query("SELECT * FROM vehicle_reservations ORDER BY Reservation_Date DESC");
if ($all_reservations) {
    if ($all_reservations->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Vehicle_ID</th><th>User_ID</th><th>User_Name</th><th>Start_Date</th><th>End_Date</th><th>Status</th><th>Created_At</th></tr>";
        while ($row = $all_reservations->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Reservation_ID'] . "</td>";
            echo "<td>" . $row['Vehicle_ID'] . "</td>";
            echo "<td>" . $row['User_ID'] . "</td>";
            echo "<td>" . $row['User_Name'] . "</td>";
            echo "<td>" . $row['Start_Date'] . "</td>";
            echo "<td>" . $row['End_Date'] . "</td>";
            echo "<td>" . $row['Status'] . "</td>";
            echo "<td>" . $row['Created_At'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No reservations found in database.<br>";
    }
} else {
    echo "❌ Error querying reservations: " . $conn->error . "<br>";
}

// Check session information
echo "<h3>5. Session Information:</h3>";
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    echo "✅ User is logged in<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "First Name: " . ($_SESSION['first_name'] ?? 'Not set') . "<br>";
    echo "Last Name: " . ($_SESSION['last_name'] ?? 'Not set') . "<br>";
} else {
    echo "❌ User is not logged in<br>";
}

// Test the exact query used in user_reservations.php
if (isset($_SESSION['user_id'])) {
    echo "<h3>6. Testing user_reservations.php Query:</h3>";
    $user_id = $_SESSION['user_id'];
    $test_query = "SELECT r.*, v.Make, v.Model, v.Year, v.Vehicle_Type, v.Color 
                   FROM vehicle_reservations r 
                   JOIN vehicle_registration v ON r.Vehicle_ID = v.Vehicle_ID 
                   WHERE r.User_ID = ? 
                   ORDER BY r.Reservation_Date DESC";
    $test_stmt = $conn->prepare($test_query);
    $test_stmt->bind_param("i", $user_id);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    
    echo "Number of reservations for user ID " . $user_id . ": " . $test_result->num_rows . "<br>";
    
    if ($test_result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Reservation_ID</th><th>Vehicle_ID</th><th>Make</th><th>Model</th><th>Start_Date</th><th>End_Date</th><th>Status</th></tr>";
        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Reservation_ID'] . "</td>";
            echo "<td>" . $row['Vehicle_ID'] . "</td>";
            echo "<td>" . $row['Make'] . "</td>";
            echo "<td>" . $row['Model'] . "</td>";
            echo "<td>" . $row['Start_Date'] . "</td>";
            echo "<td>" . $row['End_Date'] . "</td>";
            echo "<td>" . $row['Status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    $test_stmt->close();
}

echo "<br><a href='user_portal.php'>Back to User Portal</a> | <a href='user_reservations.php'>Back to Reservations</a>";
?>
