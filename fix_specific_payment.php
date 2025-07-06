<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Specific checkout request ID that's causing issues
$checkout_request_id = 'ws_CO_21062025123428870704640885';

echo "<h2>Fix Specific Payment Issue</h2>";
echo "<p>Fixing payment with checkout_request_id: <strong>$checkout_request_id</strong></p>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First, let's check if the payment exists
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkout_request_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        echo "<p style='color: green;'>✓ Payment found in database</p>";
        echo "<p>Current status: <strong>" . $payment['status'] . "</strong></p>";
        
        // If payment exists but status is not completed, update it
        if ($payment['status'] !== 'completed') {
            echo "<p>Updating payment status to 'completed'...</p>";
            
            $pdo->beginTransaction();
            
            // Update payment status
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'completed',
                    updated_at = NOW()
                WHERE checkout_request_id = ?
            ");
            $stmt->execute([$checkout_request_id]);
            
            // Update session status
            $stmt = $pdo->prepare("
                UPDATE sessions 
                SET status = 'confirmed',
                    payment_status = 'paid',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$payment['session_id']]);
            
            $pdo->commit();
            
            echo "<p style='color: green;'>✓ Payment status updated to 'completed'</p>";
            echo "<p style='color: green;'>✓ Session status updated to 'confirmed'</p>";
        } else {
            echo "<p style='color: blue;'>Payment is already marked as completed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Payment not found in database</p>";
        echo "<p>This means the payment record was never created or was deleted.</p>";
        
        // Let's check if there are any payments with similar checkout_request_id
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE checkout_request_id LIKE ?");
        $stmt->execute(['%' . substr($checkout_request_id, -10) . '%']);
        $similar_payments = $stmt->fetchAll();
        
        if ($similar_payments) {
            echo "<p>Found similar payments:</p>";
            echo "<ul>";
            foreach ($similar_payments as $similar) {
                echo "<li>ID: {$similar['id']}, Checkout ID: {$similar['checkout_request_id']}, Status: {$similar['status']}</li>";
            }
            echo "</ul>";
        }
        
        // Let's also check for any recent payments
        $stmt = $pdo->prepare("SELECT * FROM payments ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $recent_payments = $stmt->fetchAll();
        
        echo "<p>Recent payments:</p>";
        echo "<ul>";
        foreach ($recent_payments as $recent) {
            echo "<li>ID: {$recent['id']}, Checkout ID: {$recent['checkout_request_id']}, Status: {$recent['status']}, Created: {$recent['created_at']}</li>";
        }
        echo "</ul>";
    }
    
    // Now let's check the payment status page
    echo "<h3>Testing Payment Status Page</h3>";
    echo "<p><a href='tutee/payment_status.php?checkout_request_id=$checkout_request_id' target='_blank'>Click here to test payment status page</a></p>";
    
    // Show current payment statistics
    echo "<h3>Current Payment Statistics</h3>";
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    $stmt->execute();
    $stats = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . ucfirst($stat['status']) . "</td>";
        echo "<td>" . $stat['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<h3>Next Steps:</h3>
<ol>
    <li>Run the database fixes first: <a href="fix_payments.php">fix_payments.php</a></li>
    <li>Check the payment tracker: <a href="admin/payment_tracker.php">admin/payment_tracker.php</a></li>
    <li>If the payment is still not found, manually create it using the admin panel</li>
</ol> 