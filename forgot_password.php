<!-- forgot_password.php -->
<?php
require 'includes/db.php';
require 'includes/email.php';
session_start();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate secure token (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to DB
        $pdo->prepare("UPDATE passengers SET reset_token = ?, reset_expires = ? WHERE email = ?")
            ->execute([$token, $expires, $email]);

        // Build reset link
        $reset_link = "http://$_SERVER[HTTP_HOST]/bus-prebooking/reset_password.php?token=$token";

        // Send email
        $subject = "ZUPCO Password Reset";
        $message = "Hello,\n\nYou requested a password reset for your ZUPCO account.\n\nClick the link below to create a new password:\n\n$reset_link\n\nThis link expires in 1 hour.\n\nIf you didn't request this, please ignore this email.";

        if (sendZUPCOEmail($email, $subject, $message)) {
            $success = "A password reset link has been sent to your email.";
        } else {
            $error = "Failed to send reset email. Please try again.";
        }
    } else {
        // Do NOT reveal if email exists (security best practice)
        $success = "If your email is registered, a reset link has been sent.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - ZUPCO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .form { max-width: 400px; margin: 80px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1a5f23; color: white; border: none; border-radius: 4px; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .success { color: green; text-align: center; margin: 10px 0; }
        .links { text-align: center; margin-top: 15px; }
        .links a { color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form">
        <h2>Reset Password</h2>
        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>