<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "<h2>Recent Payments</h2>";
$stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 10");
$payments = $stmt->fetchAll();

if ($payments) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Session ID</th><th>Amount</th><th>Phone</th><th>Status</th><th>Checkout Request ID</th><th>Created</th></tr>";
    foreach ($payments as $payment) {
        echo "<tr>";
        echo "<td>" . $payment['id'] . "</td>";
        echo "<td>" . $payment['session_id'] . "</td>";
        echo "<td>" . $payment['amount'] . "</td>";
        echo "<td>" . $payment['phone_number'] . "</td>";
        echo "<td>" . $payment['status'] . "</td>";
        echo "<td>" . $payment['checkout_request_id'] . "</td>";
        echo "<td>" . $payment['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payments found.</p>";
}

echo "<h2>Recent Sessions</h2>";
$stmt = $pdo->query("SELECT * FROM sessions ORDER BY created_at DESC LIMIT 10");
$sessions = $stmt->fetchAll();

if ($sessions) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Tutor ID</th><th>Tutee ID</th><th>Unit ID</th><th>Slot ID</th><th>Status</th><th>Created</th></tr>";
    foreach ($sessions as $session) {
        echo "<tr>";
        echo "<td>" . $session['id'] . "</td>";
        echo "<td>" . $session['tutor_id'] . "</td>";
        echo "<td>" . $session['tutee_id'] . "</td>";
        echo "<td>" . $session['unit_id'] . "</td>";
        echo "<td>" . $session['slot_id'] . "</td>";
        echo "<td>" . $session['status'] . "</td>";
        echo "<td>" . $session['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sessions found.</p>";
}
?> 