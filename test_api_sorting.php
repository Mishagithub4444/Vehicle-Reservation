<?php
session_start();

// Simulate admin login for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 'TEST001';
$_SESSION['admin_name'] = 'Test Admin';

echo "<h2>User Management API Sorting Test</h2>";

echo "<h3>Test 1: A to Z Sorting</h3>";
// Create form data
$postData = [
    'action' => 'get_all_users',
    'sort' => 'asc'
];

$postString = http_build_query($postData);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                   "Content-Length: " . strlen($postString) . "\r\n",
        'content' => $postString
    ]
]);

$response = file_get_contents('http://localhost:8080/user_management_api.php', false, $context);
$data = json_decode($response, true);

echo "<pre>";
echo "Response: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if ($data['success']) {
    echo "Found " . count($data['users']) . " users\n";
    echo "First 3 users (A to Z):\n";
    for ($i = 0; $i < min(3, count($data['users'])); $i++) {
        $user = $data['users'][$i];
        echo "- " . $user['First_Name'] . " " . $user['Last_Name'] . " (" . $user['User_Name'] . ")\n";
    }
} else {
    echo "Error: " . $data['message'] . "\n";
}
echo "</pre>";

echo "<h3>Test 2: Z to A Sorting</h3>";
// Test DESC sorting
$postData['sort'] = 'desc';
$postString = http_build_query($postData);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                   "Content-Length: " . strlen($postString) . "\r\n",
        'content' => $postString
    ]
]);

$response = file_get_contents('http://localhost:8080/user_management_api.php', false, $context);
$data = json_decode($response, true);

echo "<pre>";
echo "Response: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if ($data['success']) {
    echo "Found " . count($data['users']) . " users\n";
    echo "First 3 users (Z to A):\n";
    for ($i = 0; $i < min(3, count($data['users'])); $i++) {
        $user = $data['users'][$i];
        echo "- " . $user['First_Name'] . " " . $user['Last_Name'] . " (" . $user['User_Name'] . ")\n";
    }
} else {
    echo "Error: " . $data['message'] . "\n";
}
echo "</pre>";
?>
