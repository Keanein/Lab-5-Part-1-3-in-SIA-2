<?php
// includes/email_helper.php - PHASE 2 PROFESSOR'S VERSION

require_once __DIR__ . '/config.php';

// Load Composer autoloader for PHPMailer (from professor's Phase 2)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Dispatches an HTML transactional email notification via configured SMTP relays
 * @param string $to Recipient electronic email location target
 * @param string $subject Message topic index title string
 * @param string $htmlBody HTML formatted email body payload
 * @param string|null $textBody Plain text alternative (auto-generated if null)
 * @return bool True if accepted for delivery, false otherwise
 */
function sendEmail($to, $subject, $htmlBody, $textBody = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings (from professor's Phase 2)
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed to $to: " . $mail->ErrorInfo);
        return false;
    }
}
?>