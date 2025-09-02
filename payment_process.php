<?php
session_start();
include 'connection/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_id = trim($_POST['payment_id']);
    $reservation_id = trim($_POST['reservation_id']);
    $total_amount = floatval($_POST['total_amount']);
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : 'Completed';
    $payment_method = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];

    // Create payments table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(50) UNIQUE NOT NULL,
        reservation_id INT NOT NULL,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_status VARCHAR(20) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES vehicle_reservations(Reservation_ID)
    )";
    $conn->query($create_table_sql);

    // Get user ID from session (if logged in) or set to 0 for guest payments
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    // Check if payment ID already exists, if so generate a new one
    $check_payment_id = "SELECT payment_id FROM payments WHERE payment_id = ?";
    $check_stmt = $conn->prepare($check_payment_id);
    $check_stmt->bind_param("s", $payment_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    // If payment ID exists, generate a new unique one
    while ($result->num_rows > 0) {
        $payment_id = 'PAY' . date('ymd') . rand(100, 999);
        $check_stmt->bind_param("s", $payment_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
    }
    $check_stmt->close();

    // Insert payment record
    $insert_sql = "INSERT INTO payments (payment_id, reservation_id, user_id, total_amount, payment_status, payment_method, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("siidsss", $payment_id, $reservation_id, $user_id, $total_amount, $payment_status, $payment_method, $payment_date);

    if ($stmt->execute()) {
        // Update reservation status to 'Paid'
        $update_res_sql = "UPDATE vehicle_reservations SET Status = 'Paid' WHERE Reservation_ID = ?";
        $update_res_stmt = $conn->prepare($update_res_sql);
        $update_res_stmt->bind_param("i", $reservation_id);
        $update_res_stmt->execute();
        $update_res_stmt->close();

        echo '<script>
            alert("Payment recorded successfully!\\n\\nPayment ID: ' . $payment_id . '\\nAmount: $' . number_format($total_amount, 2) . '\\nStatus: ' . $payment_status . '");
            window.location.href = "user_payment_history.php";
        </script>';
    } else {
        echo '<script>
            alert("Error recording payment. Please try again.");
            window.history.back();
        </script>';
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: payment.php');
    exit();
}
