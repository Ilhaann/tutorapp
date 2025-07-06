<?php
require_once 'config/database.php';

echo "<h2>Manual Payment Update</h2>";

// Get the checkout request ID from URL parameter
$checkoutRequestID = $_GET['checkout_id'] ?? null;

if (!$checkoutRequestID) {
    echo "<p style='color: red;'>Please provide a checkout_request_id parameter in the URL.</p>";
    echo "<p>Example: manual_payment_update.php?checkout_id=ws_CO_05072025160944968704640885</p>";
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h3>Updating Payment: $checkoutRequestID</h3>";
    
    // First, check if the payment exists
    $checkStmt = $pdo->prepare("SELECT id, session_id, status, amount FROM payments WHERE checkout_request_id = ?");
    $checkStmt->execute([$checkoutRequestID]);
    $payment = $checkStmt->fetch();
    
    if (!$payment) {
        echo "<p style='color: red;'>❌ Payment not found with checkout_request_id: $checkoutRequestID</p>";
        
        // Show recent payments for reference
        $recentStmt = $pdo->prepare("SELECT checkout_request_id, status, created_at FROM payments ORDER BY created_at DESC LIMIT 5");
        $recentStmt->execute();
        $recentPayments = $recentStmt->fetchAll();
        
        echo "<h4>Recent Payments:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Checkout Request ID</th><th>Status</th><th>Created</th></tr>";
        foreach ($recentPayments as $recent) {
            echo "<tr><td>{$recent['checkout_request_id']}</td><td>{$recent['status']}</td><td>{$recent['created_at']}</td></tr>";
        }
        echo "</table>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Payment found:</p>";
    echo "<ul>";
    echo "<li>Payment ID: {$payment['id']}</li>";
    echo "<li>Session ID: {$payment['session_id']}</li>";
    echo "<li>Current Status: {$payment['status']}</li>";
    echo "<li>Amount: {$payment['amount']}</li>";
    echo "</ul>";
    
    // Update the payment to completed
    $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', mpesa_receipt_number = ?, updated_at = NOW() WHERE checkout_request_id = ?");
    $receipt = 'MANUAL_UPDATE_' . time();
    $stmt->execute([$receipt, $checkoutRequestID]);
    $rowsUpdated = $stmt->rowCount();
    
    if ($rowsUpdated > 0) {
        echo "<p style='color: green;'>✅ Payment updated successfully!</p>";
        
        // Update session status
        $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$payment['session_id']]);
        echo "<p style='color: green;'>✅ Session updated to scheduled and paid</p>";
        
        // Check if there's a slot to mark as booked
        $slotStmt = $pdo->prepare("SELECT slot_id FROM sessions WHERE id = ?");
        $slotStmt->execute([$payment['session_id']]);
        $session = $slotStmt->fetch();
        
        if ($session && $session['slot_id']) {
            $stmt = $pdo->prepare("UPDATE availability_slots SET is_booked = 1 WHERE id = ?");
            $stmt->execute([$session['slot_id']]);
            echo "<p style='color: green;'>✅ Time slot marked as booked</p>";
        }
        
        echo "<h4>Summary:</h4>";
        echo "<ul>";
        echo "<li>Payment status: pending → completed</li>";
        echo "<li>Receipt number: $receipt</li>";
        echo "<li>Session status: updated to scheduled</li>";
        echo "<li>Payment status: updated to paid</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to update payment</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
table { margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style> 