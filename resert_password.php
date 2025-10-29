<!-- reset_password.php -->
<?php
require 'includes/db.php';
require 'includes/email.php';

$token = $_GET['token'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];

    // Validate token
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM passengers WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Invalid or expired reset link.";
    } elseif (strtotime($user['reset_expires']) < time()) {
        $error = "Reset link has expired. Please request a new one.";
    } else {
        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE passengers SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?")
            ->execute([$hash, $token]);

        // Optional: Notify user of password change
        $user_email = $pdo->prepare("SELECT email FROM passengers WHERE reset_token = ?");
        $user_email->execute([$token]);
        $email = $user_email->fetchColumn();
        
        if ($email) {
            sendZUPCOEmail($email, "ZUPCO Password Changed", "Your password was successfully changed. If this wasn't you, contact support immediately.");
        }

        header("Location: login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Password - ZUPCO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .form { max-width: 400px; margin: 80px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1a5f23; color: white; border: none; border-radius: 4px; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .links { text-align: center; margin-top: 15px; }
        .links a { color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form">
        <h2>Create New Password</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="password" placeholder="New Password (min 8 characters)" required minlength="8">
            <button type="submit">Reset Password</button>
        </form>
        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>