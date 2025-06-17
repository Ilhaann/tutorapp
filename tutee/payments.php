<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_role('tutee');

$db = new Database();
$pdo = $db->getConnection();

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch payment history
$stmt = $pdo->prepare("
    SELECT 
        pt.id, 
        pt.amount, 
        pt.status as transaction_type, 
        CONCAT(pt.payment_method, ' Payment') as description, 
        pt.created_at,
        u.name as subject_name,
        CONCAT(tu.first_name, ' ', tu.last_name) as tutor_name
    FROM 
        payments pt
    LEFT JOIN 
        sessions s ON pt.session_id = s.id
    LEFT JOIN 
        units u ON s.unit_id = u.id
    LEFT JOIN 
        users tu ON s.tutor_id = tu.id
    WHERE 
        s.tutee_id = ?
    ORDER BY 
        pt.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payments-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .payment-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }
        .payment-status {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .amount-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .amount-box h4 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        .amount-box p {
            margin: 0;
            color: #764ba2;
            font-size: 1.5rem;
        }
        .retry-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .retry-btn:hover {
            opacity: 0.9;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        .sidebar-header {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-profile {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid white;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .nav-link i {
            margin-right: 10px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h4><?php echo APP_NAME; ?></h4>
                </div>
                <div class="sidebar-profile">
                    <div class="avatar-container">
                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $_SESSION['user_id']; ?>.jpg" 
                             onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                             class="rounded-circle"
                             alt="Profile Picture">
                    </div>
                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p>Tutee</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="my_sessions.php">
                        <i class="bi bi-calendar"></i> My Sessions
                    </a>
                    <a class="nav-link" href="tutors.php">
                        <i class="bi bi-search"></i> Find Tutors
                    </a>
                    <a class="nav-link active" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container py-4">
                    <h2 class="mb-4">Payments</h2>

                    <!-- Pending Payments -->
                    <div class="payment-card">
                        <h3 class="mb-4">Pending Payments</h3>
                        <div class="row">
                            <?php
                            $pending_payments = array_filter($payments, function($p) {
                                return $p['transaction_type'] === 'pending';
                            });
                            
                            if (empty($pending_payments)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        No pending payments at the moment.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pending_payments as $payment): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="payment-status status-pending">
                                                Pending
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($payment['description']); ?></h5>
                                            <div class="amount-box">
                                                <h4>Amount</h4>
                                                <p>KES <?php echo number_format($payment['amount'], 2); ?></p>
                                            </div>
                                            <p class="card-text">
                                                <strong>Subject:</strong> <?php echo htmlspecialchars($payment['subject_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Tutor:</strong> <?php echo htmlspecialchars($payment['tutor_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Phone:</strong> <?php echo substr($payment['phone_number'], 0, 4) . '****' . substr($payment['phone_number'], -4); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Reference:</strong> <?php echo htmlspecialchars($payment['reference']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="payment-card">
                        <h3 class="mb-4">Payment History</h3>
                        <div class="row">
                            <?php
                            $completed_payments = array_filter($payments, function($p) {
                                return $p['transaction_type'] !== 'pending';
                            });
                            
                            if (empty($completed_payments)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        No payment history available.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($completed_payments as $payment): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="payment-status <?php echo $payment['transaction_type'] === 'completed' ? 'status-completed' : 'status-failed'; ?>">
                                                <?php echo ucfirst($payment['transaction_type']); ?>
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($payment['description']); ?></h5>
                                            <div class="amount-box">
                                                <h4>Amount</h4>
                                                <p>KES <?php echo number_format($payment['amount'], 2); ?></p>
                                            </div>
                                            <p class="card-text">
                                                <strong>Subject:</strong> <?php echo htmlspecialchars($payment['subject_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Tutor:</strong> <?php echo htmlspecialchars($payment['tutor_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Reference:</strong> <?php echo htmlspecialchars($payment['reference']); ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?>
                                            </p>
                                            <?php if ($payment['transaction_type'] === 'failed'): ?>
                                                <a href="initiate_payment.php?session_id=<?php echo $payment['session_id']; ?>" class="retry-btn">
                                                    <i class="fas fa-redo-alt me-2"></i>Retry Payment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
