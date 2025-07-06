<?php
require_once __DIR__ . '/config/database.php';

// Process pending callbacks that were stored when payment records weren't found
$pendingCallbacksFile = 'pending_callbacks.json';

if (!file_exists($pendingCallbacksFile)) {
    echo "No pending callbacks file found.\n";
    exit;
}

$pendingCallbacks = json_decode(file_get_contents($pendingCallbacksFile), true) ?: [];

if (empty($pendingCallbacks)) {
    echo "No pending callbacks to process.\n";
    exit;
}

echo "Found " . count($pendingCallbacks) . " pending callbacks to process.\n";

$db = new Database();
$pdo = $db->getConnection();

$processed = 0;
$failed = 0;

foreach ($pendingCallbacks as $index => $callback) {
    $checkoutRequestID = $callback['checkout_request_id'];
    $status = $callback['status'];
    $receipt = $callback['receipt'];
    
    echo "Processing callback for checkout_request_id: $checkoutRequestID\n";
    
    // Check if payment exists now
    $checkStmt = $pdo->prepare("SELECT id, session_id FROM payments WHERE checkout_request_id = ?");
    $checkStmt->execute([$checkoutRequestID]);
    $payment = $checkStmt->fetch();
    
    if ($payment) {
        // Payment found, update it
        $stmt = $pdo->prepare("UPDATE payments SET status = ?, mpesa_receipt_number = ?, updated_at = NOW() WHERE checkout_request_id = ?");
        $stmt->execute([$status, $receipt, $checkoutRequestID]);
        $rowsUpdated = $stmt->rowCount();
        
        if ($rowsUpdated > 0) {
            echo "  ✓ Successfully updated payment. Rows updated: $rowsUpdated\n";
            
            // If payment is successful, mark the time slot as booked and update session
            if ($status === 'completed') {
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
                
                if ($session && $session['session_type'] === 'online') {
                    echo "  ✓ Online session marked. Tutor (ID: {$session['tutor_id']}) should send meeting link.\n";
                }
            }
            
            $processed++;
            
            // Remove from pending callbacks
            unset($pendingCallbacks[$index]);
        } else {
            echo "  ✗ Failed to update payment (no rows affected)\n";
            $failed++;
        }
    } else {
        echo "  ✗ Payment still not found for checkout_request_id: $checkoutRequestID\n";
        $failed++;
    }
}

// Save updated pending callbacks (remove processed ones)
if ($processed > 0) {
    $remainingCallbacks = array_values($pendingCallbacks); // Re-index array
    file_put_contents($pendingCallbacksFile, json_encode($remainingCallbacks, JSON_PRETTY_PRINT));
    echo "\nProcessed: $processed, Failed: $failed\n";
    echo "Remaining pending callbacks: " . count($remainingCallbacks) . "\n";
} else {
    echo "\nNo callbacks were processed. Failed: $failed\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
</style> 