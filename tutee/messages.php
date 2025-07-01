<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

$user_id = $_SESSION['user_id'];
$selected_conversation = null;
$conversations = [];

try {
    // Get all conversations for this tutee (including tutors with sessions but no messages yet)
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.id as other_user_id,
            u.first_name, u.last_name, u.email,
            s.id as session_id, s.status as session_status,
            un.name as unit_name,
            (SELECT message FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.id) 
                OR (sender_id = u.id AND receiver_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.id) 
                OR (sender_id = u.id AND receiver_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM messages 
             WHERE sender_id = u.id 
                AND receiver_id = ? 
                AND is_read = 0) as unread_count
        FROM users u
        LEFT JOIN sessions s ON (s.tutor_id = u.id AND s.tutee_id = ?) OR (s.tutee_id = u.id AND s.tutor_id = ?)
        LEFT JOIN units un ON s.unit_id = un.id
        WHERE u.role = 'tutor' 
        AND u.id != ?
        AND s.id IS NOT NULL
        ORDER BY last_message_time DESC, u.first_name ASC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get specific conversation if selected
    if (isset($_GET['user_id'])) {
        $other_user_id = (int)$_GET['user_id'];
        
        // Get user details
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email,
                   s.id as session_id, s.status as session_status, s.session_type,
                   un.name as unit_name, av.start_time, av.end_time
            FROM users u
            LEFT JOIN sessions s ON (s.tutor_id = u.id AND s.tutee_id = ?) OR (s.tutee_id = u.id AND s.tutor_id = ?)
            LEFT JOIN units un ON s.unit_id = un.id
            LEFT JOIN availability_slots av ON s.slot_id = av.id
            WHERE u.id = ?
            ORDER BY av.start_time DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id, $user_id, $other_user_id]);
        $selected_conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$other_user_id, $user_id]);

        // Get messages for this conversation
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $error = 'Error loading messages: ' . $e->getMessage();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message_text = trim($_POST['message']);
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : null;

    if (!empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, session_id, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $receiver_id, $session_id, $message_text]);
            
            // Redirect to refresh the page
            header("Location: messages.php?user_id=" . $receiver_id);
            exit();
        } catch (Exception $e) {
            $error = 'Error sending message: ' . $e->getMessage();
        }
    }
}

// Get user details for sidebar
$stmt = $pdo->prepare("
    SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        .sidebar-header {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-profile {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid white;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            padding: 30px;
        }
        .messages-container {
            height: calc(100vh - 200px);
            display: flex;
        }
        .conversations-list {
            width: 300px;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e3f2fd;
        }
        .conversation-item .unread-badge {
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        .message.sent {
            margin-left: auto;
        }
        .message.received {
            margin-right: auto;
        }
        .message-content {
            padding: 10px 15px;
            border-radius: 15px;
            word-wrap: break-word;
        }
        .message.sent .message-content {
            background-color: #007bff;
            color: white;
        }
        .message.received .message-content {
            background-color: #f1f3f4;
            color: #333;
        }
        .message-time {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .message-input-container {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h4><?php echo APP_NAME; ?></h4>
                </div>
                <div class="sidebar-profile">
                    <div class="avatar-container">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                                 class="rounded-circle"
                                 alt="Profile Picture">
                        <?php else: ?>
                        <img src="<?php echo APP_URL; ?>/assets/images/avatars/<?php echo $_SESSION['user_id']; ?>.jpg" 
                             onerror="this.onerror=null; this.src='<?php echo APP_URL; ?>/assets/images/default-avatar.jpg'"
                             class="rounded-circle"
                             alt="Profile Picture">
                        <?php endif; ?>
                    </div>
                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p>Tutee</p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="my_sessions.php">
                        <i class="bi bi-calendar"></i> My Sessions
                    </a>
                    <a class="nav-link" href="tutors.php">
                        <i class="bi bi-search"></i> Find Tutors
                    </a>
                    <a class="nav-link active" href="messages.php">
                        <i class="bi bi-envelope"></i> Messages
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-wallet"></i> Payments
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col">
                    <h2><i class="fas fa-envelope"></i> Messages</h2>
                        </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="messages-container">
                    <!-- Conversations List -->
                    <div class="conversations-list">
                        <?php if (empty($conversations)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No conversations yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $conv['other_user_id']) ? 'active' : ''; ?>"
                                     onclick="window.location.href='messages.php?user_id=<?php echo $conv['other_user_id']; ?>'">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h6>
                                            <?php if ($conv['unit_name']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($conv['unit_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($conv['last_message']): ?>
                                        <p class="mb-1 text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)); ?>
                                            <?php echo strlen($conv['last_message']) > 50 ? '...' : ''; ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('M j, g:i A', strtotime($conv['last_message_time'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Area -->
                    <div class="chat-container">
                        <?php if ($selected_conversation): ?>
                            <!-- Chat Header -->
                            <div class="chat-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($selected_conversation['first_name'] . ' ' . $selected_conversation['last_name']); ?></h5>
                                        <?php if ($selected_conversation['unit_name']): ?>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($selected_conversation['unit_name']); ?>
                                                <?php if ($selected_conversation['start_time']): ?>
                                                    - <?php echo date('M j, g:i A', strtotime($selected_conversation['start_time'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $selected_conversation['session_status'] === 'scheduled' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($selected_conversation['session_status'] ?? 'No Session'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages Area -->
                            <div class="messages-area" id="messagesArea">
                                <?php if (isset($messages) && !empty($messages)): ?>
                                    <?php foreach ($messages as $msg): ?>
                                            <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                                            <div class="message-content">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>
                                            <div class="message-time">
                                                <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted mt-4">
                                        <i class="fas fa-comments fa-2x mb-2"></i>
                                        <p>No messages yet. Start the conversation!</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Message Input -->
                            <div class="message-input-container">
                                <div class="input-group">
                                    <textarea class="form-control" id="messageInput" placeholder="Type your message..." rows="2"></textarea>
                                    <button class="btn btn-primary" type="button" id="sendMessageBtn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-conversation">
                                <div class="text-center">
                                    <i class="fas fa-comments fa-3x mb-3 text-muted"></i>
                                    <h5>Select a conversation to start messaging</h5>
                                    <p class="text-muted">Choose a conversation from the list to begin chatting</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentUserId = <?php echo $user_id; ?>;
        let currentReceiverId = <?php echo $selected_conversation ? $selected_conversation['id'] : 'null'; ?>;
        let currentSessionId = <?php echo $selected_conversation && $selected_conversation['session_id'] ? $selected_conversation['session_id'] : 'null'; ?>;
        let lastMessageId = 0;
        let isSending = false; // Flag to prevent duplicate sends
        let eventListenersAttached = false; // Flag to prevent duplicate event listeners
        let displayedMessageIds = new Set(); // Track displayed message IDs to prevent duplicates

        // Initialize lastMessageId with the highest message ID from existing messages
        document.addEventListener('DOMContentLoaded', function() {
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                const existingMessages = messagesArea.querySelectorAll('.message');
                existingMessages.forEach(msg => {
                    const messageId = msg.getAttribute('data-message-id');
                    if (messageId) {
                        const id = parseInt(messageId);
                        lastMessageId = Math.max(lastMessageId, id);
                        displayedMessageIds.add(id);
                    }
                });
            }
            scrollToBottom();
            attachEventListeners();
            
            // Load new messages every 3 seconds
            setInterval(loadNewMessages, 3000);
        });

        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }

        // Send message via AJAX
        function sendMessage() {
            if (isSending) return; // Prevent duplicate sends
            
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message || !currentReceiverId) return;
            
            isSending = true;
            
            // Disable input while sending
            messageInput.disabled = true;
            document.getElementById('sendMessageBtn').disabled = true;
            
            fetch('../api/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'send_message',
                    receiver_id: currentReceiverId,
                    session_id: currentSessionId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add message to chat
                    addMessageToChat(data.message);
                    lastMessageId = Math.max(lastMessageId, data.message.id);
                    displayedMessageIds.add(data.message.id);
                    messageInput.value = '';
                } else {
                    alert('Error sending message: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            })
            .finally(() => {
                isSending = false;
                messageInput.disabled = false;
                document.getElementById('sendMessageBtn').disabled = false;
                messageInput.focus();
            });
        }

        // Add message to chat
        function addMessageToChat(messageData) {
            // Check if message already exists to prevent duplicates
            if (displayedMessageIds.has(messageData.id)) {
                return; // Message already displayed
            }
            
            const messagesArea = document.getElementById('messagesArea');
            const isSent = messageData.sender_id == currentUserId;
            
            const messageHtml = `
                <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${messageData.id}">
                    <div class="message-content">
                        ${messageData.message.replace(/\n/g, '<br>')}
                    </div>
                    <div class="message-time">
                        ${new Date(messageData.created_at).toLocaleString()}
                    </div>
                </div>
            `;
            
            // Remove "no messages" placeholder if it exists
            const noMessages = messagesArea.querySelector('.text-center');
            if (noMessages) {
                noMessages.remove();
            }
            
            messagesArea.insertAdjacentHTML('beforeend', messageHtml);
            displayedMessageIds.add(messageData.id);
            scrollToBottom();
        }

        // Load new messages
        function loadNewMessages() {
            if (!currentReceiverId) return;
            
            fetch('../api/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_messages',
                    user_id: currentReceiverId,
                    last_message_id: lastMessageId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        if (message.id > lastMessageId && !displayedMessageIds.has(message.id)) {
                            addMessageToChat(message);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
        }

        // Attach event listeners only once
        function attachEventListeners() {
            if (eventListenersAttached) return;
            console.log('Attaching event listeners for message send');
            
            const sendBtn = document.getElementById('sendMessageBtn');
            const messageInput = document.getElementById('messageInput');
            
            if (sendBtn && messageInput) {
                // Remove any existing event listeners (defensive)
                sendBtn.replaceWith(sendBtn.cloneNode(true));
                messageInput.replaceWith(messageInput.cloneNode(true));
                // Re-select after replace
                const newSendBtn = document.getElementById('sendMessageBtn');
                const newMessageInput = document.getElementById('messageInput');
                // Send message on button click
                newSendBtn.addEventListener('click', sendMessage);
                // Send message on Enter key (but allow Shift+Enter for new lines)
                newMessageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
                eventListenersAttached = true;
            }
    </script>
</body>
</html> 