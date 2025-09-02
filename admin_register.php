<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - VRMS</title>
    <link rel="stylesheet" href="user_register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Auto-generated field styling */
        input[readonly] {
            background: rgba(34, 197, 94, 0.2) !important;
            border-color: rgba(34, 197, 94, 0.5) !important;
            color: #22c55e !important;
            font-weight: bold;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .password-toggle:hover {
            color: #333;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">VRMS - Admin Portal</div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="vehicle.php">Vehicle</a></li>
                <li><a href="admin_login.php">Admin Login</a></li>
                <li><a href="user_login.php">User Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="register-section">
        <div class="register-box">
            <h2>Admin Registration</h2>
            <form action="admin_register_process.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Admin UserName:</label>
                        <input type="text" name="admin_username" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth:</label>
                        <input type="date" name="dob" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Admin ID:</label>
                        <input type="text" name="admin_id" id="admin_id" pattern="[0-9]{6}"
                            title="Admin ID is auto-generated" placeholder="Auto-generated" maxlength="6" readonly required>
                    </div>
                    <div class="form-group">
                        <label>Gender:</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Phone Number:</label>
                    <input type="tel" name="phone" required>
                </div>

                <div class="form-group full-width">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group full-width">
                    <label>Address:</label>
                    <textarea name="address" rows="2" required></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Super Admin">Super Admin</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Create Password:</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Confirm Password:</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>

                <button type="submit">Register Admin Account</button>
            </form>
            <div class="back-link">
                <a href="index.html">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>

    <script>
        // Auto-generate Admin ID
        function generateAdminId() {
            const adminId = Math.floor(100000 + Math.random() * 900000).toString();
            document.getElementById('admin_id').value = adminId;
        }

        // Generate Admin ID on page load
        document.addEventListener('DOMContentLoaded', function() {
            generateAdminId();
        });

        // Password visibility toggle function
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }

            // Admin ID validation
            const adminId = document.getElementById('admin_id').value;
            if (adminId.length !== 6 || !/^\d{6}$/.test(adminId)) {
                e.preventDefault();
                alert('Admin ID must be exactly 6 digits!');
                return false;
            }

            return true;
        });

        // Real-time validation feedback
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = 'red';
                this.style.backgroundColor = '#ffe6e6';
            } else if (confirmPassword && password === confirmPassword) {
                this.style.borderColor = 'green';
                this.style.backgroundColor = '#e6ffe6';
            } else {
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            }
        });

        // Email format validation
        document.querySelector('input[type="email"]').addEventListener('blur', function() {
            const email = this.value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email && !emailPattern.test(email)) {
                this.style.borderColor = 'red';
                this.style.backgroundColor = '#ffe6e6';
                alert('Please enter a valid email address');
            } else if (email) {
                this.style.borderColor = 'green';
                this.style.backgroundColor = '#e6ffe6';
            }
        });

        // Phone number validation
        document.querySelector('input[type="tel"]').addEventListener('blur', function() {
            const phone = this.value;
            const phonePattern = /^[\+]?[\d\s\-\(\)]{10,}$/;

            if (phone && !phonePattern.test(phone)) {
                this.style.borderColor = 'red';
                this.style.backgroundColor = '#ffe6e6';
                alert('Please enter a valid phone number (at least 10 digits)');
            } else if (phone) {
                this.style.borderColor = 'green';
                this.style.backgroundColor = '#e6ffe6';
            }
        });
    </script>
</body>

</html>