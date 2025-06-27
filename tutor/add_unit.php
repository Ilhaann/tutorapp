<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/config.php';
$database = new Database();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO units (name, code, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['unit_name'],
            $_POST['unit_code'],
            $_POST['unit_description']
        ]);
        
        header("Location: subjects.php?success=Unit added successfully!");
        exit();
    } catch (Exception $e) {
        header("Location: subjects.php?error=Failed to add unit. Please try again.");
        exit();
    }
}
?> 