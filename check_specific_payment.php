<?php
require_once 'config/database.php';

$checkoutRequestID = 'ws_CO_05072025171629065704640885';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            s.status as session_status,
            s.payment_status as session_payment_status
        FROM payments p
        LEFT JOIN sessions s ON p.session_id = s.id
        WHERE p.checkout_request_id = ?
    ");
    $stmt->execute([$checkoutRequestID]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Payment Details for: $checkoutRequestID</h2>";
    
    if ($payment) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($payment as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Payment not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
table { border-collapse: collapse; margin: 10px 0; }
td { padding: 8px; border: 1px solid #ddd; }
</style> 