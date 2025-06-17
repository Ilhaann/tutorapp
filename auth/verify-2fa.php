<?php
require_once '../config/config.php';

if (!isset($_SESSION['temp_user_id'])) {
    redirect(APP_URL . '/auth/login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize_input($_POST['code']);
    
    $db = new Database();
    $stmt = $db->executeQuery(
        "SELECT two_factor_secret FROM users WHERE id = ?",
        [$_SESSION['temp_user_id']]
    );
    
    if ($stmt && $user = $stmt->fetch()) {
        require_once '../vendor/autoload.php';
        $ga = new PHPGangsta_GoogleAuthenticator();
        
        if ($ga->verifyCode($user['two_factor_secret'], $code, 2)) {
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['role'] = $_SESSION['temp_role'];
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_role']);
            redirect(APP_URL . '/index.php');
        } else {
            $error = 'Invalid verification code.';
        }
    } else {
        $error = 'User not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Two-Factor Authentication</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <p class="text-center mb-4">Please enter the 6-digit code from your authenticator app.</p>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="code" class="form-label">Verification Code</label>
                                <input type="text" class="form-control" id="code" name="code" maxlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 