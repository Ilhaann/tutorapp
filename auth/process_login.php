<?php
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];

    // Validate email domain
    if (!is_strathmore_email($email)) {
        $_SESSION['error'] = 'Only Strathmore University email addresses are allowed.';
        redirect('login.php');
    }

    $db = new Database();
    $stmt = $db->executeQuery(
        "SELECT id, password, role, two_factor_enabled FROM users WHERE email = ? AND is_verified = 1",
        [$email]
    );

    if ($stmt && $user = $stmt->fetch()) {
        if (password_verify($password, $user['password'])) {
            if ($user['two_factor_enabled']) {
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_role'] = $user['role'];
                redirect('verify-2fa.php');
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'tutor':
                        redirect('tutor/dashboard.php');
                        break;
                    case 'tutee':
                        redirect('tutee/dashboard.php');
                        break;
                    case 'admin':
                        redirect('admin/dashboard.php');
                        break;
                    default:
                        redirect('index.php');
                }
            }
        } else {
            $_SESSION['error'] = 'Invalid password.';
            redirect('login.php');
        }
    } else {
        $_SESSION['error'] = 'Invalid email or account not verified.';
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>
