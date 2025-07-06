<?php
echo "<h2>Testing Real M-PESA Callback</h2>";

// Test the callback with a real POST request
$callbackUrl = 'https://048f-105-160-44-189.ngrok-free.app/tutorapp/mpesa_callback.php';

// Real M-PESA callback data structure
$realCallbackData = [
    'Body' => [
        'stkCallback' => [
            'MerchantRequestID' => 'test-merchant-123',
            'CheckoutRequestID' => 'test-checkout-456',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CallbackMetadata' => [
                'Item' => [
                    ['Name' => 'Amount', 'Value' => 1.00],
                    ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST_RECEIPT_123'],
                    ['Name' => 'Balance'],
                    ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                    ['Name' => 'PhoneNumber', 'Value' => 254704640885]
                ]
            ]
        ]
    ]
];

$jsonData = json_encode($realCallbackData);

echo "<h3>Testing POST request to:</h3>";
echo "<p><strong>URL:</strong> $callbackUrl</p>";
echo "<h3>Callback Data:</h3>";
echo "<pre>" . json_encode($realCallbackData, JSON_PRETTY_PRINT) . "</pre>";

// Send POST request
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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Response:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong> $response</p>";

if ($error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
}

if ($httpCode == 200) {
    echo "<p style='color: green;'>✅ Callback URL is accessible and working!</p>";
    echo "<p>M-PESA should be able to reach your callback successfully.</p>";
} else {
    echo "<p style='color: red;'>❌ Callback URL is not accessible (HTTP $httpCode)</p>";
    echo "<p>M-PESA cannot reach your callback URL.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style> 