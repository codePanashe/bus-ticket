<!-- register.php -->
<?php
require 'includes/db.php';
require 'includes/email.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $id = trim($_POST['national_id']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // === PASSWORD VALIDATION ===
    $passwordValid = true;
    $passwordErrors = [];

    if (strlen($password) < 8) {
        $passwordErrors[] = "at least 8 characters";
        $passwordValid = false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = "at least one uppercase letter";
        $passwordValid = false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = "at least one lowercase letter";
        $passwordValid = false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = "at least one number";
        $passwordValid = false;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $passwordErrors[] = "at least one special character (e.g., !, @, #, $)";
        $passwordValid = false;
    }

    if (!$passwordValid) {
        $error = "Password must contain: " . implode(", ", $passwordErrors) . ".";
    } else {
        // Password is valid → proceed
        $pass = password_hash($password, PASSWORD_DEFAULT);
        $code = rand(100000, 999999);

        try {
            $stmt = $pdo->prepare("INSERT INTO passengers (full_name, national_id, phone, email, password, verification_code, verified) 
                                   VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->execute([$name, $id, $phone, $email, $pass, $code]);

            $subject = "ZUPCO Account Verification";
            $message = "Hello $name,\n\nYour ZUPCO verification code is: $code\n\nEnter this code to activate your account.";

            if (sendZUPCOEmail($email, $subject, $message)) {
                $_SESSION['temp_email'] = $email;
                $_SESSION['verification_code'] = $code;
                header("Location: verify.php");
                exit;
            } else {
                $error = "Failed to send verification email. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Registration failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - ZUPCO</title>
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
        <h2>Passenger Registration</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="national_id" placeholder="76-897654P90" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <small style="display:block; margin-top:-8px; color:#555; font-size:0.85em;">
                Password must include uppercase, lowercase, number, special char, and be 8+ chars.
            </small>
            <button type="submit">Register</button>
        </form>
        <p class="links">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>