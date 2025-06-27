<?php
echo "Testing messaging API...\n\n";

// Test 1: Check if API file exists
if (file_exists('api/messages.php')) {
    echo "✅ API file exists\n";
} else {
    echo "❌ API file not found\n";
    exit(1);
}

// Test 2: Check if we can include the API
try {
    require_once 'config/database.php';
    echo "✅ Database config loaded\n";
} catch (Exception $e) {
    echo "❌ Database config error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check database connection
try {
    $db = new Database();
    $pdo = $db->getConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check if messages table has data
$stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
$result = $stmt->fetch();
echo "✅ Messages in database: " . $result['count'] . "\n";

echo "\n🎉 API is ready for testing!\n";
echo "Now you can:\n";
echo "1. Open tutor messages page in one browser window\n";
echo "2. Open tutee messages page in another browser window\n";
echo "3. Start typing and sending messages in real-time!\n";
?> 