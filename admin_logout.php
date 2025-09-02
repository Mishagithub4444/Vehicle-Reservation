<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page with success message
echo '<script>alert("Admin logout successful!"); window.location.href = "login.php";</script>';
exit();
?>
