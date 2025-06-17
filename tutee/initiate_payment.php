<?php
require_once '../bootstrap.php';
require_once '../config/database.php';
require_once '../config/mpesa.php';
require_role('tutee');

use App\Payments\Daraja;

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get session ID from URL
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

// Get session details
$stmt = $conn->prepare("
    SELECT 
        s.*, 
        t.first_name as tutor_first_name, 
        t.last_name as tutor_last_name,
        tp.hourly_rate,
        u.first_name as tutee_first_name, 
        u.last_name as tutee_last_name,
        a.start_time,
        a.end_time
    FROM sessions s
    JOIN users t ON s.tutor_id = t.id
    JOIN tutor_profiles tp ON tp.user_id = t.id
    JOIN users u ON s.tutee_id = u.id
    JOIN availability_slots a ON s.slot_id = a.id
    WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if session exists and belongs to current user
if (!$session || $session['tutee_id'] != $_SESSION['user_id']) {
    header("Location: ../dashboard.php");
    exit();
}

// Calculate total amount
$duration = strtotime($session['end_time']) - strtotime($session['start_time']);
$total_hours = ceil($duration / 3600); // Round up to next hour
$total_amount = $total_hours * $session['hourly_rate'];

// Process payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $phone_number = $_POST['phone_number'];
        
        // Validate phone number
        if (!preg_match("/^254[0-9]{9}$/", $phone_number)) {
            throw new Exception("Please enter a valid Kenyan phone number starting with 254");
        }

        // Check if payment already exists
        $stmt = $conn->prepare("SELECT id FROM payments WHERE session_id = ? AND status != 'completed'");
        $stmt->execute([$session_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("A payment is already in progress for this session");
        }

        // Generate reference
        $reference = 'TUT-' . $session_id . '-' . time();

        // Create payment record
        $stmt = $conn->prepare("INSERT INTO payments 
                                (session_id, amount, status, payment_method, 
                                 transaction_id) 
                                VALUES (?, ?, 'pending', 'MPESA', ?)");
        $stmt->execute([$session_id, $total_amount, $reference]);
        $payment_id = $conn->lastInsertId();

        // Initialize Daraja class
        require_once '../app/payments/daraja.php';
        $daraja = new Daraja();
        
        $payment = $daraja->initiatePayment(
            $total_amount,
            $phone_number,
            $reference,
            "Payment for tutoring session with " . $session['tutor_first_name'] . " " . $session['tutor_last_name']
        );

        // Update payment record with MPESA details
        $stmt = $conn->prepare("UPDATE payments 
                                SET merchant_request_id = ?, 
                                    checkout_request_id = ?
                                WHERE id = ?");
        $stmt->execute([
            $payment['MerchantRequestID'],
            $payment['CheckoutRequestID'],
            $payment['CheckoutRequestID'],
            $payment_id
        ]);

        // Redirect to payment status page
        header("Location: ../payments.php?payment_id=" . $payment_id);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiate Payment - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .amount-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        .amount-box h3 {
            color: #2c3e50;
            margin: 0;
        }
        .amount-box p {
            color: #764ba2;
            font-size: 2rem;
            margin: 0.5rem 0 0;
        }
        .phone-input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 1rem;
        }
        .phone-input:focus {
            outline: none;
            border-color: #764ba2;
        }
        .payment-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        .payment-btn:hover {
            opacity: 0.9;
        }
        .error-message {
            color: #dc3545;
            margin: 1rem 0;
            padding: 0.5rem;
            border-radius: 5px;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2 class="text-center mb-4">Initiate Payment</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="amount-box">
            <h3>Amount to Pay</h3>
            <p>KES <?php echo number_format($total_amount, 2); ?></p>
        </div>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="tel" class="phone-input" id="phone_number" name="phone_number" 
                       value="<?php echo isset($session['tutee_phone']) ? htmlspecialchars($session['tutee_phone']) : ''; ?>"
                       placeholder="Enter your phone number (e.g. 254712345678)" required>
            </div>

            <div class="mb-3">
                <p class="text-muted">You will be charged KES <?php echo number_format($total_amount, 2); ?> via MPESA</p>
                <p class="text-muted">Payment will be processed through Strathmore Tutor Platform's MPESA account</p>
            </div>

            <button type="submit" class="payment-btn">
                <i class="fas fa-money-bill-wave me-2"></i>
                Pay with MPESA
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="../dashboard.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
