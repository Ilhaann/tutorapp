<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Testing messaging system...\n\n";
    
    // Test 1: Check if messages table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Messages table exists\n";
    } else {
        echo "❌ Messages table does not exist\n";
        exit(1);
    }
    
    // Test 2: Check table structure
    $stmt = $pdo->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Messages table structure:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    
    // Test 3: Check if there are any existing messages
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Current message count: {$result['count']}\n";
    
    // Test 4: Check if users exist for testing
    $stmt = $pdo->query("SELECT id, first_name, last_name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Available users for testing:\n";
    foreach ($users as $user) {
        echo "   - ID {$user['id']}: {$user['first_name']} {$user['last_name']} ({$user['role']})\n";
    }
    
    // Test 5: Check if sessions exist for testing
    $stmt = $pdo->query("SELECT s.id, s.status, t.first_name as tutor_name, te.first_name as tutee_name 
                        FROM sessions s 
                        JOIN users t ON s.tutor_id = t.id 
                        JOIN users te ON s.tutee_id = te.id 
                        LIMIT 3");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Available sessions for testing:\n";
    foreach ($sessions as $session) {
        echo "   - Session {$session['id']}: {$session['tutor_name']} → {$session['tutee_name']} ({$session['status']})\n";
    }
    
    echo "\n🎉 Messaging system is ready!\n";
    echo "You can now:\n";
    echo "1. Visit /tutor/messages.php to see tutor messaging interface\n";
    echo "2. Visit /tutee/messages.php to see tutee messaging interface\n";
    echo "3. Use the 'Message Tutor' button on session details pages\n";
    echo "4. Messages will be linked to specific sessions automatically\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 