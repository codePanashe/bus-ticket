<!-- login.php -->
<?php
session_start();
require 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Check if admin
    $stmt = $pdo->prepare("SELECT admin_id, password FROM admins WHERE username = ?");
    $stmt->execute([$email]); // Assuming admin logs in with username (or use email if you prefer)
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        header("Location: admin/dashboard.php");
        exit;
    }

    // 2. Check if passenger
    $stmt = $pdo->prepare("SELECT id, password, verified FROM passengers WHERE email = ?");
    $stmt->execute([$email]);
    $passenger = $stmt->fetch();

    if ($passenger) {
        if ($passenger['verified'] == 0) {
            $error = "Please verify your account first.";
        } elseif (password_verify($password, $passenger['password'])) {
            $_SESSION['user_id'] = $passenger['id'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ZUPCO Login</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; }
        .form { max-width: 400px; margin: 80px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1a5f23; color: white; border: none; border-radius: 4px; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .links { text-align: center; margin-top: 15px; }
        .links a { color: #1a5f23; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="form">
        <h2>ZUPCO Login</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="email" placeholder="Email (Passenger) or Username (Admin)" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="links">
            <a href="register.php">Register as new Passenger</a>
        </div>

        <!-- passenger forgot_password -->
        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a> 
        </div>
    </div>
</body>
</html>