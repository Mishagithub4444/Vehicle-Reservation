<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration - Vehicle Reservation</title>
    <link rel="stylesheet" href="driver_register.css">
</head>

<body>
    <header>
        <div class="logo">Vehicle Reserve</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Vehicle</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.html">Register</a></li>
            </ul>
        </nav>
    </header>

    <section class="register-section">
        <div class="register-box">
            <h2>Driver Registration</h2>
            <form action="driver_register_process.php" method="POST">
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
                        <label>Driver UserName:</label>
                        <input type="text" name="driver_username" required>
                    </div>
                    <div class="form-group">
                        <label>Driver ID:</label>
                        <input type="text" name="driver_id" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>License Number:</label>
                        <input type="text" name="license_number" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth:</label>
                        <input type="date" name="dob" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <input type="tel" name="phone" required>
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
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group full-width">
                    <label>Address:</label>
                    <textarea name="address" rows="2" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Availability:</label>
                        <select name="availability" required>
                            <option value="">Select Availability</option>
                            <option value="Available">Available</option>
                            <option value="Busy">Busy</option>
                            <option value="Off Duty">Off Duty</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Create Password:</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group full-width">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit">Register</button>
            </form>
            <div class="back-link">
                <a href="register.html">Back</a>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2025 Vehicle Reservation Management System. All rights reserved.</p>
    </footer>
</body>

</html>