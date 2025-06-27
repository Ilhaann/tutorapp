<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get tutor's units
$units_query = "
    SELECT u.id, u.name, u.code
    FROM units u
    JOIN tutor_units tu ON u.id = tu.unit_id
    WHERE tu.tutor_id = :tutor_id
    ORDER BY u.name
";
$units_stmt = $pdo->prepare($units_query);
$units_stmt->execute([':tutor_id' => $user_id]);
$units = $units_stmt->fetchAll();

// Get current availability
$stmt = $pdo->prepare("
    SELECT a.*, u.name as unit_name
    FROM availability_slots a
    LEFT JOIN sessions s ON a.id = s.slot_id
    LEFT JOIN units u ON s.unit_id = u.id
    WHERE a.tutor_id = ? 
    AND a.start_time >= CURDATE()
    ORDER BY a.start_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$current_availability = $stmt->fetchAll();

// Group availability by day
$availability_by_day = [];
foreach ($current_availability as $slot) {
    $day = date('Y-m-d', strtotime($slot['start_time']));
    if (!isset($availability_by_day[$day])) {
        $availability_by_day[$day] = [];
    }
    $availability_by_day[$day][] = $slot;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $unit_id = $_POST['unit_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Create datetime values
    $start_datetime = date('Y-m-d H:i:s', strtotime("$date $start_time"));
    $end_datetime = date('Y-m-d H:i:s', strtotime("$date $end_time"));
    
    // Validate times
    if ($start_datetime >= $end_datetime) {
        $error = "End time must be after start time";
    } else {
        // Check for overlapping slots
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM availability_slots 
            WHERE tutor_id = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $start_datetime, $start_datetime,
            $end_datetime, $end_datetime,
            $start_datetime, $end_datetime
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "This time slot overlaps with an existing slot";
        } else {
            try {
                // Insert into availability_slots
                $stmt = $pdo->prepare("
                    INSERT INTO availability_slots (tutor_id, unit_id, start_time, end_time) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $unit_id, $start_datetime, $end_datetime]);
                
                $success = "Time slot added successfully";
                
                // Redirect to refresh the page
                header("Location: availability.php");
                exit();
            } catch (Exception $e) {
                $error = "Error adding time slot: " . $e->getMessage();
            }
        }
    }
}

// Handle delete time slot
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'delete' &&
    isset($_POST['availability_id'])
) {
    $availability_id = intval($_POST['availability_id']);

    // Get all session IDs for this slot
    $stmt = $pdo->prepare("SELECT id FROM sessions WHERE slot_id = ?");
    $stmt->execute([$availability_id]);
    $session_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($session_ids)) {
        // Delete all payments for these sessions
        $in = str_repeat('?,', count($session_ids) - 1) . '?';
        $pdo->prepare("DELETE FROM payments WHERE session_id IN ($in)")->execute($session_ids);
        // Delete all sessions for this slot
        $pdo->prepare("DELETE FROM sessions WHERE id IN ($in)")->execute($session_ids);
    }

    // Then, delete the slot itself
    $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$availability_id, $user_id]);
    header("Location: availability.php");
    exit();
}

$days = [
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',
    'Sunday' => 'Sunday'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/sidebar.css" rel="stylesheet">
    <style>
        .availability-container {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .time-slot {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
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
        .day-slots {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        .day-header {
            color: #1a237e;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .time-slot {
            background: white;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .time-slot:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="availability-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Availability</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Add Time Slot Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add Time Slot</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="unit_id" class="form-label">Unit</label>
                                        <select class="form-select" id="unit_id" name="unit_id" required>
                                            <option value="">Select Unit</option>
                                            <?php foreach ($units as $unit): ?>
                                                <option value="<?php echo $unit['id']; ?>">
                                                    <?php echo htmlspecialchars($unit['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" required 
                                               min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label">End Time</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_slot" class="btn btn-primary">Add Time Slot</button>
                        </form>
                    </div>
                </div>

                <!-- Current Availability -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current Availability</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availability_by_day)): ?>
                            <p class="text-muted">No availability slots set.</p>
                        <?php else: ?>
                            <?php foreach ($availability_by_day as $day => $slots): ?>
                                <div class="day-slots mb-4">
                                    <h6 class="day-header">
                                        <?php echo date('l, F j, Y', strtotime($day)); ?>
                                    </h6>
                                    <div class="time-slots">
                                        <?php foreach ($slots as $slot): ?>
                                            <div class="time-slot">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>
                                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                                            <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                                        </strong>
                                                        <?php if (!empty($slot['unit_name'])): ?>
                                                            <span class="text-muted ms-2">
                                                                (<?php echo htmlspecialchars($slot['unit_name']); ?>)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="availability_id" value="<?php echo $slot['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this time slot?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
