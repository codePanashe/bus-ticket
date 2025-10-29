<?php
// check-payment.php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Paynow\Payments\Paynow;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$integrationId  = $_ENV['PAYNOW_INTEGRATION_ID'] ?? null;
$integrationKey = $_ENV['PAYNOW_INTEGRATION_KEY'] ?? null;

$reference = $_GET['reference'] ?? ($_SESSION['paynow_reference'] ?? '');

if (!$reference) {
    die("Invalid reference");
}

try {
    // Get payment details from database
    $stmt = $pdo->prepare("
        SELECT b.*, p.paynow_poll_url, p.paynow_reference 
        FROM bookings b 
        WHERE b.paynow_reference = ? OR b.booking_id = ?
    ");
    $stmt->execute([$reference, $reference]);
    $payment = $stmt->fetch();

    if (!$payment) {
        die("Payment not found");
    }

    $paynow = new Paynow($integrationId, $integrationKey, null, null);
    $status = $paynow->pollTransaction($payment['paynow_poll_url']);

    if ($status->paid()) {
        // Update payment status
        $pdo->prepare("UPDATE bookings SET payment_status = 'confirmed' WHERE booking_id = ?")
            ->execute([$payment['booking_id']]);
            
        $_SESSION['success'] = "Payment confirmed!";
        header("Location: ../ticket.php?booking_id=" . $payment['booking_id']);
    } else {
        echo json_encode([
            'status' => $status->status(),
            'paid' => $status->paid(),
            'success' => false,
            'message' => 'Payment not yet completed'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>