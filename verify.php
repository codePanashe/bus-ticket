<!-- verify.php -->
<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['temp_email'])) {
    header("Location: register.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = $_POST['code'];
    if ($input_code == $_SESSION['verification_code']) {
        $email = $_SESSION['temp_email'];
        $pdo->prepare("UPDATE passengers SET verified = 1 WHERE email = ?")->execute([$email]);
        unset($_SESSION['verification_code'], $_SESSION['temp_email']);
        $_SESSION['user_email'] = $email;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid verification code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Account - ZUPCO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .form { max-width: 400px; margin: 80px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1a5f23; color: white; border: none; border-radius: 4px; }
        .error { color: red; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="form">
        <h2>Account Verification</h2>
        <p>Enter the 6-digit code sent to your email.</p>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="code" placeholder="Verification Code" required>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>