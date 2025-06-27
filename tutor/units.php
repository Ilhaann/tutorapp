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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete existing tutor units
        $stmt = $pdo->prepare("DELETE FROM tutor_units WHERE tutor_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Insert new tutor units
        if (!empty($_POST['units'])) {
            $stmt = $pdo->prepare("INSERT INTO tutor_units (tutor_id, unit_id) VALUES (?, ?)");
            foreach ($_POST['units'] as $unit_id) {
                $stmt->execute([$_SESSION['user_id'], $unit_id]);
            }
        }
        $success = "Units updated successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all available units
$stmt = $pdo->prepare("SELECT * FROM units ORDER BY name ASC");
$stmt->execute();
$all_units = $stmt->fetchAll();

// Get tutor's current units
$stmt = $pdo->prepare("
    SELECT u.* 
    FROM units u
    JOIN tutor_units tu ON u.id = tu.unit_id
    WHERE tu.tutor_id = ?
    ORDER BY u.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$tutor_units = $stmt->fetchAll();
$tutor_unit_ids = array_column($tutor_units, 'id');

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Manage Units</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Select Units You Teach</h5>
                        <p class="card-text">Check all the units you are qualified to teach.</p>
                        
                        <div class="row">
                            <?php foreach ($all_units as $unit): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="units[]" 
                                               value="<?php echo $unit['id']; ?>"
                                               id="unit_<?php echo $unit['id']; ?>"
                                               <?php echo in_array($unit['id'], $tutor_unit_ids) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unit_<?php echo $unit['id']; ?>">
                                            <?php echo htmlspecialchars($unit['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?> 