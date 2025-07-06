<?php
require_once 'config/database.php';

echo "<h2>Auto-Complete Payment System</h2>";

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
    
    if (empty($pendingPayments)) {
        echo "<p>No pending payments to auto-complete.</p>";
        
        // Show recent payments
        $stmt = $pdo->prepare("
            SELECT id, checkout_request_id, status, amount, created_at, updated_at 
            FROM payments 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentPayments = $stmt->fetchAll();
        
        echo "<h3>Recent Payments:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Checkout ID</th><th>Status</th><th>Amount</th><th>Created</th><th>Updated</th></tr>";
        foreach ($recentPayments as $payment) {
            $statusColor = $payment['status'] === 'completed' ? 'green' : 'orange';
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['checkout_request_id']}</td>";
            echo "<td style='color: $statusColor;'>{$payment['status']}</td>";
            echo "<td>{$payment['amount']}</td>";
            echo "<td>{$payment['created_at']}</td>";
            echo "<td>{$payment['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<h3>Auto-completing pending payments:</h3>";
        
        foreach ($pendingPayments as $payment) {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update payment to completed
                $receipt = 'AUTO_COMPLETE_' . time() . '_' . $payment['id'];
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
                
                echo "<p style='color: green;'>✅ Payment ID {$payment['id']} auto-completed successfully!</p>";
                echo "<ul>";
                echo "<li>Checkout ID: {$payment['checkout_request_id']}</li>";
                echo "<li>Amount: {$payment['amount']}</li>";
                echo "<li>Receipt: $receipt</li>";
                echo "<li>Session updated to scheduled and paid</li>";
                echo "</ul>";
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "<p style='color: red;'>❌ Error auto-completing payment {$payment['id']}: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>

<script>
// Auto-refresh every 10 seconds
setTimeout(function() {
    location.reload();
}, 10000);
</script> 