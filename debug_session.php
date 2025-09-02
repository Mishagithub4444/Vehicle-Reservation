<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<h3>All Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Specific Session Variables:</h3>";
echo "<ul>";
echo "<li><strong>driver_logged_in:</strong> " . (isset($_SESSION['driver_logged_in']) ? ($_SESSION['driver_logged_in'] ? 'true' : 'false') : 'not set') . "</li>";
echo "<li><strong>admin_logged_in:</strong> " . (isset($_SESSION['admin_logged_in']) ? ($_SESSION['admin_logged_in'] ? 'true' : 'false') : 'not set') . "</li>";
echo "<li><strong>driver_id:</strong> " . ($_SESSION['driver_id'] ?? 'not set') . "</li>";
echo "<li><strong>first_name:</strong> " . ($_SESSION['first_name'] ?? 'not set') . "</li>";
echo "<li><strong>last_name:</strong> " . ($_SESSION['last_name'] ?? 'not set') . "</li>";
echo "<li><strong>email:</strong> " . ($_SESSION['email'] ?? 'not set') . "</li>";
echo "<li><strong>role:</strong> " . ($_SESSION['role'] ?? 'not set') . "</li>";
echo "</ul>";

echo "<h3>Access Checks:</h3>";
$is_admin_access = isset($_GET['admin_access']) && $_GET['admin_access'] === 'true' &&
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_driver_access = isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true;

echo "<ul>";
echo "<li><strong>is_admin_access:</strong> " . ($is_admin_access ? 'true' : 'false') . "</li>";
echo "<li><strong>is_driver_access:</strong> " . ($is_driver_access ? 'true' : 'false') . "</li>";
echo "<li><strong>GET admin_access:</strong> " . ($_GET['admin_access'] ?? 'not set') . "</li>";
echo "</ul>";

echo "<h3>Navigation Links:</h3>";
echo "<p><a href='driver_portal.php'>Back to Driver Portal</a></p>";
echo "<p><a href='driver_earnings_report.php'>Try Earnings Report</a></p>";
echo "<p><a href='driver_login.php'>Driver Login</a></p>";
?>
