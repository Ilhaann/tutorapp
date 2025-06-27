<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../auth/login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

// Get all requests for this tutor
$stmt = $pdo->prepare("SELECT sr.*, u.first_name, u.last_name 
                      FROM session_requests sr
                      JOIN users u ON sr.tutee_id = u.id
                      WHERE sr.tutor_id = ? ORDER BY sr.status ASC, sr.id DESC");
$stmt->execute([$tutor_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Requests - Tutor</title>
</head>
<body>
    <h2>Incoming Session Requests</h2>

    <?php foreach ($requests as $r): ?>
        <div style="border:1px solid #ccc; padding:15px; margin:10px;">
            <strong>Subject:</strong> <?= htmlspecialchars($r['subject']) ?><br>
            <strong>Time:</strong> <?= $r['preferred_day'] ?> @ <?= $r['preferred_time'] ?><br>
            <strong>Tutee:</strong> <?= $r['first_name'] . " " . $r['last_name'] ?><br>
            <strong>Status:</strong> <?= ucfirst($r['status']) ?><br>

            <?php if ($r['status'] === 'matched'): ?>
                <a href="respond_session.php?id=<?= $r['id'] ?>&action=approve">✅ Approve</a> |
                <a href="respond_session.php?id=<?= $r['id'] ?>&action=reject">❌ Reject</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
