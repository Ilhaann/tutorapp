<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
$db = new Database();
$pdo = $db->getConnection();

// Check if user is logged in and is a tutee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Get user data for sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    // Fetch upcoming sessions
    $stmt = $pdo->prepare("
        SELECT s.id, s.tutor_id, s.tutee_id, s.unit_id, s.slot_id, s.status, 
               s.session_type, s.location, s.meeting_link, s.notes, s.created_at,
               t.first_name as tutor_first_name, t.last_name as tutor_last_name, 
               un.name as unit_name, '../assets/images/default-avatar.png' as tutor_picture,
               av.start_time, av.end_time
        FROM sessions s
        JOIN users t ON s.tutor_id = t.id
        JOIN units un ON s.unit_id = un.id
        JOIN availability_slots av ON s.slot_id = av.id
        WHERE s.tutee_id = ? AND s.status IN ('pending', 'confirmed')
        ORDER BY av.start_time ASC
    ");
    $stmt->execute([$user_id]);
    $upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch completed sessions
    $stmt = $pdo->prepare("
        SELECT s.id, s.tutor_id, s.tutee_id, s.unit_id, s.slot_id, s.status, 
               s.session_type, s.location, s.meeting_link, s.notes, s.created_at,
               t.first_name as tutor_first_name, t.last_name as tutor_last_name, 
               un.name as unit_name, '../assets/images/default-avatar.png' as tutor_picture,
               av.start_time, av.end_time
        FROM sessions s
        JOIN users t ON s.tutor_id = t.id
        JOIN units un ON s.unit_id = un.id
        JOIN availability_slots av ON s.slot_id = av.id
        WHERE s.tutee_id = ? AND s.status = 'completed'
        ORDER BY av.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $completed_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching sessions: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
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

        .main-content {
            background-color: #f4f6f9;
            padding: 30px;
        }
        .sessions-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .sessions-header h2 {
            margin: 0;
            font-weight: 300;
        }
        .session-card {
            background: white;
            border: none;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 6px 15px rgba(36,37,38,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .session-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(36,37,38,0.15);
        }
        .session-card-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .session-card-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        .session-card-body {
            padding: 15px;
        }
        .session-status {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 20px;
        }
        .session-status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .session-status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .session-status-completed {
            background-color: #17a2b8;
            color: white;
        }
        .session-detail-icon {
            color: #6c757d;
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .session-details {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
    </style>
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
                    <a class="nav-link active" href="my_sessions.php">
                        <i class="bi bi-calendar"></i> My Sessions
                    </a>
                    <a class="nav-link" href="tutors.php">
                        <i class="bi bi-search"></i> Find Tutors
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Upcoming Sessions -->
                <div class="sessions-header">
                    <h2>Upcoming Sessions</h2>
                </div>
                <?php if (empty($upcoming_sessions)): ?>
                    <div class="alert alert-info text-center">No upcoming sessions found.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($upcoming_sessions as $session): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card session-card position-relative">
                                    <span class="session-status session-status-<?php echo strtolower($session['status']); ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                    <div class="session-card-header">
                                        <img src="<?php echo $session['tutor_picture'] ?? '../assets/images/default-avatar.png'; ?>" 
                                             alt="Tutor">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($session['unit_name']); ?></small>
                                        </div>
                                    </div>
                                    <div class="session-card-body">
                                        <div class="session-details">
                                            <p class="mb-2">
                                                <i class="fas fa-calendar session-detail-icon"></i>
                                                <?php echo date('M d, Y', strtotime($session['start_time'])); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-clock session-detail-icon"></i>
                                                <?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?>
                                            </p>
                                            <?php if ($session['session_type']): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-video session-detail-icon"></i>
                                                    <?php echo htmlspecialchars(ucfirst($session['session_type'])); ?> Session
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Completed Sessions -->
                <div class="sessions-header">
                    <h2>Completed Sessions</h2>
                </div>
                <?php if (empty($completed_sessions)): ?>
                    <div class="alert alert-info text-center">No completed sessions found.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($completed_sessions as $session): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card session-card position-relative">
                                    <span class="session-status session-status-<?php echo strtolower($session['status']); ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                    <div class="session-card-header">
                                        <img src="<?php echo $session['tutor_picture'] ?? '../assets/images/default-avatar.png'; ?>" 
                                             alt="Tutor">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($session['unit_name']); ?></small>
                                        </div>
                                    </div>
                                    <div class="session-card-body">
                                        <div class="session-details">
                                            <p class="mb-2">
                                                <i class="fas fa-calendar session-detail-icon"></i>
                                                <?php echo date('M d, Y', strtotime($session['start_time'])); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-clock session-detail-icon"></i>
                                                <?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?>
                                            </p>
                                            <?php if ($session['session_type']): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-video session-detail-icon"></i>
                                                    <?php echo htmlspecialchars(ucfirst($session['session_type'])); ?> Session
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
