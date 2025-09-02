<?php
session_start();
include 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id_input = trim($_POST['admin_id']);
    $admin_username = trim($_POST['admin_name']);
    $admin_password = $_POST['password'];

    // Validate input
    if (empty($admin_id_input) || empty($admin_username) || empty($admin_password)) {
        echo '<script>alert("Please fill in all fields."); window.history.back();</script>';
        exit();
    }

    // Keep admin_id as string since it's stored as VARCHAR in database
    $admin_id = trim($admin_id_input);

    // Check admin credentials (using password verification for hashed passwords)
    $sql = "SELECT * FROM admin_registration WHERE Admin_ID = ? AND Admin_UserName = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('ss', $admin_id, $admin_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin_data = $result->fetch_assoc();

            // Verify the password using password_verify()
            if (password_verify($admin_password, $admin_data['Admin_Password'])) {
                // Password verified - set up admin session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin_data['Admin_ID'];
                $_SESSION['admin_name'] = $admin_data['Admin_UserName'];
                $_SESSION['first_name'] = $admin_data['First_Name'];
                $_SESSION['last_name'] = $admin_data['Last_Name'];
                $_SESSION['email'] = $admin_data['Email'];
                $_SESSION['phone'] = $admin_data['Phone_Number'];
                $_SESSION['dob'] = $admin_data['Date_of_Birth'];
                $_SESSION['gender'] = $admin_data['Gender'];
                $_SESSION['address'] = $admin_data['Address'];
                $_SESSION['role'] = $admin_data['Admin_Role'];
                $_SESSION['admin_level'] = 'Standard'; // Default value
                $_SESSION['department'] = 'General'; // Default value

                echo '<script>alert("Login successful! Welcome Admin ' . htmlspecialchars($admin_data['First_Name']) . '!"); window.location.href = "admin_portal.php";</script>';
            } else {
                // Password doesn't match
                echo '<script>alert("Invalid admin ID, username, or password. Please try again."); window.history.back();</script>';
            }
        } else {
            // No admin found with the provided ID and username
            echo '<script>alert("Invalid admin ID or username. Please try again."); window.history.back();</script>';
        }

        $stmt->close();
    } else {
        echo '<script>alert("Database error: ' . htmlspecialchars($conn->error) . '"); window.history.back();</script>';
    }

    $conn->close();
} else {
    // Redirect to login page if accessed directly
    header('Location: admin_login.php');
    exit();
}
?>

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$admin_id_input = trim($_POST['admin_id']);
$admin_username = trim($_POST['admin_name']);
$admin_password = $_POST['password'];

// Validate input
if (empty($admin_id_input) || empty($admin_username) || empty($admin_password)) {
echo '<script>
    alert("Please fill in all fields.");
    window.history.back();
</script>';
exit();
}

// Keep admin_id as string since it's stored as VARCHAR in database
$admin_id = trim($admin_id_input);

// Use the existing database connection from db.php
// The $conn variable is already available from the included file

// Check admin credentials (using password verification for hashed passwords)
$sql = "SELECT * FROM admin_registration WHERE Admin_ID = ? AND Admin_UserName = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param('ss', $admin_id, $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
$admin_data = $result->fetch_assoc();

// Verify the password using password_verify()
if (password_verify($admin_password, $admin_data['Admin_Password'])) {
// Password verified - now proceed with 2FA

// Store temporary admin data in session for 2FA process
$_SESSION['temp_admin_data'] = [
'admin_id' => $admin_data['Admin_ID'],
'admin_name' => $admin_data['Admin_UserName'],
'first_name' => $admin_data['First_Name'],
'last_name' => $admin_data['Last_Name'],
'email' => $admin_data['Email'],
'phone' => $admin_data['Phone_Number'],
'dob' => $admin_data['Date_of_Birth'],
'gender' => $admin_data['Gender'],
'address' => $admin_data['Address'],
'role' => $admin_data['Admin_Role'],
'admin_level' => 'Standard',
'department' => 'General'
];

// Initialize 2FA
$twoFactor = new TwoFactorAuth($conn);
$verification_id = $twoFactor->createVerification($admin_id, 'admin');

if ($verification_id) {
$_SESSION['verification_id'] = $verification_id;
$_SESSION['user_type'] = 'admin';
$_SESSION['user_id'] = $admin_id;

// Redirect to 2FA verification page
header('Location: two_factor_verification.php');
exit();
} else {
echo '<script>
    alert("Error setting up verification. Please try again.");
    window.history.back();
</script>';
}
} else {
// Password doesn't match
echo '<script>
    alert("Invalid admin ID, username, or password. Please try again.");
    window.history.back();
</script>';
}
} else {
// No admin found with the provided ID and username
echo '<script>
    alert("Invalid admin ID or username. Please try again.");
    window.history.back();
</script>';
}

$stmt->close();
} else {
echo '<script>
    alert("Database error: ' . $conn->error . '");
    window.history.back();
</script>';
}
$_SESSION['department'] = 'General'; // Default since this field doesn't exist in table

echo '<script>
    alert("Login successful! Welcome Admin ' . $admin_data['First_Name'] . '!");
    window.location.href = "admin_portal.php";
</script>';
} else {
// Password doesn't match
echo '<script>
    alert("Invalid admin ID, username, or password. Please try again.");
    window.history.back();
</script>';
}
} else {
// No admin found with the provided ID and username
echo '<script>
    alert("Invalid admin ID or username. Please try again.");
    window.history.back();
</script>';
}

$stmt->close();
} else {
echo '<script>
    alert("Database error: ' . $conn->error . '");
    window.history.back();
</script>';
}

$conn->close();
} else {
// Redirect to login page if accessed directly
header('Location: admin_login.php');
exit();
}