<?php
require_once 'config/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Update tutor 71's hourly rate to 500 KES (a more reasonable amount)
$stmt = $pdo->prepare("UPDATE tutor_profiles SET hourly_rate = 500 WHERE user_id = 71");
$result = $stmt->execute();

if ($result) {
    echo "Successfully updated tutor 71's hourly rate to 500 KES\n";
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT hourly_rate FROM tutor_profiles WHERE user_id = 71");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "New hourly rate: " . $result['hourly_rate'] . " KES\n";
} else {
    echo "Failed to update hourly rate\n";
}
?> 