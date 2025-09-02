<?php
// Create diverse sample notifications for testing
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'vrms';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get first user ID
$user_query = "SELECT User_ID FROM user_registration ORDER BY User_ID ASC LIMIT 1";
$user_result = $conn->query($user_query);

if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_id = $user['User_ID'];
    
    // Sample notifications
    $notifications = [
        [
            'title' => '🎉 Welcome to VRMS!',
            'message' => 'Your account has been successfully created. Start exploring our vehicle rental services!',
            'type' => 'success',
            'priority' => 'high'
        ],
        [
            'title' => '🚗 New Vehicle Available',
            'message' => 'A Toyota Camry 2023 is now available for rent in your area.',
            'type' => 'info',
            'priority' => 'medium'
        ],
        [
            'title' => '⚠️ Payment Reminder',
            'message' => 'Your rental payment for Reservation #R001 is due tomorrow.',
            'type' => 'warning',
            'priority' => 'high'
        ],
        [
            'title' => '✅ Reservation Confirmed',
            'message' => 'Your reservation for Honda Civic has been confirmed for tomorrow 10:00 AM.',
            'type' => 'reservation',
            'priority' => 'medium'
        ],
        [
            'title' => '💳 Payment Successful',
            'message' => 'Payment of $150 has been processed successfully for your rental.',
            'type' => 'payment',
            'priority' => 'medium'
        ],
        [
            'title' => '🔧 System Maintenance',
            'message' => 'Scheduled maintenance will occur tonight from 2-4 AM. Service may be temporarily unavailable.',
            'type' => 'system',
            'priority' => 'low'
        ]
    ];
    
    $success_count = 0;
    foreach ($notifications as $notification) {
        $query = "INSERT INTO Notification (User_ID, User_Type, Title, Message, Type, Priority, Created_At) 
                  VALUES (?, 'user', ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $user_id, $notification['title'], $notification['message'], $notification['type'], $notification['priority']);
        
        if ($stmt->execute()) {
            $success_count++;
        }
    }
    
    echo "✅ Created $success_count sample notifications for User ID: $user_id\n";
    echo "🔗 Visit: http://localhost/VRMS/user_portal.php to see notifications\n";
    
} else {
    echo "❌ No users found. Please create a user account first.\n";
}

$conn->close();
?>
