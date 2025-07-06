<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Payment Tracking Fixes</h2>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p>Starting payment tracking fixes...</p>";
    
    // 1. Add missing columns to payments table
    echo "<p>1. Adding missing columns to payments table...</p>";
    $pdo->exec("ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `failure_reason` text DEFAULT NULL AFTER `response_description`");
    $pdo->exec("ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`");
    echo "<p style='color: green;'>✓ Added failure_reason and updated_at columns</p>";
    
    // 2. Add 'confirmed' status to sessions table
    echo "<p>2. Adding 'confirmed' status to sessions table...</p>";
    $pdo->exec("ALTER TABLE `sessions` MODIFY COLUMN `status` enum('pending','scheduled','completed','cancelled','rejected','confirmed') DEFAULT 'pending'");
    echo "<p style='color: green;'>✓ Added 'confirmed' status to sessions</p>";
    
    // 3. Add useful indexes for payment tracking
    echo "<p>3. Adding payment tracking indexes...</p>";
    $pdo->exec("ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_status` (`status`)");
    $pdo->exec("ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_updated_at` (`updated_at`)");
    $pdo->exec("ALTER TABLE `sessions` ADD INDEX IF NOT EXISTS `idx_session_status` (`status`)");
    echo "<p style='color: green;'>✓ Added payment tracking indexes</p>";
    
    // 4. Create payment tracking view
    echo "<p>4. Creating payment tracking view...</p>";
    $view_sql = "
    CREATE OR REPLACE VIEW `payment_tracking` AS
    SELECT 
        p.id as payment_id,
        p.amount,
        p.status as payment_status,
        p.response_code,
        p.response_description,
        p.failure_reason,
        p.mpesa_receipt_number,
        p.merchant_request_id,
        p.checkout_request_id,
        p.created_at as payment_created,
        p.updated_at as payment_updated,
        s.id as session_id,
        s.status as session_status,
        s.payment_status as session_payment_status,
        s.created_at as session_created,
        tutee.first_name as tutee_first_name,
        tutee.last_name as tutee_last_name,
        tutee.email as tutee_email,
        tutor.first_name as tutor_first_name,
        tutor.last_name as tutor_last_name,
        tutor.email as tutor_email,
        u.name as unit_name,
        u.code as unit_code,
        a.start_time,
        a.end_time
    FROM payments p
    LEFT JOIN sessions s ON p.session_id = s.id
    LEFT JOIN users tutee ON s.tutee_id = tutee.id
    LEFT JOIN users tutor ON s.tutor_id = tutor.id
    LEFT JOIN units u ON s.unit_id = u.id
    LEFT JOIN availability_slots a ON s.slot_id = a.id
    ORDER BY p.created_at DESC
    ";
    $pdo->exec($view_sql);
    echo "<p style='color: green;'>✓ Created payment tracking view</p>";
    
    // 5. Show current payment statistics
    echo "<p>5. Current payment statistics:</p>";
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(amount) as total FROM payments GROUP BY status");
    $stmt->execute();
    $stats = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status</th><th>Count</th><th>Total Amount</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . ucfirst($stat['status']) . "</td>";
        echo "<td>" . $stat['count'] . "</td>";
        echo "<td>KES " . number_format($stat['total'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✓ All payment tracking fixes completed successfully!</p>";
    echo "<p><a href='admin/payment_tracker.php'>Go to Payment Tracker</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 