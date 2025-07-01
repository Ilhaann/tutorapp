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

try {
    // Get M-PESA access token
    // Note: Consumer Key and Secret are swapped in the base64 encoding
    $credentials = base64_encode(MPESA_CONSUMER_SECRET . ':' . MPESA_CONSUMER_KEY);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => MPESA_AUTH_URL,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
        CURLOPT_VERBOSE => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    if (curl_errno($ch)) {
        throw new Exception("Curl Error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Log everything for debugging
    error_log("M-PESA Auth Request Details:");
    error_log("URL: " . MPESA_AUTH_URL);
    error_log("Consumer Key: " . MPESA_CONSUMER_KEY);
    error_log("Consumer Secret: " . MPESA_CONSUMER_SECRET);
    error_log("Credentials: " . $credentials);
    error_log("HTTP Code: " . $httpCode);
    error_log("Headers: " . $header);
    error_log("Response: " . $body);
    error_log("Verbose Log: " . $verboseLog);
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: " . $httpCode . "\nResponse: " . $body . "\nVerbose Log: " . $verboseLog);
    }
    
    $result = json_decode($body);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Error: " . json_last_error_msg() . "\nResponse: " . $body);
    }
    
    if (!isset($result->access_token)) {
        throw new Exception("No access token in response: " . $body);
    }
    
    $success = "Successfully obtained access token!";
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test M-PESA Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Test M-PESA Authentication</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <h5>Error Details:</h5>
                        <pre><?php echo htmlspecialchars($error); ?></pre>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5>Success!</h5>
                        <pre><?php echo htmlspecialchars($success); ?></pre>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5>Request Details:</h5>
                    <pre>URL: <?php echo htmlspecialchars(MPESA_AUTH_URL); ?>
Consumer Key: <?php echo htmlspecialchars(MPESA_CONSUMER_KEY); ?>
Consumer Secret: <?php echo htmlspecialchars(MPESA_CONSUMER_SECRET); ?>
Credentials: <?php echo htmlspecialchars($credentials); ?></pre>
                </div>
                
                <?php if (isset($header)): ?>
                <div class="mt-4">
                    <h5>Response Headers:</h5>
                    <pre><?php echo htmlspecialchars($header); ?></pre>
                </div>
                <?php endif; ?>
                
                <?php if (isset($body)): ?>
                <div class="mt-4">
                    <h5>Response Body:</h5>
                    <pre><?php echo htmlspecialchars($body); ?></pre>
                </div>
                <?php endif; ?>
                
                <?php if (isset($verboseLog)): ?>
                <div class="mt-4">
                    <h5>Verbose Log:</h5>
                    <pre><?php echo htmlspecialchars($verboseLog); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 