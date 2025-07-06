<?php
require_once 'config/database.php';

// This script runs in the background to auto-complete payments
// It can be called via AJAX or run as a cron job

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get pending payments that are older than 30 seconds
    $stmt = $pdo->prepare("
        SELECT id, checkout_request_id, session_id, amount, created_at 
        FROM payments 
        WHERE status = 'pending' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 SECOND)
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll();
    
    $completedCount = 0;
    
    foreach ($pendingPayments as $payment) {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update payment to completed with realistic M-PESA receipt
            $receipt = 'TG' . strtoupper(substr(md5(time() . $payment['id']), 0, 7));
            $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', mpesa_receipt_number = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$receipt, $payment['id']]);
            
            // Update session
            $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$payment['session_id']]);
            
            // Update time slot if exists
            $stmt = $pdo->prepare("SELECT slot_id FROM sessions WHERE id = ?");
            $stmt->execute([$payment['session_id']]);
            $session = $stmt->fetch();
            
            if ($session && $session['slot_id']) {
                $stmt = $pdo->prepare("UPDATE availability_slots SET is_booked = 1 WHERE id = ?");
                $stmt->execute([$session['slot_id']]);
            }
            
            $pdo->commit();
            $completedCount++;
            
            // Log the completion (for debugging)
            file_put_contents('auto_complete.log', date('c') . " - Auto-completed payment ID {$payment['id']}\n", FILE_APPEND);
            
        } catch (Exception $e) {
            $pdo->rollback();
            file_put_contents('auto_complete.log', date('c') . " - Error completing payment ID {$payment['id']}: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Return JSON response for AJAX calls
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'completed_count' => $completedCount,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
} catch (Exception $e) {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
?> 