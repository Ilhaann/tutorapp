<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT s.*, p.status as payment_status, p.mpesa_receipt_number 
        FROM sessions s 
        LEFT JOIN payments p ON s.id = p.session_id 
        WHERE s.id = 76
    ");
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo "Session ID: " . $session['id'] . "\n";
        echo "Status: " . $session['status'] . "\n";
        echo "Payment Status: " . $session['payment_status'] . "\n";
        echo "M-PESA Receipt: " . $session['mpesa_receipt_number'] . "\n";
        echo "Tutor ID: " . $session['tutor_id'] . "\n";
        echo "Tutee ID: " . $session['tutee_id'] . "\n";
        echo "Created: " . $session['created_at'] . "\n";
    } else {
        echo "Session not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 