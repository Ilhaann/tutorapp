<?php
require_once __DIR__ . '/config/database.php';

// Log the raw callback for debugging
file_put_contents('mpesa_callback.log', date('c') . "\n" . file_get_contents('php://input') . "\n\n", FILE_APPEND);

header('Content-Type: application/json');

// Parse the callback JSON
$callback = json_decode(file_get_contents('php://input'), true);

// Log the parsed callback for debugging
file_put_contents('mpesa_callback.log', date('c') . "\nPARSED: " . print_r($callback, true) . "\n\n", FILE_APPEND);

// Extract payment info (for Lipa na M-PESA Online)
$receipt = null;
$status = 'failed';
$checkoutRequestID = null;
if (isset($callback['Body']['stkCallback'])) {
    $stk = $callback['Body']['stkCallback'];
    $checkoutRequestID = $stk['CheckoutRequestID'] ?? null;
    if (isset($stk['ResultCode']) && $stk['ResultCode'] == 0) {
        // Success
        $status = 'completed';
        if (isset($stk['CallbackMetadata']['Item'])) {
            foreach ($stk['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $receipt = $item['Value'];
                }
            }
        }
    } else {
        $status = 'failed';
    }
}

// Update the payment in the database
if ($checkoutRequestID) {
    $db = new Database();
    $pdo = $db->getConnection();
    file_put_contents('mpesa_callback.log', date('c') . "\nUpdating payment for checkout_request_id: $checkoutRequestID, status: $status, receipt: $receipt\n", FILE_APPEND);
    $stmt = $pdo->prepare("UPDATE payments SET status = ?, mpesa_receipt_number = ? WHERE checkout_request_id = ?");
    $stmt->execute([$status, $receipt, $checkoutRequestID]);
    file_put_contents('mpesa_callback.log', date('c') . "\nRows updated: " . $stmt->rowCount() . "\n", FILE_APPEND);
    
    // If payment is successful, mark the time slot as booked and update session
    if ($status === 'completed') {
        // Get the session and slot_id from the payment record
        $stmt = $pdo->prepare("SELECT session_id FROM payments WHERE checkout_request_id = ?");
        $stmt->execute([$checkoutRequestID]);
        $payment = $stmt->fetch();
        if ($payment && $payment['session_id']) {
            // Update session to scheduled and paid
            $stmt = $pdo->prepare("UPDATE sessions SET status = 'scheduled', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$payment['session_id']]);
            // Get slot_id and session_type
            $stmt = $pdo->prepare("SELECT slot_id, session_type, tutor_id FROM sessions WHERE id = ?");
            $stmt->execute([$payment['session_id']]);
            $session = $stmt->fetch();
            if ($session && $session['slot_id']) {
                // Mark the slot as booked
                $stmt = $pdo->prepare("UPDATE availability_slots SET is_booked = 1 WHERE id = ?");
                $stmt->execute([$session['slot_id']]);
            }
            // If online, add a note for the tutor (placeholder: could be a notification or DB field)
            if ($session && $session['session_type'] === 'online') {
                // Placeholder: log a note for the tutor to send the meeting link
                file_put_contents('mpesa_callback.log', date('c') . "\nTUTOR NOTE: Session ".$payment['session_id']." is online. Tutor (ID: ".$session['tutor_id'].") should send meeting link.\n\n", FILE_APPEND);
            }
        }
    }
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']); 