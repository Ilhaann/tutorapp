<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

$tutor_id = $_GET['tutor_id'] ?? null;

if (!$tutor_id) {
    header("Location: tutors.php");
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

// Get tutor details
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, tp.hourly_rate
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ? AND u.role = 'tutor'
");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch();

if (!$tutor) {
    header("Location: tutors.php");
    exit();
}

// Get tutor's subjects
$stmt = $pdo->prepare("
    SELECT u.id, u.name
    FROM units u
    JOIN tutor_units tu ON u.id = tu.unit_id
    WHERE tu.tutor_id = ?
    ORDER BY u.name
");
$stmt->execute([$tutor_id]);
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Subject - <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        .subject-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .subject-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
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
        .tutor-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .tutor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .main-content {
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Select Subject for Tutoring Session</h4>
                        </div>
                        <div class="card-body">
                            <div class="tutor-info mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $tutor['id']; ?>.jpg" 
                                         onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                                         class="tutor-avatar me-3"
                                         alt="Tutor Avatar">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="bi bi-currency-dollar"></i> 
                                                <?php echo number_format($tutor['hourly_rate'] ?? 0, 2); ?>/hour
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form action="select_time_slot.php" method="GET">
                                <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                                
                                <div class="row">
                                    <?php foreach ($subjects as $subject): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card subject-card" data-subject-id="<?php echo $subject['id']; ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($subject['name']); ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="subject_id" id="selectedSubject" required>
                                
                                <div class="text-end mt-4">
                                    <a href="tutors.php" class="btn btn-outline-light me-2">Back</a>
                                    <button type="submit" class="btn btn-primary" id="continueBtn" disabled>Continue to Time Slots</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const subjectCards = document.querySelectorAll('.subject-card');
            const selectedSubjectInput = document.getElementById('selectedSubject');
            const continueBtn = document.getElementById('continueBtn');

            subjectCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    subjectCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    
                    // Update hidden input
                    selectedSubjectInput.value = this.dataset.subjectId;
                    
                    // Enable continue button
                    continueBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html> 