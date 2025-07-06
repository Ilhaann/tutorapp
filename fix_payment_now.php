<?php
require_once 'config/database.php';

$checkoutRequestID = 'ws_CO_05072025171629065704640885';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>Fixing Payment: $checkoutRequestID</h2>";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update payment
    $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', mpesa_receipt_number = ?, updated_at = NOW() WHERE checkout_request_id = ?");
    $receipt = 'FIXED_RECEIPT_' . time();
    $stmt->execute([$receipt, $checkoutRequestID]);
    $rowsUpdated = $stmt->rowCount();
    
    echo "<p>Payment rows updated: $rowsUpdated</p>";
    
    // Get session ID
    $stmt = $pdo->prepare("SELECT session_id FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestID]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        // Update session
        $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$payment['session_id']]);
        echo "<p>Session updated to scheduled and paid</p>";
        
        // Update time slot if exists
        $stmt = $pdo->prepare("SELECT slot_id FROM sessions WHERE id = ?");
        $stmt->execute([$payment['session_id']]);
        $session = $stmt->fetch();
        
        if ($session && $session['slot_id']) {
            $stmt = $pdo->prepare("UPDATE availability_slots SET is_booked = 1 WHERE id = ?");
            $stmt->execute([$session['slot_id']]);
            echo "<p>Time slot marked as booked</p>";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "<p style='color: green;'>✅ Payment fixed successfully!</p>";
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT status, mpesa_receipt_number FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestID]);
    $result = $stmt->fetch();
    
    echo "<h3>Verification:</h3>";
    echo "<p><strong>Status:</strong> {$result['status']}</p>";
    echo "<p><strong>Receipt:</strong> {$result['mpesa_receipt_number']}</p>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
</style> 