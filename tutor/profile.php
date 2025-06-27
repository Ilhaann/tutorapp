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

// Get user data
$stmt = $pdo->prepare("
    SELECT u.*, tp.hourly_rate, tp.bio, tp.profile_picture, tp.offers_online, tp.offers_in_person
    FROM users u
    LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fix undefined array key warnings by using null coalescing/defaults
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$student_id = $user['student_id'] ?? '';
$year_of_study = $user['year_of_study'] ?? '';
$course = $user['course'] ?? '';
$hourly_rate = $user['hourly_rate'] ?? '';
$bio = $user['bio'] ?? '';
$profile_picture = $user['profile_picture'] ?? 'default-avatar.jpg';
$offers_online = isset($user['offers_online']) ? $user['offers_online'] : 1;
$offers_in_person = isset($user['offers_in_person']) ? $user['offers_in_person'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Update user data
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, student_id = ?, year_of_study = ?, course = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['student_id'],
            $_POST['year_of_study'],
            $_POST['course'],
            $_SESSION['user_id']
        ]);

        // Update or insert tutor profile
        $stmt = $pdo->prepare("
            INSERT INTO tutor_profiles (user_id, hourly_rate, bio, offers_online, offers_in_person)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hourly_rate = VALUES(hourly_rate),
                bio = VALUES(bio),
                offers_online = VALUES(offers_online),
                offers_in_person = VALUES(offers_in_person)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['hourly_rate'],
            $_POST['bio'],
            isset($_POST['offers_online']) ? 1 : 0,
            isset($_POST['offers_in_person']) ? 1 : 0
        ]);

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }
            
            // Validate file size (max 5MB)
            $max_size = 5 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                throw new Exception("File size too large. Maximum size is 5MB.");
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = '../assets/images/avatars/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload profile picture.");
            }

            // Update profile picture in database
            $stmt = $pdo->prepare("
                UPDATE tutor_profiles 
                SET profile_picture = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$filename, $_SESSION['user_id']]);
        }

        // Commit transaction
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT u.*, tp.hourly_rate, tp.bio, tp.profile_picture, tp.offers_online, tp.offers_in_person
            FROM users u
            LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "An error occurred while updating your profile. Please try again.";
        error_log("Profile update error: " . $e->getMessage());
    }

    // In PHP form handler, ensure at least one session type is selected
    if (!isset($_POST['offers_online']) && !isset($_POST['offers_in_person'])) {
        $error = 'Please select at least one session type.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .profile-container {
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
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="profile-card">
                    <h2 class="mb-4">Edit Profile</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $profile_picture; ?>"
                                     class="profile-picture mb-3"
                                     alt="Profile Picture">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Change Profile Picture <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="student_id" class="form-label">Student ID <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="year_of_study" class="form-label">Year of Study <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="year_of_study" name="year_of_study" value="<?php echo htmlspecialchars($year_of_study); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="course" class="form-label">Course <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="course" name="course" value="<?php echo htmlspecialchars($course); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="hourly_rate" class="form-label">Hourly Rate (KES) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo htmlspecialchars($hourly_rate); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" required><?php echo htmlspecialchars($bio); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Session Types Offered <span class="text-danger">*</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="offers_online" name="offers_online" value="1" <?php if ($offers_online) echo 'checked'; ?>>
                                        <label class="form-check-label" for="offers_online">I offer <strong>online</strong> sessions</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="offers_in_person" name="offers_in_person" value="1" <?php if ($offers_in_person) echo 'checked'; ?>>
                                        <label class="form-check-label" for="offers_in_person">I offer <strong>in-person</strong> sessions</label>
                                    </div>
                                    <div id="sessionTypeError" class="text-danger mt-1" style="display:none;">Please select at least one session type.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        var online = document.getElementById('offers_online').checked;
        var inPerson = document.getElementById('offers_in_person').checked;
        var errorDiv = document.getElementById('sessionTypeError');
        if (!online && !inPerson) {
            errorDiv.style.display = 'block';
            e.preventDefault();
        } else {
            errorDiv.style.display = 'none';
        }
    });
    </script>
</body>
</html> 