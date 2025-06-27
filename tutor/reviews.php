<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/config.php';
$database = new Database();
$pdo = $database->getConnection();

// Get user details with profile picture
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, tp.profile_picture
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get reviews with proper joins
$stmt = $pdo->prepare("
    SELECT r.*, 
           u.first_name, 
           u.last_name, 
           un.name as unit_name,
           s.unit_id
    FROM reviews r
    JOIN sessions s ON r.session_id = s.id
    JOIN users u ON r.reviewer_id = u.id
    JOIN units un ON s.unit_id = un.id
    WHERE s.tutor_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM reviews r
    JOIN sessions s ON r.session_id = s.id
    WHERE s.tutor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$rating_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .reviews-container {
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
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .review-rating {
            color: #f1c40f;
            font-size: 1.2rem;
        }
        .rating-stats {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        .rating-stats .rating {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f1c40f;
        }
        .rating-stats .total-reviews {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="reviews-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="rating-stats">
                    <div class="rating">
                        <?php echo number_format($rating_stats['avg_rating'] ?? 0, 1); ?>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="total-reviews">
                        Based on <?php echo $rating_stats['total_reviews'] ?? 0; ?> reviews
                    </div>
                </div>

                <div class="review-card">
                    <h2 class="mb-4">Your Reviews</h2>

                    <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews yet.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <?php echo htmlspecialchars($review['unit_name']); ?>
                                            </h6>
                                            <div class="review-rating mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="card-text"><?php echo htmlspecialchars($review['comment'] ?? ''); ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 