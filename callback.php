<?php
// Log all incoming requests
error_log("M-PESA Callback Received:");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request Headers: " . json_encode(getallheaders()));
error_log("Request Body: " . file_get_contents('php://input'));

// Send a success response
header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]); 