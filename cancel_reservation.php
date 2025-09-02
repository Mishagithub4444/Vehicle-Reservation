<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: user_login.php');
    exit();
}

include 'connection/db.php';

$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($reservation_id <= 0) {
    echo '<script>alert("Invalid reservation."); window.location.href = "user_reservations.php";</script>';
    exit();
}

// Get reservation details to update vehicle status
$get_reservation_sql = "SELECT Vehicle_ID FROM vehicle_reservations WHERE Reservation_ID = ? AND User_ID = ?";
$get_stmt = $conn->prepare($get_reservation_sql);
$get_stmt->bind_param("ii", $reservation_id, $user_id);
$get_stmt->execute();
$get_result = $get_stmt->get_result();

if ($get_result->num_rows === 0) {
    echo '<script>alert("Reservation not found or you do not have permission to cancel it."); window.location.href = "user_reservations.php";</script>';
    exit();
}

$reservation_data = $get_result->fetch_assoc();
$vehicle_id = $reservation_data['Vehicle_ID'];

// Update reservation status to Cancelled
$cancel_sql = "UPDATE vehicle_reservations SET Status = 'Cancelled' WHERE Reservation_ID = ? AND User_ID = ?";
$cancel_stmt = $conn->prepare($cancel_sql);
$cancel_stmt->bind_param("ii", $reservation_id, $user_id);

if ($cancel_stmt->execute()) {
    // Check if any rows were actually affected
    if ($cancel_stmt->affected_rows > 0) {
        // Update vehicle status back to Available
        $update_vehicle_sql = "UPDATE vehicle_registration SET Status = 'Available' WHERE Vehicle_ID = ?";
        $update_stmt = $conn->prepare($update_vehicle_sql);
        $update_stmt->bind_param("s", $vehicle_id);
        
        if ($update_stmt->execute()) {
            echo '<script>
                alert("✅ Reservation cancelled successfully!\\n\\nThe vehicle ' . $vehicle_id . ' is now available for others to reserve.");
                window.location.href = "user_reservations.php";
            </script>';
        } else {
            echo '<script>
                alert("⚠️ Reservation was cancelled but there was an issue updating vehicle availability.\\n\\nPlease contact support if needed.");
                window.location.href = "user_reservations.php";
            </script>';
        }
        $update_stmt->close();
    } else {
        echo '<script>
            alert("❌ No changes were made. The reservation may already be cancelled or does not exist.");
            window.location.href = "user_reservations.php";
        </script>';
    }
} else {
    echo '<script>
        alert("❌ Error cancelling reservation: ' . addslashes($cancel_stmt->error) . '\\n\\nPlease try again or contact support.");
        window.location.href = "user_reservations.php";
    </script>';
}

$get_stmt->close();
$cancel_stmt->close();
$conn->close();
?>
