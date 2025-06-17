<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

$request_id = $_GET['request_id'] ?? null;

if (!$request_id) {
    die("Request ID missing.");
}

// Step 1: Fetch the session request
$stmt = $pdo->prepare("SELECT * FROM session_requests WHERE id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    die("Session request not found.");
}

$subject = $request['subject'];
$day = $request['preferred_day'];
$time = $request['preferred_time'];

// Step 2: Find matching tutor
$query = "
    SELECT u.id, u.first_name, u.last_name 
    FROM users u
    INNER JOIN tutors_subjects ts ON u.id = ts.tutor_id
    INNER JOIN availability a ON u.id = a.tutor_id
    WHERE ts.subject = ? AND a.day = ? AND a.time = ? AND u.role = 'tutor'
";
$stmt = $pdo->prepare($query);
$stmt->execute([$subject, $day, $time]);
$tutor = $stmt->fetch();

if ($tutor) {
    // Step 3: Update session request with tutor ID
    $stmt = $pdo->prepare("UPDATE session_requests SET tutor_id = ?, status = 'matched' WHERE id = ?");
    $stmt->execute([$tutor['id'], $request_id]);

    echo "<p>Matched with Tutor: " . $tutor['first_name'] . " " . $tutor['last_name'] . "</p>";
} else {
    echo "<p>No matching tutor found at the moment. Please try a different time or subject.</p>";
}
?>

<br>
<a href="dashboard.php">Back to Dashboard</a>
