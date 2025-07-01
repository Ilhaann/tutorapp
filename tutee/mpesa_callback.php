<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Log all incoming requests
error_log("M-PESA Callback Received:");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request Headers: " . json_encode(getallheaders()));
error_log("Request Body: " . file_get_contents('php://input'));

// Get the callback data
$callback_data = file_get_contents('php://input');
$callback = json_decode($callback_data);

// Verify the callback is from M-PESA
if (!isset($callback->Body->stkCallback)) {
    exit('Invalid callback');
}

$stk_callback = $callback->Body->stkCallback;
$checkout_request_id = $stk_callback->CheckoutRequestID;
$result_code = $stk_callback->ResultCode;
$result_desc = $stk_callback->ResultDesc;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get payment record
    $stmt = $pdo->prepare("
        SELECT p.*, s.id as session_id 
        FROM payments p
        JOIN sessions s ON p.session_id = s.id
        WHERE p.checkout_request_id = ?
    ");
    $stmt->execute([$checkout_request_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        exit('Payment not found');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    if ($result_code === 0) {
        // Payment successful
        $receipt_number = null;
        if (isset($stk_callback->CallbackMetadata->Item)) {
            foreach ($stk_callback->CallbackMetadata->Item as $item) {
                if ($item->Name === 'MpesaReceiptNumber') {
                    $receipt_number = $item->Value;
                    break;
                }
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'completed',
                mpesa_receipt_number = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$receipt_number, $payment['id']]);
        
        // Update session status
        $stmt = $pdo->prepare("
            UPDATE sessions 
            SET status = 'confirmed',
                payment_status = 'paid',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['session_id']]);
    } else {
        // Payment failed
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'failed',
                failure_reason = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$result_desc, $payment['id']]);
        
        // Update session status
        $stmt = $pdo->prepare("
            UPDATE sessions 
            SET status = 'cancelled',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['session_id']]);
        
        // Free up the time slot
        $stmt = $pdo->prepare("
            UPDATE availability_slots 
            SET is_booked = 0 
            WHERE id = (
                SELECT slot_id 
                FROM sessions 
                WHERE id = ?
            )
        ");
        $stmt->execute([$payment['session_id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("M-PESA callback error: " . $e->getMessage());
}

// Send a success response
header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]); 