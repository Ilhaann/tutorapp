<?php
require_once 'config/database.php';

echo "<h2>M-Pesa Callback Logic Test</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Test 1: Check recent payments
    echo "<h3>Recent Payments in Database</h3>";
    $stmt = $pdo->prepare("SELECT id, session_id, amount, status, checkout_request_id, mpesa_receipt_number, created_at FROM payments ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $payments = $stmt->fetchAll();
    
    if ($payments) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Session ID</th><th>Amount</th><th>Status</th><th>Checkout Request ID</th><th>Receipt</th><th>Created</th></tr>";
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['session_id']}</td>";
            echo "<td>{$payment['amount']}</td>";
            echo "<td>{$payment['status']}</td>";
            echo "<td>{$payment['checkout_request_id']}</td>";
            echo "<td>{$payment['mpesa_receipt_number']}</td>";
            echo "<td>{$payment['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No payments found in database.</p>";
    }
    
    // Test 2: Check callback log
    echo "<h3>Recent Callback Log Entries</h3>";
    if (file_exists('mpesa_callback.log')) {
        $logContent = file_get_contents('mpesa_callback.log');
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -20); // Last 20 lines
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(implode("\n", $recentLines));
        echo "</pre>";
    } else {
        echo "<p>No callback log file found.</p>";
    }
    
    // Test 3: Test callback URL accessibility
    echo "<h3>Callback URL Test</h3>";
    $callbackUrl = 'https://048f-105-160-44-189.ngrok-free.app/tutorapp/mpesa_callback.php';
    echo "<p>Main callback URL: <a href='$callbackUrl' target='_blank'>$callbackUrl</a></p>";
    
    $testCallbackUrl = 'https://048f-105-160-44-189.ngrok-free.app/tutorapp/tutee/test_mpesa.php';
    echo "<p>Test callback URL: <a href='$testCallbackUrl' target='_blank'>$testCallbackUrl</a></p>";
    
    // Test 4: Simulate a callback
    echo "<h3>Simulate Callback</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_callback' value='1'>";
    echo "<button type='submit'>Test Callback Processing</button>";
    echo "</form>";
    
    if (isset($_POST['test_callback'])) {
        echo "<h4>Callback Test Results:</h4>";
        
        // Simulate a successful payment callback
        $testCallback = [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'test-' . time(),
                    'CheckoutRequestID' => 'ws_CO_TEST' . time(),
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1.00],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST' . time()],
                            ['Name' => 'Balance'],
                            ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                            ['Name' => 'PhoneNumber', 'Value' => '254700000000']
                        ]
                    ]
                ]
            ]
        ];
        
        echo "<p>Simulated callback data:</p>";
        echo "<pre>" . json_encode($testCallback, JSON_PRETTY_PRINT) . "</pre>";
        
        // Note: This is just for testing the data structure
        echo "<p><strong>Note:</strong> This test only shows the callback data structure. To actually test the callback, you would need to make a real M-Pesa payment.</p>";
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
pre { max-height: 300px; overflow-y: auto; }
</style> 