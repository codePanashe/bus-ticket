<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

use Paynow\Payments\Paynow;
use Dotenv\Dotenv;

// load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$integrationId  = $_ENV['PAYNOW_INTEGRATION_ID'] ?? null;
$integrationKey = $_ENV['PAYNOW_INTEGRATION_KEY'] ?? null;
$returnUrl = $_ENV['PAYNOW_RETURN_URL'] ?? null;
$resultUrl = $_ENV['PAYNOW_RESULT_URL'] ?? null;

if (!$integrationId || !$integrationKey || !$returnUrl || !$resultUrl) {
    http_response_code(500);
    die('Payment configuration not set. Check .env.');
}

// Get booking info from POST
$schedule_id = $_POST['schedule_id'] ?? null;
$seats_input = $_POST['seats'] ?? null; // expected a string (e.g. "12,13") or array
$amount = floatval($_POST['amount'] ?? 0);

if (is_string($seats_input)) {
    $seats = array_filter(array_map('trim', explode(',', $seats_input)));
} elseif (is_array($seats_input)) {
    $seats = array_map('trim', $seats_input);
} else {
    $seats = [];
}

if (!$schedule_id || empty($seats) || $amount <= 0) {
    http_response_code(400);
    die('Invalid booking data.');
}

// Simple availability check: ensure none of these seats are already booked (confirmed or pending)
$placeholders = implode(',', array_fill(0, count($seats), '?'));
$sql = "SELECT seat_number FROM bookings WHERE schedule_id = ? AND seat_number IN ($placeholders) AND payment_status IN ('pending','confirmed')";
$stmt = $pdo->prepare($sql);
$params = array_merge([$schedule_id], $seats);
$stmt->execute($params);
$conflicts = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($conflicts)) {
    http_response_code(409);
    die('Some seats are already taken: ' . implode(',', $conflicts));
}

// Insert a pending booking row (you previously used one row to represent the whole booking)
$ticket_number = 'PENDING-' . date('YmdHis') . '-' . str_pad(rand(0,9999), 4, '0');

$insert = $pdo->prepare("
    INSERT INTO bookings (passenger_id, schedule_id, seat_number, seats, ticket_number, payment_status, payment_ref, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', NULL, NOW())
");

try {
    $pdo->beginTransaction();
    $insert->execute([
        $_SESSION['user_id'] ?? null,
        $schedule_id,
        $seats[0],                    // seat_number field keeps the first seat for indexing
        implode(',', $seats),         // seats csv
        $ticket_number
    ]);
    $booking_id = (int)$pdo->lastInsertId();
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('DB insert pending booking error: ' . $e->getMessage());
    http_response_code(500);
    die('Server error creating booking.');
}

// Save in session so user-return flow can pick it up
$_SESSION['booking_pending'] = [
    'booking_id' => $booking_id,
    'schedule_id' => $schedule_id,
    'seats' => $seats,
    'total_fare' => $amount
];

// Initialize Paynow
$paynow = new Paynow($integrationId, $integrationKey, $returnUrl, $resultUrl);

// Create payment
$customerEmail = $_SESSION['user_email'] ?? 'guest@example.com';
$payment = $paynow->createPayment("ZUPCO Booking #{$booking_id}", $customerEmail);
$payment->add("Bus Ticket", $amount);

try {
    $response = $paynow->send($payment);
    if ($response->success()) {
        $pollUrl = $response->pollUrl();
        // store pollUrl in bookings row to match later (server notifications or poll fallback)
        $u = $pdo->prepare("UPDATE bookings SET paynow_poll_url = ? WHERE booking_id = ?");
        $u->execute([$pollUrl, $booking_id]);

        // also keep in session for convenience
        $_SESSION['paynow_poll_url'] = $pollUrl;

        // Redirect user to Paynow
        header("Location: " . $response->redirectUrl());
        exit;
    } else {
        error_log('Paynow send failed: ' . print_r($response, true));
        http_response_code(502);
        echo "Failed to initiate payment. Please try again later.";
    }
} catch (Exception $e) {
    error_log('Paynow exception: ' . $e->getMessage());
    http_response_code(500);
    echo "Payment service error. Please try again later.";
}
