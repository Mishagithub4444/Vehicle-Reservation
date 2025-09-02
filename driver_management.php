<?php
session_start();

// Check if admin is accessing or regular driver is logged in
$is_admin_access = isset($_GET['admin_access']) && $_GET['admin_access'] === 'true' &&
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_driver_access = isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true;

// If neither admin nor driver access, redirect to driver login
if (!$is_admin_access && !$is_driver_access) {
    header('Location: driver_login.php');
    exit();
}

// Database connection
include 'connection/db.php';

// If admin is accessing, load first driver or specific driver for viewing
if ($is_admin_access && !$is_driver_access) {
    // Admin accessing driver management - get first driver or specific driver
    $selected_driver_id = isset($_GET['view_driver_id']) ? $_GET['view_driver_id'] : null;

    if ($selected_driver_id) {
        $query = "SELECT * FROM driver_registration WHERE Driver_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $selected_driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $query = "SELECT * FROM driver_registration ORDER BY Driver_ID ASC LIMIT 1";
        $result = mysqli_query($conn, $query);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $driver_data = mysqli_fetch_assoc($result);
        // Set variables for display purposes (without interfering with admin session)
        $driver_info = [
            'driver_id' => $driver_data['Driver_ID'] ?? 'N/A',
            'first_name' => $driver_data['First_Name'] ?? 'N/A',
            'last_name' => $driver_data['Last_Name'] ?? 'N/A',
            'email' => $driver_data['Email'] ?? 'N/A',
            'phone' => $driver_data['Phone_Number'] ?? 'N/A',
            'license' => $driver_data['License_Number'] ?? 'N/A',
            'status' => $driver_data['Status'] ?? 'Unknown',
            'experience' => $driver_data['Years_of_Experience'] ?? '0',
            'rating' => $driver_data['Rating'] ?? '0.0',
            'address' => $driver_data['Address'] ?? 'N/A',
            'created_at' => $driver_data['Created_At'] ?? 'Unknown'
        ];
    } else {
        // No drivers found
        $driver_info = [
            'driver_id' => 'N/A',
            'first_name' => 'No Drivers',
            'last_name' => 'Found',
            'email' => 'N/A',
            'phone' => 'N/A',
            'license' => 'N/A',
            'status' => 'N/A',
            'experience' => 'N/A',
            'rating' => 'N/A',
            'address' => 'N/A',
            'created_at' => 'N/A'
        ];
    }
} else {
    // Regular driver access - fetch fresh driver data from database
    $driver_id = $_SESSION['driver_id'];
    $query = "SELECT * FROM driver_registration WHERE Driver_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $driver_data = $result->fetch_assoc();

    // Set driver info from database
    if ($driver_data) {
        $driver_info = [
            'driver_id' => $driver_data['Driver_ID'] ?? 'N/A',
            'first_name' => $driver_data['First_Name'] ?? 'N/A',
            'last_name' => $driver_data['Last_Name'] ?? 'N/A',
            'email' => $driver_data['Email'] ?? 'N/A',
            'phone' => $driver_data['Phone_Number'] ?? 'N/A',
            'license' => $driver_data['License_Number'] ?? 'N/A',
            'status' => $driver_data['Status'] ?? 'Unknown',
            'experience' => $driver_data['Years_of_Experience'] ?? '0',
            'rating' => $driver_data['Rating'] ?? '0.0',
            'address' => $driver_data['Address'] ?? 'N/A',
            'created_at' => $driver_data['Created_At'] ?? 'Unknown'
        ];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin_access ? 'Driver Management - Admin Portal' : 'My Profile - Driver Portal'; ?> - Vehicle Reservation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: #8b0000;
            color: white;
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
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
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        nav a:hover,
        nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .portal-section {
            padding: 20px;
        }

        .portal-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .header-controls {
            position: absolute;
            top: -10px;
            left: 0;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-back:hover {
            background: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }

        .welcome-header h1 {
            color: <?php echo $is_admin_access ? '#8b0000' : '#004080'; ?>;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .admin-notice {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
        }

        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .driver-search-panel,
        .driver-details-panel,
        .driver-actions-panel,
        .driver-statistics-panel {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .panel-header {
            color: #8b0000;
            font-size: 1.4rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f1f1;
        }

        /* All Drivers Table Styles */
        .all-drivers-table-container {
            margin-top: 15px;
        }

        .table-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .table-controls .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #8b0000;
            color: white;
        }

        .btn-primary:hover {
            background: #660000;
        }

        /* Sort button styles */
        .btn-sort {
            background: #6c757d !important;
            color: white !important;
            font-size: 0.9rem;
            padding: 8px 14px !important;
            transition: all 0.3s ease;
        }

        .btn-sort:hover {
            background: #5a6268 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-sort.active {
            background: #8b0000 !important;
            box-shadow: 0 4px 12px rgba(139,0,0,0.3);
        }

        .btn-sort.active:hover {
            background: #660000 !important;
        }

        .drivers-table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .drivers-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 0.9rem;
        }

        .drivers-table th,
        .drivers-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .drivers-table th {
            background: #8b0000;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .drivers-table tbody tr:hover {
            background: #f8f9fa;
        }

        .drivers-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .drivers-table tbody tr:nth-child(even):hover {
            background: #f1f1f1;
        }

        .loading-cell {
            text-align: center;
            padding: 40px !important;
            color: #6c757d;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }

        /* Driver Details Panel */
        .panel-header-with-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-edit:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .edit-form {
            display: none;
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .edit-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-save {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-save:hover {
            background: #0056b3;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .driver-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #8b0000;
        }

        .detail-item label {
            font-weight: bold;
            color: #555;
        }

        .detail-item span {
            color: #333;
            text-align: right;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        /* Statistics Panel */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #8b0000, #a61e1e);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
        }

        .stat-card h4 {
            font-size: 0.9rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: bold;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .management-grid {
                grid-template-columns: 1fr;
            }

            .drivers-table {
                font-size: 0.8rem;
            }

            .drivers-table th,
            .drivers-table td {
                padding: 8px 4px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 2px;
            }

            .action-btn {
                padding: 4px 6px;
                font-size: 0.7rem;
            }

            .header-controls {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <?php if ($is_admin_access): ?>
                    <li><a href="admin_portal.php">Admin Portal</a></li>
                    <li><a href="driver_management.php?admin_access=true" class="active">Driver Management</a></li>
                    <li><a href="admin_logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="driver_portal.php" class="active">My Portal</a></li>
                    <li><a href="driver_logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="portal-section">
        <div class="portal-container">
            <div class="welcome-header">
                <?php if ($is_admin_access): ?>
                    <div class="header-controls">
                        <a href="admin_portal.php" class="btn btn-back">← Back to Admin Portal</a>
                    </div>
                    <h1>🚗 Driver Management Portal</h1>
                    <p>Manage all driver accounts, profiles, and information</p>
                    <div class="admin-notice">
                        🔒 Administrator Access Mode - Full Driver Management Controls
                    </div>
                <?php else: ?>
                    <div class="header-controls">
                        <a href="driver_portal.php" class="btn btn-back">← Back to Driver Portal</a>
                    </div>
                    <h1>Welcome, <?php echo htmlspecialchars($driver_info['first_name'] . ' ' . $driver_info['last_name']); ?>!</h1>
                    <p>Status: <span class="status-badge status-<?php echo strtolower($driver_info['status']); ?>"><?php echo htmlspecialchars($driver_info['status']); ?></span></p>
                <?php endif; ?>
            </div>

            <?php if ($is_admin_access): ?>
                <!-- Admin Driver Management Interface -->
                <div class="management-grid">
                    <!-- All Drivers Panel -->
                    <div class="driver-search-panel">
                        <h2 class="panel-header">👥 All Drivers Information</h2>

                        <!-- Drivers Table -->
                        <div class="all-drivers-table-container">
                            <div class="table-controls">
                                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                    <button onclick="refreshAllDrivers()" class="btn btn-primary">🔄 Refresh</button>
                                    
                                    <!-- Sorting Controls -->
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span style="font-weight: 600; color: #2c3e50; font-size: 0.95rem;">🔀 Sort by Name:</span>
                                        <button onclick="sortDrivers('asc')" id="sort-asc-btn" class="btn btn-sort active">📊 A to Z</button>
                                        <button onclick="sortDrivers('desc')" id="sort-desc-btn" class="btn btn-sort">📈 Z to A</button>
                                    </div>
                                    
                                    <span id="sort-status" style="font-size: 0.85rem; color: #6c757d; font-style: italic;">
                                        📋 Currently: A to Z (Ascending)
                                    </span>
                                </div>
                            </div>
                            
                            <div class="drivers-table-wrapper">
                                <table class="drivers-table" id="all-drivers-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>License</th>
                                            <th>Status</th>
                                            <th>Experience</th>
                                            <th>Rating</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="drivers-table-body">
                                        <tr>
                                            <td colspan="10" class="loading-cell">Loading drivers...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Details Panel -->
                    <div class="driver-details-panel">
                        <div class="panel-header-with-controls">
                            <h2 class="panel-header">📋 Driver Details</h2>
                            <button id="edit-driver-btn" class="btn-edit" style="display: none;" onclick="toggleEditMode()">
                                ✏️ Edit
                            </button>
                        </div>

                        <div id="selected-driver-details">
                            <div class="no-data" id="no-driver-selected">
                                👆 Click on any driver from the table to view their detailed information
                            </div>
                            <div id="driver-info-content" style="display: none;">
                                <!-- Driver details will be loaded here dynamically -->
                            </div>
                            
                            <!-- Edit Driver Form -->
                            <div id="edit-driver-form" class="edit-form">
                                <h3>Edit Driver Information</h3>
                                <form id="driver-edit-form">
                                    <input type="hidden" id="edit-driver-id" name="driver_id">
                                    
                                    <div class="form-group">
                                        <label for="edit-first-name">First Name:</label>
                                        <input type="text" id="edit-first-name" name="first_name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-last-name">Last Name:</label>
                                        <input type="text" id="edit-last-name" name="last_name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-email">Email:</label>
                                        <input type="email" id="edit-email" name="email" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-phone">Phone Number:</label>
                                        <input type="tel" id="edit-phone" name="phone_number" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-license">License Number:</label>
                                        <input type="text" id="edit-license" name="license_number" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-status">Status:</label>
                                        <select id="edit-status" name="status" required>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Suspended">Suspended</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-experience">Years of Experience:</label>
                                        <input type="number" id="edit-experience" name="years_of_experience" min="0" max="50">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-address">Address:</label>
                                        <input type="text" id="edit-address" name="address">
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn-save" onclick="saveDriverChanges()">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="cancelEdit()">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Panel -->
                <div class="driver-statistics-panel">
                    <h2 class="panel-header">📊 Driver Statistics</h2>
                    <div class="stats-grid" id="stats-grid">
                        <div class="stat-card">
                            <h4>Total Drivers</h4>
                            <span class="stat-number" id="total-drivers">0</span>
                        </div>
                        <div class="stat-card">
                            <h4>Active Drivers</h4>
                            <span class="stat-number" id="active-drivers">0</span>
                        </div>
                        <div class="stat-card">
                            <h4>Average Rating</h4>
                            <span class="stat-number" id="avg-rating">0.0</span>
                        </div>
                        <div class="stat-card">
                            <h4>New This Month</h4>
                            <span class="stat-number" id="new-drivers">0</span>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Regular Driver Interface -->
                <div class="driver-details-panel">
                    <h2 class="panel-header">👤 My Profile Information</h2>
                    <div class="driver-details-grid">
                        <div class="detail-item">
                            <label>Driver ID:</label>
                            <span><?php echo htmlspecialchars($driver_info['driver_id']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Full Name:</label>
                            <span><?php echo htmlspecialchars($driver_info['first_name'] . ' ' . $driver_info['last_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($driver_info['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span><?php echo htmlspecialchars($driver_info['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>License Number:</label>
                            <span><?php echo htmlspecialchars($driver_info['license']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge status-<?php echo strtolower($driver_info['status']); ?>"><?php echo htmlspecialchars($driver_info['status']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Experience:</label>
                            <span><?php echo htmlspecialchars($driver_info['experience']); ?> years</span>
                        </div>
                        <div class="detail-item">
                            <label>Rating:</label>
                            <span><?php echo htmlspecialchars($driver_info['rating']); ?>/5.0</span>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <label>Address:</label>
                            <span><?php echo htmlspecialchars($driver_info['address']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Member Since:</label>
                            <span><?php echo htmlspecialchars($driver_info['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer style="background: #333; color: white; text-align: center; padding: 20px; margin-top: 40px;">
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>

    <script>
        let currentSelectedDriverId = null;
        let currentDriverData = null;
        let isEditMode = false;
        let isAdminAccess = <?php echo json_encode($is_admin_access); ?>;

        // Load drivers on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (isAdminAccess) {
                console.log('Page loaded, loading all drivers and statistics...');
                loadAllDrivers();
                loadDriverStatistics();
            }
        });

        // Load all drivers function
        function loadAllDrivers() {
            loadAllDriversWithSort(currentSortOrder);
        }

        // Display all drivers in table
        function displayAllDriversTable(drivers) {
            const tableBody = document.getElementById('drivers-table-body');

            if (drivers.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="10" class="loading-cell">No drivers found in the system</td></tr>';
                return;
            }

            let html = '';
            drivers.forEach((driver) => {
                const firstName = escapeHtml(driver.First_Name || 'N/A');
                const lastName = escapeHtml(driver.Last_Name || 'N/A');
                const email = escapeHtml(driver.Email || 'N/A');
                const phone = escapeHtml(driver.Phone_Number || 'N/A');
                const license = escapeHtml(driver.License_Number || 'N/A');
                const status = escapeHtml(driver.Status || 'Unknown');
                const experience = escapeHtml(driver.Years_of_Experience || 'N/A');
                const rating = driver.Rating ? parseFloat(driver.Rating).toFixed(1) : 'N/A';
                const joinedDate = driver.Created_At ? new Date(driver.Created_At).toLocaleDateString() : 'N/A';

                html += `
                    <tr>
                        <td><strong>${driver.Driver_ID}</strong></td>
                        <td>${firstName} ${lastName}</td>
                        <td>${email}</td>
                        <td>${phone}</td>
                        <td>${license}</td>
                        <td><span class="status-badge status-${status.toLowerCase()}">${status}</span></td>
                        <td>${experience} years</td>
                        <td>${rating}${rating !== 'N/A' ? '/5.0' : ''}</td>
                        <td>${joinedDate}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn btn-view" onclick="viewDriverDetails('${driver.Driver_ID}')" title="View Details">👁️</button>
                                <button class="action-btn btn-delete" onclick="deleteDriver('${driver.Driver_ID}')" title="Delete Driver">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = html;
        }

        // Refresh all drivers
        function refreshAllDrivers() {
            loadAllDrivers();
        }

        // Global variable to track current sort order
        let currentSortOrder = 'asc';

        // Sort drivers function
        function sortDrivers(sortOrder) {
            currentSortOrder = sortOrder;
            
            // Update button states
            const ascBtn = document.getElementById('sort-asc-btn');
            const descBtn = document.getElementById('sort-desc-btn');
            const statusSpan = document.getElementById('sort-status');
            
            if (sortOrder === 'asc') {
                ascBtn.classList.add('active');
                descBtn.classList.remove('active');
                statusSpan.textContent = '📋 Currently: A to Z (Ascending)';
            } else {
                descBtn.classList.add('active');
                ascBtn.classList.remove('active');
                statusSpan.textContent = '📋 Currently: Z to A (Descending)';
            }
            
            // Load drivers with new sort order
            loadAllDriversWithSort(sortOrder);
        }

        // Load all drivers with specific sort order
        function loadAllDriversWithSort(sortOrder) {
            const tableBody = document.getElementById('drivers-table-body');
            tableBody.innerHTML = '<tr><td colspan="10" class="loading-cell">Loading drivers...</td></tr>';

            const formData = new FormData();
            formData.append('action', 'get_all_drivers');
            formData.append('sort', sortOrder);

            fetch('driver_management_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Sorted drivers data:', data);
                    if (data.success && data.drivers) {
                        displayAllDriversTable(data.drivers);
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="10" class="loading-cell">❌ ${data.message || 'No drivers found'}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Load sorted drivers error:', error);
                    tableBody.innerHTML = `<tr><td colspan="10" class="loading-cell">❌ Error loading drivers: ${error.message}</td></tr>`;
                });
        }

        // View driver details (loads in the details panel)
        function viewDriverDetails(driverId) {
            console.log('Viewing driver details for ID:', driverId);
            currentSelectedDriverId = driverId;
            loadDriverDetails(driverId);
        }

        // Delete driver function
        function deleteDriver(driverId) {
            if (!confirm('Are you sure you want to delete this driver? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_driver');
            formData.append('driver_id', driverId);

            fetch('driver_management_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Driver deleted successfully');
                        loadAllDrivers(); // Refresh the table
                        // Clear driver details panel if this driver was selected
                        if (currentSelectedDriverId == driverId) {
                            document.getElementById('no-driver-selected').style.display = 'block';
                            document.getElementById('driver-info-content').style.display = 'none';
                            document.getElementById('edit-driver-btn').style.display = 'none';
                            document.getElementById('edit-driver-form').classList.remove('active');
                            currentSelectedDriverId = null;
                            currentDriverData = null;
                            isEditMode = false;
                        }
                    } else {
                        alert('Error deleting driver: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting driver: ' + error.message);
                });
        }

        // Load detailed driver information
        function loadDriverDetails(driverId) {
            const formData = new FormData();
            formData.append('action', 'get_driver_details');
            formData.append('driver_id', driverId);

            fetch('driver_management_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.driver) {
                        displayDriverDetails(data.driver);
                    } else {
                        document.getElementById('driver-info-content').innerHTML = `<div class="no-data">❌ ${data.message || 'Error loading driver details'}</div>`;
                        document.getElementById('no-driver-selected').style.display = 'none';
                        document.getElementById('driver-info-content').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading driver details:', error);
                    document.getElementById('driver-info-content').innerHTML = `<div class="no-data">❌ Error: ${error.message}</div>`;
                    document.getElementById('no-driver-selected').style.display = 'none';
                    document.getElementById('driver-info-content').style.display = 'block';
                });
        }

        // Display driver details
        function displayDriverDetails(driver) {
            const detailsHtml = `
                <div class="driver-details-grid">
                    <div class="detail-item">
                        <label>Driver ID:</label>
                        <span>${escapeHtml(driver.Driver_ID)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Full Name:</label>
                        <span>${escapeHtml(driver.First_Name)} ${escapeHtml(driver.Last_Name)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${escapeHtml(driver.Email)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <span>${escapeHtml(driver.Phone_Number)}</span>
                    </div>
                    <div class="detail-item">
                        <label>License Number:</label>
                        <span>${escapeHtml(driver.License_Number)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge status-${driver.Status.toLowerCase()}">${escapeHtml(driver.Status)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Experience:</label>
                        <span>${escapeHtml(driver.Years_of_Experience)} years</span>
                    </div>
                    <div class="detail-item">
                        <label>Rating:</label>
                        <span>${driver.Rating ? parseFloat(driver.Rating).toFixed(1) : 'N/A'}${driver.Rating ? '/5.0' : ''}</span>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Address:</label>
                        <span>${escapeHtml(driver.Address || 'Not provided')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Member Since:</label>
                        <span>${driver.Created_At ? new Date(driver.Created_At).toLocaleDateString() : 'Unknown'}</span>
                    </div>
                </div>
            `;

            document.getElementById('driver-info-content').innerHTML = detailsHtml;
            document.getElementById('no-driver-selected').style.display = 'none';
            document.getElementById('driver-info-content').style.display = 'block';
            
            // Show edit button and store current driver data
            document.getElementById('edit-driver-btn').style.display = 'block';
            currentDriverData = driver;
        }

        // Load driver statistics
        function loadDriverStatistics() {
            const formData = new FormData();
            formData.append('action', 'get_statistics');

            fetch('driver_management_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.stats) {
                        document.getElementById('total-drivers').textContent = data.stats.total_drivers || 0;
                        document.getElementById('active-drivers').textContent = data.stats.active_drivers || 0;
                        document.getElementById('avg-rating').textContent = (data.stats.avg_rating || 0).toFixed(1);
                        document.getElementById('new-drivers').textContent = data.stats.new_drivers || 0;
                    }
                })
                .catch(error => {
                    console.error('Error loading statistics:', error);
                });
        }

        // Toggle edit mode
        function toggleEditMode() {
            if (isEditMode) {
                cancelEdit();
            } else {
                enterEditMode();
            }
        }

        // Enter edit mode
        function enterEditMode() {
            if (!currentDriverData) {
                alert('Please select a driver first');
                return;
            }

            isEditMode = true;
            
            // Populate form with current driver data
            document.getElementById('edit-driver-id').value = currentDriverData.Driver_ID;
            document.getElementById('edit-first-name').value = currentDriverData.First_Name || '';
            document.getElementById('edit-last-name').value = currentDriverData.Last_Name || '';
            document.getElementById('edit-email').value = currentDriverData.Email || '';
            document.getElementById('edit-phone').value = currentDriverData.Phone_Number || '';
            document.getElementById('edit-license').value = currentDriverData.License_Number || '';
            document.getElementById('edit-status').value = currentDriverData.Status || 'Active';
            document.getElementById('edit-experience').value = currentDriverData.Years_of_Experience || '';
            document.getElementById('edit-address').value = currentDriverData.Address || '';
            
            // Hide driver details and show edit form
            document.getElementById('driver-info-content').style.display = 'none';
            document.getElementById('edit-driver-form').classList.add('active');
            
            // Update edit button
            document.getElementById('edit-driver-btn').textContent = '❌ Cancel Edit';
            document.getElementById('edit-driver-btn').style.background = '#dc3545';
        }

        // Cancel edit mode
        function cancelEdit() {
            isEditMode = false;
            
            // Hide edit form and show driver details
            document.getElementById('edit-driver-form').classList.remove('active');
            document.getElementById('driver-info-content').style.display = 'block';
            
            // Reset edit button
            document.getElementById('edit-driver-btn').textContent = '✏️ Edit';
            document.getElementById('edit-driver-btn').style.background = '#28a745';
        }

        // Save driver changes
        function saveDriverChanges() {
            const formData = new FormData();
            formData.append('action', 'update_driver');
            formData.append('driver_id', document.getElementById('edit-driver-id').value);
            formData.append('first_name', document.getElementById('edit-first-name').value);
            formData.append('last_name', document.getElementById('edit-last-name').value);
            formData.append('email', document.getElementById('edit-email').value);
            formData.append('phone_number', document.getElementById('edit-phone').value);
            formData.append('license_number', document.getElementById('edit-license').value);
            formData.append('status', document.getElementById('edit-status').value);
            formData.append('years_of_experience', document.getElementById('edit-experience').value);
            formData.append('address', document.getElementById('edit-address').value);

            fetch('driver_management_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Driver information updated successfully!');
                    
                    // Refresh the drivers table
                    loadAllDrivers();
                    
                    // Reload the driver details with updated information
                    loadDriverDetails(currentDriverData.Driver_ID);
                    
                    // Exit edit mode
                    cancelEdit();
                } else {
                    alert('Error updating driver: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                alert('Error updating driver: ' + error.message);
            });
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            }) : '';
        }
    </script>
</body>

</html>
