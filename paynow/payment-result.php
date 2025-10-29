<?php
// payment-result.php
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

if (!$integrationId || !$integrationKey) {
    die('Payment configuration error.');
}

// Get poll URL from session or database
$pollUrl = $_SESSION['paynow_poll_url'] ?? null;
$booking = $_SESSION['booking_pending'] ?? null;

// If no session data, try to get from database using reference
if (!$pollUrl && isset($_GET['reference'])) {
    $stmt = $pdo->prepare("SELECT paynow_poll_url, booking_id FROM bookings WHERE paynow_reference = ?");
    $stmt->execute([$_GET['reference']]);
    $booking_data = $stmt->fetch();
    
    if ($booking_data) {
        $pollUrl = $booking_data['paynow_poll_url'];
        $booking_id = $booking_data['booking_id'];
        
        // Reconstruct booking from database
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $booking_row = $stmt->fetch();
        
        if ($booking_row) {
            $booking = [
                'booking_id' => $booking_row['booking_id'],
                'schedule_id' => $booking_row['schedule_id'],
                'seats' => explode(',', $booking_row['seats']),
                'total_fare' => $booking_row['total_amount']
            ];
        }
    }
}

if (!$pollUrl || !$booking) {
    $_SESSION['error'] = "No pending payment found.";
    header("Location: ../dashboard.php");
    exit;
}

$paynow = new Paynow($integrationId, $integrationKey, null, null);

try {
    $response = $paynow->pollTransaction($pollUrl);
    $status = $response->status();

    if ($response->paid()) {
        // Final availability check
        $conflicts = [];
        foreach ($booking['seats'] as $seat) {
            $check_stmt = $pdo->prepare("
                SELECT 1 FROM bookings 
                WHERE schedule_id = ? 
                AND (seat_number = ? OR FIND_IN_SET(?, seats)) 
                AND payment_status = 'confirmed'
                AND booking_id != ?
                LIMIT 1
            ");
            $check_stmt->execute([$booking['schedule_id'], $seat, $seat, $booking['booking_id']]);
            if ($check_stmt->fetch()) {
                $conflicts[] = $seat;
            }
        }

        if (!empty($conflicts)) {
            // Payment succeeded but seats taken - handle refund scenario
            $_SESSION['error'] = "Payment received but seats " . implode(', ', $conflicts) . " were taken. Contact support for refund.";
            
            // Mark booking as conflict for admin review
            $pdo->prepare("UPDATE bookings SET payment_status = 'conflict' WHERE booking_id = ?")
                ->execute([$booking['booking_id']]);
                
            header("Location: ../dashboard.php");
            exit;
        }

        // Process successful payment
        $pdo->beginTransaction();

        // Generate final ticket number
        $ticketNo = 'ZUPCO-' . date('Ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Update booking to confirmed
        $update_stmt = $pdo->prepare("
            UPDATE bookings 
            SET ticket_number = ?, payment_status = 'confirmed', payment_ref = ?, confirmed_at = NOW()
            WHERE booking_id = ?
        ");
        $update_stmt->execute([$ticketNo, $response->reference(), $booking['booking_id']]);

        // Update available seats
        $pdo->prepare("UPDATE schedules SET available_seats = available_seats - ? WHERE schedule_id = ?")
            ->execute([count($booking['seats']), $booking['schedule_id']]);

        $pdo->commit();

        // Prepare ticket data for display
        $_SESSION['ticket'] = [
            'ticket_number' => $ticketNo,
            'seats' => implode(', ', $booking['seats']),
            'total_fare' => $booking['total_fare'],
            'booking_id' => $booking['booking_id']
        ];

        // Cleanup session
        unset($_SESSION['booking_pending'], $_SESSION['paynow_poll_url'], $_SESSION['paynow_reference']);

        // Redirect to success page
        header("Location: ../ticket.php");
        exit;

    } else {
        // Payment not completed
        $status_message = "Payment status: " . htmlspecialchars($status);
        
        // Update booking status based on Paynow status
        if (in_array($status, ['cancelled', 'failed', 'expired'])) {
            $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE booking_id = ?")
                ->execute([$status, $booking['booking_id']]);
        }
        
        // Show status page with option to retry
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Payment Status</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 30px; text-align: center; }
                .status-pending { color: #856404; background: #fff3cd; padding: 20px; border-radius: 5px; }
                .status-failed { color: #721c24; background: #f8d7da; padding: 20px; border-radius: 5px; }
                .btn { display: inline-block; padding: 10px 20px; margin: 10px; text-decoration: none; border-radius: 5px; }
                .btn-retry { background: #007bff; color: white; }
                .btn-dashboard { background: #6c757d; color: white; }
            </style>
        </head>
        <body>
            <h2>Payment Status</h2>
            <div class="<?= in_array($status, ['cancelled', 'failed', 'expired']) ? 'status-failed' : 'status-pending' ?>">
                <p><strong><?= $status_message ?></strong></p>
                <?php if ($response->instructions()): ?>
                    <p><?= htmlspecialchars($response->instructions()) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if (in_array($status, ['sent', 'created', 'awaiting delivery'])): ?>
                    <p>Your payment is being processed. This page will refresh automatically.</p>
                    <script>
                        setTimeout(() => window.location.reload(), 5000);
                    </script>
                <?php endif; ?>
                
                <a href="check-payment.php?reference=<?= urlencode($_SESSION['paynow_reference'] ?? '') ?>" class="btn btn-retry">Check Payment Status</a>
                <a href="../dashboard.php" class="btn btn-dashboard">Back to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Payment verification error: ' . $e->getMessage());
    $_SESSION['error'] = "Payment verification failed. Please contact support.";
    header("Location: ../dashboard.php");
    exit;
}
?>