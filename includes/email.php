<?php
// includes/email.php

// ✅ Place 'use' at the very top (required by PHP)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ✅ Load autoloader once
require_once __DIR__ . '/../vendor/autoload.php';

// ✅ Prevent redeclaration
if (!function_exists('sendZUPCOEmail')) {

    /**
     * Sends a plain-text email via Gmail SMTP
     * @param string $to      Recipient email (e.g., passenger@gmail.com)
     * @param string $subject Email subject
     * @param string $body    Plain-text message
     * @return bool           True on success
     */
    function sendZUPCOEmail($to, $subject, $body) {
        // Load .env
        $dotenv = __DIR__ . '/../.env';
        if (file_exists($dotenv)) {
            $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }

        $mail = new PHPMailer(true);

        try {
            // Gmail SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['EMAIL_USERNAME'] ?? 'your@gmail.com';
            $mail->Password   = $_ENV['EMAIL_PASSWORD'] ?? 'your_app_password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Sender and recipient
            $mail->setFrom($_ENV['EMAIL_FROM'] ?? 'noreply@zupco.co.zw', $_ENV['EMAIL_FROM_NAME'] ?? 'ZUPCO');
            $mail->addAddress($to);

            // Content
            $mail->isHTML(false); // Plain text for verification codes
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email to $to failed. Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>