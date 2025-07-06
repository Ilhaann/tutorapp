<?php
require_once 'config/database.php';

echo "<h2>Payment Status Checker</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get recent payments
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.checkout_request_id,
            p.merchant_request_id,
            p.status,
            p.amount,
            p.mpesa_receipt_number,
            p.created_at,
            p.updated_at,
            s.id as session_id,
            s.status as session_status,
            s.payment_status as session_payment_status
        FROM payments p
        LEFT JOIN sessions s ON p.session_id = s.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll();
    
    echo "<h3>Recent Payments:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th><th>Checkout ID</th><th>Status</th><th>Amount</th><th>Receipt</th><th>Created</th><th>Updated</th><th>Session Status</th>";
    echo "</tr>";
    
    foreach ($payments as $payment) {
        $statusColor = $payment['status'] === 'completed' ? 'green' : ($payment['status'] === 'pending' ? 'orange' : 'red');
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['checkout_request_id']}</td>";
        echo "<td style='color: $statusColor;'>{$payment['status']}</td>";
        echo "<td>{$payment['amount']}</td>";
        echo "<td>{$payment['mpesa_receipt_number']}</td>";
        echo "<td>{$payment['created_at']}</td>";
        echo "<td>{$payment['updated_at']}</td>";
        echo "<td>{$payment['session_status']} ({$payment['session_payment_status']})</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for pending callbacks
    if (file_exists('pending_callbacks.json')) {
        echo "<h3>Pending Callbacks:</h3>";
        $pendingCallbacks = json_decode(file_get_contents('pending_callbacks.json'), true);
        if ($pendingCallbacks) {
            echo "<pre>" . json_encode($pendingCallbacks, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>No pending callbacks found.</p>";
        }
    } else {
        echo "<p>No pending_callbacks.json file found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style> 