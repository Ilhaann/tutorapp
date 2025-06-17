<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Fetch user details
$user_stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if user not found
if (!$user) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

// Get filter parameters
$subject_id = $_GET['subject_id'] ?? '';
$level = $_GET['level'] ?? '';
$sort = $_GET['sort'] ?? 'rating';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get all subjects for the filter dropdown
$subjects = $pdo->query("SELECT id, name FROM units ORDER BY name")->fetchAll();

// Build the query based on filters
$where_conditions = ["u.role = 'tutor'"];
$params = [];

if ($subject_id) {
    $where_conditions[] = "ts.unit_id = ?";
    $params[] = $subject_id;
}

if ($level) {
    $where_conditions[] = "ts.level = ?";
    $params[] = $level;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "
    SELECT COUNT(DISTINCT u.id)
    FROM users u
    JOIN tutor_units ts ON u.id = ts.tutor_id
    $where_clause
";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_tutors = intval($count_stmt->fetchColumn());
$total_pages = ceil($total_tutors / $per_page);

// Determine sort order
$order_by = match($sort) {
    'rating' => "avg_rating DESC, review_count DESC",
    'reviews' => "review_count DESC, avg_rating DESC",
    'name' => "u.first_name ASC, u.last_name ASC",
    default => "avg_rating DESC, review_count DESC"
};

// Get tutors with their subjects and ratings
$query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        GROUP_CONCAT(DISTINCT CONCAT(s.name, ' (', ts.level, ')') SEPARATOR ', ') as subjects,
        COUNT(DISTINCT r.id) as review_count,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM users u
    JOIN tutor_units ts ON u.id = ts.tutor_id
    JOIN units s ON ts.unit_id = s.id
    LEFT JOIN sessions ssn ON u.id = ssn.tutor_id
    LEFT JOIN reviews r ON ssn.id = r.session_id
    $where_clause
    GROUP BY u.id, u.first_name, u.last_name
    ORDER BY $order_by
    LIMIT " . intval($per_page) . " OFFSET " . intval($offset);

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tutors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Tutors - Strathmore Peer Tutoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .tutors-container {
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
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .tutor-card {
            height: 100%;
        }
        .tutor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: -50px auto 15px;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .rating {
            color: #ffc107;
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
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .sort-dropdown {
            min-width: 150px;
        }
    </style>
</head>
<body>
    <div class="tutors-container">
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
                    <a class="nav-link active" href="tutors.php">
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
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" 
                                            <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="level" class="form-label">Level</label>
                            <select class="form-select" id="level" name="level">
                                <option value="">All Levels</option>
                                <option value="Beginner" <?php echo $level === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo $level === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo $level === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                                <option value="reviews" <?php echo $sort === 'reviews' ? 'selected' : ''; ?>>Most Reviews</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="tutors.php" class="btn btn-outline-secondary ms-2">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="row">
                    <?php if (empty($tutors)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-search" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No tutors found</h5>
                            <p>Try adjusting your search filters.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tutors as $tutor): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card tutor-card">
                                    <div class="card-body text-center">
                                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $tutor['id']; ?>.jpg" 
                                             alt="Tutor Avatar" 
                                             class="tutor-avatar"
                                             onerror="this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>
                                        </h5>
                                        <div class="rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= round($tutor['avg_rating']) ? '-fill' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <small class="text-muted">
                                                (<?php echo $tutor['review_count']; ?> reviews)
                                            </small>
                                        </div>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($tutor['subjects']); ?>
                                            </small>
                                        </p>
                                        <a href="request_tutor.php?tutor_id=<?php echo $tutor['id']; ?>" 
                                           class="btn btn-primary">
                                            Request Session
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?subject_id=<?php echo $subject_id; ?>&level=<?php echo $level; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 