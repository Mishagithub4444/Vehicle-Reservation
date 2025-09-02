<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Vehicle Reservation</title>
    <link rel="stylesheet" href="./user_portal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="user_portal.css" type="text/css">
    <style>
        /* Fallback styles in case CSS file doesn't load - Admin Theme */
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
            max-width: 1200px;
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

        .user-type {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 0 5px;
        }

        .portal-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .profile-section,
        .actions-section,
        .account-status,
        .admin-stats {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-section h2,
        .actions-section h2,
        .account-status h2,
        .admin-stats h2 {
            color: #8b0000;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #333;
            padding: 8px 0;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .badge.admin {
            background: #8b0000;
            color: white;
        }

        .badge.super-admin {
            background: #4b0000;
            color: white;
        }

        .badge.manager {
            background: #6a5acd;
            color: white;
        }

        .badge.supervisor {
            background: #ff6347;
            color: white;
        }

        .badge.active {
            background: #e8f5e8;
            color: #4caf50;
        }

        .badge.high {
            background: #8b0000;
            color: white;
        }

        .badge.medium {
            background: #ff6347;
            color: white;
        }

        .badge.low {
            background: #32cd32;
            color: white;
        }

        .badge.it {
            background: #1e90ff;
            color: white;
        }

        .badge.hr {
            background: #ff69b4;
            color: white;
        }

        .badge.operations {
            background: #ffa500;
            color: white;
        }

        .badge.finance {
            background: #32cd32;
            color: white;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: linear-gradient(135deg, #dc3545, #8b0000);
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .status-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .status-item label {
            font-weight: bold;
            color: #555;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        .admin-stats {
            border-left: 4px solid #8b0000;
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

            .welcome-header h1 {
                font-size: 2rem;
            }

            .portal-content {
                grid-template-columns: 1fr;
            }

            .management-tabs {
                flex-direction: column;
            }

            .tab-btn {
                text-align: center;
                border-bottom: none;
                border-right: 3px solid transparent;
            }

            .tab-btn.active {
                border-right-color: #8b0000;
                border-bottom-color: transparent;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .user-details {
                grid-template-columns: 1fr;
            }

            .user-actions {
                justify-content: center;
                flex-wrap: wrap;
            }

            .search-form input,
            .search-form select {
                width: 100%;
                margin-bottom: 10px;
                margin-right: 0;
            }

            .search-btn {
                width: 100%;
                margin-left: 0;
            }

            #users-table {
                font-size: 0.8rem;
            }

            #users-table th,
            #users-table td {
                padding: 8px 4px;
            }
        }

        /* Update Forms Styles */
        .update-forms-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .update-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: #8b0000;
            border-bottom-color: #8b0000;
            font-weight: bold;
        }

        .tab-btn:hover {
            color: #8b0000;
        }

        .update-form {
            display: none;
            padding: 20px 0;
        }

        .update-form.active {
            display: block;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8b0000;
            box-shadow: 0 0 5px rgba(139, 0, 0, 0.3);
        }

        .search-btn {
            background: #8b0000;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .search-btn:hover {
            background: #660000;
        }

        .update-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .update-btn:hover {
            background: #45a049;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }

        /* User Management Styles */
        .management-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .management-tabs {
            display: flex;
            border-bottom: 2px solid #f1f1f1;
            margin-bottom: 20px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab-btn.active {
            color: #8b0000;
            border-bottom-color: #8b0000;
        }

        .tab-btn:hover {
            color: #8b0000;
            background: #f8f9fa;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-container {
            overflow-x: auto;
        }

        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .refresh-btn:hover {
            background: #218838;
        }

        #users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #users-table th,
        #users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        #users-table th {
            background: #8b0000;
            color: white;
            font-weight: bold;
        }

        #users-table tr:hover {
            background: #f5f5f5;
        }

        .user-actions {
            display: flex;
            gap: 5px;
        }

        .view-btn,
        .edit-btn,
        .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
        }

        .edit-btn {
            background: #ffc107;
            color: black;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .view-btn:hover,
        .edit-btn:hover,
        .delete-btn:hover {
            opacity: 0.8;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-form .form-group {
            margin-bottom: 15px;
        }

        .search-form .form-group:last-child {
            margin-bottom: 0;
        }

        .search-form input,
        .search-form select {
            width: calc(100% - 120px);
            margin-right: 10px;
        }

        #search-results {
            min-height: 200px;
        }

        .user-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .user-card h4 {
            color: #8b0000;
            margin-bottom: 15px;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 10px;
        }

        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .user-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .user-detail-item label {
            font-weight: bold;
            color: #555;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #8b0000, #dc3545);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.2);
        }

        .stat-card h4 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .close-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="vehicle_portal.php?admin_access=true">Vehicle</a></li>
                <li><a href="admin_portal.php" class="active">Admin Portal</a></li>
                <li><a href="login.html">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="portal-section">
        <div class="portal-container">
            <div class="welcome-header">
                <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</h1>
                <p>Role: <span class="user-type"><?php echo htmlspecialchars($_SESSION['role']); ?></span> |
                    Level: <span class="user-type"><?php echo htmlspecialchars($_SESSION['admin_level']); ?></span> |
                    Department: <span class="user-type"><?php echo htmlspecialchars($_SESSION['department']); ?></span></p>
            </div>

            <div class="portal-content">
                <div class="profile-section">
                    <h2>Admin Profile Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>First Name:</label>
                            <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Last Name:</label>
                            <span><?php echo htmlspecialchars($_SESSION['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Admin Name:</label>
                            <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Admin ID:</label>
                            <span><?php echo htmlspecialchars($_SESSION['admin_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone Number:</label>
                            <span><?php echo htmlspecialchars($_SESSION['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Date of Birth:</label>
                            <span><?php echo htmlspecialchars($_SESSION['dob']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Gender:</label>
                            <span><?php echo htmlspecialchars($_SESSION['gender']); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Address:</label>
                            <span><?php echo htmlspecialchars($_SESSION['address']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Role:</label>
                            <span class="badge <?php echo strtolower($_SESSION['role']); ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Admin Level:</label>
                            <span class="badge <?php echo strtolower($_SESSION['admin_level']); ?>"><?php echo htmlspecialchars($_SESSION['admin_level']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Department:</label>
                            <span class="badge <?php echo strtolower($_SESSION['department']); ?>"><?php echo htmlspecialchars($_SESSION['department']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="actions-section">
                    <h2>Admin Control Panel</h2>
                    <div class="action-buttons">
                        <a href="user_management.php?admin_access=true" class="action-btn">User Management</a>
                        <a href="driver_management.php?admin_access=true" class="action-btn">Driver Management</a>
                        <a href="vehicle_portal.php?admin_access=true" class="action-btn">Vehicle Management</a>
                        <a href="reservation_reports.php" class="action-btn">Reservation Reports</a>
                    </div>
                </div>

                <div class="account-status">
                    <h2>Admin Status & Permissions</h2>
                    <div class="status-info">
                        <div class="status-item">
                            <label>Admin Role:</label>
                            <span class="badge <?php echo strtolower($_SESSION['role']); ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>Access Level:</label>
                            <span class="badge <?php echo strtolower($_SESSION['admin_level']); ?>"><?php echo htmlspecialchars($_SESSION['admin_level']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>Department:</label>
                            <span class="badge <?php echo strtolower($_SESSION['department']); ?>"><?php echo htmlspecialchars($_SESSION['department']); ?></span>
                        </div>
                        <div class="status-item">
                            <label>Status:</label>
                            <span class="badge active">Active</span>
                        </div>
                        <div class="status-item">
                            <label>Last Login:</label>
                            <span>Today</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>

    <script>
        // Tab functionality
        function showUpdateForm(formType) {
            // Hide all forms
            document.querySelectorAll('.update-form').forEach(form => {
                form.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected form
            document.getElementById(formType + '-update-form').classList.add('active');

            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Load user data
        function loadUserData() {
            const userId = document.getElementById('user_id').value;
            if (!userId) {
                alert('Please enter a User ID');
                return;
            }

            fetch('admin_load_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `type=user&id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('user_fname').value = data.data.First_Name || '';
                        document.getElementById('user_lname').value = data.data.Last_Name || '';
                        document.getElementById('user_email').value = data.data.Email || '';
                        document.getElementById('user_phone').value = data.data.Phone_Number || '';
                    } else {
                        alert('User not found: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user data');
                });
        }

        // User Management Functions
        function showSection(sectionId) {
            // Hide all management sections
            document.querySelectorAll('.management-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                if (sectionId === 'user-management') {
                    loadAllUsers();
                    loadUserStats();
                }
            }
        }

        function closeSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'none';
            }
        }

        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId).classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');

            // Load data based on tab
            if (tabId === 'view-users') {
                loadAllUsers();
            } else if (tabId === 'user-stats') {
                loadUserStats();
            }
        }

        function loadAllUsers() {
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading users...</td></tr>';

            fetch('get_all_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        tbody.innerHTML = '';
                        data.users.forEach(user => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${user.User_ID}</td>
                                <td>${user.First_Name} ${user.Last_Name}</td>
                                <td>${user.Email}</td>
                                <td>${user.Phone_Number}</td>
                                <td>${user.User_Type}</td>
                                <td><span class="status-badge status-active">Active</span></td>
                                <td class="user-actions">
                                    <button class="view-btn" onclick="viewUser(${user.User_ID})">View</button>
                                    <button class="edit-btn" onclick="editUser(${user.User_ID})">Edit</button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No users found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = '<tr><td colspan="7" class="no-data">Error loading users</td></tr>';
                });
        }

        function searchUser() {
            const searchType = document.getElementById('search-type').value;
            const searchValue = document.getElementById('search-value').value.trim();

            if (!searchValue) {
                alert('Please enter a search value');
                return;
            }

            const resultsContainer = document.getElementById('search-results');
            resultsContainer.innerHTML = '<div class="loading">Searching...</div>';

            fetch('search_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `search_type=${searchType}&search_value=${encodeURIComponent(searchValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        resultsContainer.innerHTML = '';
                        data.users.forEach(user => {
                            const userCard = document.createElement('div');
                            userCard.className = 'user-card';
                            userCard.innerHTML = `
                                <h4>User ID: ${user.User_ID} - ${user.First_Name} ${user.Last_Name}</h4>
                                <div class="user-details">
                                    <div class="user-detail-item">
                                        <label>Email:</label>
                                        <span>${user.Email}</span>
                                    </div>
                                    <div class="user-detail-item">
                                        <label>Phone:</label>
                                        <span>${user.Phone_Number}</span>
                                    </div>
                                    <div class="user-detail-item">
                                        <label>User Type:</label>
                                        <span>${user.User_Type}</span>
                                    </div>
                                    <div class="user-detail-item">
                                        <label>Date of Birth:</label>
                                        <span>${user.Date_of_Birth}</span>
                                    </div>
                                    <div class="user-detail-item">
                                        <label>Gender:</label>
                                        <span>${user.Gender}</span>
                                    </div>
                                    <div class="user-detail-item">
                                        <label>Address:</label>
                                        <span>${user.Address}</span>
                                    </div>
                                </div>
                                <div class="user-actions" style="margin-top: 15px;">
                                    <button class="view-btn" onclick="viewUser(${user.User_ID})">View Details</button>
                                    <button class="edit-btn" onclick="editUser(${user.User_ID})">Edit User</button>
                                </div>
                            `;
                            resultsContainer.appendChild(userCard);
                        });
                    } else {
                        resultsContainer.innerHTML = '<div class="no-data">No users found matching your search criteria</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsContainer.innerHTML = '<div class="no-data">Error performing search</div>';
                });
        }

        function loadUserStats() {
            fetch('get_user_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-users-count').textContent = data.stats.total_users;
                        document.getElementById('premium-users-count').textContent = data.stats.premium_users;
                        document.getElementById('regular-users-count').textContent = data.stats.regular_users;
                        document.getElementById('recent-registrations-count').textContent = data.stats.recent_registrations;
                    } else {
                        console.error('Error loading user stats:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function viewUser(userId) {
            // Redirect to user details or open modal
            alert(`Viewing user details for User ID: ${userId}`);
            // You can implement a modal or redirect to user details page
        }

        function editUser(userId) {
            // Redirect to user edit form or open modal
            alert(`Editing user with User ID: ${userId}`);
            // You can implement user editing functionality
        }

        // Load driver data
        function loadDriverData() {
            const driverId = document.getElementById('driver_id').value;
            if (!driverId) {
                alert('Please enter a Driver ID');
                return;
            }

            fetch('admin_load_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `type=driver&id=${driverId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('driver_fname').value = data.data.First_Name || '';
                        document.getElementById('driver_lname').value = data.data.Last_Name || '';
                        document.getElementById('driver_email').value = data.data.Email || '';
                        document.getElementById('driver_phone').value = data.data.Phone_Number || '';
                        document.getElementById('driver_license').value = data.data.License_Number || '';
                        document.getElementById('driver_status').value = data.data.Status || '';
                    } else {
                        alert('Driver not found: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading driver data');
                });
        }

        // Load vehicle data
        function loadVehicleData() {
            const vehicleId = document.getElementById('vehicle_id').value;
            if (!vehicleId) {
                alert('Please enter a Vehicle ID');
                return;
            }

            fetch('admin_load_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `type=vehicle&id=${vehicleId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('vehicle_name').value = data.data.Vehicle_Name || '';
                        document.getElementById('vehicle_type').value = data.data.Vehicle_Type || '';
                        document.getElementById('vehicle_model').value = data.data.Vehicle_Model || '';
                        document.getElementById('license_plate').value = data.data.License_Plate || '';
                        document.getElementById('vehicle_status').value = data.data.Available || '';
                    } else {
                        alert('Vehicle not found: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading vehicle data');
                });
        }

        // Update user profile
        function updateUserProfile(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('type', 'user');

            fetch('admin_update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User profile updated successfully!');
                        event.target.reset();
                    } else {
                        alert('Error updating user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating user profile');
                });
        }

        // Update driver profile
        function updateDriverProfile(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('type', 'driver');

            fetch('admin_update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Driver profile updated successfully!');
                        event.target.reset();
                    } else {
                        alert('Error updating driver: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating driver profile');
                });
        }

        // Update vehicle profile
        function updateVehicleProfile(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('type', 'vehicle');

            fetch('admin_update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Vehicle profile updated successfully!');
                        event.target.reset();
                    } else {
                        alert('Error updating vehicle: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating vehicle profile');
                });
        }
    </script>
</body>

</html>