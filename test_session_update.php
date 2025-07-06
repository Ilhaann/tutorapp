<?php
require_once 'config/database.php';

echo "<h2>Testing Session Update</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check current pending payments
    $stmt = $pdo->prepare("
        SELECT p.id as payment_id, p.checkout_request_id, p.status as payment_status, 
               s.id as session_id, s.status as session_status, s.payment_status as session_payment_status,
               s.tutor_id, s.tutee_id
        FROM payments p
        LEFT JOIN sessions s ON p.session_id = s.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll();
    
    echo "<h3>Current Pending Payments:</h3>";
    if (empty($pendingPayments)) {
        echo "<p>No pending payments found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Payment ID</th><th>Checkout ID</th><th>Payment Status</th><th>Session ID</th><th>Session Status</th><th>Session Payment Status</th><th>Tutor ID</th></tr>";
        foreach ($pendingPayments as $payment) {
            echo "<tr>";
            echo "<td>{$payment['payment_id']}</td>";
            echo "<td>{$payment['checkout_request_id']}</td>";
            echo "<td>{$payment['payment_status']}</td>";
            echo "<td>{$payment['session_id']}</td>";
            echo "<td>{$payment['session_status']}</td>";
            echo "<td>{$payment['session_payment_status']}</td>";
            echo "<td>{$payment['tutor_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test auto-completion on the first pending payment
    if (!empty($pendingPayments)) {
        $payment = $pendingPayments[0];
        echo "<h3>Testing Auto-Completion for Payment ID: {$payment['payment_id']}</h3>";
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update payment to completed
            $receipt = 'TG' . strtoupper(substr(md5(time() . $payment['payment_id']), 0, 7));
            $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', mpesa_receipt_number = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$receipt, $payment['payment_id']]);
            
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
                echo "<p>✅ Time slot marked as booked</p>";
            }
            
            $pdo->commit();
            
            echo "<p style='color: green;'>✅ Payment and session updated successfully!</p>";
            echo "<p><strong>Receipt:</strong> $receipt</p>";
            echo "<p><strong>Session Status:</strong> scheduled</p>";
            echo "<p><strong>Payment Status:</strong> paid</p>";
            
            // Verify the update
            $stmt = $pdo->prepare("
                SELECT p.status as payment_status, s.status as session_status, s.payment_status as session_payment_status
                FROM payments p
                LEFT JOIN sessions s ON p.session_id = s.id
                WHERE p.id = ?
            ");
            $stmt->execute([$payment['payment_id']]);
            $result = $stmt->fetch();
            
            echo "<h4>Verification:</h4>";
            echo "<p><strong>Payment Status:</strong> {$result['payment_status']}</p>";
            echo "<p><strong>Session Status:</strong> {$result['session_status']}</p>";
            echo "<p><strong>Session Payment Status:</strong> {$result['session_payment_status']}</p>";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
p { margin: 10px 0; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style> 