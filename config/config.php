<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/database.php';

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Strathmore Tutor Platform');
define('APP_URL', 'http://localhost/tutorapp');

// File upload settings
define('STRATHMORE_EMAIL_DOMAIN', '@strathmore.edu');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', 'uploads/');

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@tutorapp.strathmore.edu');
define('SMTP_FROM_NAME', 'Strathmore Tutor Platform');

// Payment settings (if monetized)
define('PLATFORM_COMMISSION', 0.10); // 10%
define('MINIMUM_WITHDRAWAL', 1000.00); // KES 1000

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_strathmore_email($email) {
    // Debug logging
    error_log("Checking email: " . $email);
    error_log("Extracted domain: " . strtolower(substr(strrchr($email, "@"), 1)));
    
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    $allowed_domains = ['strathmore.edu', 'smu.ac.ke'];
    $result = in_array($domain, $allowed_domains);
    
    // Debug logging
    error_log("Allowed domains: " . implode(", ", $allowed_domains));
    error_log("Validation result: " . ($result ? 'PASS' : 'FAIL'));
    
    return $result;
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function redirect($url) {
    if (strpos($url, 'http') === 0) {
        // If it's an absolute URL, use it as is
        header("Location: " . $url);
    } else {
        // If it's a relative URL, prepend APP_URL only if it doesn't start with /
        header("Location: " . APP_URL . "/" . ltrim($url, '/'));
    }
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function get_user_role() {
    return $_SESSION['role'] ?? null;
}

function require_role($role) {
    require_login();
    if (get_user_role() !== $role) {
        redirect('index.php');
    }
}

// File upload function
function upload_file($file, $target_dir = UPLOAD_DIR) {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = basename($file['name']);
    $target_path = $target_dir . uniqid() . '_' . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    }
    return false;
}

// Email function
function send_email($to, $subject, $message) {
    require_once __DIR__ . '/SendMail.php';
    
    $mailer = new \App\Config\SendMail();
    $mailMsg = [
        'to_email' => $to,
        'to_name' => '', // You can add the recipient's name if available
        'subject' => $subject,
        'message' => $message
    ];
    
    return $mailer->send($mailMsg);
}
?> 