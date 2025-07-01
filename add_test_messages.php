<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Adding test messages between tutor and tutee...\n\n";
    
    // Session details
    $session_id = 76;
    $tutor_id = 71; // Fatuma Omar
    $tutee_id = 67; // Nicole Njeri
    
    // Test messages
    $messages = [
        [
            'sender_id' => $tutee_id,
            'receiver_id' => $tutor_id,
            'session_id' => $session_id,
            'message' => 'Hi Fatuma! I just booked our integral calculus session for June 27th. I\'m really looking forward to it!',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'sender_id' => $tutor_id,
            'receiver_id' => $tutee_id,
            'session_id' => $session_id,
            'message' => 'Hi Nicole! Great to hear from you. I\'m excited to help you with integral calculus. What specific topics would you like to focus on?',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 45 minutes'))
        ],
        [
            'sender_id' => $tutee_id,
            'receiver_id' => $tutor_id,
            'session_id' => $session_id,
            'message' => 'I\'m struggling with integration by parts and trigonometric substitutions. Also, where should we meet for the session?',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 30 minutes'))
        ],
        [
            'sender_id' => $tutor_id,
            'receiver_id' => $tutee_id,
            'session_id' => $session_id,
            'message' => 'Perfect! Those are common challenging topics. We can meet at the library study room on the 2nd floor. It\'s usually quiet and has good lighting. What time works best for you on the 27th?',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 15 minutes'))
        ],
        [
            'sender_id' => $tutee_id,
            'receiver_id' => $tutor_id,
            'session_id' => $session_id,
            'message' => 'The library sounds perfect! I can meet at 10:30 AM, which gives us 15 minutes before our scheduled session. Should I bring my calculus textbook and notes?',
            'created_at' => date('Y-m-d H:i:s', strtotime('-45 minutes'))
        ],
        [
            'sender_id' => $tutor_id,
            'receiver_id' => $tutee_id,
            'session_id' => $session_id,
            'message' => 'Yes, please bring your textbook and any notes you have. I\'ll also bring some practice problems. See you at 10:30 AM on the 27th!',
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]
    ];
    
    // Insert messages
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, session_id, message, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($messages as $msg) {
        $stmt->execute([
            $msg['sender_id'],
            $msg['receiver_id'], 
            $msg['session_id'],
            $msg['message'],
            0, // is_read = false
            $msg['created_at']
        ]);
        echo "✅ Added message: " . substr($msg['message'], 0, 50) . "...\n";
    }
    
    echo "\n🎉 Test messages added successfully!\n";
    echo "Now you can:\n";
    echo "1. Log in as Fatuma (tutor) and check messages\n";
    echo "2. Log in as Nicole (tutee) and check messages\n";
    echo "3. See the conversation in the messaging interface\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 