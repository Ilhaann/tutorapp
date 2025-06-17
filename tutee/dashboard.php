<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_role('tutee');

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

// Fetch user details
$stmt = $pdo->prepare("
    SELECT CONCAT(first_name, ' ', last_name) as name, email FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch upcoming sessions
$stmt = $pdo->prepare("
    SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) as tutor_name, 
           un.name as subject_name 
    FROM sessions s 
    JOIN users u ON s.tutor_id = u.id 
    LEFT JOIN units un ON s.unit_id = un.id 
    WHERE s.tutee_id = ? AND s.status = 'scheduled' 
    ORDER BY s.created_at ASC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_sessions = $stmt->fetchAll();

// Fetch recent tutors
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, tp.bio, 
            AVG(COALESCE(s.rating, 0)) as avg_rating, 
            COUNT(s.id) as review_count 
    FROM users u 
    JOIN tutor_profiles tp ON u.id = tp.user_id 
    LEFT JOIN sessions ses ON u.id = ses.tutor_id 
    LEFT JOIN reviews s ON ses.id = s.session_id 
    WHERE u.role = 'tutor' AND tp.is_approved = 1 
    GROUP BY u.id, u.first_name, u.last_name, tp.bio, tp.rating 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_tutors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutee Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background-color: #f8f9fa;
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
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        .session-item {
            padding: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .session-item:last-child {
            border-bottom: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-picture-container {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            position: relative;
            overflow: hidden;
            border-radius: 50%;
            border: 3px solid white;
            background-color: #f8f9fa;
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                    <h5><?php echo htmlspecialchars($user['name'] ?? 'Tutee'); ?></h5>
                    <p>Tutee</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
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
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="dashboard-header">
                    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <a href="tutors.php" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Find a Tutor
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="my_sessions.php" class="btn btn-success w-100">
                            <i class="bi bi-calendar-plus"></i> Schedule Session
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="profile.php" class="btn btn-info w-100">
                            <i class="bi bi-person"></i> Update Profile
                        </a>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upcoming Sessions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tutor</th>
                                        <th>Subject</th>
                                        <th>Date & Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_sessions as $session): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($session['tutor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($session['subject_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($session['start_time'])); ?></td>
                                        <td><?php echo $session['duration']; ?> minutes</td>
                                        <td>
                                            <span class="badge bg-<?php echo $session['status'] === 'scheduled' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($session['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                    </div>
                </div>

                <!-- Recent Tutors -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Tutors</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($recent_tutors as $tutor): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card tutor-card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($tutor['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($tutor['bio'], 0, 100)) . '...'; ?></p>
                                        <div class="rating">
                                            <span class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= round($tutor['avg_rating']) ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                            <span class="review-count">(<?php echo $tutor['review_count']; ?> reviews)</span>
                                        </div>
                                        <a href="tutor_profile.php?id=<?php echo $tutor['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="tutors.php" class="btn btn-primary">View All Tutors</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
