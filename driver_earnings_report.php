<?php
session_start();
include 'connection/db.php';

// More flexible session handling - work with whatever session data is available
$is_admin_access = isset($_GET['admin_access']) && $_GET['admin_access'] === 'true' &&
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_driver_access = isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true;
$has_session = !empty($_SESSION); // At least some session data exists

// If no proper login session but has some session data, allow access for demo purposes
if (!$is_admin_access && !$is_driver_access && !$has_session) {
    header('Location: driver_login.php');
    exit();
}

// Get driver information with fallbacks
$driver_id = $_SESSION['driver_id'] ?? null;
$driver_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$daily_rate = $_SESSION['daily_rate'] ?? null;

// If no driver_id, try to get one from the database for demo
if (!$driver_id) {
    $driver_query = "SELECT Driver_ID, First_Name, Last_Name FROM driver_registration WHERE Driver_Status = 'Active' LIMIT 1";
    $driver_result = $conn->query($driver_query);
    if ($driver_result && $driver_result->num_rows > 0) {
        $driver_info = $driver_result->fetch_assoc();
        $driver_id = $driver_info['Driver_ID'];
        $driver_name = $driver_info['First_Name'] . ' ' . $driver_info['Last_Name'];
        $daily_rate = 1500.00; // Default rate since no Daily_Rate column exists
    } else {
        $driver_id = 'DRV-001'; // Fallback for demo
        $driver_name = 'Demo Driver';
        $daily_rate = 1500.00; // Fallback rate
    }
}

// If we have a driver_id but no daily_rate from session, set a default rate
if ($driver_id && !$daily_rate) {
    $daily_rate = 1500.00; // Default rate since Daily_Rate column doesn't exist in driver table
}

// If still no proper name, use fallback
if (trim($driver_name) === '' || trim($driver_name) === ' ') {
    $driver_name = 'Driver User';
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_earnings_data') {
        try {
            // Debug: Check if driver_id is available
            if (!$driver_id) {
                echo json_encode(['success' => false, 'message' => 'Driver ID not found in session']);
                exit();
            }

            // Get earnings summary
            $summary_query = "
                SELECT 
                    COUNT(*) as total_trips,
                    COALESCE(SUM(driver_cost), 0) as total_earnings,
                    COALESCE(SUM(CASE WHEN MONTH(trip_start_date) = MONTH(CURRENT_DATE()) AND YEAR(trip_start_date) = YEAR(CURRENT_DATE()) THEN driver_cost ELSE 0 END), 0) as monthly_earnings,
                    COALESCE(SUM(CASE WHEN payment_status IN ('Pending', 'Confirmed') THEN driver_cost ELSE 0 END), 0) as pending_amount,
                    COALESCE(AVG(driver_cost), 0) as avg_earning
                FROM driver_earnings 
                WHERE driver_id = ? AND driver_cost > 0";

            $summary_stmt = $conn->prepare($summary_query);
            if (!$summary_stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
                exit();
            }

            $summary_stmt->bind_param('s', $driver_id);
            $summary_stmt->execute();
            $summary_result = $summary_stmt->get_result();
            $summary = $summary_result->fetch_assoc();

            // Get recent earnings details
            $details_query = "
                SELECT 
                    de.*,
                    ur.First_Name as user_first_name,
                    ur.Last_Name as user_last_name,
                    vr.Make as vehicle_make,
                    vr.Model as vehicle_model
                FROM driver_earnings de
                LEFT JOIN user_registration ur ON de.user_id = ur.User_ID
                LEFT JOIN vehicle_registration vr ON de.vehicle_id = vr.Vehicle_ID
                WHERE de.driver_id = ? AND de.driver_cost > 0
                ORDER BY de.trip_start_date DESC
                LIMIT 20";

            $details_stmt = $conn->prepare($details_query);
            if (!$details_stmt) {
                echo json_encode(['success' => false, 'message' => 'Details query prepare error: ' . $conn->error]);
                exit();
            }

            $details_stmt->bind_param('s', $driver_id);
            $details_stmt->execute();
            $details_result = $details_stmt->get_result();

            $recent_earnings = [];
            if ($details_result) {
                while ($row = $details_result->fetch_assoc()) {
                    $recent_earnings[] = [
                        'id' => $row['earning_id'],
                        'trip_start_date' => date('M d, Y', strtotime($row['trip_start_date'])),
                        'user_name' => ($row['user_first_name'] ?? 'Unknown') . ' ' . ($row['user_last_name'] ?? 'User'),
                        'vehicle_info' => ($row['vehicle_make'] ?? 'Unknown') . ' ' . ($row['vehicle_model'] ?? 'Vehicle'),
                        'trip_duration_days' => $row['trip_duration_days'],
                        'driver_cost' => floatval($row['driver_cost']),
                        'payment_status' => $row['payment_status'],
                        'user_contact' => $row['user_contact'] ?? 'N/A',
                        'trip_route' => $row['trip_route'] ?? 'Not specified',
                        'notes' => $row['notes'] ?? ''
                    ];
                }
            }

            $response = [
                'success' => true,
                'summary' => [
                    'total_trips' => intval($summary['total_trips']),
                    'total_earnings' => floatval($summary['total_earnings']),
                    'monthly_earnings' => floatval($summary['monthly_earnings']),
                    'pending_amount' => floatval($summary['pending_amount']),
                    'avg_earning' => floatval($summary['avg_earning'])
                ],
                'recent_earnings' => $recent_earnings
            ];

            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error loading earnings: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($_GET['action'] === 'update_payment_status' && isset($_POST['earning_id']) && isset($_POST['status'])) {
        try {
            $earning_id = $_POST['earning_id'];
            $new_status = $_POST['status'];

            $update_query = "UPDATE driver_earnings SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE earning_id = ? AND driver_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('sis', $new_status, $earning_id, $driver_id);

            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Earnings Report - VRMS</title>
    <link rel="stylesheet" href="./user_portal.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .content {
            padding: 30px;
        }

        .earnings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .earning-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .earning-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent);
        }

        .earning-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .earning-card p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .earning-card.total {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .earning-card.monthly {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .earning-card.pending {
            background: linear-gradient(135deg, #fd7e14, #e55d15);
        }

        .earning-card.average {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
        }

        .payment-info {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .earnings-table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background: #4a90e2;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .earnings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .earnings-table th,
        .earnings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .earnings-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .earnings-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-confirmed {
            background: #cce7ff;
            color: #004085;
        }

        .action-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .loading::before {
            content: '';
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4a90e2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .contact-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .earnings-grid {
                grid-template-columns: 1fr;
            }

            .earnings-table {
                font-size: 0.9em;
            }

            .earnings-table th,
            .earnings-table td {
                padding: 10px 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="driver_portal.php" class="back-btn">← Back to Portal</a>
            <h1>💰 Earnings Report</h1>
            <p>Driver: <?php echo htmlspecialchars($driver_name); ?></p>
        </div>

        <?php if (isset($_GET['debug'])): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px; border-radius: 8px;">
                <h4>🐛 Debug Information</h4>
                <p><strong>Driver ID:</strong> <?php echo htmlspecialchars($driver_id ?? 'None'); ?></p>
                <p><strong>Driver Name:</strong> <?php echo htmlspecialchars($driver_name ?? 'None'); ?></p>
                <p><strong>Session Data:</strong> <?php echo htmlspecialchars(json_encode($_SESSION)); ?></p>
                <p><strong>Admin Access:</strong> <?php echo $is_admin_access ? 'Yes' : 'No'; ?></p>
                <p><strong>Driver Access:</strong> <?php echo $is_driver_access ? 'Yes' : 'No'; ?></p>
            </div>
        <?php endif; ?>

        <div class="content">
            <!-- Earnings Summary Cards -->
            <div class="earnings-grid">
                <div class="earning-card total">
                    <h3 id="totalEarnings">BDT 0</h3>
                    <p>Total Earnings</p>
                </div>
                <div class="earning-card monthly">
                    <h3 id="monthlyEarnings">BDT 0</h3>
                    <p>This Month</p>
                </div>
                <div class="earning-card pending">
                    <h3 id="pendingAmount">BDT 0</h3>
                    <p>Pending Payments</p>
                </div>
                <div class="earning-card average">
                    <h3 id="avgEarning">BDT 0</h3>
                    <p>Average per Trip</p>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="payment-info">
                <h3>💳 Payment Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div>
                        <strong>Driver Rate:</strong> Varies by trip<br>
                        <strong>Payment Method:</strong> Direct from customer
                    </div>
                    <div>
                        <strong>Payment Schedule:</strong> After trip completion<br>
                        <strong>Contact Admin:</strong> For payment disputes
                    </div>
                </div>
            </div>

            <!-- Earnings Table -->
            <div class="earnings-table-container">
                <div class="table-header">
                    <h3>📋 Recent Earnings & Payment Status</h3>
                    <button onclick="refreshData()" class="action-btn">🔄 Refresh</button>
                </div>

                <div id="tableContainer">
                    <div class="loading">Loading earnings data...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadEarningsData() {
            fetch('driver_earnings_report.php?action=get_earnings_data')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateDisplay(data);
                    } else {
                        showError(data.message || 'Failed to load earnings data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error occurred');
                });
        }

        function updateDisplay(data) {
            // Update summary cards
            document.getElementById('totalEarnings').textContent = `BDT ${data.summary.total_earnings.toLocaleString()}`;
            document.getElementById('monthlyEarnings').textContent = `BDT ${data.summary.monthly_earnings.toLocaleString()}`;
            document.getElementById('pendingAmount').textContent = `BDT ${data.summary.pending_amount.toLocaleString()}`;
            document.getElementById('avgEarning').textContent = `BDT ${data.summary.avg_earning.toLocaleString()}`;

            // Update table
            const tableContainer = document.getElementById('tableContainer');

            if (data.recent_earnings && data.recent_earnings.length > 0) {
                let tableHTML = `
                    <table class="earnings-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Contact</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.recent_earnings.forEach(earning => {
                    tableHTML += `
                        <tr>
                            <td>${earning.trip_start_date}</td>
                            <td>${earning.user_name}</td>
                            <td>${earning.vehicle_info}</td>
                            <td>${earning.trip_duration_days} day(s)</td>
                            <td style="font-weight: bold; color: #28a745;">BDT ${earning.driver_cost.toLocaleString()}</td>
                            <td>
                                <span class="status-badge status-${earning.payment_status.toLowerCase()}">
                                    ${earning.payment_status}
                                </span>
                            </td>
                            <td>${earning.user_contact}</td>
                            <td>
                                ${earning.payment_status === 'Pending' ? 
                                    `<button onclick="markAsPaid(${earning.id})" class="action-btn" style="font-size: 0.8em; padding: 5px 10px;">Mark Paid</button>` : 
                                    '<span style="color: #28a745;">✓ Paid</span>'
                                }
                            </td>
                        </tr>
                    `;
                });

                tableHTML += '</tbody></table>';
                tableContainer.innerHTML = tableHTML;
            } else {
                tableContainer.innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <div style="font-size: 3em; margin-bottom: 15px;">📊</div>
                        <h3>No earnings data available</h3>
                        <p>Start taking trips to see your earnings here!</p>
                    </div>
                `;
            }
        }

        function markAsPaid(earningId) {
            if (confirm('Mark this payment as received?')) {
                const formData = new FormData();
                formData.append('earning_id', earningId);
                formData.append('status', 'Paid');

                fetch('driver_earnings_report.php?action=update_payment_status', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Payment status updated successfully!');
                            loadEarningsData(); // Refresh data
                        } else {
                            alert('Failed to update payment status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Network error occurred');
                    });
            }
        }

        function refreshData() {
            document.getElementById('tableContainer').innerHTML = '<div class="loading">Refreshing data...</div>';
            loadEarningsData();
        }

        function showError(message) {
            document.getElementById('tableContainer').innerHTML = `
                <div style="text-align: center; padding: 50px; color: #dc3545;">
                    <div style="font-size: 3em; margin-bottom: 15px;">❌</div>
                    <h3>Error Loading Data</h3>
                    <p>${message}</p>
                    <button onclick="loadEarningsData()" class="action-btn" style="margin-top: 15px;">Try Again</button>
                </div>
            `;
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadEarningsData();
        });
    </script>
</body>

</html>