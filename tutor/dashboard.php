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
require_role('tutor');

$database = new Database();
$pdo = $database->getConnection();

// Fetch user details
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, tp.profile_picture, tp.hourly_rate, tp.bio, tp.offers_online, tp.offers_in_person
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Determine if profile is incomplete
$profile_incomplete = (
    empty($user['profile_picture']) || $user['profile_picture'] === 'default-avatar.jpg' ||
    empty($user['hourly_rate']) ||
    empty($user['bio']) ||
    (empty($user['offers_online']) && empty($user['offers_in_person']))
);

// Get tutor's units
$stmt = $pdo->prepare("
    SELECT u.* 
    FROM units u
    JOIN tutor_units tu ON u.id = tu.unit_id
    WHERE tu.tutor_id = ? 
    ORDER BY u.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$units = $stmt->fetchAll();

// Get upcoming sessions
$stmt = $pdo->prepare("
    SELECT s.*, u.first_name, u.last_name, un.name as unit_name,
           av.start_time, av.end_time
    FROM sessions s
    JOIN users u ON s.tutee_id = u.id
    JOIN units un ON s.unit_id = un.id
    JOIN availability_slots av ON s.slot_id = av.id
    WHERE s.tutor_id = ? 
    AND s.status = 'scheduled'
    AND av.start_time >= CURDATE()
    ORDER BY av.start_time ASC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_sessions = $stmt->fetchAll();

// Get recent reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name, s.unit_id, un.name as unit_name
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    JOIN sessions s ON r.session_id = s.id
    JOIN units un ON s.unit_id = un.id
    WHERE s.tutor_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_reviews = $stmt->fetchAll();

// Get recent messages
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.first_name, u.last_name,
        (SELECT message FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = other_user_id 
            AND receiver_id = ? 
            AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
    AND u.id != ?
    ORDER BY last_message_time DESC
    LIMIT 3
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$recent_messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-container {
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1a237e;
        }
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        .session-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .review-rating {
            color: #f1c40f;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid">
                    <?php if ($profile_incomplete): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            To offer sessions, please <a href="profile.php" class="alert-link">complete your profile</a>.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <h2 class="mb-4">Dashboard</h2>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <div class="stats-number"><?php echo count($units); ?></div>
                            <div class="stats-label">Units</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <div class="stats-number"><?php echo count($upcoming_sessions); ?></div>
                            <div class="stats-label">Upcoming Sessions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card text-center">
                            <div class="stats-number"><?php echo count($recent_reviews); ?></div>
                            <div class="stats-label">Recent Reviews</div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div class="stats-card">
                    <h4 class="mb-3">Upcoming Sessions</h4>
                    <?php if (empty($upcoming_sessions)): ?>
                        <p class="text-muted">No upcoming sessions.</p>
                    <?php else: ?>
                        <?php foreach ($upcoming_sessions as $session): ?>
                            <div class="session-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($session['unit_name']); ?></h5>
                                        <p class="mb-1">
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-calendar"></i> 
                                            <?php echo date('M d, Y', strtotime($session['start_time'])); ?> 
                                            at 
                                            <?php echo date('h:i A', strtotime($session['start_time'])); ?>
                                        </p>
                                    </div>
                                    <a href="sessions.php?id=<?php echo $session['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Reviews -->
                <div class="stats-card">
                    <h4 class="mb-3">Recent Reviews</h4>
                    <?php if (empty($recent_reviews)): ?>
                        <p class="text-muted">No reviews yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_reviews as $review): ?>
                            <div class="review-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h5>
                                        <div class="review-rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Messages -->
                <div class="stats-card">
                    <h4 class="mb-3">Recent Messages</h4>
                    <?php if (empty($recent_messages)): ?>
                        <p class="text-muted">No messages yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_messages as $message): ?>
                            <div class="session-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                            <?php if ($message['unread_count'] > 0): ?>
                                                <span class="badge bg-danger ms-2"><?php echo $message['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="mb-1 text-truncate" style="max-width: 300px;">
                                            <?php echo htmlspecialchars(substr($message['last_message'], 0, 50)); ?>
                                            <?php echo strlen($message['last_message']) > 50 ? '...' : ''; ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('M j, g:i A', strtotime($message['last_message_time'])); ?>
                                        </small>
                                    </div>
                                    <a href="messages.php?user_id=<?php echo $message['other_user_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        Reply
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="messages.php" class="btn btn-outline-primary">View All Messages</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>