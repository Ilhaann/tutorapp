<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/config.php';
$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

// Handle session actions (accept/reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $session_id = $_GET['id'];
        $action = $_GET['action'];
        
        if ($action === 'accept') {
            $stmt = $pdo->prepare("
                UPDATE sessions 
                SET status = 'accepted' 
                WHERE id = ? AND tutor_id = ? AND status = 'pending'
            ");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            $success = "Session accepted successfully!";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE sessions 
                SET status = 'rejected' 
                WHERE id = ? AND tutor_id = ? AND status = 'pending'
            ");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            $success = "Session rejected successfully!";
        } elseif ($action === 'complete') {
            $stmt = $pdo->prepare("
                UPDATE sessions 
                SET status = 'completed' 
                WHERE id = ? AND tutor_id = ? AND status = 'accepted'
            ");
            $stmt->execute([$session_id, $_SESSION['user_id']]);
            $success = "Session marked as completed!";
        }
    } catch (Exception $e) {
        $error = "An error occurred while processing your request.";
        error_log("Session action error: " . $e->getMessage());
    }
}

// Get session details if ID is provided
$session_details = null;
if (isset($_GET['id']) && !isset($_GET['action'])) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone,
               un.name as unit_name
        FROM sessions s
        JOIN users u ON s.tutee_id = u.id
        JOIN units un ON s.unit_id = un.id
        WHERE s.id = ? AND s.tutor_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $session_details = $stmt->fetch();
}

// Get user details with profile picture
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, tp.profile_picture
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get sessions
$stmt = $pdo->prepare("
    SELECT s.*, u.first_name, u.last_name, un.name as unit_name,
           av.start_time, av.end_time
    FROM sessions s
    JOIN users u ON s.tutee_id = u.id
    JOIN units un ON s.unit_id = un.id
    JOIN availability_slots av ON s.slot_id = av.id
    WHERE s.tutor_id = ?
    ORDER BY av.start_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .sessions-container {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 10px;
            position: fixed;
            width: 200px;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 5px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 10px;
        }
        .sidebar-header h4 {
            color: white;
            font-weight: bold;
            margin: 0;
            text-align: center;
            font-size: 1.1rem;
        }
        .sidebar-profile {
            padding: 5px 0;
            margin-bottom: 10px;
            text-align: center;
        }
        .avatar-container {
            width: 60px;
            height: 60px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 50%;
            background-color: #f8f9fa;
        }
        .sidebar-profile h5 {
            color: white;
            margin: 5px 0 0 0;
            text-align: center;
            font-size: 0.95rem;
        }
        .sidebar-profile p {
            color: white;
            margin: 0;
            text-align: center;
            font-size: 0.85rem;
        }
        .sidebar .nav-link {
            color: white;
            padding: 8px 10px;
            margin: 2px 0;
            border-radius: 5px;
            font-weight: 500;
            background-color: rgba(255,255,255,0.1);
            font-size: 0.9rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            margin-right: 8px;
            font-size: 1rem;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
        }
        .session-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .session-status {
            font-weight: 500;
        }
        .status-scheduled {
            color: #28a745;
        }
        .status-completed {
            color: #6c757d;
        }
        .status-cancelled {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="sessions-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <?php if ($session_details): ?>
                    <!-- Session Details View -->
                    <div class="session-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Session Details</h2>
                            <a href="sessions.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Sessions
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h4>Student Information</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($session_details['first_name'] . ' ' . $session_details['last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($session_details['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($session_details['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h4>Session Information</h4>
                                <p><strong>Unit:</strong> <?php echo htmlspecialchars($session_details['unit_name']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($session_details['date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($session_details['time'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo $session_details['status']; ?>">
                                        <?php echo ucfirst($session_details['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <?php if ($session_details['status'] === 'pending'): ?>
                            <div class="mt-4">
                                <a href="?action=accept&id=<?php echo $session_details['id']; ?>" 
                                   class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Accept Session
                                </a>
                                <a href="?action=reject&id=<?php echo $session_details['id']; ?>" 
                                   class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Reject Session
                                </a>
                                <a href="messages.php?user_id=<?php echo $session_details['tutee_id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-envelope"></i> Message Student
                                </a>
                            </div>
                        <?php elseif ($session_details['status'] === 'scheduled'): ?>
                            <div class="mt-4">
                                <a href="?action=complete&id=<?php echo $session_details['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Mark as Completed
                                </a>
                                <a href="messages.php?user_id=<?php echo $session_details['tutee_id']; ?>" 
                                   class="btn btn-info">
                                    <i class="bi bi-envelope"></i> Message Student
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4">
                                <a href="messages.php?user_id=<?php echo $session_details['tutee_id']; ?>" 
                                   class="btn btn-secondary">
                                    <i class="bi bi-envelope"></i> Message Student
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Sessions List View -->
                    <div class="session-card">
                        <h2 class="mb-4">Your Sessions</h2>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (empty($sessions)): ?>
                            <p class="text-muted">No sessions found.</p>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="card-title">
                                                    <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                                </h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?php echo htmlspecialchars($session['unit_name']); ?>
                                                </h6>
                                                <p class="mb-1">
                                                    <i class="bi bi-calendar"></i> 
                                                    <?php echo date('M d, Y', strtotime($session['start_time'])); ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="bi bi-clock"></i> 
                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo match($session['status']) {
                                                    'scheduled' => 'success',
                                                    'completed' => 'secondary',
                                                    'cancelled' => 'danger',
                                                    default => 'warning'
                                                };
                                            ?>">
                                                <?php echo ucfirst($session['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 