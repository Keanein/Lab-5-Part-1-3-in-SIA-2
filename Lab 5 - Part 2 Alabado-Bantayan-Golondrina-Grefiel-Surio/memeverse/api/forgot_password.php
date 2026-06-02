<?php
// api/forgot_password.php - PHASE 3 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['error' => 'Email is required']);
    exit;
}

// Find user
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Always return success even if email not found (security: don't reveal if email exists)
if (!$user) {
    echo json_encode(['success' => true]);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $token, $expires]);

$resetLink = SITE_URL . "reset_password.php?token=" . $token;

$subject = "Reset your MemeVerse password";
$htmlBody = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Reset Password</title></head>
<body style='font-family: Arial, sans-serif; background: #fef9e8; padding: 20px;'>
    <div style='max-width: 500px; margin: 0 auto; background: white; border-radius: 24px; padding: 30px; text-align: center;'>
        <div style='font-size: 3rem;'>🎭</div>
        <h2>Reset Your Password</h2>
        <p>Hi " . htmlspecialchars($user['username']) . ",</p>
        <p>We received a request to reset your password. Click the button below to create a new one.</p>
        <a href='" . $resetLink . "' style='display: inline-block; background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 40px; margin: 20px 0;'>Reset Password</a>
        <p>If you didn't request this, you can ignore this email.</p>
        <p>The link will expire in 1 hour.</p>
        <hr>
        <small>MemeVerse -- Share the laughter</small>
    </div>
</body>
</html>
";

sendEmail($email, $subject, $htmlBody);

echo json_encode(['success' => true]);
exit;
?>