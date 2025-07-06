<?php
require_once 'config/database.php';

echo "<h2>Direct Callback Test</h2>";

// Simulate the callback data directly
$testCallback = [
    'Body' => [
        'stkCallback' => [
            'MerchantRequestID' => '61fa-4c2c-b3db-088b7d2b6ffc60335',
            'CheckoutRequestID' => 'ws_CO_05072025112435021704640885',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CallbackMetadata' => [
                'Item' => [
                    ['Name' => 'Amount', 'Value' => 1.00],
                    ['Name' => 'MpesaReceiptNumber', 'Value' => 'TG57OQ82E3'],
                    ['Name' => 'Balance'],
                    ['Name' => 'TransactionDate', 'Value' => 20250705112215],
                    ['Name' => 'PhoneNumber', 'Value' => 254704640885]
                ]
            ]
        ]
    ]
];

echo "<h3>Test Callback Data:</h3>";
echo "<pre>" . json_encode($testCallback, JSON_PRETTY_PRINT) . "</pre>";

// Extract payment info (same logic as mpesa_callback.php)
$receipt = null;
$status = 'failed';
$checkoutRequestID = null;

if (isset($testCallback['Body']['stkCallback'])) {
    $stk = $testCallback['Body']['stkCallback'];
    $checkoutRequestID = $stk['CheckoutRequestID'] ?? null;
    
    if (isset($stk['ResultCode']) && $stk['ResultCode'] == 0) {
        // Success
        $status = 'completed';
        if (isset($stk['CallbackMetadata']['Item'])) {
            foreach ($stk['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $receipt = $item['Value'];
                }
            }
        }
    } else {
        $status = 'failed';
    }
}

echo "<h3>Extracted Data:</h3>";
echo "<p><strong>Checkout Request ID:</strong> $checkoutRequestID</p>";
echo "<p><strong>Status:</strong> $status</p>";
echo "<p><strong>Receipt:</strong> $receipt</p>";

// Test the database update logic
if ($checkoutRequestID) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        echo "<h3>Testing Database Update:</h3>";
        
        // Retry logic for race conditions
        $maxRetries = 5;
        $retryDelay = 1; // seconds
        $payment = null;
        $rowsUpdated = 0;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            echo "<p><strong>Attempt $attempt:</strong></p>";
            
            // Check if payment exists
            $checkStmt = $pdo->prepare("SELECT id, session_id FROM payments WHERE checkout_request_id = ?");
            $checkStmt->execute([$checkoutRequestID]);
            $payment = $checkStmt->fetch();
            
            if ($payment) {
                echo "<p style='color: green;'>✅ Payment found in database</p>";
                
                // Payment found, update it
                $stmt = $pdo->prepare("UPDATE payments SET status = ?, mpesa_receipt_number = ?, updated_at = NOW() WHERE checkout_request_id = ?");
                $stmt->execute([$status, $receipt, $checkoutRequestID]);
                $rowsUpdated = $stmt->rowCount();
                
                echo "<p style='color: green;'>✅ Payment updated successfully. Rows updated: $rowsUpdated</p>";
                
                // Update session if payment is completed
                if ($status === 'completed') {
                    $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
                    $stmt->execute([$payment['session_id']]);
                    echo "<p style='color: green;'>✅ Session updated to scheduled and paid</p>";
                }
                
                break; // Success, exit retry loop
            } else {
                echo "<p style='color: orange;'>⚠️ Payment not found in database</p>";
                
                if ($attempt < $maxRetries) {
                    echo "<p>Waiting $retryDelay seconds before retry...</p>";
                    sleep($retryDelay);
                } else {
                    echo "<p style='color: red;'>❌ All attempts failed. Payment record not found.</p>";
                    
                    // Show recent payments for debugging
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
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No checkout request ID found in callback</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
p { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style> 