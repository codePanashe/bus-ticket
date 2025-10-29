<?php
// includes/db.php
// Database connection using PDO for ZUPCO Bus Pre-Booking System

$host = 'localhost';
$dbname = 'zupco_bus';
$username = 'root';      // Default for XAMPP
$password = '';          // Default for XAMPP (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
