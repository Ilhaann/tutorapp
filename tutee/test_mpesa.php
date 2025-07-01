<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/mpesa_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_number = $_POST['phone_number'] ?? '';
    $amount = $_POST['amount'] ?? '';
    
    if (empty($phone_number) || empty($amount)) {
        $error = "Please fill in all fields";
    } else {
        try {
            // Format phone number
            $phone_number = '254' . ltrim($phone_number, '0');
            
            // Get M-PESA access token
            $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
            
            $ch = curl_init(MPESA_AUTH_URL);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . $credentials,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception("Curl Error: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode . " Response: " . $response);
            }
            
            $result = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Error: " . json_last_error_msg() . " Response: " . $response);
            }
            
            if (!isset($result->access_token)) {
                throw new Exception("No access token in response: " . $response);
            }
            
            // Log successful token
            error_log("Successfully obtained M-PESA access token");
            
            // Initiate STK Push
            $timestamp = date('YmdHis');
            $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
            
            // Validate amount
            $amount = (int)$amount;
            if ($amount < 1) {
                throw new Exception("Amount must be at least 1 KES");
            }
            
            // Validate phone number
            if (!preg_match('/^254[0-9]{9}$/', $phone_number)) {
                throw new Exception("Invalid phone number format. Must be 254 followed by 9 digits");
            }
            
            $stk_push_data = [
                'BusinessShortCode' => MPESA_SHORTCODE,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone_number,
                'PartyB' => MPESA_SHORTCODE,
                'PhoneNumber' => $phone_number,
                'CallBackURL' => 'https://0a90-197-232-77-95.ngrok-free.app/tutorapp/tutee/test_mpesa.php',
                'AccountReference' => 'Test Payment',
                'TransactionDesc' => 'Test M-PESA Payment'
            ];
            
            // Log STK Push request details
            error_log("STK Push Request Details:");
            error_log("URL: " . MPESA_STK_PUSH_URL);
            error_log("Data: " . json_encode($stk_push_data, JSON_PRETTY_PRINT));
            
            $ch = curl_init(MPESA_STK_PUSH_URL);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $result->access_token,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($stk_push_data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_VERBOSE => true
            ]);
            
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            
            if (curl_errno($ch)) {
                throw new Exception("STK Push Curl Error: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("STK Push Response Details:");
                error_log("HTTP Code: " . $httpCode);
                error_log("Response: " . $response);
                error_log("Verbose Log: " . $verboseLog);
                throw new Exception("STK Push HTTP Error: " . $httpCode . " Response: " . $response);
            }
            
            $result = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("STK Push JSON Error: " . json_last_error_msg() . " Response: " . $response);
            }
            
            if (isset($result->CheckoutRequestID)) {
                $success = "M-PESA prompt sent successfully! Check your phone.";
                error_log("STK Push successful. CheckoutRequestID: " . $result->CheckoutRequestID);
            } else {
                throw new Exception("Failed to initiate M-PESA payment. Response: " . $response);
            }
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            error_log("M-PESA Test Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test M-PESA Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 500px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Test M-PESA Payment</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+254</span>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone_number" 
                                   name="phone_number" 
                                   placeholder="7XXXXXXXX"
                                   pattern="[0-9]{9}"
                                   maxlength="9"
                                   required>
                        </div>
                        <small class="text-muted">Enter your M-PESA registered phone number</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (KES)</label>
                        <input type="number" 
                               class="form-control" 
                               id="amount" 
                               name="amount" 
                               min="1"
                               required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send M-PESA Prompt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneNumber = document.getElementById('phone_number').value;
            if (!phoneNumber.match(/^[0-9]{9}$/)) {
                e.preventDefault();
                alert('Please enter a valid 9-digit phone number');
            }
        });
    </script>
</body>
</html> 