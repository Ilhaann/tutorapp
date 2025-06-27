<?php
session_start();
require_once '../config/database.php';

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch pending tutor applications
$stmt = $pdo->prepare("
    SELECT 
        id, name, email, expertise, 
        availability, qualifications, 
        application_submitted_at 
    FROM users 
    WHERE role = 'tutor' AND approval_status = 'pending'
");
$stmt->execute();
$pending_tutors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tutor Applications</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .approve { background-color: green; color: white; }
        .reject { background-color: red; color: white; }
    </style>
</head>
<body>
    <h2>Pending Tutor Applications</h2>
    
    <?php if (empty($pending_tutors)): ?>
        <p>No pending tutor applications.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Expertise</th>
                    <th>Availability</th>
                    <th>Qualifications</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_tutors as $tutor): ?>
                <tr>
                    <td><?= htmlspecialchars($tutor['name']) ?></td>
                    <td><?= htmlspecialchars($tutor['email']) ?></td>
                    <td><?= htmlspecialchars($tutor['expertise']) ?></td>
                    <td><?= htmlspecialchars($tutor['availability']) ?></td>
                    <td><?= htmlspecialchars($tutor['qualifications']) ?></td>
                    <td><?= htmlspecialchars($tutor['application_submitted_at']) ?></td>
                    <td>
                        <form method="POST" action="process_tutor_approval.php">
                            <input type="hidden" name="tutor_id" value="<?= $tutor['id'] ?>">
                            <button type="submit" name="action" value="approve" class="approve">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>