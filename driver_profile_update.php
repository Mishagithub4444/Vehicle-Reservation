<?php
session_start();

// Check if driver is logged in
if (!isset($_SESSION['driver_logged_in']) || $_SESSION['driver_logged_in'] !== true) {
    header('Location: driver_login.html');
    exit();
}

include 'connection/db.php';

// Get driver's current data
$driver_id = $_SESSION['driver_id'];
$query = "SELECT * FROM driver_registration WHERE Driver_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $driver_id); // Use 's' for string since Driver_ID is VARCHAR(6)
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

$update_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $driver_username = trim($_POST['driver_username']);
    $license_number = trim($_POST['license_number']);
    $dob = $_POST['dob'];
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];
    $availability = $_POST['availability'];
    $hourly_rate = floatval($_POST['hourly_rate']);

    // Debug: Check if license_number and gender are being received
    error_log("License Number: " . $license_number);
    error_log("Gender: " . $gender);
    error_log("Driver ID: " . $driver_id);
    error_log("Daily Rate: " . $hourly_rate);

    // Update password only if provided
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $update_query = "UPDATE driver_registration SET First_Name=?, Last_Name=?, Driver_UserName=?, License_Number=?, Date_of_Birth=?, Phone_Number=?, Gender=?, Email=?, Address=?, Driver_Status=?, Availability=?, Daily_Rate=?, Driver_Password=? WHERE Driver_ID=?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssssssssdss", $first_name, $last_name, $driver_username, $license_number, $dob, $phone, $gender, $email, $address, $status, $availability, $hourly_rate, $password, $driver_id);
    } else {
        $update_query = "UPDATE driver_registration SET First_Name=?, Last_Name=?, Driver_UserName=?, License_Number=?, Date_of_Birth=?, Phone_Number=?, Gender=?, Email=?, Address=?, Driver_Status=?, Availability=?, Daily_Rate=? WHERE Driver_ID=?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssssssssds", $first_name, $last_name, $driver_username, $license_number, $dob, $phone, $gender, $email, $address, $status, $availability, $hourly_rate, $driver_id);
    }

    if ($update_stmt->execute()) {
        $update_message = "Profile updated successfully!";
        // Refresh driver data
        $refresh_stmt = $conn->prepare("SELECT * FROM driver_registration WHERE Driver_ID = ?");
        $refresh_stmt->bind_param("s", $driver_id); // Use 's' for string
        $refresh_stmt->execute();
        $refresh_result = $refresh_stmt->get_result();
        $driver = $refresh_result->fetch_assoc();

        // Update session variables with new data
        $_SESSION['first_name'] = $driver['First_Name'];
        $_SESSION['last_name'] = $driver['Last_Name'];
        $_SESSION['driver_username'] = $driver['Driver_UserName'];
        $_SESSION['license_number'] = $driver['License_Number'];
        $_SESSION['dob'] = $driver['Date_of_Birth'];
        $_SESSION['phone'] = $driver['Phone_Number'];
        $_SESSION['gender'] = $driver['Gender'];
        $_SESSION['email'] = $driver['Email'];
        $_SESSION['address'] = $driver['Address'];
        $_SESSION['driver_status'] = $driver['Driver_Status'];
        $_SESSION['availability'] = $driver['Availability'];
        $_SESSION['daily_rate'] = $driver['Daily_Rate'];

        $refresh_stmt->close();
    } else {
        $update_message = "Error updating profile: " . $conn->error;
    }
    $update_stmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Driver Profile - Vehicle Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #1abc9c 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        header {
            background: rgba(45, 62, 80, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #1abc9c, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        nav a:hover {
            background: rgba(26, 188, 156, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 188, 156, 0.3);
        }

        .update-section {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
            padding: 40px 20px;
            position: relative;
            z-index: 5;
        }

        .update-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 1s ease-out;
        }

        .update-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .update-header h1 {
            color: #2d3e50;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .update-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .update-form {
            display: grid;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 400;
            background: #f8fafc;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .update-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(149, 165, 166, 0.25);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(149, 165, 166, 0.4);
        }

        .message {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.25);
        }

        footer {
            background: rgba(45, 62, 80, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 25px;
            position: relative;
            z-index: 10;
            font-weight: 400;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .update-container {
                margin: 20px;
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .update-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="driver_portal.php">My Portal</a></li>
                <li><a href="driver_logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="update-section">
        <div class="update-container">
            <div class="update-header">
                <h1>Update Driver Profile</h1>
                <p>Keep your information up to date</p>
            </div>

            <?php if ($update_message): ?>
                <div class="message">
                    <?php echo htmlspecialchars($update_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="update-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($driver['First_Name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($driver['Last_Name']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="driver_username">Driver Username:</label>
                        <input type="text" name="driver_username" id="driver_username" value="<?php echo htmlspecialchars($driver['Driver_UserName']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="license_number">License Number:</label>
                        <input type="text" name="license_number" id="license_number" value="<?php echo htmlspecialchars($driver['License_Number']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth:</label>
                        <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($driver['Date_of_Birth']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($driver['Phone_Number']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender" required>
                            <option value="Male" <?php echo ($driver['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($driver['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($driver['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($driver['Email']); ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" required><?php echo htmlspecialchars($driver['Address']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Driver Status:</label>
                        <select name="status" id="status" required>
                            <option value="Active" <?php echo ($driver['Driver_Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($driver['Driver_Status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Suspended" <?php echo ($driver['Driver_Status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="availability">Availability:</label>
                        <select name="availability" id="availability" required>
                            <option value="Available" <?php echo ($driver['Availability'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Busy" <?php echo ($driver['Availability'] == 'Busy') ? 'selected' : ''; ?>>Busy</option>
                            <option value="Off Duty" <?php echo ($driver['Availability'] == 'Off Duty') ? 'selected' : ''; ?>>Off Duty</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="hourly_rate">Daily Rate (BDT):</label>
                    <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" min="0" value="<?php echo htmlspecialchars($driver['Daily_Rate'] ?? '0.00'); ?>" required>
                    <small style="color: #666; display: block; margin-top: 5px;">Enter your daily driving rate in Bangladeshi Taka</small>
                </div>

                <div class="form-group full-width">
                    <label for="password">New Password (leave blank to keep current):</label>
                    <input type="password" name="password" id="password" placeholder="Enter new password or leave blank">
                </div>

                <div class="update-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <a href="driver_portal.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Portal
                    </a>
                </div>
            </form>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>
</body>

</html>