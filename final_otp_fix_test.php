<!DOCTYPE html>
<html>
<head>
    <title>Final OTP Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0; color: #155724; }
        .error { background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0; color: #721c24; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 15px 0; color: #0c5460; }
        .test-btn { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<h1>🔧 Final Fix for "Invalid or expired verification code"</h1>

<div class="success">
    <h3>✅ Applied Fixes:</h3>
    <ul>
        <li><strong>Data Type Consistency:</strong> Ensured user_id is always handled as string in both storage and verification</li>
        <li><strong>Session Data Fix:</strong> Updated user_data in session to have string user_id</li>
        <li><strong>Input Sanitization:</strong> Added trimming and validation for OTP input</li>
        <li><strong>Debug Logging:</strong> Enhanced error logging for troubleshooting</li>
        <li><strong>Bind Param Fix:</strong> Fixed reference passing issues</li>
    </ul>
</div>

<div class="info">
    <h3>🧪 Complete Test Process</h3>
    <p>Follow these steps to test the fix:</p>
    <ol>
        <li><strong>Clear Browser Data:</strong> Clear cookies/session (or use incognito mode)</li>
        <li><strong>Test Login:</strong> Go to user login and try the 2FA process</li>
        <li><strong>Check Logs:</strong> Look at PHP error logs for debug information</li>
    </ol>
</div>

<div style="text-align: center; margin: 30px 0;">
    <h2>🚀 Start Fresh Test</h2>
    <button class="test-btn" onclick="window.open('user_login.php', '_blank')">Open User Login</button>
    <button class="test-btn" onclick="window.open('debug_otp_detailed.php', '_blank')">Open Debug Tool</button>
    <button class="test-btn" onclick="window.open('test_complete_fix.php', '_blank')">Run Complete Test</button>
</div>

<div class="info">
    <h3>📋 Test Credentials</h3>
    <p><strong>Username:</strong> john_doe</p>
    <p><strong>User ID:</strong> 1001</p>
    <p><strong>Password:</strong> password123</p>
    <p><strong>✅ Check:</strong> "Sign In With 2FA (Enhanced Security)" checkbox</p>
</div>

<div class="success">
    <h3>🎯 Expected Behavior</h3>
    <ol>
        <li>Login form submits successfully with 2FA checkbox checked</li>
        <li>Alert shows with 6-digit OTP code</li>
        <li>Verification page loads correctly</li>
        <li>Entering the correct OTP proceeds to user portal</li>
        <li><strong>NO MORE "Invalid or expired verification code" error!</strong></li>
    </ol>
</div>

<?php
// Clear any lingering session data to ensure fresh test
session_start();
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'pending') !== false || strpos($key, 'temp') !== false || strpos($key, 'verification') !== false) {
        unset($_SESSION[$key]);
    }
}

echo "<div class='info'>";
echo "<h3>🔄 Session Cleared</h3>";
echo "<p>All pending verification sessions have been cleared for fresh testing.</p>";
echo "</div>";
?>

<div class="error">
    <h3>🚨 If Still Getting Error</h3>
    <p>If you still see "Invalid or expired verification code", please:</p>
    <ol>
        <li>Check PHP error logs for specific error messages</li>
        <li>Use the debug tool to see exact OTP data</li>
        <li>Ensure you're entering the OTP exactly as shown</li>
        <li>Try in a fresh browser session/incognito mode</li>
        <li>Verify database connection is working</li>
    </ol>
</div>

</body>
</html>
