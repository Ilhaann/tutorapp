<?php
session_start();
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        try {
            // Check if user exists and is not verified
            $stmt = $pdo->prepare("SELECT id, first_name, verification_token FROM users WHERE email = ? AND is_verified = FALSE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate new verification code
                $verification_code = bin2hex(random_bytes(16));
                
                // Set new expiration time to 5 minutes from now
                $expiration_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                // Update verification code and expiration time
                $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_expires_at = ? WHERE id = ?");
                $stmt->execute([$verification_code, $expiration_time, $user['id']]);

                // Send verification email
                $verification_link = BASE_URL . "/auth/verify.php";
                $email_subject = "New Verification Code - Strathmore Peer Tutoring";
                $email_body = "Dear " . $user['first_name'] . ",\n\n";
                $email_body .= "Here is your new verification code:\n\n";
                $email_body .= "Verification Code: " . $verification_code . "\n\n";
                $email_body .= "This code will expire in 5 minutes.\n\n";
                $email_body .= "Enter this code at: " . $verification_link . "\n\n";
                $email_body .= "If you did not request this verification, please ignore this email.\n\n";
                $email_body .= "Best regards,\nStrathmore Peer Tutoring Team";

                send_email($email, $email_subject, $email_body);
                $success = 'A new verification code has been sent to your email.';
            } else {
                $error = 'No unverified account found with this email address.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Resend verification error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification Code - Strathmore Peer Tutoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h2>Resend Verification Code</h2>
                <p class="text-muted">Enter your email to receive a new verification code</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Resend Code</button>
            </form>

            <div class="text-center mt-3">
                <p>Remember your code? <a href="verify.php">Verify your account</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 