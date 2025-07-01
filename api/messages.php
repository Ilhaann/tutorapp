<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../config/database.php';
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'send_message':
                    $receiver_id = (int)$input['receiver_id'];
                    $message = trim($input['message']);
                    $session_id = isset($input['session_id']) ? (int)$input['session_id'] : null;
                    
                    if (empty($message)) {
                        throw new Exception('Message cannot be empty');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO messages (sender_id, receiver_id, session_id, message, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$user_id, $receiver_id, $session_id, $message]);
                    
                    $message_id = $pdo->lastInsertId();
                    
                    // Get the sent message details
                    $stmt = $pdo->prepare("
                        SELECT m.*, u.first_name, u.last_name
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.id = ?
                    ");
                    $stmt->execute([$message_id]);
                    $sent_message = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $sent_message
                    ]);
                    break;
                    
                case 'get_messages':
                    $other_user_id = (int)$input['user_id'];
                    $last_message_id = isset($input['last_message_id']) ? (int)$input['last_message_id'] : 0;
                    
                    $stmt = $pdo->prepare("
                        SELECT m.*, u.first_name, u.last_name
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
                           OR (m.sender_id = ? AND m.receiver_id = ?))
                        AND m.id > ?
                        ORDER BY m.created_at ASC
                    ");
                    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id, $last_message_id]);
                    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Mark messages as read (only for messages sent to current user)
                    if (!empty($messages)) {
                        $stmt = $pdo->prepare("
                            UPDATE messages 
                            SET is_read = 1 
                            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
                        ");
                        $stmt->execute([$other_user_id, $user_id]);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'messages' => $messages
                    ]);
                    break;
                    
                case 'get_unread_count':
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as unread_count
                        FROM messages 
                        WHERE receiver_id = ? AND is_read = 0
                    ");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'unread_count' => (int)$result['unread_count']
                    ]);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('Action is required');
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'conversations') {
            // Get all conversations for the user
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as other_user_id,
                    u.first_name, u.last_name, u.email,
                    s.id as session_id, s.status as session_status,
                    un.name as unit_name,
                    (SELECT message FROM messages 
                     WHERE (sender_id = ? AND receiver_id = other_user_id) 
                        OR (sender_id = other_user_id AND receiver_id = ?)
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages 
                     WHERE (sender_id = ? AND receiver_id = other_user_id) 
                        OR (sender_id = other_user_id AND receiver_id = ?)
                     ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages 
                     WHERE sender_id = other_user_id 
                        AND receiver_id = ? 
                        AND is_read = 0) as unread_count
                FROM messages m
                JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
                LEFT JOIN sessions s ON m.session_id = s.id
                LEFT JOIN units un ON s.unit_id = un.id
                WHERE (m.sender_id = ? OR m.receiver_id = ?)
                AND u.id != ?
                ORDER BY last_message_time DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } else {
            throw new Exception('Invalid request');
        }
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 