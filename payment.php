<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vrms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX request for reservation details
if (isset($_POST['action']) && $_POST['action'] == 'get_reservation_amount') {
    $reservation_id = $_POST['reservation_id'];
    
    // Query to get reservation amount - checking multiple possible tables
    $query = "SELECT Total_Cost as amount FROM vehicle_reservations WHERE Reservation_ID = ? 
              UNION 
              SELECT total_cost as amount FROM reservations WHERE id = ?
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $reservation_id, $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'amount' => $row['amount']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Get reservation details if coming from reservation
$reservation_id = isset($_GET['reservation_id']) ? $_GET['reservation_id'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '';

// Generate shorter unique payment ID
$payment_id = 'PAY' . date('ymd') . rand(100, 999);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Vehicle Reservation</title>
    <link rel="stylesheet" href="payment.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4a90e2;
            text-decoration: none;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        nav a:hover,
        nav a.active {
            color: #4a90e2;
            background: rgba(74, 144, 226, 0.1);
        }

        .payment-section {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding: 2rem;
        }

        .payment-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .payment-header h2 {
            color: #4a90e2;
            font-size: 1.6rem;
            font-weight: 300;
            margin-bottom: 0.4rem;
        }

        .payment-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .payment-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            color: white;
            font-size: 1.3rem;
        }

        .input-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #4a90e2;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            color: #333;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            background: white;
        }

        .input-group input[readonly] {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #6c757d;
            cursor: not-allowed;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 0.4rem;
            margin-bottom: 0.8rem;
        }

        .method-option {
            padding: 0.6rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .method-option:hover {
            border-color: #4a90e2;
            background: rgba(74, 144, 226, 0.05);
        }

        .method-option.selected {
            border-color: #4a90e2;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
        }

        .button-group {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }

        .btn {
            flex: 1;
            padding: 0.8rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #357abd, #2968a3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 144, 226, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .bottom-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid #e0e0e0;
        }

        .link-btn {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .link-btn:hover {
            background: rgba(74, 144, 226, 0.1);
            color: #357abd;
        }

        .amount-display {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 0.8rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1.2rem;
        }

        .amount-display .currency {
            font-size: 1.6rem;
            font-weight: 300;
        }

        .amount-display .label {
            font-size: 0.8rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .payment-section {
                padding: 1rem;
            }

            .payment-container {
                padding: 1.2rem;
                max-width: 100%;
            }

            .button-group {
                flex-direction: column;
                gap: 0.6rem;
            }

            nav ul {
                display: none;
            }

            .payment-header h2 {
                font-size: 1.4rem;
            }

            .payment-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }

        /* Loading animation */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        /* Small colorful back button */
        .btn-back {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #ff5252, #f44336);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-back:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
    </style>
</head>

<body>
    <header>
        <div class="header-content">
            <div class="logo">Vehicle Reserve</div>
            <nav>
                <ul>
                    <li><a href="index.html">🏠 Home</a></li>
                    <li><a href="vehicle.html">🚗 Vehicle</a></li>
                    <li><a href="user_portal.php">👤 Portal</a></li>
                    <li><a href="payment.php" class="active">💳 Payment</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="payment-section">
        <div class="payment-container">
            <div class="payment-header">
                <div class="payment-icon">💳</div>
                <h2>Secure Payment</h2>
                <p>Complete your vehicle reservation payment</p>
            </div>

            <?php if (!empty($amount)): ?>
                <div class="amount-display">
                    <div class="label">Total Amount</div>
                    <div class="currency">৳<?php echo number_format((float)$amount, 2); ?></div>
                </div>
            <?php endif; ?>

            <form action="payment_process.php" method="POST" id="paymentForm">
                <div class="input-group">
                    <label>💼 Payment ID</label>
                    <input type="text" name="payment_id" value="<?php echo $payment_id; ?>" readonly>
                </div>

                <div class="input-group">
                    <label>🎫 Reservation ID</label>
                    <input type="text" name="reservation_id" value="<?php echo htmlspecialchars($reservation_id); ?>" required placeholder="Enter your reservation ID">
                </div>

                <div class="input-group">
                    <label>💰 Total Amount (৳)</label>
                    <input type="number" step="0.01" name="total_amount" value="<?php echo htmlspecialchars($amount); ?>" required placeholder="0.00" min="0">
                </div>

                <div class="input-group">
                    <label>💳 Payment Method</label>
                    <div class="payment-methods">
                        <div class="method-option" onclick="selectPaymentMethod('BKash', this)">
                            📱 BKash
                        </div>
                        <div class="method-option" onclick="selectPaymentMethod('Nagad', this)">
                            📱 Nagad
                        </div>
                        <div class="method-option" onclick="selectPaymentMethod('Rocket', this)">
                            🚀 Rocket
                        </div>
                        <div class="method-option" onclick="selectPaymentMethod('Credit Card', this)">
                            💳 Credit
                        </div>
                        <div class="method-option" onclick="selectPaymentMethod('Debit Card', this)">
                            💳 Debit
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="selectedMethod" required>
                </div>

                <div class="input-group">
                    <label>📅 Payment Date</label>
                    <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>" readonly>
                </div>

                <div class="button-group">
                    <button type="submit" name="confirm_payment" class="btn btn-success">
                        ✅ Confirm Payment
                    </button>
                </div>

                <div class="bottom-links">
                    <button type="button" onclick="javascript:history.back()" class="btn-back">← Back</button>
                </div>
            </form>
        </div>
    </section>

    <script>
        function selectPaymentMethod(method, element) {
            // Remove selected class from all options
            document.querySelectorAll('.method-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            element.classList.add('selected');

            // Set hidden input value
            document.getElementById('selectedMethod').value = method;
        }

        // Form validation and animation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.getElementById('selectedMethod').value;

            if (!paymentMethod) {
                e.preventDefault();
                alert('⚠️ Please select a payment method before submitting.');
                return;
            }

            // Add loading animation
            const submitBtns = document.querySelectorAll('.btn');
            submitBtns.forEach(btn => {
                btn.classList.add('loading');
                btn.disabled = true;
            });

            // Show confirmation
            const amount = document.querySelector('input[name="total_amount"]').value;
            const reservationId = document.querySelector('input[name="reservation_id"]').value;

            if (!confirm(`🔐 Confirm Payment\n\n💰 Amount: ৳${amount}\n🎫 Reservation: ${reservationId}\n💳 Method: ${paymentMethod}\n\nProceed with payment?`)) {
                e.preventDefault();
                submitBtns.forEach(btn => {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                });
            }
        });

        // Auto-fetch amount when reservation ID is entered with debouncing
        let reservationTimeout;
        document.querySelector('input[name="reservation_id"]').addEventListener('input', function(e) {
            const reservationId = e.target.value.trim();
            const amountInput = document.querySelector('input[name="total_amount"]');
            
            console.log('Reservation ID entered:', reservationId); // Debug log
            
            // Clear previous timeout
            clearTimeout(reservationTimeout);
            
            if (reservationId.length >= 1) {
                // Add loading indicator immediately
                amountInput.placeholder = 'Loading...';
                amountInput.style.backgroundColor = '#f8f9fa';
                
                // Debounce the API call - wait 500ms after user stops typing
                reservationTimeout = setTimeout(() => {
                    console.log('Fetching amount for reservation:', reservationId); // Debug log
                    
                    // Fetch reservation amount via AJAX
                    fetch('payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_reservation_amount&reservation_id=${encodeURIComponent(reservationId)}`
                    })
                    .then(response => {
                        console.log('Response received:', response); // Debug log
                        return response.json();
                    })
                    .then(data => {
                        console.log('Data received:', data); // Debug log
                        if (data.success) {
                            amountInput.value = parseFloat(data.amount).toFixed(2);
                            amountInput.style.backgroundColor = '#d4edda'; // Light green success color
                            amountInput.placeholder = '0.00';
                            
                            // Update amount display if it exists
                            const amountDisplay = document.querySelector('.amount-display .currency');
                            if (amountDisplay) {
                                amountDisplay.textContent = '৳' + parseFloat(data.amount).toFixed(2);
                            }
                            
                            // Show success feedback
                            showNotification('✅ Amount loaded successfully!', 'success');
                        } else {
                            amountInput.style.backgroundColor = '#f8d7da'; // Light red error color
                            amountInput.placeholder = 'Reservation not found';
                            showNotification('❌ ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        amountInput.style.backgroundColor = '#f8d7da';
                        amountInput.placeholder = 'Error loading amount';
                        showNotification('❌ Error loading reservation details', 'error');
                    });
                }, 500); // Wait 500ms after user stops typing
            } else {
                // Reset amount field when reservation ID is empty
                amountInput.value = '';
                amountInput.style.backgroundColor = '';
                amountInput.placeholder = '0.00';
            }
        });

        // Also trigger on blur (when user clicks away from field)
        document.querySelector('input[name="reservation_id"]').addEventListener('blur', function(e) {
            const reservationId = e.target.value.trim();
            const amountInput = document.querySelector('input[name="total_amount"]');
            
            if (reservationId.length >= 1 && amountInput.value === '') {
                // Only fetch if amount field is empty
                console.log('Fetching amount on blur for reservation:', reservationId);
                
                amountInput.placeholder = 'Loading...';
                amountInput.style.backgroundColor = '#f8f9fa';
                
                fetch('payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_reservation_amount&reservation_id=${encodeURIComponent(reservationId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        amountInput.value = parseFloat(data.amount).toFixed(2);
                        amountInput.style.backgroundColor = '#d4edda';
                        amountInput.placeholder = '0.00';
                        showNotification('✅ Amount loaded successfully!', 'success');
                    } else {
                        amountInput.style.backgroundColor = '#f8d7da';
                        amountInput.placeholder = 'Reservation not found';
                        showNotification('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    amountInput.style.backgroundColor = '#f8d7da';
                    amountInput.placeholder = 'Error loading amount';
                    showNotification('❌ Error loading reservation details', 'error');
                });
            }
        });

        // Notification function
        function showNotification(message, type) {
            // Remove existing notification if any
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
                ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Auto-format amount input
        document.querySelector('input[name="total_amount"]').addEventListener('input', function(e) {
            const value = parseFloat(e.target.value);
            if (!isNaN(value) && value >= 0) {
                // Update the amount display if it exists
                const amountDisplay = document.querySelector('.amount-display .currency');
                if (amountDisplay) {
                    amountDisplay.textContent = '৳' + value.toFixed(2);
                }
            }
        });

        // Animate on page load
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.payment-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';

            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>