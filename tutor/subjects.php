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

$success = '';
$error = '';

// Get user details with profile picture
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, tp.profile_picture
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle unit creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add_unit') {
            // Validate input
            $unit_name = trim($_POST['unit_name']);
            $unit_code = trim($_POST['unit_code']);
            $unit_description = trim($_POST['unit_description']);
            
            if (empty($unit_name) || empty($unit_code)) {
                throw new Exception("Unit name and code are required.");
            }
            
            // Check if unit already exists by code or name
            $stmt = $pdo->prepare("SELECT id FROM units WHERE code = ? OR name = ?");
            $stmt->execute([$unit_code, $unit_name]);
            $existing_unit = $stmt->fetch();
            if ($existing_unit) {
                $unit_id = $existing_unit['id'];
                // Check if tutor already teaches this unit
                $stmt = $pdo->prepare("SELECT 1 FROM tutor_units WHERE tutor_id = ? AND unit_id = ?");
                $stmt->execute([$_SESSION['user_id'], $unit_id]);
                if ($stmt->fetch()) {
                    throw new Exception("You already teach this unit.");
                }
                // Add the unit to tutor's units
                $stmt = $pdo->prepare("INSERT INTO tutor_units (tutor_id, unit_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $unit_id]);
                $success = "Unit added successfully!";
            } else {
                // Insert new unit
                $stmt = $pdo->prepare("
                    INSERT INTO units (name, code, description)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$unit_name, $unit_code, $unit_description]);
                $unit_id = $pdo->lastInsertId();
                // Add the unit to tutor's units
                $stmt = $pdo->prepare("INSERT INTO tutor_units (tutor_id, unit_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $unit_id]);
                $success = "Unit added successfully!";
            }
        } elseif ($_POST['action'] === 'delete_unit' && isset($_POST['unit_id'])) {
            // Delete the unit from tutor's units
            $stmt = $pdo->prepare("
                DELETE FROM tutor_units 
                WHERE tutor_id = ? AND unit_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $_POST['unit_id']]);
            
            $success = "Unit removed successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get tutor's units
$stmt = $pdo->prepare("
    SELECT u.*
    FROM units u
    JOIN tutor_units tu ON u.id = tu.unit_id
    WHERE tu.tutor_id = ?
    ORDER BY u.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$units = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Units - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .units-container {
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
        .unit-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .add-unit-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .unit-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .unit-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .unit-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            background: #e9ecef;
            color: #495057;
        }
        .unit-badge.beginner { background: #cff4fc; color: #055160; }
        .unit-badge.intermediate { background: #d1e7dd; color: #0f5132; }
        .unit-badge.advanced { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>
    <div class="units-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="add-unit-card">
                    <h2 class="mb-4">Add New Unit</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_unit">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit_name" class="form-label">Unit Name</label>
                                <input type="text" class="form-control" id="unit_name" name="unit_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_code" class="form-label">Unit Code</label>
                                <input type="text" class="form-control" id="unit_code" name="unit_code" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="unit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="unit_description" name="unit_description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Unit
                        </button>
                    </form>
                </div>

                <div class="unit-card">
                    <h2 class="mb-4">Your Units</h2>
                    <?php if (empty($units)): ?>
                        <div class="alert alert-info">
                            You haven't added any units yet. Use the form above to add your first unit.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($units as $unit): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="unit-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($unit['name']); ?></h5>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars($unit['code']); ?></p>
                                            </div>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this unit?');">
                                                <input type="hidden" name="action" value="delete_unit">
                                                <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <?php if (!empty($unit['description'])): ?>
                                            <p class="mt-2 mb-0 text-muted">
                                                <?php echo htmlspecialchars($unit['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
