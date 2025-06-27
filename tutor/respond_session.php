<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    die("Invalid request.");
}

$new_status = $action === 'approve' ? 'approved' : 'rejected';

$stmt = $pdo->prepare("UPDATE session_requests SET status = ? WHERE id = ?");
$stmt->execute([$new_status, $id]);

header("Location: session_requests.php");
exit();
