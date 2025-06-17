<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is a tutee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutee') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if tutor_id is provided
if (!isset($_GET['tutor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tutor ID is required']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get available slots for the tutor that are not already booked
    $stmt = $pdo->prepare("
        SELECT 
            av.id,
            DATE_FORMAT(av.start_time, '%Y-%m-%d') as date,
            DATE_FORMAT(av.start_time, '%h:%i %p') as start_time,
            DATE_FORMAT(av.end_time, '%h:%i %p') as end_time
        FROM availability_slots av
        LEFT JOIN sessions s ON av.id = s.slot_id AND s.status IN ('pending', 'accepted')
        WHERE av.tutor_id = ? 
        AND av.start_time > NOW()
        AND s.id IS NULL
        ORDER BY av.start_time ASC
    ");
    
    $stmt->execute([$_GET['tutor_id']]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($slots);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    error_log("Error fetching available slots: " . $e->getMessage());
} 