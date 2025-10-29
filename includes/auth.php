<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Optional: Validate that the user still exists in the database (enhanced security)
require 'db.php';

$stmt = $pdo->prepare("SELECT id FROM passengers WHERE id = ? AND verified = 1");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    // User not found or not verified — destroy session and redirect
    session_destroy();
    header("Location: login.php?error=unauthorized");
    exit();
}
?>