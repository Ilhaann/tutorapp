<?php
require_once 'config/database.php';

$checkout_request_id = 'ws_CO_05072025112435021704640885';

echo "<h2>Debug Payment: $checkout_request_id</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if payment exists
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkout_request_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        echo "<h3>Payment Found:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($payment as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        // Check session details
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$payment['session_id']]);
        $session = $stmt->fetch();
        
        if ($session) {
            echo "<h3>Session Details:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($session as $key => $value) {
                echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
        }
        
        // Simulate what should happen when callback arrives
        echo "<h3>Simulate Callback Processing:</h3>";
        
        // Simulate successful payment callback
        $simulatedCallback = [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => $payment['merchant_request_id'],
                    'CheckoutRequestID' => $checkout_request_id,
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => $payment['amount']],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'SIMULATED_RECEIPT_' . time()],
                            ['Name' => 'Balance'],
                            ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                            ['Name' => 'PhoneNumber', 'Value' => '254704640885']
                        ]
                    ]
                ]
            ]
        ];
        
        echo "<p>Simulated callback data:</p>";
        echo "<pre>" . json_encode($simulatedCallback, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test the update logic
        echo "<h3>Testing Update Logic:</h3>";
        
        $receipt = 'SIMULATED_RECEIPT_' . time();
        $status = 'completed';
        
        // Update payment
        $stmt = $pdo->prepare("UPDATE payments SET status = ?, mpesa_receipt_number = ?, updated_at = NOW() WHERE checkout_request_id = ?");
        $result = $stmt->execute([$status, $receipt, $checkout_request_id]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Payment update successful. Rows affected: " . $stmt->rowCount() . "</p>";
            
            // Update session
            $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
            $result = $stmt->execute([$payment['session_id']]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Session update successful. Rows affected: " . $stmt->rowCount() . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Session update failed</p>";
            }
            
            // Check updated payment
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE checkout_request_id = ?");
            $stmt->execute([$checkout_request_id]);
            $updatedPayment = $stmt->fetch();
            
            echo "<h3>Updated Payment:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($updatedPayment as $key => $value) {
                echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'>❌ Payment update failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Payment not found with checkout_request_id: $checkout_request_id</p>";
        
        // Show recent payments
        $stmt = $pdo->prepare("SELECT checkout_request_id, status, created_at FROM payments ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $recentPayments = $stmt->fetchAll();
        
        echo "<h3>Recent Payments:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Checkout Request ID</th><th>Status</th><th>Created</th></tr>";
        foreach ($recentPayments as $recent) {
            echo "<tr><td>{$recent['checkout_request_id']}</td><td>{$recent['status']}</td><td>{$recent['created_at']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style> 