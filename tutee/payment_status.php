<?php
require_once '../config/config.php';
require_once '../config/database.php';

$checkout_request_id = $_GET['checkout_request_id'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;
$status = null;
$receipt = null;
$error = null;
$debug_info = null;

if ($checkout_request_id) {
    $db = new Database();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT status, mpesa_receipt_number FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkout_request_id]);
    $row = $stmt->fetch();
    if ($row) {
        $status = $row['status'];
        $receipt = $row['mpesa_receipt_number'];
    } else {
        $error = 'Payment not found.';
        $debug_info = "Looking for checkout_request_id: " . htmlspecialchars($checkout_request_id);
    }
} elseif ($payment_id) {
    $db = new Database();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("SELECT status, mpesa_receipt_number, checkout_request_id FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $row = $stmt->fetch();
    if ($row) {
        $status = $row['status'];
        $receipt = $row['mpesa_receipt_number'];
        $checkout_request_id = $row['checkout_request_id'];
    } else {
        $error = 'Payment not found.';
        $debug_info = "Looking for payment_id: " . htmlspecialchars($payment_id);
    }
} else {
    $error = 'Missing payment reference.';
    $debug_info = "No checkout_request_id or payment_id provided in URL";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h2>Payment Status</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php if ($debug_info): ?>
                                <div class="alert alert-info">
                                    <strong>Debug Info:</strong> <?php echo $debug_info; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($checkout_request_id): ?>
                                <div class="alert alert-info mt-3" id="pending-msg">
                                    <i class="fas fa-clock"></i> If you just paid, please wait while we confirm your payment. This page will update automatically.
                                </div>
                                <script>
                                    setInterval(function() {
                                        $.get(window.location.pathname + '?checkout_request_id=<?php echo urlencode($checkout_request_id); ?>', function(data) {
                                            if (data.includes('Payment successful!')) {
                                                location.reload();
                                            }
                                        });
                                    }, 5000);
                                </script>
                            <?php endif; ?>
                        <?php elseif ($status === 'completed'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Payment successful!<br>
                                Receipt: <strong><?php echo htmlspecialchars($receipt); ?></strong>
                            </div>
                            <p class="text-muted">Redirecting to session details...</p>
                            <script>
                                setTimeout(function() {
                                    window.location.href = 'session_details.php?checkout_request_id=<?php echo urlencode($checkout_request_id); ?>';
                                }, 3000);
                            </script>
                        <?php elseif ($status === 'pending'): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-clock"></i> Payment is still pending. Please wait or refresh this page.
                            </div>
                            <script>
                                setInterval(function() {
                                    $.get(window.location.pathname + '?checkout_request_id=<?php echo urlencode($checkout_request_id); ?>', function(data) {
                                        if (data.includes('Payment successful!')) {
                                            location.reload();
                                        }
                                    });
                                }, 5000);
                            </script>
                        <?php elseif ($status === 'failed'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> Payment failed. Please try again.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">Unknown payment status.</div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <?php if ($status === 'completed'): ?>
                                <a href="session_details.php?checkout_request_id=<?php echo urlencode($checkout_request_id); ?>" class="btn btn-primary">
                                    View Session Details
                                </a>
                            <?php endif; ?>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 