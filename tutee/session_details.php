<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header('Location: ../auth/login.php');
    exit();
}

$session_id = $_GET['session_id'] ?? null;
$checkout_request_id = $_GET['checkout_request_id'] ?? null;

if (!$session_id && !$checkout_request_id) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

try {
    if ($checkout_request_id) {
        // Get session details from checkout request ID
        $stmt = $pdo->prepare("
            SELECT s.*, p.status as payment_status, p.mpesa_receipt_number, p.amount,
                   t.first_name as tutor_first_name, t.last_name as tutor_last_name, t.email as tutor_email,
                   u.name as unit_name, av.start_time, av.end_time
            FROM sessions s
            JOIN payments p ON s.id = p.session_id
            JOIN users t ON s.tutor_id = t.id
            JOIN units u ON s.unit_id = u.id
            JOIN availability_slots av ON s.slot_id = av.id
            WHERE p.checkout_request_id = ? AND s.tutee_id = ?
        ");
        $stmt->execute([$checkout_request_id, $_SESSION['user_id']]);
    } else {
        // Get session details from session ID
        $stmt = $pdo->prepare("
            SELECT s.*, p.status as payment_status, p.mpesa_receipt_number, p.amount,
                   t.first_name as tutor_first_name, t.last_name as tutor_last_name, t.email as tutor_email,
                   u.name as unit_name, av.start_time, av.end_time
            FROM sessions s
            LEFT JOIN payments p ON s.id = p.session_id
            JOIN users t ON s.tutor_id = t.id
            JOIN units u ON s.unit_id = u.id
            JOIN availability_slots av ON s.slot_id = av.id
            WHERE s.id = ? AND s.tutee_id = ?
        ");
        $stmt->execute([$session_id, $_SESSION['user_id']]);
    }
    
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        header('Location: dashboard.php');
        exit();
    }
    
} catch (Exception $e) {
    $error = 'Error loading session details: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
        }
        .session-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title">Session Details</h2>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php else: ?>
                            <!-- Session Status -->
                            <div class="text-center mb-4">
                                <?php if ($session['payment_status'] === 'completed'): ?>
                                    <span class="status-badge bg-success text-white">
                                        <i class="fas fa-check-circle"></i> Payment Confirmed
                                    </span>
                                <?php elseif ($session['payment_status'] === 'pending'): ?>
                                    <span class="status-badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Payment Pending
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge bg-danger text-white">
                                        <i class="fas fa-times-circle"></i> Payment Failed
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Session Information -->
                            <div class="session-info">
                                <h5><i class="fas fa-book"></i> Subject</h5>
                                <p class="mb-3"><?php echo htmlspecialchars($session['unit_name']); ?></p>
                                
                                <h5><i class="fas fa-user"></i> Tutor</h5>
                                <p class="mb-3"><?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></p>
                                
                                <h5><i class="fas fa-calendar"></i> Date & Time</h5>
                                <p class="mb-3">
                                    <?php echo date('F j, Y', strtotime($session['start_time'])); ?><br>
                                    <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                </p>
                                
                                <h5><i class="fas fa-clock"></i> Duration</h5>
                                <p class="mb-3">
                                    <?php 
                                    $duration = strtotime($session['end_time']) - strtotime($session['start_time']);
                                    $hours = floor($duration / 3600);
                                    $minutes = ($duration % 3600) / 60;
                                    echo $hours . ' hour' . ($hours != 1 ? 's' : '') . 
                                         ($minutes > 0 ? ' ' . $minutes . ' minute' . ($minutes != 1 ? 's' : '') : '');
                                    ?>
                                </p>
                                
                                <h5><i class="fas fa-money-bill"></i> Amount</h5>
                                <p class="mb-3">KES <?php echo number_format($session['amount'], 2); ?></p>
                                
                                <?php if ($session['mpesa_receipt_number']): ?>
                                    <h5><i class="fas fa-receipt"></i> M-PESA Receipt</h5>
                                    <p class="mb-3"><?php echo htmlspecialchars($session['mpesa_receipt_number']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Session Type -->
                            <div class="session-info">
                                <h5><i class="fas fa-video"></i> Session Type</h5>
                                <p class="mb-3">
                                    <?php if ($session['session_type'] === 'online'): ?>
                                        <i class="fas fa-video"></i> Online Session
                                        <?php if ($session['meeting_link']): ?>
                                            <br><a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" target="_blank" class="btn btn-primary btn-sm mt-2">
                                                Join Meeting
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="fas fa-map-marker-alt"></i> In-Person Session
                                        <?php if ($session['location']): ?>
                                            <br><strong>Location:</strong> <?php echo htmlspecialchars($session['location']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="session-info">
                                <h5><i class="fas fa-envelope"></i> Contact Information</h5>
                                <p class="mb-3">
                                    <strong>Tutor Email:</strong> <?php echo htmlspecialchars($session['tutor_email']); ?><br>
                                    <strong>Session ID:</strong> <?php echo htmlspecialchars($session['id']); ?>
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="text-center mt-4">
                                <?php if ($session['payment_status'] === 'completed'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> Your session is confirmed! 
                                        The tutor will contact you with further details.
                                    </div>
                                    
                                    <!-- Message Tutor Button -->
                                    <div class="mt-3">
                                        <a href="messages.php?user_id=<?php echo $session['tutor_id']; ?>" class="btn btn-primary btn-lg">
                                            <i class="fas fa-envelope"></i> Message Tutor
                                        </a>
                                        <p class="text-muted mt-2">Click to start a conversation with <?php echo htmlspecialchars($session['tutor_first_name']); ?></p>
                                    </div>
                                <?php elseif ($session['payment_status'] === 'pending'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock"></i> Payment is being processed. 
                                        Please wait or refresh this page.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Payment failed. 
                                        Please try booking again.
                                    </div>
                                    <a href="tutors.php" class="btn btn-primary">Book Another Session</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 