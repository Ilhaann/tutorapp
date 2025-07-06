<?php
require_once 'config/database.php';

echo "<h2>Session Status Check</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get all recent sessions with their payment status
    $stmt = $pdo->prepare("
        SELECT s.id, s.tutor_id, s.tutee_id, s.status as session_status, s.payment_status,
               p.id as payment_id, p.status as payment_status, p.mpesa_receipt_number,
               u1.first_name as tutor_name, u2.first_name as tutee_name,
               s.created_at, s.updated_at
        FROM sessions s
        LEFT JOIN payments p ON s.id = p.session_id
        LEFT JOIN users u1 ON s.tutor_id = u1.id
        LEFT JOIN users u2 ON s.tutee_id = u2.id
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll();
    
    echo "<h3>Recent Sessions:</h3>";
    if (empty($sessions)) {
        echo "<p>No sessions found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>Session ID</th><th>Tutor</th><th>Tutee</th><th>Session Status</th><th>Payment Status</th><th>Payment ID</th><th>Payment Status</th><th>Receipt</th><th>Created</th><th>Updated</th>";
        echo "</tr>";
        
        foreach ($sessions as $session) {
            $sessionStatusColor = $session['session_status'] === 'scheduled' ? 'green' : 
                                ($session['session_status'] === 'pending' ? 'orange' : 'red');
            $paymentStatusColor = $session['payment_status'] === 'paid' ? 'green' : 
                                ($session['payment_status'] === 'pending' ? 'orange' : 'red');
            
            echo "<tr>";
            echo "<td>{$session['id']}</td>";
            echo "<td>{$session['tutor_name']} (ID: {$session['tutor_id']})</td>";
            echo "<td>{$session['tutee_name']} (ID: {$session['tutee_id']})</td>";
            echo "<td style='color: $sessionStatusColor;'>{$session['session_status']}</td>";
            echo "<td style='color: $paymentStatusColor;'>{$session['payment_status']}</td>";
            echo "<td>{$session['payment_id']}</td>";
            echo "<td>{$session['payment_status']}</td>";
            echo "<td>{$session['mpesa_receipt_number']}</td>";
            echo "<td>{$session['created_at']}</td>";
            echo "<td>{$session['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check specific tutor sessions
    echo "<h3>Checking Tutor Sessions (Tutor ID: 94):</h3>";
    $stmt = $pdo->prepare("
        SELECT s.id, s.status as session_status, s.payment_status,
               p.status as payment_status, p.mpesa_receipt_number,
               u.first_name, u.last_name
        FROM sessions s
        LEFT JOIN payments p ON s.id = p.session_id
        LEFT JOIN users u ON s.tutee_id = u.id
        WHERE s.tutor_id = 94
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $tutorSessions = $stmt->fetchAll();
    
    if (empty($tutorSessions)) {
        echo "<p>No sessions found for tutor ID 94.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Session ID</th><th>Tutee</th><th>Session Status</th><th>Payment Status</th><th>Payment Status</th><th>Receipt</th></tr>";
        foreach ($tutorSessions as $session) {
            $sessionStatusColor = $session['session_status'] === 'scheduled' ? 'green' : 'orange';
            $paymentStatusColor = $session['payment_status'] === 'paid' ? 'green' : 'orange';
            
            echo "<tr>";
            echo "<td>{$session['id']}</td>";
            echo "<td>{$session['first_name']} {$session['last_name']}</td>";
            echo "<td style='color: $sessionStatusColor;'>{$session['session_status']}</td>";
            echo "<td style='color: $paymentStatusColor;'>{$session['payment_status']}</td>";
            echo "<td>{$session['payment_status']}</td>";
            echo "<td>{$session['mpesa_receipt_number']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style> 