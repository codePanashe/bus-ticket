<?php
// payment.php (place in bus-prebooking/payment/payment.php)
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';


use Paynow\Payments\Paynow;

$error = '';

// --- Replace with your Paynow credentials ---
$integrationId  = '22367';
                    
$integrationKey = 'eeb9c2e0-b1d6-4e4c-8e51-64aedda8ab59';
$merchantEmail  = 'mubangopanashe20@gmail.com'; // Paynow merchant email (needed for sandbox test mode)
// ------------------------------------------------

$returnUrl = 'http://localhost/bus-prebooking/payment/payment-return.php';
$resultUrl = 'http://localhost/bus-prebooking/payment/payment-result.php';

// --- Handle seat-selection POST (same logic you had) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['confirm_payment'])) {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $fare = floatval($_POST['fare'] ?? 0);
    $seats_input = $_POST['seats'] ?? '';

    if (!$schedule_id || empty($seats_input)) {
        $_SESSION['error'] = "Invalid booking data.";
        header("Location: ../dashboard.php");
        exit;
    }

    $seat_list = array_map('intval', array_filter(array_map('trim', explode(',', $seats_input))));
    if (empty($seat_list) || count($seat_list) > 6) {
        $_SESSION['error'] = "Please select 1–6 seats.";
        header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id) . "&fare=" . urlencode($fare));
        exit;
    }

    // Check availability
    $placeholders = str_repeat('?,', count($seat_list) - 1) . '?';
    $stmt = $pdo->prepare("SELECT seat_number FROM bookings WHERE schedule_id = ? AND FIND_IN_SET(seat_number, ?) AND payment_status = 'confirmed'");
    // older schema used seat_number as single; to be safe store CSV check - fallback query:
    // We'll check each seat individually if necessary
    $conflicts = [];
    foreach ($seat_list as $s) {
        $q = $pdo->prepare("SELECT seat_number FROM bookings WHERE schedule_id = ? AND FIND_IN_SET(?, seats) AND payment_status = 'confirmed'");
        $q->execute([$schedule_id, $s]);
        if ($q->fetch()) $conflicts[] = $s;
    }

    if (!empty($conflicts)) {
        $_SESSION['error'] = "Some seats are no longer available: " . implode(', ', $conflicts);
        header("Location: ../select_seat.php?schedule_id=" . urlencode($schedule_id) . "&fare=" . urlencode($fare));
        exit;
    }

    // Save booking to session
    $_SESSION['booking'] = [
        'schedule_id' => $schedule_id,
        'seats' => $seat_list,
        'fare_per_seat' => $fare,
        'total_fare' => $fare * count($seat_list)
    ];

    header("Location: ../payment/payment.php");
    exit;
}

// --- Handle confirm payment POST: initiate Paynow ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $booking = $_SESSION['booking'] ?? null;
    if (!$booking) {
        $_SESSION['error'] = "No active booking found.";
        header("Location: ../dashboard.php");
        exit;
    }

    try {
        // Safeguard: make sure seats still available (race protection)
        $conflicts = [];
        foreach ($booking['seats'] as $s) {
            $q = $pdo->prepare("SELECT 1 FROM bookings WHERE schedule_id = ? AND FIND_IN_SET(?, seats) AND payment_status = 'confirmed' LIMIT 1");
            $q->execute([$booking['schedule_id'], $s]);
            if ($q->fetch()) $conflicts[] = $s;
        }
        if (!empty($conflicts)) {
            throw new Exception("Seats no longer available: " . implode(', ', $conflicts));
        }

        // Init Paynow with return/result URLs
        $paynow = new Paynow($integrationId, $integrationKey, $returnUrl, $resultUrl);

        // Use merchant email for sandbox test mode — in live you should use customer email
        $customerEmail = $_SESSION['user_email'] ?? $merchantEmail;
        $bookingId = isset($booking['booking_id']) ? $booking['booking_id'] : rand(1000, 9999);

        $payment = $paynow->createPayment("ZUPCO Booking #{$bookingId}", $customerEmail);
        $payment->add("Bus Ticket ({$booking['seats'][0]} and others)", number_format($booking['total_fare'], 2, '.', ''));

        $response = $paynow->send($payment);

        if ($response->success()) {
            // Save poll url + current booking to session for later confirmation
            $_SESSION['paynow_poll_url'] = $response->pollUrl();
            $_SESSION['booking_pending'] = $booking;

            // redirect user to Paynow (sandbox or live depending on creds)
            header("Location: " . $response->redirectUrl());
            exit;
        } else {
            // show detailed error
            $err = property_exists($response, 'errors') ? print_r($response, true) : json_encode($response);
            $error = "Payment initiation failed. " . htmlspecialchars($err);
        }
    } catch (Exception $e) {
        $error = "Payment error: " . $e->getMessage();
    }
}

// --- GET: display booking summary (page view) ---
$booking = $_SESSION['booking'] ?? null;
if (!$booking) {
    $_SESSION['error'] = "No active booking. Please start a new one.";
    header("Location: ../dashboard.php");
    exit;
}

// Fetch route info for display
$stmt = $pdo->prepare("
    SELECT r.departure, r.destination, s.departure_time, b.fleet_number
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON b.route_id = r.route_id
    WHERE s.schedule_id = ?
");
$stmt->execute([$booking['schedule_id']]);
$route_info = $stmt->fetch();

if (!$route_info) {
    $_SESSION['error'] = "Invalid schedule. Please start a new booking.";
    unset($_SESSION['booking']);
    header("Location: ../dashboard.php");
    exit;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payment - ZUPCO</title>
<style>
/* minimal styles - copy from your original payment page */
body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
.container { max-width:600px; margin:30px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.header { background:#1a5f23; color:#fff; padding:12px; text-align:center; border-radius:8px 8px 0 0; }
.fare { font-size:24px; color:#1a5f23; text-align:center; margin:20px 0; }
.btn-pay{ display:block; width:100%; padding:12px; background:#1a5f23; color:#fff; border:0; border-radius:6px; font-size:18px; cursor:pointer; }
.error{ color:red; text-align:center; margin:10px 0; }
</style>
</head>
<body>
<div class="container">
    <div class="header"><h2>Secure Payment</h2></div>

    <h3>Booking Summary</h3>
    <p><strong>Route:</strong> <?= htmlspecialchars($route_info['departure']) ?> → <?= htmlspecialchars($route_info['destination']) ?></p>
    <p><strong>Bus:</strong> <?= htmlspecialchars($route_info['fleet_number']) ?></p>
    <p><strong>Seats:</strong> <?= implode(', ', $booking['seats']) ?></p>

    <div class="fare">$<?= number_format($booking['total_fare'], 2) ?></div>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="confirm_payment" value="1">
        <button class="btn-pay" type="submit">Pay with Paynow / EcoCash</button>
    </form>

    <p style="text-align:center;margin-top:10px;"><a href="../select_seat.php?schedule_id=<?= urlencode($booking['schedule_id']) ?>&fare=<?= urlencode($booking['fare_per_seat']) ?>">← Change Seats</a></p>
</div>
</body>
</html>
