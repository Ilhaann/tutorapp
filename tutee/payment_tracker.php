<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Only allow admin access for security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

if ($action === 'fix_payment') {
    $payment_id = $_POST['payment_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;
    
    if ($payment_id && $new_status) {
        try {
            $pdo->beginTransaction();
            
            // Update payment status
            $stmt = $pdo->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $payment_id]);
            
            // Get session ID
            $stmt = $pdo->prepare("SELECT session_id FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // Update session status based on payment status
                $session_status = ($new_status === 'completed') ? 'confirmed' : 'pending';
                $stmt = $pdo->prepare("UPDATE sessions SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$session_status, $payment['session_id']]);
            }
            
            $pdo->commit();
            $message = "Payment status updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error updating payment: " . $e->getMessage();
        }
    }
}

// Get all payments with session details
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        s.id as session_id,
        s.status as session_status,
        tutee.first_name as tutee_first_name,
        tutee.last_name as tutee_last_name,
        tutor.first_name as tutor_first_name,
        tutor.last_name as tutor_last_name,
        u.name as unit_name
    FROM payments p
    LEFT JOIN sessions s ON p.session_id = s.id
    LEFT JOIN users tutee ON s.tutee_id = tutee.id
    LEFT JOIN users tutor ON s.tutor_id = tutor.id
    LEFT JOIN units u ON s.unit_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$payments = $stmt->fetchAll();

// Get payment statistics
$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments 
    GROUP BY status
");
$stmt->execute();
$stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracker - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-pending { color: #ffc107; }
        .status-completed { color: #28a745; }
        .status-failed { color: #dc3545; }
        .status-refunded { color: #6c757d; }
        .debug-info { font-size: 0.8em; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="bi bi-credit-card"></i> Payment Tracker</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <?php foreach ($stats as $stat): ?>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title status-<?php echo $stat['status']; ?>">
                                        <?php echo ucfirst($stat['status']); ?>
                                    </h5>
                                    <p class="card-text">
                                        <strong><?php echo $stat['count']; ?></strong> payments<br>
                                        <strong>KES <?php echo number_format($stat['total_amount'], 2); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Payments Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Session</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>M-PESA Receipt</th>
                                        <th>Checkout ID</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['id']; ?></td>
                                            <td>
                                                <strong>Session #<?php echo $payment['session_id']; ?></strong><br>
                                                <small class="debug-info">
                                                    <?php echo $payment['tutee_first_name'] . ' ' . $payment['tutee_last_name']; ?> → 
                                                    <?php echo $payment['tutor_first_name'] . ' ' . $payment['tutor_last_name']; ?><br>
                                                    <?php echo $payment['unit_name']; ?>
                                                </small>
                                            </td>
                                            <td>KES <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                                <?php if ($payment['session_status']): ?>
                                                    <br><small class="debug-info">Session: <?php echo $payment['session_status']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $payment['mpesa_receipt_number'] ?: '<em>None</em>'; ?>
                                            </td>
                                            <td>
                                                <small class="debug-info"><?php echo $payment['checkout_request_id'] ?: '<em>None</em>'; ?></small>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fixModal<?php echo $payment['id']; ?>">
                                                    <i class="bi bi-wrench"></i> Fix
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Fix Modal -->
                                        <div class="modal fade" id="fixModal<?php echo $payment['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Fix Payment #<?php echo $payment['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="?action=fix_payment">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Current Status: <strong><?php echo ucfirst($payment['status']); ?></strong></label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">New Status:</label>
                                                                <select name="new_status" class="form-select" required>
                                                                    <option value="">Select status...</option>
                                                                    <option value="pending">Pending</option>
                                                                    <option value="completed">Completed</option>
                                                                    <option value="failed">Failed</option>
                                                                    <option value="refunded">Refunded</option>
                                                                </select>
                                                            </div>
                                                            <div class="alert alert-info">
                                                                <small>
                                                                    <strong>Note:</strong> Setting status to "completed" will also update the session status to "confirmed".
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 