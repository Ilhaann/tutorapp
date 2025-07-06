<?php
require_once 'config/database.php';

// New payment details
$checkoutRequestID = 'ws_CO_05072025173945007704640885';
$merchantRequestID = '61fa-4c2c-b3db-088b7d2b6ffc63604';
$receipt = 'NEW_PAYMENT_RECEIPT_' . time();

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

echo "<h2>Triggering Callback for New Payment</h2>";
echo "<p><strong>Checkout ID:</strong> $checkoutRequestID</p>";

// Send the callback
$callbackUrl = 'http://localhost/tutorapp/mpesa_callback.php';
$jsonData = json_encode($callbackData);

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
curl_close($ch);

echo "<p><strong>Response:</strong> $response</p>";

// Check the result
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT status, mpesa_receipt_number, updated_at FROM payments WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestID]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $statusColor = $payment['status'] === 'completed' ? 'green' : 'red';
        echo "<p><strong>Status:</strong> <span style='color: $statusColor;'>{$payment['status']}</span></p>";
        echo "<p><strong>Receipt:</strong> {$payment['mpesa_receipt_number']}</p>";
        echo "<p><strong>Updated:</strong> {$payment['updated_at']}</p>";
        
        if ($payment['status'] === 'completed') {
            echo "<p style='color: green;'>✅ New payment callback working correctly!</p>";
        } else {
            echo "<p style='color: red;'>❌ Still pending - check logs for errors</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
p { margin: 10px 0; }
</style> 