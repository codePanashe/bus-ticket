<?php
// payment-result.php (place in bus-prebooking/payment/payment-result.php)
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

use Paynow\Payments\Paynow;

$integrationId  = 'YOUR_INTEGRATION_ID';
$integrationKey = 'YOUR_INTEGRATION_KEY';

// The poll URL may be present in session, or Paynow might pass it (some setups use GET)
$pollUrl = trim($_SESSION['paynow_poll_url'] ?? ($_GET['pollurl'] ?? ''));

$booking = $_SESSION['booking_pending'] ?? null;

if (!$pollUrl || !$booking) {
    $_SESSION['error'] = "No pending payment found.";
    header("Location: ../dashboard.php");
    exit;
}

// Create Paynow instance (explicit nulls to satisfy static analyzers)
$paynow = new Paynow($integrationId, $integrationKey, null, null);

try {
    $response = $paynow->pollTransaction($pollUrl);

    if ($response->paid()) {
        // double-check seats are still available before committing
        $conflicts = [];
        foreach ($booking['seats'] as $s) {
            $q = $pdo->prepare("SELECT 1 FROM bookings WHERE schedule_id = ? AND FIND_IN_SET(?, seats) AND payment_status = 'confirmed' LIMIT 1");
            $q->execute([$booking['schedule_id'], $s]);
            if ($q->fetch()) $conflicts[] = $s;
        }

        if (!empty($conflicts)) {
            // Payment succeeded but seats already taken — handle refund or alert admin
            // For now, mark session error and redirect user to dashboard
            $_SESSION['error'] = "Payment received but some seats were taken: " . implode(', ', $conflicts) . ". Please contact support.";
            // Optionally: record the payment reference to reconcile and process refund
            header("Location: ../dashboard.php");
            exit;
        }

        // Commit booking in DB inside transaction
        $pdo->beginTransaction();

        // Generate ticket number
        $ticketNo = 'ZUPCO-' . date('Ymd') . '-' . str_pad(random_int(0, 9999), 4, '0');

        // Insert booking (store seats as CSV to maintain compatibility)
        $seatsCsv = implode(',', $booking['seats']);
        $stmt = $pdo->prepare("
            INSERT INTO bookings (passenger_id, schedule_id, seat_number, seats, ticket_number, payment_status, payment_ref)
            VALUES (?, ?, ?, ?, ?, 'confirmed', ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $booking['schedule_id'],
            $booking['seats'][0] ?? null,
            $seatsCsv,
            $ticketNo,
            $response->reference()
        ]);

        // Optionally multiple inserts per seat if your schema requires one row per seat,
        // uncomment and use the block below instead of the single insert above:
        /*
        foreach ($booking['seats'] as $s) {
            $pdo->prepare("
                INSERT INTO bookings (passenger_id, schedule_id, seat_number, seats, ticket_number, payment_status, payment_ref)
                VALUES (?, ?, ?, ?, ?, 'confirmed', ?)
            ")->execute([
                $_SESSION['user_id'],
                $booking['schedule_id'],
                $s,
                $seatsCsv,
                $ticketNo,
                $response->reference()
            ]);
        }
        */

        // Update available seats counter
        $pdo->prepare("UPDATE schedules SET available_seats = available_seats - ? WHERE schedule_id = ?")
            ->execute([count($booking['seats']), $booking['schedule_id']]);

        $pdo->commit();

        // Save ticket to session for displaying
        $_SESSION['ticket'] = [
            'ticket_number' => $ticketNo,
            'name' => ($_SESSION['user_fullname'] ?? null) ?: (function() use ($pdo) {
                // try to fetch name
                return '';
            }),
            'seats' => $seatsCsv,
            'total_fare' => $booking['total_fare']
        ];

        // cleanup
        unset($_SESSION['booking_pending'], $_SESSION['paynow_poll_url'], $_SESSION['booking']);

        // redirect to ticket page (one final confirmation)
        header("Location: ../ticket.php");
        exit;

    } else {
        // Not paid (or pending). Show status and debug info.
        $status = $response->status() ?? 'unknown';
        $message = "Payment not completed yet. Status: " . htmlspecialchars((string)$status);
        // show errors if any
        $debug = print_r($response, true);

        ?>
        <!doctype html>
        <html>
        <head><meta charset="utf-8"><title>Payment Status</title></head>
        <body style="font-family:Arial, sans-serif; padding:30px;">
            <h2>Payment Status</h2>
            <p><?= $message ?></p>
            <pre><?= htmlspecialchars($debug) ?></pre>
            <p><a href="../dashboard.php">Back to dashboard</a></p>
        </body>
        </html>
        <?php
        exit;
    }

} catch (Exception $e) {
    // handle exception
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = "Payment verification failed: " . $e->getMessage();
    header("Location: ../dashboard.php");
    exit;
}
