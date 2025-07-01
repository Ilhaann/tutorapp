<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

$tutor_id = $_GET['tutor_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;

if (!$tutor_id || !$subject_id) {
    header("Location: tutors.php");
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

// Get tutor and subject details
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, tp.hourly_rate, un.name as subject_name
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    JOIN units un ON un.id = ?
    WHERE u.id = ? AND u.role = 'tutor'
");
$stmt->execute([$subject_id, $tutor_id]);
$details = $stmt->fetch();

if (!$details) {
    header("Location: tutors.php");
    exit();
}

// Get available time slots for the next 7 days
$stmt = $pdo->prepare("
    SELECT id, DATE_FORMAT(start_time, '%Y-%m-%d %H:%i') as start_time, 
           DATE_FORMAT(end_time, '%H:%i') as end_time,
           TIMESTAMPDIFF(HOUR, start_time, end_time) as duration
    FROM availability_slots
    WHERE tutor_id = ? 
      AND unit_id = ?
      AND start_time > NOW()
      AND start_time < DATE_ADD(NOW(), INTERVAL 7 DAY)
      AND is_booked = 0
    ORDER BY start_time ASC
");
$stmt->execute([$tutor_id, $subject_id]);
$time_slots = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Time Slot - <?php echo htmlspecialchars($details['first_name'] . ' ' . $details['last_name']); ?></title>
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
        .time-slot {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .time-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .time-slot.selected {
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
        .session-details {
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
        .date-slots {
            display: grid;
            gap: 10px;
        }
        .alert-info {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 10px;
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
                            <h4 class="mb-0">Select Time Slot</h4>
                        </div>
                        <div class="card-body">
                            <div class="session-details mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $details['id']; ?>.jpg" 
                                         onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                                         class="tutor-avatar me-3"
                                         alt="Tutor Avatar">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($details['first_name'] . ' ' . $details['last_name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            Subject: <?php echo htmlspecialchars($details['subject_name']); ?><br>
                                            <i class="bi bi-currency-dollar"></i> 
                                            <?php echo number_format($details['hourly_rate'] ?? 0, 2); ?>/hour
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form action="payment.php" method="GET">
                                <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                                <input type="hidden" name="amount" value="<?php echo $details['hourly_rate']; ?>">
                                <input type="hidden" name="slot_id" id="selected_slot_id" value="">
                                
                                <div class="time-slots">
                                    <?php if (empty($time_slots)): ?>
                                        <div class="alert alert-info">
                                            No available time slots found for the next 7 days.
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $current_date = '';
                                        foreach ($time_slots as $slot): 
                                            $slot_date = date('Y-m-d', strtotime($slot['start_time']));
                                            if ($current_date !== $slot_date):
                                                if ($current_date !== '') echo '</div>'; // Close previous date group
                                                $current_date = $slot_date;
                                        ?>
                                            <h5 class="mt-4 mb-3 text-white"><?php echo date('l, F j, Y', strtotime($slot_date)); ?></h5>
                                            <div class="date-slots">
                                        <?php endif; ?>
                                        
                                        <div class="card time-slot mb-2" data-slot-id="<?php echo $slot['id']; ?>" 
                                             data-duration="<?php echo $slot['duration']; ?>"
                                             onclick="selectTimeSlot(this)">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                                        <?php echo $slot['end_time']; ?>
                                                    </div>
                                                    <div class="text-muted">
                                                        <?php echo $slot['duration']; ?> hour<?php echo $slot['duration'] > 1 ? 's' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if ($current_date !== '') echo '</div>'; // Close last date group ?>
                                    <?php endif; ?>
                                </div>

                                <div class="text-end mt-4">
                                    <a href="tutor_subjects.php?tutor_id=<?php echo $tutor_id; ?>" class="btn btn-outline-light me-2">Back</a>
                                    <button type="submit" class="btn btn-primary" id="continueBtn" disabled>Continue to Payment</button>
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
    function selectTimeSlot(element) {
        // Remove selected class from all slots
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Add selected class to clicked slot
        element.classList.add('selected');
        
        // Update hidden input with selected slot ID
        document.getElementById('selected_slot_id').value = element.dataset.slotId;
        
        // Enable continue button
        document.getElementById('continueBtn').disabled = false;
    }
    </script>
</body>
</html> 