<?php
session_start();

// If driver is already logged in, redirect to driver portal
if (isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true) {
    header('Location: driver_portal.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login - Vehicle Reservation</title>
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

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 10;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        nav a:hover,
        nav a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .login-section {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s ease;
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .login-icon i {
            font-size: 2rem;
            color: white;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #34495e;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-group input {
            width: 100%;
            padding: 1rem;
            padding-left: 3rem;
            border: 2px solid rgba(52, 73, 94, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            color: #2c3e50;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 3rem;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 3rem;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-link {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(52, 73, 94, 0.1);
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #764ba2;
        }

        /* Loading and success states */
        .login-box.loading {
            transform: scale(0.98);
            opacity: 0.8;
        }

        .login-box.success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(46, 204, 113, 0.1));
            border-color: #27ae60;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .login-section {
                padding: 1rem;
            }

            .login-box {
                padding: 2rem;
                max-width: 100%;
            }

            nav ul {
                flex-direction: column;
                gap: 0.5rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 1.5rem;
                border-radius: 15px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .login-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 1.5rem;
            }

            .login-icon i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">Vehicle Reserve</a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="vehicle.php">Vehicle</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="driver_login.php" class="active">Driver Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="login-section">
        <div class="login-box">
            <div class="login-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h2>Driver Login</h2>
            <form action="driver_login_process.php" method="POST" id="loginForm">
                <div class="input-group">
                    <label for="driver_username">Driver Username</label>
                    <input type="text" name="driver_username" id="driver_username" placeholder="Enter your username"
                        required>
                    <i class="fas fa-user"></i>
                </div>
                <div class="input-group">
                    <label for="driver_id">Driver ID</label>
                    <input type="text" name="driver_id" id="driver_id" placeholder="Enter your Driver ID" required>
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            <div class="register-link">
                <p>Don't have an account? <a href="driver_register.html">Register as Driver</a></p>
            </div>
        </div>
    </section>

    <script>
        // Password visibility toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });

        // Add form submission animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const loginBox = document.querySelector('.login-box');

            // Add loading state
            loginBox.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';

            // Remove loading state after a short delay (for demonstration)
            setTimeout(() => {
                loginBox.classList.remove('loading');
                loginBox.classList.add('success');
            }, 1000);
        });

        // Add input focus animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateX(5px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>

</html>