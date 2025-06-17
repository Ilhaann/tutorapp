<?php
session_start();
require_once '../config/db.php';
require_once '../app/Config/SendMail.php';  // Correct the path to the SendMail class

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $verification_token = rand(100000, 999999); // 6-digit verification code

    // Insert user into DB with token
    $sql = "INSERT INTO users (student_id, name, email, password, role, verification_token, is_verified)
            VALUES (?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$student_id, $name, $email, $password, $role, $verification_token]);

        // Store temp email in session for verification
        $_SESSION['temp_email'] = $email;

        // Send verification email using SendMail class
        if (\App\Config\SendMail::send($email, $name, $verification_token)) {
            // Redirect to verification page
            header("Location: verify.php");
            exit();
        } else {
            die("Failed to send the verification email. Please try again.");
        }

    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
} else {
    echo "Invalid request.";
}

// Update user as verified
$stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = ?");
$stmt->execute([$user['id']]);
?>
