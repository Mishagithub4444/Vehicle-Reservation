<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

include 'connection/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Reports - Admin Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
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
            padding: 40px 20px;
        }

        .portal-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-header h1 {
            color: #8b0000;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 10px 0;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #8b0000;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Statistics Cards */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #8b0000, #a61e1e);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: bold;
            display: block;
        }

        /* Reservations Table */
        .reservations-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .section-header h2 {
            color: #8b0000;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th {
            background: #8b0000;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        .reservations-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }

        .reservations-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .portal-container {
                padding: 0 10px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .stats-section {
                grid-template-columns: 1fr;
            }

            .reservations-table {
                font-size: 0.9rem;
            }

            .reservations-table th,
            .reservations-table td {
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="admin_portal.php">Admin Portal</a></li>
                <li><a href="reservation_reports.php" class="active">Reservation Reports</a></li>
                <li><a href="admin_logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="portal-section">
        <div class="portal-container">
            <div class="welcome-header">
                <h1>📊 Reservation Reports</h1>
                <div class="admin-badge">🔒 Administrator Access</div>
                <p>Comprehensive reservation management and analytics</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3>🔍 Filter Reservations</h3>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="start-date">Start Date:</label>
                        <input type="date" id="start-date" name="start_date">
                    </div>
                    <div class="filter-group">
                        <label for="end-date">End Date:</label>
                        <input type="date" id="end-date" name="end_date">
                    </div>
                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="user-search">User Search:</label>
                        <input type="text" id="user-search" name="user_search" placeholder="Search by user name/email">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="applyFilters()">🔍 Apply Filters</button>
                    <button class="btn btn-secondary" onclick="clearFilters()">🗑️ Clear Filters</button>
                    <button class="btn btn-secondary" onclick="exportData()">📊 Export Data</button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-section" id="stats-section">
                <div class="stat-card">
                    <h3>Total Reservations</h3>
                    <span class="stat-number" id="total-reservations">0</span>
                </div>
                <div class="stat-card">
                    <h3>Active Reservations</h3>
                    <span class="stat-number" id="active-reservations">0</span>
                </div>
                <div class="stat-card">
                    <h3>Completed Reservations</h3>
                    <span class="stat-number" id="completed-reservations">0</span>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <span class="stat-number" id="total-revenue">BDT 0</span>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="reservations-section">
                <div class="section-header">
                    <h2>🚗 All Reservations</h2>
                </div>
                <div class="table-container">
                    <table class="reservations-table" id="reservations-table">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>User</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reservations-tbody">
                            <tr>
                                <td colspan="10" class="no-data">Loading reservations...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Load reservations on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadReservations();
            loadStatistics();
        });

        // Load all reservations
        function loadReservations(filters = {}) {
            const formData = new FormData();
            formData.append('action', 'get_all_reservations');
            
            // Add filters if provided
            if (filters.start_date) formData.append('start_date', filters.start_date);
            if (filters.end_date) formData.append('end_date', filters.end_date);
            if (filters.status) formData.append('status', filters.status);
            if (filters.user_search) formData.append('user_search', filters.user_search);

            fetch('reservation_reports_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReservations(data.reservations);
                } else {
                    document.getElementById('reservations-tbody').innerHTML = 
                        `<tr><td colspan="10" class="no-data">❌ ${data.message || 'Error loading reservations'}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error loading reservations:', error);
                document.getElementById('reservations-tbody').innerHTML = 
                    `<tr><td colspan="10" class="no-data">❌ Error loading reservations</td></tr>`;
            });
        }

        // Display reservations in table
        function displayReservations(reservations) {
            const tbody = document.getElementById('reservations-tbody');
            
            if (!reservations || reservations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="no-data">📋 No reservations found</td></tr>';
                return;
            }

            const rows = reservations.map(reservation => {
                const startDate = new Date(reservation.Start_Date).toLocaleDateString();
                const endDate = new Date(reservation.End_Date).toLocaleDateString();
                const createdDate = new Date(reservation.Created_At).toLocaleDateString();
                
                return `
                    <tr>
                        <td>${escapeHtml(reservation.Reservation_ID)}</td>
                        <td>${escapeHtml(reservation.User_Name || 'N/A')}</td>
                        <td>${escapeHtml(reservation.Vehicle_Info || 'N/A')}</td>
                        <td>${escapeHtml(reservation.Driver_Name || 'Self-Drive')}</td>
                        <td>${startDate}</td>
                        <td>${endDate}</td>
                        <td>BDT ${parseFloat(reservation.Total_Cost || 0).toLocaleString()}</td>
                        <td><span class="status-badge status-${(reservation.Status || 'pending').toLowerCase()}">${escapeHtml(reservation.Status || 'Pending')}</span></td>
                        <td>${createdDate}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn btn-view" onclick="viewReservation(${reservation.Reservation_ID})" title="View Details">👁️</button>
                                <button class="action-btn btn-edit" onclick="editReservation(${reservation.Reservation_ID})" title="Edit">✏️</button>
                                <button class="action-btn btn-delete" onclick="deleteReservation(${reservation.Reservation_ID})" title="Delete">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = rows;
        }

        // Load statistics
        function loadStatistics() {
            const formData = new FormData();
            formData.append('action', 'get_reservation_stats');

            fetch('reservation_reports_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.stats) {
                    document.getElementById('total-reservations').textContent = data.stats.total_reservations || 0;
                    document.getElementById('active-reservations').textContent = data.stats.active_reservations || 0;
                    document.getElementById('completed-reservations').textContent = data.stats.completed_reservations || 0;
                    document.getElementById('total-revenue').textContent = 'BDT ' + (parseFloat(data.stats.total_revenue || 0)).toLocaleString();
                }
            })
            .catch(error => {
                console.error('Error loading statistics:', error);
            });
        }

        // Apply filters
        function applyFilters() {
            const filters = {
                start_date: document.getElementById('start-date').value,
                end_date: document.getElementById('end-date').value,
                status: document.getElementById('status-filter').value,
                user_search: document.getElementById('user-search').value
            };

            loadReservations(filters);
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('start-date').value = '';
            document.getElementById('end-date').value = '';
            document.getElementById('status-filter').value = '';
            document.getElementById('user-search').value = '';
            loadReservations();
        }

        // Export data
        function exportData() {
            const filters = {
                start_date: document.getElementById('start-date').value,
                end_date: document.getElementById('end-date').value,
                status: document.getElementById('status-filter').value,
                user_search: document.getElementById('user-search').value
            };

            const queryParams = new URLSearchParams();
            queryParams.append('action', 'export_reservations');
            for (const [key, value] of Object.entries(filters)) {
                if (value) queryParams.append(key, value);
            }

            window.open(`reservation_reports_api.php?${queryParams.toString()}`, '_blank');
        }

        // View reservation details
        function viewReservation(reservationId) {
            alert(`View reservation details for ID: ${reservationId}\nThis feature can be expanded to show detailed modal or redirect to details page.`);
        }

        // Edit reservation
        function editReservation(reservationId) {
            alert(`Edit reservation ID: ${reservationId}\nThis feature can be expanded to show edit modal or redirect to edit page.`);
        }

        // Delete reservation
        function deleteReservation(reservationId) {
            if (!confirm('Are you sure you want to delete this reservation? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_reservation');
            formData.append('reservation_id', reservationId);

            fetch('reservation_reports_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Reservation deleted successfully');
                    loadReservations();
                    loadStatistics();
                } else {
                    alert('❌ Error deleting reservation: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting reservation:', error);
                alert('❌ Error deleting reservation');
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
