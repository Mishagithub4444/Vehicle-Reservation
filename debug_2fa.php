<?php
// Debug 2FA form submission
echo "<h2>2FA Debug Information</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>2FA Checkbox Status:</h3>";
    if (isset($_POST['enable_2fa'])) {
        echo "✅ enable_2fa checkbox was submitted<br>";
        echo "Value: " . $_POST['enable_2fa'] . "<br>";
        $enable_2fa = ($_POST['enable_2fa'] == '1' || $_POST['enable_2fa'] == 'on');
        echo "Evaluated as: " . ($enable_2fa ? 'TRUE (2FA enabled)' : 'FALSE (2FA disabled)') . "<br>";
    } else {
        echo "❌ enable_2fa checkbox was NOT submitted<br>";
    }
} else {
    echo "<p>No POST data received. Please submit a form to debug.</p>";
}
?>

<form method="POST">
    <h3>Test 2FA Checkbox</h3>
    <label>
        <input type="checkbox" name="enable_2fa" value="1"> Enable 2FA Test
    </label>
    <br><br>
    <input type="text" name="test_field" placeholder="Test field" required>
    <br><br>
    <button type="submit">Test Submit</button>
</form>
