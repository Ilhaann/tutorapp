<?php
require_once '../config/config.php';
require_once '../app/payments/PaymentController.php';

header('Content-Type: application/json');

try {
    $paymentController = new App\Payments\PaymentController();
    
    // Handle payment initiation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'initiate') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['amount']) || !isset($data['phone_number']) || !isset($data['session_id'])) {
            throw new Exception('Missing required parameters');
        }
        
        $result = $paymentController->initiatePayment(
            $data['amount'],
            $data['phone_number'],
            $data['session_id']
        );
        
        echo json_encode($result);
    }
    
    // Handle callback
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'callback') {
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $paymentController->handleCallback($data);
        echo json_encode($result);
    }
    
    else {
        throw new Exception('Invalid request');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
