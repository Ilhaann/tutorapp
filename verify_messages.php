<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Verifying messages for session 76...\n\n";
    
    // Count messages
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE session_id = 76");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total messages for session 76: " . $result['count'] . "\n\n";
    
    // Show recent messages
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sender.first_name as sender_name, 
               receiver.first_name as receiver_name
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE m.session_id = 76
        ORDER BY m.created_at ASC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll();
    
    echo "Recent conversation:\n";
    echo "===================\n";
    foreach ($messages as $msg) {
        $time = date('H:i', strtotime($msg['created_at']));
        echo "[{$time}] {$msg['sender_name']}: {$msg['message']}\n\n";
    }
    
    echo "✅ Messages are ready for testing!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 