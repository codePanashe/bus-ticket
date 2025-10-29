<!-- ticket.php -->
<?php
require 'includes/auth.php';

session_start();
if (!isset($_SESSION['ticket'])) {
    header("Location: dashboard.php");
    exit;
}
$ticket = $_SESSION['ticket'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Ticket - ZUPCO</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; margin: 0; padding: 20px; }
        .ticket-container { max-width: 600px; margin: 20px auto; background: white; border: 2px solid #1a5f23; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .ticket-header { background: #1a5f23; color: white; padding: 20px; text-align: center; }
        .ticket-body { padding: 25px; }
        .ticket-row { display: flex; justify-content: space-between; margin: 12px 0; font-size: 16px; }
        .label { font-weight: bold; color: #1a5f23; }
        .value { color: #333; }
        .ticket-number { text-align: center; margin: 25px 0; padding: 15px; background: #e8f5e9; border: 1px dashed #1a5f23; font-size: 20px; letter-spacing: 2px; font-weight: bold; color: #1a5f23; }
        .footer-note { text-align: center; font-size: 13px; color: #666; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
        .btn-actions { text-align: center; margin-top: 20px; }
        .btn { display: inline-block; margin: 0 10px; padding: 10px 20px; background: #1a5f23; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn:hover { background: #134a1a; }
        @media print {
            body { background: white; }
            .btn-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>Zimbabwe United Passenger Company (ZUPCO)</h1>
            <p>Official Digital Ticket</p>
        </div>
        <div class="ticket-body">
            <div class="ticket-row">
                <span class="label">Passenger:</span>
                <span class="value"><?= htmlspecialchars($ticket['name']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Route:</span>
                <span class="value"><?= htmlspecialchars($ticket['departure']) ?> → <?= htmlspecialchars($ticket['destination']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Bus Fleet:</span>
                <span class="value"><?= htmlspecialchars($ticket['fleet']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Seat(s):</span>
                <span class="value"><?= htmlspecialchars($ticket['seats']) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Total Fare:</span>
                <span class="value">$<?= number_format($ticket['total_fare'], 2) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Departure:</span>
                <span class="value"><?= date('D, j M Y \a\t g:i A', strtotime($ticket['date_time'])) ?></span>
            </div>
            <div class="ticket-row">
                <span class="label">Pickup Point:</span>
                <span class="value">Main Terminal, <?= htmlspecialchars($ticket['departure']) ?></span>
            </div>
            <div class="ticket-number">
                <?= htmlspecialchars($ticket['ticket_number']) ?>
            </div>
            <div class="footer-note">
                • Present this ticket at boarding for verification.<br>
                • Valid for all listed seats on the specified date and time.<br>
                • Not transferable.
            </div>
        </div>
    </div>

    <div class="btn-actions">
        <a href="javascript:window.print()" class="btn">Save / Print Ticket</a>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>