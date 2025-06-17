<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['role'])) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit();
    }
    $redirect_url = $_SESSION['role'] === 'tutor' ? '/tutor/dashboard.php' : '/tutee/dashboard.php';
    header('Location: ' . APP_URL . $redirect_url);
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = $_POST['verification_code'] ?? '';

    if (empty($verification_code)) {
        $error = 'Please enter the verification code';
    } else {
        try {
            // Get email from session
            $email = $_SESSION['temp_email'] ?? '';
            if (empty($email)) {
                $error = 'Session expired. Please register again.';
            } else {
                // Check if verification code matches
                $stmt = $pdo->prepare("SELECT id, verification_token, role, first_name, last_name FROM users WHERE email = ? AND verification_token = ?");
                $stmt->execute([$email, $verification_code]);
                $user = $stmt->fetch();

                if ($user) {
                    // Update user as verified
                    $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['is_verified'] = true;
                    unset($_SESSION['temp_email']); // Clear temporary email

                    // Debug session variables
                    error_log("Verification successful - User ID: " . $_SESSION['user_id']);
                    error_log("Role: " . $_SESSION['role']);
                    error_log("Name: " . $_SESSION['name']);

                    // Redirect based on role
                    if ($_SESSION['role'] === 'tutor') {
                        header('Location: ' . APP_URL . '/tutor/dashboard.php');
                    } else {
                        header('Location: ' . APP_URL . '/tutee/dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid verification code';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Verification error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account - Strathmore Peer Tutoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .verification-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        .verification-icon {
            width: 80px;
            height: 80px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .verification-icon i {
            font-size: 40px;
            color: #667eea;
        }
        .verification-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 8px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin: 20px 0;
        }
        .verification-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-verify {
            background: #667eea;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-verify:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <div class="verification-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="text-center mb-4">
                <h2>Verify Your Account</h2>
                <p class="text-muted">Enter the 6-digit code sent to your email</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" class="form-control verification-input" id="verification_code" name="verification_code" 
                           maxlength="6" pattern="[0-9]*" inputmode="numeric" required autofocus
                           placeholder="Enter 6-digit code">
                </div>

                <button type="submit" class="btn btn-verify w-100">Verify Account</button>
            </form>

            <div class="text-center mt-4">
                <p class="mt-3">Didn't receive the code? <a href="resend_verification.php">Resend verification code</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css"></script>
    <script>
        // Auto-focus and move to next input
        const input = document.getElementById('verification_code');
        input.addEventListener('input', function() {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html> 