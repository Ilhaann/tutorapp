<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Initialize database connection first
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch user details if not already in session
if (!isset($_SESSION['first_name'])) {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
    }
}

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

$errors = [];
$success = false;

// Get all subjects (units)
$stmt = $pdo->query("SELECT id, name FROM units ORDER BY name");
$subjects = $stmt->fetchAll();

// Get available tutors for the selected subject
$selected_tutors = [];
if (isset($_GET['subject_id'])) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.first_name, u.last_name, tu.level, 
               COUNT(DISTINCT r.id) as review_count,
               COALESCE(AVG(r.rating), 0) as avg_rating
        FROM users u
        JOIN tutor_units tu ON u.id = tu.tutor_id
        LEFT JOIN sessions s ON u.id = s.tutor_id
        LEFT JOIN reviews r ON s.id = r.session_id
        WHERE tu.unit_id = ? AND u.role = 'tutor'
        GROUP BY u.id, u.first_name, u.last_name, tu.level
        ORDER BY avg_rating DESC, review_count DESC
    ");
    $stmt->execute([$_GET['subject_id']]);
    $selected_tutors = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? '';
    $tutor_id = $_POST['tutor_id'] ?? '';
    $slot_id = $_POST['slot_id'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Validate inputs
    if (empty($subject_id) || empty($tutor_id) || empty($slot_id)) {
        $errors[] = 'All fields are required';
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Get slot details with unit_id
            $stmt = $pdo->prepare("
                SELECT s.*, tu.unit_id 
                FROM availability_slots s
                JOIN tutor_units tu ON s.tutor_id = tu.tutor_id
                WHERE s.id = ?
            ");
            $stmt->execute([$slot_id]);
            $slot = $stmt->fetch();

            if (!$slot) {
                throw new Exception('Slot not found');
            }

            // Get tutor's hourly rate
            $stmt = $pdo->prepare("SELECT tp.hourly_rate 
                                  FROM tutor_profiles tp 
                                  JOIN users u ON tp.user_id = u.id 
                                  WHERE u.id = ?");
            $stmt->execute([$tutor_id]);
            $tutor = $stmt->fetch();

            if (!$tutor) {
                throw new Exception('Tutor not found');
            }

            // Calculate duration and total amount
            $start_time = strtotime($slot['start_time']);
            $end_time = strtotime($slot['end_time']);
            $duration = ceil(($end_time - $start_time) / 3600); // Round up to next hour
            $total_amount = $duration * $tutor['hourly_rate'];

            // Create session record
            $stmt = $pdo->prepare("INSERT INTO sessions 
                                   (tutee_id, tutor_id, unit_id, slot_id, notes)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $tutor_id,
                $slot['unit_id'],
                $slot_id,
                $notes
            ]);
            $session_id = $pdo->lastInsertId();

            // Update slot status
            $stmt = $pdo->prepare("UPDATE availability_slots 
                                   SET is_booked = 1 
                                   WHERE id = ?");
            $stmt->execute([$slot_id]);

            // Commit transaction
            $pdo->commit();

            // Redirect to payment page
            header("Location: initiate_payment.php?session_id=" . $session_id);
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = 'Error booking session: ' . $e->getMessage();
        }
    }
    if (empty($subject_id)) {
        $errors[] = "Please select a subject";
    }
    if (empty($tutor_id)) {
        $errors[] = "Please select a tutor";
    }
    if (empty($slot_id)) {
        $errors[] = "Please select an available time slot";
    }

    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert session request
            $stmt = $pdo->prepare("
                INSERT INTO sessions (tutee_id, tutor_id, unit_id, slot_id, notes)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $tutor_id, $slot['unit_id'], $slot_id, $notes]);

            // Commit transaction
            $pdo->commit();
            $success = true;

            // Redirect to dashboard with success message
            $_SESSION['success_message'] = "Tutoring session requested successfully!";
            header("Location: dashboard.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred. Please try again later.";
            error_log("Session request error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Tutor - Strathmore Peer Tutoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .request-container {
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
        }
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        .tutor-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tutor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .tutor-card.selected {
            border: 2px solid #667eea;
        }
        .time-slot {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .time-slot:hover {
            background-color: #f8f9fa;
        }
        .time-slot.selected {
            background-color: #e9ecef;
            border-color: #667eea;
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
    </style>
</head>
<body>
    <div class="request-container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h4 class="text-center mb-0">Strathmore</h4>
                    <p class="text-center mb-0">Peer Tutoring</p>
                </div>
                <div class="sidebar-profile">
                    <div class="profile-picture-container">
                        <?php
                        $avatar_path = '../assets/images/avatars/' . $_SESSION['user_id'] . '.jpg';
                        $default_avatar = '../assets/images/default-avatar.jpg';
                        
                        if (file_exists($avatar_path)) {
                            echo '<img src="' . htmlspecialchars($avatar_path) . '" alt="Profile Picture" class="profile-picture">';
                        } else {
                            echo '<img src="' . htmlspecialchars($default_avatar) . '" alt="Profile Picture" class="profile-picture">';
                        }
                        ?>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h5>
                    <p class="mb-0 text-muted">Tutee</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="request_tutor.php">
                        <i class="bi bi-plus-circle"></i> Request Tutor
                    </a>
                    <a class="nav-link" href="my_sessions.php">
                        <i class="bi bi-calendar-check"></i> My Sessions
                    </a>
                    <a class="nav-link" href="tutors.php">
                        <i class="bi bi-people"></i> Find Tutors
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Request a Tutoring Session</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="requestForm">
                            <!-- Step 1: Select Subject -->
                            <div class="mb-4">
                                <h5>1. Select Subject</h5>
                                <select class="form-select" name="subject_id" id="subjectSelect" required>
                                    <option value="">Choose a subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" 
                                                <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Step 2: Select Tutor -->
                            <div class="mb-4" id="tutorSection" style="display: none;">
                                <h5>2. Select Tutor</h5>
                                <div class="row" id="tutorList">
                                    <?php foreach ($selected_tutors as $tutor): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card tutor-card" data-tutor-id="<?php echo $tutor['id']; ?>">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>
                                                    </h6>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            Level: <?php echo htmlspecialchars($tutor['level']); ?><br>
                                                            Rating: <?php echo number_format($tutor['avg_rating'], 1); ?> 
                                                            (<?php echo $tutor['review_count']; ?> reviews)
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="tutor_id" id="selectedTutor" required>
                            </div>

                            <!-- Step 3: Select Time Slot -->
                            <div class="mb-4" id="timeSlotSection" style="display: none;">
                                <h5>3. Select Time Slot</h5>
                                <div id="timeSlotList">
                                    <!-- Time slots will be loaded dynamically -->
                                </div>
                                <input type="hidden" name="slot_id" id="selectedSlot" required>
                            </div>

                            <!-- Step 4: Additional Notes -->
                            <div class="mb-4" id="notesSection" style="display: none;">
                                <h5>4. Additional Notes (Optional)</h5>
                                <textarea class="form-control" name="notes" rows="3" 
                                          placeholder="Add any specific topics or areas you'd like to focus on..."></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary" id="submitButton" disabled>
                                    Request Session
                                </button>
                            </div>
    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subjectSelect');
            const tutorSection = document.getElementById('tutorSection');
            const timeSlotSection = document.getElementById('timeSlotSection');
            const notesSection = document.getElementById('notesSection');
            const submitButton = document.getElementById('submitButton');
            const tutorCards = document.querySelectorAll('.tutor-card');
            let selectedTutorId = null;

            // Handle subject selection
            subjectSelect.addEventListener('change', function() {
                if (this.value) {
                    window.location.href = `request_tutor.php?subject_id=${this.value}`;
                }
            });

            // Handle tutor selection
            tutorCards.forEach(card => {
                card.addEventListener('click', function() {
                    tutorCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedTutorId = this.dataset.tutorId;
                    document.getElementById('selectedTutor').value = selectedTutorId;
                    timeSlotSection.style.display = 'block';
                    loadTimeSlots(selectedTutorId);
                });
            });

            // Load time slots for selected tutor
            function loadTimeSlots(tutorId) {
                fetch(`get_available_slots.php?tutor_id=${tutorId}`)
                    .then(response => response.json())
                    .then(slots => {
                        const timeSlotList = document.getElementById('timeSlotList');
                        timeSlotList.innerHTML = '';
                        
                        if (slots.length === 0) {
                            timeSlotList.innerHTML = '<div class="alert alert-info">No available time slots found.</div>';
                            return;
                        }

                        slots.forEach(slot => {
                            const timeSlot = document.createElement('div');
                            timeSlot.className = 'time-slot';
                            timeSlot.dataset.slotId = slot.id;
                            timeSlot.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${slot.date}</strong><br>
                                        <small class="text-muted">${slot.start_time} - ${slot.end_time}</small>
                                    </div>
                                </div>
                            `;
                            timeSlot.addEventListener('click', function() {
                                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                                this.classList.add('selected');
                                document.getElementById('selectedSlot').value = this.dataset.slotId;
                                notesSection.style.display = 'block';
                                submitButton.disabled = false;
                            });
                            timeSlotList.appendChild(timeSlot);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading time slots:', error);
                        timeSlotList.innerHTML = '<div class="alert alert-danger">Error loading time slots. Please try again.</div>';
                    });
            }

            // Show tutor section if tutors are available
            if (document.querySelectorAll('.tutor-card').length > 0) {
                tutorSection.style.display = 'block';
            }
        });
    </script>
</body>
</html>
