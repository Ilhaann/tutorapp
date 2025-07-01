<?php
namespace App\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';  // Adjust the path if needed

class SendMail {

    public static function send($to, $name, $verification_token) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                // Enable SMTP authentication
            $mail->Username   = 'fatumamm99@gmail.com';  // SMTP username
            $mail->Password   = 'mjdn nvnf qkcq iiyi';  // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use PHPMailer constant for encryption
            $mail->Port       = 587;                 // Port to connect to

            // Recipients
            $mail->setFrom('fatumamm99@gmail.com', 'Strathmore Peer Tutoring');
            $mail->addAddress($to, $name);  // Add recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Strathmore Peer Tutoring';
            $mail->Body    = "<p>Hello <strong>$name</strong>,</p>
                              <p>Thank you for registering. Please use the verification code below to activate your account:</p>
                              <h2 style='color: #667eea;'>$verification_token</h2>
                              <p>If you did not request this, please ignore this email.</p>";

            // Send the email
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
}
