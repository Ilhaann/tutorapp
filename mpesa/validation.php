<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/payments/daraja.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data)) {
        throw new Exception('No data received');
    }

    // Log the validation request
    file_put_contents(__DIR__ . '/mpesa_validation.log', 
        date('Y-m-d H:i:s') . ' - ' . json_encode($data) . "\n", 
        FILE_APPEND
    );

    // Process the validation
    $daraja = new Daraja();
    $response = $daraja->processValidation($data);

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
