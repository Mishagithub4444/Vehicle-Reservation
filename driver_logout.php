<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page with success message
echo '<script>alert("You have been successfully logged out!"); window.location.href = "login.html";</script>';
exit();
