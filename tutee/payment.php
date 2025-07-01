<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header('Location: login.php');
    exit();
}

$tutor_id = $_GET['tutor_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;
$slot_id = $_GET['slot_id'] ?? null;
$error_message = null;
$details = null;

if (!$tutor_id || !$subject_id || !$slot_id) {
    $error_message = "Missing required information. Please select a tutor, subject, and time slot.";
} else {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Get tutor and slot details
        $stmt = $pdo->prepare("
            SELECT 
                u.first_name,
                u.last_name,
                tp.hourly_rate,
                un.name as unit_name,
                sl.start_time,
                sl.end_time
            FROM users u
            JOIN tutor_profiles tp ON u.id = tp.user_id
            JOIN tutor_units tu ON u.id = tu.tutor_id
            JOIN units un ON tu.unit_id = un.id
            JOIN availability_slots sl ON u.id = sl.tutor_id AND sl.unit_id = un.id
            WHERE u.id = ? 
            AND un.id = ? 
            AND sl.id = ?
            AND sl.is_booked = 0
        ");
        $stmt->execute([$tutor_id, $subject_id, $slot_id]);
        $details = $stmt->fetch();
        
        if (!$details) {
            $error_message = "Sorry, the selected time slot is no longer available for this unit/tutor. Please select another slot.";
        } else {
            // Calculate session duration and amount
            $start_time = new DateTime($details['start_time']);
            $end_time = new DateTime($details['end_time']);
            $duration = $start_time->diff($end_time);
            $hours = $duration->h + ($duration->i / 60);
            $amount = $hours * $details['hourly_rate'];
        }

        // Fetch tutee profile
        $user_stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $user_stmt->execute([$_SESSION['user_id']]);
        $tutee = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $profile_incomplete = (
            empty($tutee['first_name']) ||
            empty($tutee['last_name']) ||
            empty($tutee['student_id']) ||
            empty($tutee['year_of_study']) ||
            empty($tutee['course'])
        );
        if ($profile_incomplete) {
            echo '<div class="alert alert-danger">You must complete your profile before booking a session. <a href="profile.php" class="btn btn-primary btn-sm ms-2">Complete Profile</a></div>';
            exit();
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - TutorApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .card-body {
            padding: 2rem;
        }
        .payment-option {
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .payment-option i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .mobile-input {
            max-width: 300px;
            margin: 0 auto;
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
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        .alert-info {
            background: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border: none;
            border-radius: 10px;
            color: #dc3545;
        }
        .main-content {
            padding: 40px 0;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .form-control {
            border: 1px solid rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4">Complete Your Payment</h3>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error_message; ?>
                                    <br>
                                    <a href="select_time_slot.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>&subject_id=<?php echo htmlspecialchars($subject_id); ?>" class="btn btn-primary mt-2">Back to Time Slots</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-4">
                                    <h5>Session Summary</h5>
                                    <p class="mb-1"><strong>Unit:</strong> <?php echo htmlspecialchars($details['unit_name']); ?></p>
                                    <p class="mb-1"><strong>Tutor:</strong> <?php echo htmlspecialchars($details['first_name'] . ' ' . $details['last_name']); ?></p>
                                    <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y', strtotime($details['start_time'])); ?></p>
                                    <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($details['start_time'])); ?> - <?php echo date('g:i A', strtotime($details['end_time'])); ?></p>
                                    <p class="mb-1"><strong>Duration:</strong> <?php echo $hours; ?> hour(s)</p>
                                    <p class="mb-0"><strong>Amount:</strong> KES <?php echo number_format($amount, 2); ?></p>
                                </div>
                                <form id="paymentForm" action="initiate_payment.php" method="POST" autocomplete="off">
                                    <input type="hidden" name="tutor_id" value="<?php echo htmlspecialchars($tutor_id); ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                                    <input type="hidden" name="slot_id" value="<?php echo htmlspecialchars($slot_id); ?>">
                                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                                    <input type="hidden" name="session_type" value="online">
                                    <div class="payment-option selected">
                                        <div class="text-center">
                                            <i class="fas fa-mobile-alt"></i>
                                            <h5>M-PESA</h5>
                                            <p class="text-muted">Pay using M-PESA mobile money</p>
                                            <div class="mobile-input mt-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">+254</span>
                                                    <input type="text" class="form-control" name="phone_number" id="phone_number" placeholder="7XXXXXXXX or 2547XXXXXXXX" maxlength="12" required pattern="[0-9]{9,12}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-primary w-100" id="payBtn">Pay Now</button>
                                        <div id="paymentStatus" class="mt-3"></div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 