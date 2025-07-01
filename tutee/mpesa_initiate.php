<?php
require_once '../app/payments/daraja.php';
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Show a simple HTML form for amount and phone
    echo '<!DOCTYPE html><html><head><title>Pay with M-PESA</title></head><body>';
    echo '<h2>Pay with M-PESA</h2>';
    echo '<form method="POST">';
    echo '<label>Amount: <input type="number" step="0.01" name="amount" required></label><br><br>';
    echo '<label>Phone: <input type="text" name="phone" required placeholder="07XXXXXXXX or 2547XXXXXXXX"></label><br><br>';
    echo '<button type="submit">Pay Now</button>';
    echo '</form>';
    echo '</body></html>';
    exit;
}

header('Content-Type: application/json');
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : null;
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$reference = isset($_POST['reference']) ? trim($_POST['reference']) : 'TUTORAPP';
$desc = isset($_POST['desc']) ? trim($_POST['desc']) : 'Tutoring session payment';

if (!$amount || !$phone) {
    // Debug output
    error_log('POST data: ' . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Missing amount or phone', 'debug' => $_POST]);
    exit;
}

// Format phone to 2547XXXXXXXX
$phone = preg_replace('/^0/', '254', $phone);
if (!preg_match('/^2547\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone format. Use 07XXXXXXXX or 2547XXXXXXXX']);
    exit;
}

try {
    $daraja = new Daraja();
    $result = $daraja->initiateSTKPush($amount, $phone, $reference, $desc);
    echo json_encode(['success' => true, 'message' => 'STK Push initiated', 'data' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 