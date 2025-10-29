<?php
// payment-return.php (place in bus-prebooking/payment/payment-return.php)
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

// If Paynow returns some query parameters you may inspect them (optional)
$info = $_GET ?? [];

// If we have a poll URL in session, go confirm; otherwise show message.
$poll = $_SESSION['paynow_poll_url'] ?? null;

// Friendly UX: redirect to payment-result.php to finalize confirmation
if ($poll) {
    // Quick feedback page before redirect
    header("Refresh:2; url=payment-result.php");
    ?>
    <!doctype html>
    <html>
    <head><meta charset="utf-8"><title>Returning from Paynow</title></head>
    <body style="font-family:Arial, sans-serif; text-align:center; padding:40px;">
        <h2>Thanks — checking payment status...</h2>
        <p>If you are not redirected automatically, <a href="payment-result.php">click here</a>.</p>
    </body>
    </html>
    <?php
    exit;
} else {
    // No poll url in session — show friendly message
    ?>
    <!doctype html>
    <html>
    <head><meta charset="utf-8"><title>Payment Returned</title></head>
    <body style="font-family:Arial, sans-serif; text-align:center; padding:40px;">
        <h2>Returned from Paynow</h2>
        <p>We could not find a pending transaction in your session. If you already paid, contact support or try viewing your bookings in the dashboard.</p>
        <p><a href="../dashboard.php">Back to dashboard</a></p>
    </body>
    </html>
    <?php
    exit;
}
