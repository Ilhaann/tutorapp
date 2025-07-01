<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get session details and verify it belongs to this tutee and is completed
$stmt = $pdo->prepare("
    SELECT s.*, 
           t.first_name as tutor_first_name, t.last_name as tutor_last_name,
           un.name as unit_name,
           av.start_time, av.end_time
    FROM sessions s
    JOIN users t ON s.tutor_id = t.id
    JOIN units un ON s.unit_id = un.id
    JOIN availability_slots av ON s.slot_id = av.id
    WHERE s.id = ? AND s.tutee_id = ? AND s.status = 'completed'
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: my_sessions.php");
    exit();
}

// Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE session_id = ? AND reviewer_id = ?");
$stmt->execute([$session_id, $user_id]);
$existing_review = $stmt->fetch();

if ($existing_review) {
    header("Location: my_sessions.php?error=already_reviewed");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating >= 1 && $rating <= 5) {
        try {
            // Insert review
            $stmt = $pdo->prepare("
                INSERT INTO reviews (session_id, reviewer_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$session_id, $user_id, $rating, $comment]);
            
            // Update tutor's average rating
            $stmt = $pdo->prepare("
                UPDATE tutor_profiles tp
                SET rating = (
                    SELECT AVG(r.rating)
                    FROM reviews r
                    JOIN sessions s ON r.session_id = s.id
                    WHERE s.tutor_id = tp.user_id
                )
                WHERE tp.user_id = ?
            ");
            $stmt->execute([$session['tutor_id']]);
            
            header("Location: my_sessions.php?success=review_submitted");
            exit();
        } catch (Exception $e) {
            $error = 'Error submitting review: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select a valid rating (1-5 stars)';
    }
}

// Get user details for sidebar
$stmt = $pdo->prepare("
    SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Session - <?php echo APP_NAME; ?></title>
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
        .rating-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .star-rating {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating:hover,
        .star-rating.active {
            color: #f1c40f;
        }
        .session-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                                 class="rounded-circle"
                                 alt="Profile Picture">
                        <?php else: ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $_SESSION['user_id']; ?>.jpg" 
                                 onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                                 class="rounded-circle"
                                 alt="Profile Picture">
                        <?php endif; ?>
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
                    <a class="nav-link" href="messages.php">
                        <i class="bi bi-envelope"></i> Messages
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="rating-form">
                                <div class="text-center mb-4">
                                    <h2><i class="bi bi-star-fill text-warning"></i> Rate Your Session</h2>
                                    <p class="text-muted">Share your experience with this tutor</p>
                                </div>

                                <!-- Session Information -->
                                <div class="session-info">
                                    <h5>Session Details</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Tutor:</strong> <?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></p>
                                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($session['unit_name']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($session['start_time'])); ?></p>
                                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?></p>
                                            <p><strong>Type:</strong> <?php echo ucfirst($session['session_type']); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>

                                <!-- Rating Form -->
                                <form method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">How would you rate this session?</label>
                                        <div class="text-center">
                                            <div class="star-container">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star star-rating" data-rating="<?php echo $i; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="rating-input" value="0">
                                            <p class="text-muted mt-2" id="rating-text">Click on a star to rate</p>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="comment" class="form-label fw-bold">Additional Comments (Optional)</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="4" 
                                                  placeholder="Share your experience, what went well, or suggestions for improvement..."></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="my_sessions.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Back to Sessions
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Submit Review
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('rating-input');
        const ratingText = document.getElementById('rating-text');
        
        const ratingDescriptions = {
            0: 'Click on a star to rate',
            1: 'Poor - Not satisfied',
            2: 'Fair - Could be better',
            3: 'Good - Met expectations',
            4: 'Very Good - Exceeded expectations',
            5: 'Excellent - Outstanding experience'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                
                // Update star display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill');
                    } else {
                        s.classList.remove('active');
                        s.classList.remove('bi-star-fill');
                        s.classList.add('bi-star');
                    }
                });
                
                // Update description
                ratingText.textContent = ratingDescriptions[rating];
            });

            // Hover effects
            star.addEventListener('mouseenter', function() {
                const rating = this.dataset.rating;
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#f1c40f';
                    }
                });
            });

            star.addEventListener('mouseleave', function() {
                const currentRating = ratingInput.value;
                stars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.style.color = '#f1c40f';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html> 