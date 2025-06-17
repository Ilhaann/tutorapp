<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
$db = new Database();
$pdo = $db->getConnection();

// Check if user is logged in and is a tutee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }
            
            if ($_FILES['profile_picture']['size'] > $max_size) {
                throw new Exception("File size too large. Maximum size is 5MB.");
            }
            
            $upload_dir = '../assets/images/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                // Update profile picture in database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
                $success = "Profile picture updated successfully!";
            } else {
                throw new Exception("Failed to upload profile picture.");
            }
        }

        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $student_id = trim($_POST['student_id']);
        $year_of_study = (int)$_POST['year_of_study'];
        $course = trim($_POST['course']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);

        // Validate input
        if (empty($first_name) || empty($last_name) || empty($student_id) || empty($course)) {
            throw new Exception('Please fill in all required fields');
        }

        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, student_id = ?, 
                year_of_study = ?, course = ?, phone = ?, bio = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $first_name, $last_name, $student_id,
            $year_of_study, $course, $phone, $bio,
            $user_id
        ]);

        $success = $success ?: "Profile updated successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: auto;
            min-height: 100%;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-content {
            background-color: #f4f6f9;
            padding: 30px;
            min-height: 100vh;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        .profile-picture:hover {
            transform: scale(1.05);
        }
        .profile-picture-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        .profile-picture-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: rgba(102,126,234,0.9);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            color: white;
            transition: background 0.3s ease;
        }
        .profile-picture-upload:hover {
            background: rgba(102,126,234,1);
        }
        .profile-picture-upload input {
            display: none;
        }
        .form-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem;
            border-color: #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }
        .btn-primary {
            background-color: #667eea;
            border-color: #667eea;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #764ba2;
            border-color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row g-0 flex-nowrap" style="height: 100vh;">
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h4><?php echo APP_NAME; ?></h4>
                </div>
                <div class="sidebar-profile">
                    <div class="avatar-container">
                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $_SESSION['user_id']; ?>.jpg" 
                             onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                             class="rounded-circle"
                             alt="Profile Picture">
                    </div>
                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p>Tutee</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="my_sessions.php">
                        <i class="bi bi-calendar"></i> My Sessions
                    </a>
                    <a class="nav-link" href="tutors.php">
                        <i class="bi bi-search"></i> Find Tutors
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 main-content" style="height: 100vh; overflow-y: auto; padding-bottom: 50px;">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="col-12 text-center">
                                    <div class="profile-picture-container">
                                        <img src="<?php echo APP_URL; ?>/assets/images/profiles/<?php echo $user['profile_picture'] ?? 'default.jpg'; ?>" 
                                             class="profile-picture" alt="Profile Picture" id="profilePreview">
                                        <label class="profile-picture-upload">
                                            <i class="fas fa-camera"></i>
                                            <input type="file" name="profile_picture" accept="image/*" 
                                                   onchange="previewProfilePicture(event)">
                                        </label>
                                    </div>
                                    <h4 class="mt-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                    <p class="text-muted">Tutee</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" 
                                               value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" required>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="year_of_study" class="form-label">Year of Study</label>
                                            <select class="form-select" id="year_of_study" name="year_of_study" required>
                                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($user['year_of_study'] == $i) ? 'selected' : ''; ?>>
                                                        Year <?php echo $i; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="course" class="form-label">Course</label>
                                            <select class="form-select" id="course" name="course" required>
                                                <option value="">Select Course</option>
                                                <option value="BTM" <?php echo ($user['course'] == 'BTM') ? 'selected' : ''; ?>>Bachelor of Science in Tourism Management (BTM)</option>
                                                <option value="BHM" <?php echo ($user['course'] == 'BHM') ? 'selected' : ''; ?>>Bachelor of Science in Hospitality Management (BHM)</option>
                                                <option value="BBSFENG" <?php echo ($user['course'] == 'BBSFENG') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Engineering (BBSFENG)</option>
                                                <option value="BBSFE" <?php echo ($user['course'] == 'BBSFE') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Economics (BBSFE)</option>
                                                <option value="BBSACT" <?php echo ($user['course'] == 'BBSACT') ? 'selected' : ''; ?>>Bachelor of Business Science: Actuarial Science (BBSACT)</option>
                                                <option value="BICS" <?php echo ($user['course'] == 'BICS') ? 'selected' : ''; ?>>Bachelor Of Science In Informatics And Computer Science (BICS)</option>
                                                <option value="BBIT" <?php echo ($user['course'] == 'BBIT') ? 'selected' : ''; ?>>Bachelor Of Business Information Technology (BBIT)</option>
                                                <option value="BCNS" <?php echo ($user['course'] == 'BCNS') ? 'selected' : ''; ?>>BSc. Computer Networks and Cyber Security (BCNS)</option>
                                                <option value="LLB" <?php echo ($user['course'] == 'LLB') ? 'selected' : ''; ?>>Bachelor of Laws (LLB)</option>
                                                <option value="BAC" <?php echo ($user['course'] == 'BAC') ? 'selected' : ''; ?>>Bachelor of Arts in Communication (BAC)</option>
                                                <option value="BAIS" <?php echo ($user['course'] == 'BAIS') ? 'selected' : ''; ?>>Bachelor of Arts in International Studies</option>
                                                <option value="BDP" <?php echo ($user['course'] == 'BDP') ? 'selected' : ''; ?>>Bachelor of Arts in Development Studies and Philosophy (BDP)</option>
                                                <option value="BSCM" <?php echo ($user['course'] == 'BSCM') ? 'selected' : ''; ?>>Bachelor of Science in Supply Chain and Operations Management (BSCM)</option>
                                                <option value="BFS" <?php echo ($user['course'] == 'BFS') ? 'selected' : ''; ?>>Bachelor of Financial Services (BFS)</option>
                                                <option value="BSEEE" <?php echo ($user['course'] == 'BSEEE') ? 'selected' : ''; ?>>Bachelor Of Science In Electrical and Electronics Engineering (BSEEE)</option>
                                                <option value="BScSDS" <?php echo ($user['course'] == 'BScSDS') ? 'selected' : ''; ?>>BSc in Statistics and Data Science (BScSDS)</option>
                                                <option value="BCOM" <?php echo ($user['course'] == 'BCOM') ? 'selected' : ''; ?>>Bachelor of Commerce (BCOM)</option>
                                            </select>
                                        </div>
                                    </div>
                                <div class="col-md-6">
                                    <label for="year_of_study" class="form-label">Year of Study</label>
                                    <select class="form-select" id="year_of_study" name="year_of_study" required>
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($user['year_of_study'] == $i) ? 'selected' : ''; ?>>
                                                Year <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="course" class="form-label">Course</label>
                                    <select class="form-select" id="course" name="course" required>
                                        <option value="">Select Course</option>
                                        <option value="BTM" <?php echo ($user['course'] == 'BTM') ? 'selected' : ''; ?>>Bachelor of Science in Tourism Management (BTM)</option>
                                        <option value="BHM" <?php echo ($user['course'] == 'BHM') ? 'selected' : ''; ?>>Bachelor of Science in Hospitality Management (BHM)</option>
                                        <option value="BBSFENG" <?php echo ($user['course'] == 'BBSFENG') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Engineering (BBSFENG)</option>
                                        <option value="BBSFE" <?php echo ($user['course'] == 'BBSFE') ? 'selected' : ''; ?>>Bachelor of Business Science: Financial Economics (BBSFE)</option>
                                        <option value="BBSACT" <?php echo ($user['course'] == 'BBSACT') ? 'selected' : ''; ?>>Bachelor of Business Science: Actuarial Science (BBSACT)</option>
                                        <option value="BICS" <?php echo ($user['course'] == 'BICS') ? 'selected' : ''; ?>>Bachelor Of Science In Informatics And Computer Science (BICS)</option>
                                        <option value="BBIT" <?php echo ($user['course'] == 'BBIT') ? 'selected' : ''; ?>>Bachelor Of Business Information Technology (BBIT)</option>
                                        <option value="BCNS" <?php echo ($user['course'] == 'BCNS') ? 'selected' : ''; ?>>BSc. Computer Networks and Cyber Security (BCNS)</option>
                                        <option value="LLB" <?php echo ($user['course'] == 'LLB') ? 'selected' : ''; ?>>Bachelor of Laws (LLB)</option>
                                        <option value="BAC" <?php echo ($user['course'] == 'BAC') ? 'selected' : ''; ?>>Bachelor of Arts in Communication (BAC)</option>
                                        <option value="BAIS" <?php echo ($user['course'] == 'BAIS') ? 'selected' : ''; ?>>Bachelor of Arts in International Studies</option>
                                        <option value="BDP" <?php echo ($user['course'] == 'BDP') ? 'selected' : ''; ?>>Bachelor of Arts in Development Studies and Philosophy (BDP)</option>
                                        <option value="BSCM" <?php echo ($user['course'] == 'BSCM') ? 'selected' : ''; ?>>Bachelor of Science in Supply Chain and Operations Management (BSCM)</option>
                                        <option value="BFS" <?php echo ($user['course'] == 'BFS') ? 'selected' : ''; ?>>Bachelor of Financial Services (BFS)</option>
                                        <option value="BSEEE" <?php echo ($user['course'] == 'BSEEE') ? 'selected' : ''; ?>>Bachelor Of Science In Electrical and Electronics Engineering (BSEEE)</option>
                                        <option value="BScSDS" <?php echo ($user['course'] == 'BScSDS') ? 'selected' : ''; ?>>BSc in Statistics and Data Science (BScSDS)</option>
                                        <option value="BCOM" <?php echo ($user['course'] == 'BCOM') ? 'selected' : ''; ?>>Bachelor of Commerce (BCOM)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview profile picture before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
