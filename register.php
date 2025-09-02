<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Vehicle Reservation</title>
    <link rel="stylesheet" href="user_login.css">
    <style>
        .register-section {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 85vh;
            text-align: center;
        }
        .register-box {
            background-color: rgba(0, 0, 0, 0.8);
            padding: 40px 30px;
            border-radius: 10px;
            width: 370px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.3);
        }
        .register-box h2 {
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .register-buttons {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
        }
        .register-buttons a button {
            width: 100%;
            padding: 14px;
            background-color: #ffcc00;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            color: #000;
            transition: background 0.3s;
        }
        .register-buttons a button:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Vehicle</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="active">Register</a></li>
                <!-- Removed User Login, Driver Login, Admin Panel from navigation -->
            </ul>
        </nav>
    </header>

    <section class="register-section">
        <div class="register-box">
            <h2>Register As</h2>
            <div class="register-buttons">
                <a href="user_register.php"><button>User</button></a>
                <a href="driver_register.php"><button>Driver</button></a>
                <a href="admin_register.php"><button>Admin</button></a>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>
</body>
</html>