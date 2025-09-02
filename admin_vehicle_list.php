<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Database connection
include 'connection/db.php';

// Get all vehicles from the database
$query = "SELECT * FROM vehicle_registration ORDER BY Registration_Date DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Fleet Management - Admin Panel</title>
    <link rel="stylesheet" href="./user_portal.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
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

        nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-section h1 {
            color: #8b0000;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header-section p {
            color: #666;
            font-size: 1.1rem;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .add-vehicle-btn {
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .add-vehicle-btn:hover {
            background: #218838;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-box button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .vehicle-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .vehicle-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
        }

        .vehicle-id {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-reserved {
            background: #fff3cd;
            color: #856404;
        }

        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        .vehicle-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 2px;
        }

        .detail-value {
            font-weight: bold;
            color: #333;
        }

        .vehicle-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s;
            text-align: center;
            flex: 1;
            min-width: 80px;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
        }

        .view-btn:hover {
            background: #138496;
        }

        .edit-btn {
            background: #ffc107;
            color: #212529;
        }

        .edit-btn:hover {
            background: #e0a800;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .no-vehicles {
            text-align: center;
            color: #666;
            font-size: 1.2rem;
            margin: 60px 0;
        }

        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #8b0000;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                justify-content: center;
            }
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve <span style="font-size: 0.6em; background: #dc3545; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">ADMIN</span></div>
        <nav>
            <ul>
                <li><a href="admin_portal.php">Admin Portal</a></li>
                <li><a href="vehicle_portal.php?admin_access=true">Vehicle Management</a></li>
                <li><a href="admin_vehicle_list.php">All Vehicles</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <?php
        // Calculate statistics
        $total_vehicles = mysqli_num_rows($result);

        $available_query = "SELECT COUNT(*) as count FROM vehicle_registration WHERE Status = 'Available'";
        $available_result = mysqli_query($conn, $available_query);
        $available_count = mysqli_fetch_assoc($available_result)['count'];

        $reserved_query = "SELECT COUNT(*) as count FROM vehicle_registration WHERE Status = 'Reserved'";
        $reserved_result = mysqli_query($conn, $reserved_query);
        $reserved_count = mysqli_fetch_assoc($reserved_result)['count'];

        $maintenance_query = "SELECT COUNT(*) as count FROM vehicle_registration WHERE Status = 'Maintenance'";
        $maintenance_result = mysqli_query($conn, $maintenance_query);
        $maintenance_count = mysqli_fetch_assoc($maintenance_result)['count'];
        ?>

        <div class="stats-bar">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_vehicles; ?></div>
                    <div class="stat-label">Total Vehicles</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $available_count; ?></div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $reserved_count; ?></div>
                    <div class="stat-label">Reserved</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $maintenance_count; ?></div>
                    <div class="stat-label">Maintenance</div>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <div class="search-box">
                <input type="text" placeholder="Search vehicles..." id="searchInput">
                <button type="button" onclick="searchVehicles()">Search</button>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="vehicles-grid" id="vehiclesContainer">
            <?php
            if ($total_vehicles > 0) {
                // Reset result pointer
                mysqli_data_seek($result, 0);

                while ($vehicle = mysqli_fetch_assoc($result)) {
                    $status_class = 'status-' . strtolower($vehicle['Status']);
            ?>
                    <div class="vehicle-card" data-search="<?php echo htmlspecialchars(strtolower($vehicle['Make'] . ' ' . $vehicle['Model'] . ' ' . $vehicle['License_Plate'] . ' ' . $vehicle['Vehicle_Type'])); ?>">
                        <div class="vehicle-header">
                            <div class="vehicle-title"><?php echo htmlspecialchars($vehicle['Make'] . ' ' . $vehicle['Model']); ?></div>
                            <div class="vehicle-id">ID: <?php echo htmlspecialchars($vehicle['Vehicle_ID']); ?></div>
                        </div>

                        <div class="status-badge <?php echo $status_class; ?>">
                            <?php
                            $status_icons = [
                                'Available' => '✅ Available',
                                'Reserved' => '📅 Reserved',
                                'Maintenance' => '🔧 Maintenance'
                            ];
                            echo $status_icons[$vehicle['Status']] ?? $vehicle['Status'];
                            ?>
                        </div>

                        <div class="vehicle-details">
                            <div class="detail-item">
                                <div class="detail-label">License Plate</div>
                                <div class="detail-value"><?php echo htmlspecialchars($vehicle['License_Plate']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Year</div>
                                <div class="detail-value"><?php echo htmlspecialchars($vehicle['Year']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Type</div>
                                <div class="detail-value"><?php echo htmlspecialchars($vehicle['Vehicle_Type']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Fuel</div>
                                <div class="detail-value"><?php echo htmlspecialchars($vehicle['Fuel_Type']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Transmission</div>
                                <div class="detail-value"><?php echo htmlspecialchars($vehicle['Transmission']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Daily Rate</div>
                                <div class="detail-value">৳<?php echo number_format($vehicle['Rental_Rate'], 2); ?></div>
                            </div>
                        </div>

                        <div class="vehicle-actions">
                            <a href="vehicle_portal.php?admin_access=true&view_vehicle_id=<?php echo $vehicle['Vehicle_ID']; ?>" class="action-btn view-btn">View</a>
                            <a href="#" onclick="editVehicle('<?php echo $vehicle['Vehicle_ID']; ?>')" class="action-btn edit-btn">Edit</a>
                            <a href="#" onclick="deleteVehicle('<?php echo $vehicle['Vehicle_ID']; ?>', '<?php echo addslashes($vehicle['Make'] . ' ' . $vehicle['Model']); ?>')" class="action-btn delete-btn">Delete</a>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="no-vehicles">No vehicles found in the system.</div>';
            }
            ?>
        </div>
    </div>

    <script>
        function searchVehicles() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const vehicleCards = document.querySelectorAll('.vehicle-card');

            vehicleCards.forEach(card => {
                const searchData = card.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function editVehicle(vehicleId) {
            if (confirm('Edit vehicle information for Vehicle ID: ' + vehicleId + '?')) {
                window.location.href = 'vehicle_update.php?vehicle_id=' + vehicleId;
            }
        }

        function deleteVehicle(vehicleId, vehicleName) {
            if (confirm('Are you sure you want to delete ' + vehicleName + '?\n\nThis action cannot be undone.')) {
                if (confirm('This will permanently remove the vehicle from the system. Continue?')) {
                    window.location.href = 'vehicle_delete.php?vehicle_id=' + vehicleId;
                }
            }
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('input', searchVehicles);
    </script>
</body>

</html>