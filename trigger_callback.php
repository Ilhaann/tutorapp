<?php
require_once 'config/database.php';

echo "<h2>Trigger Callback for Pending Payment</h2>";

// Your specific payment details
$checkoutRequestID = 'ws_CO_05072025171629065704640885';
$merchantRequestID = '19434-1751722589-1'; // This would be from your payment initiation
$receipt = 'MANUAL_RECEIPT_' . time();

// Simulate the M-PESA callback data
$callbackData = [
    'Body' => [
        'stkCallback' => [
            'MerchantRequestID' => $merchantRequestID,
            'CheckoutRequestID' => $checkoutRequestID,
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CallbackMetadata' => [
                'Item' => [
                    ['Name' => 'Amount', 'Value' => 1.00],
                    ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                    ['Name' => 'Balance'],
                    ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                    ['Name' => 'PhoneNumber', 'Value' => 254704640885]
                ]
            ]
        ]
    ]
];

echo "<h3>Simulated Callback Data:</h3>";
echo "<pre>" . json_encode($callbackData, JSON_PRETTY_PRINT) . "</pre>";

// Send the callback to your local callback handler
$callbackUrl = 'http://localhost/tutorapp/mpesa_callback.php';
$jsonData = json_encode($callbackData);

echo "<h3>Sending callback to: $callbackUrl</h3>";

// Use cURL to send the callback
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $callbackUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Callback Response:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong> $response</p>";

if ($error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
}

// Check if the payment was updated
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, status, mpesa_receipt_number, updated_at FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestID]);
    $payment = $stmt->fetch();
    
    echo "<h3>Payment Status After Callback:</h3>";
    if ($payment) {
        $statusColor = $payment['status'] === 'completed' ? 'green' : 'red';
        echo "<p><strong>Status:</strong> <span style='color: $statusColor;'>{$payment['status']}</span></p>";
        echo "<p><strong>Receipt:</strong> {$payment['mpesa_receipt_number']}</p>";
        echo "<p><strong>Updated:</strong> {$payment['updated_at']}</p>";
        
        if ($payment['status'] === 'completed') {
            echo "<p style='color: green;'>✅ Payment successfully updated!</p>";
        } else {
            echo "<p style='color: red;'>❌ Payment still pending. Check the callback logs for errors.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Payment not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style> 