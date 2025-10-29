<?php
// send_reminders.php - Run via cron job (e.g., hourly)
require 'includes/db.php';

// Function to simulate sending SMS/email
function sendNotification($email, $phone, $message) {
    // In production: use Twilio (SMS) + PHPMailer/SendGrid (email)
    error_log("EMAIL to $email: $message");
    error_log("SMS to $phone: $message");
    return true; // Simulate success
}

try {
    // Get all confirmed bookings with passenger & schedule info
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_id, 
            p.email, 
            p.phone, 
            p.full_name,
            s.departure_time,
            r.departure AS pickup_point,
            b.seat_number,
            bu.fleet_number,
            b.ticket_number
        FROM bookings b
        JOIN passengers p ON b.passenger_id = p.id
        JOIN schedules s ON b.schedule_id = s.schedule_id
        JOIN buses bu ON s.bus_id = bu.bus_id
        JOIN routes r ON bu.route_id = r.route_id
        WHERE b.payment_status = 'confirmed'
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();

    foreach ($bookings as $booking) {
        $departure_time = new DateTime($booking['departure_time']);
        $now = new DateTime();
        $interval = $now->diff($departure_time);
        $hours_diff = ($departure_time->getTimestamp() - $now->getTimestamp()) / 3600;

        // Check if 24-hour reminder should be sent
        if ($hours_diff > 23 && $hours_diff <= 25) {
            // Check if already sent
            $log = $pdo->prepare("SELECT 1 FROM reminders_log WHERE booking_id = ? AND reminder_type = '24h'");
            $log->execute([$booking['booking_id']]);
            if (!$log->fetch()) {
                $msg = "🚌 ZUPCO Reminder (24h): Hi {$booking['full_name']}, your trip from {$booking['pickup_point']} departs tomorrow at " . $departure_time->format('g:i A') . ". Bus: {$booking['fleet_number']}, Seat: {$booking['seat_number']}. Ticket: {$booking['ticket_number']}";
                if (sendNotification($booking['email'], $booking['phone'], $msg)) {
                    $pdo->prepare("INSERT INTO reminders_log (booking_id, reminder_type) VALUES (?, '24h')")->execute([$booking['booking_id']]);
                    echo "24h reminder sent for booking {$booking['booking_id']}\n";
                }
            }
        }

        // Check if 2-hour reminder should be sent
        if ($hours_diff > 1.5 && $hours_diff <= 2.5) {
            $log = $pdo->prepare("SELECT 1 FROM reminders_log WHERE booking_id = ? AND reminder_type = '2h'");
            $log->execute([$booking['booking_id']]);
            if (!$log->fetch()) {
                $msg = "🚌 ZUPCO Final Reminder: Hi {$booking['full_name']}, your bus departs in 2 hours from {$booking['pickup_point']} at " . $departure_time->format('g:i A') . ". Bus: {$booking['fleet_number']}, Seat: {$booking['seat_number']}. Don't be late!";
                if (sendNotification($booking['email'], $booking['phone'], $msg)) {
                    $pdo->prepare("INSERT INTO reminders_log (booking_id, reminder_type) VALUES (?, '2h')")->execute([$booking['booking_id']]);
                    echo "2h reminder sent for booking {$booking['booking_id']}\n";
                }
            }
        }
    }

    echo "Reminder check completed at " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    error_log("Reminder error: " . $e->getMessage());
    exit(1);
}
