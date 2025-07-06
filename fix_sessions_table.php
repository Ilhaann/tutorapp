<?php
require_once 'config/database.php';

echo "<h2>Fixing Sessions Table</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check current columns in sessions table
    $stmt = $pdo->prepare("DESCRIBE sessions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Current columns in sessions table:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Add missing columns
    $missingColumns = [
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'payment_status' => "ENUM('pending', 'paid', 'failed') DEFAULT 'pending'"
    ];
    
    foreach ($missingColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            echo "<p>Adding missing column: $columnName</p>";
            try {
                $pdo->exec("ALTER TABLE sessions ADD COLUMN $columnName $columnDef");
                echo "<p style='color: green;'>✅ Added column: $columnName</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Column $columnName might already exist or error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Column $columnName already exists</p>";
        }
    }
    
    // Update existing sessions to have proper status
    echo "<h3>Updating existing sessions:</h3>";
    
    // Update sessions that have completed payments to be scheduled and paid
    $stmt = $pdo->prepare("
        UPDATE sessions s
        JOIN payments p ON s.id = p.session_id
        SET s.status = 'scheduled', s.payment_status = 'paid', s.updated_at = NOW()
        WHERE p.status = 'completed' AND s.status = 'pending'
    ");
    $stmt->execute();
    $rowsUpdated = $stmt->rowCount();
    echo "<p style='color: green;'>✅ Updated $rowsUpdated sessions to scheduled and paid</p>";
    
    echo "<h3>Sessions table fixed successfully!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
</style> 