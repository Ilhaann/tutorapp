<?php
namespace App\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class SendMail {
    private $mail;
    private $error;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->error = null;
        
        try {
            // Enable debugging only in development
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
            
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'fatumamm99@gmail.com';
            $this->mail->Password = 'mjdn nvnf qkcq iiyi';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port = 465;
            
            // Default sender
            $this->mail->setFrom('fatumamm99@gmail.com', 'Strathmore Peer Tutoring');
            
            // Additional settings
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Encoding = 'base64';
            
            // Timeout settings
            $this->mail->Timeout = 60;
            $this->mail->SMTPKeepAlive = true;
        } catch (PHPMailerException $e) {
            $this->error = "Mailer Error: " . $e->getMessage();
            error_log($this->error);
        }
    }

    public function send($mailMsg) {
        if ($this->error) {
            error_log($this->error);
            return false;
        }

        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            
            // Validate required fields
            if (empty($mailMsg['to_email'])) {
                throw new PHPMailerException('Recipient email is required');
            }
            if (empty($mailMsg['subject'])) {
                throw new PHPMailerException('Email subject is required');
            }
            if (empty($mailMsg['message'])) {
                throw new PHPMailerException('Email message is required');
            }

            // Recipients
            $this->mail->addAddress($mailMsg['to_email'], $mailMsg['to_name'] ?? '');
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $mailMsg['subject'];
            $this->mail->Body = nl2br($mailMsg['message']);
            
            // Add plain text version
            $this->mail->AltBody = strip_tags($mailMsg['message']);
            
            // Send the email
            $result = $this->mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: " . $mailMsg['to_email']);
            } else {
                error_log("Failed to send email to: " . $mailMsg['to_email']);
            }
            
            return $result;
        } catch (PHPMailerException $e) {
            $this->error = "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
            error_log($this->error);
            return false;
        }
    }

    public function getError() {
        return $this->error;
    }

    public function addAttachment($path, $name = '') {
        try {
            $this->mail->addAttachment($path, $name);
            return true;
        } catch (PHPMailerException $e) {
            $this->error = "Could not add attachment: " . $e->getMessage();
            return false;
        }
    }

    public function addCC($email, $name = '') {
        try {
            $this->mail->addCC($email, $name);
            return true;
        } catch (PHPMailerException $e) {
            $this->error = "Could not add CC: " . $e->getMessage();
            return false;
        }
    }

    public function addBCC($email, $name = '') {
        try {
            $this->mail->addBCC($email, $name);
            return true;
        } catch (PHPMailerException $e) {
            $this->error = "Could not add BCC: " . $e->getMessage();
            return false;
        }
    }
} 