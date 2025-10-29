<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

use Paynow\Payments\Paynow;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$integrationId  = $_ENV['PAYNOW_INTEGRATION_ID'] ?? null;
$integrationKey = $_ENV['PAYNOW_INTEGRATION_KEY'] ?? null;
$returnUrl = $_ENV['PAYNOW_RETURN_URL'] ?? 'http://localhost/bus-prebooking/payment/payment-return.php';
$resultUrl = $_ENV['PAYNOW_RESULT_URL'] ?? 'http://localhost/bus-prebooking/payment/payment-result.php';

if (!$integrationId || !$integrationKey) {
    http_response_code(500);
    die('Payment configuration not set. Check .env file.');
}

// Get booking info from POST
$schedule_id = $_POST['schedule_id'] ?? null;
$seats_input = $_POST['seats'] ?? null;
$amount = floatval($_POST['amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'ecocash';
$phone_number = $_POST['phone_number'] ?? '';

// Validate phone number for EcoCash
if ($payment_method === 'ecocash' && empty($phone_number)) {
    $_SESSION['error'] = "Phone number is required for EcoCash payment.";
    header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id));
    exit;
}

if ($payment_method === 'ecocash' && !preg_match('/^(077|078|071)\d{7}$/', $phone_number)) {
    $_SESSION['error'] = "Please provide a valid Econet number (077, 078, or 071).";
    header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id));
    exit;
}

if (is_string($seats_input)) {
    $seats = array_filter(array_map('trim', explode(',', $seats_input)));
} elseif (is_array($seats_input)) {
    $seats = array_map('trim', $seats_input);
} else {
    $seats = [];
}

if (!$schedule_id || empty($seats) || $amount <= 0) {
    $_SESSION['error'] = "Invalid booking data.";
    header("Location: ../dashboard.php");
    exit;
}

// Enhanced availability check
$conflicts = [];
foreach ($seats as $seat) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM bookings 
        WHERE schedule_id = ? 
        AND (seat_number = ? OR FIND_IN_SET(?, seats)) 
        AND payment_status IN ('pending', 'confirmed')
        LIMIT 1
    ");
    $stmt->execute([$schedule_id, $seat, $seat]);
    if ($stmt->fetch()) {
        $conflicts[] = $seat;
    }
}

if (!empty($conflicts)) {
    $_SESSION['error'] = "Some seats are already taken: " . implode(', ', $conflicts);
    header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id));
    exit;
}

// Insert pending booking
$ticket_number = 'PENDING-' . date('YmdHis') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO bookings (passenger_id, schedule_id, seat_number, seats, ticket_number, payment_status, payment_method, phone_number, total_amount, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $schedule_id,
        $seats[0], // first seat for indexing
        implode(',', $seats),
        $ticket_number,
        $payment_method,
        $phone_number ?: null,
        $amount
    ]);
    
    $booking_id = (int)$pdo->lastInsertId();
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('DB insert pending booking error: ' . $e->getMessage());
    $_SESSION['error'] = "Server error creating booking.";
    header("Location: ../dashboard.php");
    exit;
}

// Save in session
$_SESSION['booking_pending'] = [
    'booking_id' => $booking_id,
    'schedule_id' => $schedule_id,
    'seats' => $seats,
    'total_fare' => $amount,
    'payment_method' => $payment_method,
    'phone_number' => $phone_number
];

// Initialize Paynow
$paynow = new Paynow($integrationId, $integrationKey, $returnUrl, $resultUrl);

// Create payment
$customerEmail = $_SESSION['user_email'] ?? 'guest@example.com';
$payment_reference = "ZUPCO-" . $booking_id . "-" . date('His');

try {
    $payment = $paynow->createPayment($payment_reference, $customerEmail);
    $payment->add("Bus Ticket - " . implode(', ', $seats), $amount);

    // Send payment based on method
    if ($payment_method === 'ecocash') {
        // For EcoCash mobile payment
        $response = $paynow->sendMobile($payment, $phone_number, 'ecocash');
    } else {
        // For web payments
        $response = $paynow->send($payment);
    }

    if ($response->success()) {
        $pollUrl = $response->pollUrl();
        $paynowReference = $response->reference();
        
        // Store payment details in database
        $update_stmt = $pdo->prepare("
            UPDATE bookings 
            SET paynow_poll_url = ?, paynow_reference = ?, payment_method = ?
            WHERE booking_id = ?
        ");
        $update_stmt->execute([$pollUrl, $paynowReference, $payment_method, $booking_id]);

        // Store in session for redirect flow
        $_SESSION['paynow_poll_url'] = $pollUrl;
        $_SESSION['paynow_reference'] = $paynowReference;

        // Redirect based on payment method
        if ($payment_method === 'ecocash') {
            // For mobile payments, show instructions page
            $_SESSION['payment_instructions'] = $response->instructions() ?? 'Check your phone for payment prompt';
            header("Location: payment-instructions.php");
        } else {
            // For web payments, redirect to Paynow gateway
            header("Location: " . $response->redirectUrl());
        }
        exit;
        
    } else {
        // Payment initiation failed
        error_log('Paynow send failed: ' . print_r($response, true));
        
        // Clean up the pending booking
        $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?")->execute([$booking_id]);
        unset($_SESSION['booking_pending']);
        
        $_SESSION['error'] = "Failed to initiate payment. Please try again.";
        header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id));
        exit;
    }
    
} catch (Exception $e) {
    error_log('Paynow exception: ' . $e->getMessage());
    
    // Clean up on exception
    $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?")->execute([$booking_id]);
    unset($_SESSION['booking_pending']);
    
    $_SESSION['error'] = "Payment service error. Please try again.";
    header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id));
    exit;
}
?>