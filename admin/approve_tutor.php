<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Access denied: User not admin");
    header("Location: ../auth/login.php");
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Received POST request");
    
    $tutorId = $_POST['tutor_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    error_log("POST data - tutor_id: " . $tutorId . ", action: " . $action);

    if (!is_numeric($tutorId) || !in_array($action, ['approve', 'reject'])) {
        error_log("Invalid parameters: tutor_id=" . $tutorId . ", action=" . $action);
        header("Location: users.php?error=Invalid+parameters");
        exit();
    }

    try {
        error_log("Creating database connection");
        $db = new Database();
        
        // Get tutor info
        error_log("Querying tutor info for ID: " . $tutorId);
        $stmt = $db->executeQuery("SELECT id, first_name, last_name, email FROM users WHERE id = ? AND role = 'tutor'", [$tutorId]);
        $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tutor) {
            throw new Exception('Tutor not found');
        }

        // Update tutor status
        $status = $action === 'approve' ? 'approved' : 'rejected';
        error_log("Updating tutor status to: " . $status);
        $stmt = $db->executeQuery("UPDATE users SET approval_status = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
            [$status, $action === 'approve' ? 1 : 0, $tutorId]);
            
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update tutor status');
        }

        // Send email
        $subject = $action === 'approve' ? 
            "Tutor Application Approved - Strathmore Tutor Platform" : 
            "Tutor Application Status - Strathmore Tutor Platform";
            
        $message = "Dear " . htmlspecialchars($tutor['first_name']) . " " . htmlspecialchars($tutor['last_name']) . ",\n\n";
        $message .= $action === 'approve' ? 
            "Your tutor application has been approved! You can now start accepting tutoring sessions.\n\n" : 
            "Your tutor application has been reviewed and we regret to inform you that it has been rejected.\n";
        $message .= "Best regards,\nStrathmore Tutor Platform Team";
            
        error_log("Sending email to: " . $tutor['email']);
        send_email($tutor['email'], $subject, $message);

        // Redirect with success message
        error_log("Approval process completed successfully");
        header("Location: view_user.php?id=" . $tutorId . "&success=" . urlencode($action === 'approve' ? 'Tutor approved successfully' : 'Tutor rejected successfully'));
        exit;
        
    } catch (Exception $e) {
        error_log("Exception caught: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: view_user.php?id=" . $tutorId . "&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    error_log("Not a POST request");
    header("Location: users.php");
    exit;
}
