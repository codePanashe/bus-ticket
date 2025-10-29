<!-- admin/manifests.php -->
<?php
require 'auth.php';
require '../includes/db.php';

// Fetch upcoming schedules (next 7 days)
$schedules = $pdo->query("
    SELECT s.schedule_id, s.departure_time,
           b.fleet_number,
           r.departure, r.destination
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON b.route_id = r.route_id
    WHERE s.departure_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY s.departure_time ASC
")->fetchAll();

$manifest = null;
$selected_schedule = null;

// Generate manifest if schedule_id is provided
if (isset($_GET['schedule_id'])) {
    $schedule_id = (int)$_GET['schedule_id'];
    
    // Get schedule info
    $stmt = $pdo->prepare("
        SELECT s.departure_time, b.fleet_number, r.departure, r.destination
        FROM schedules s
        JOIN buses b ON s.bus_id = b.bus_id
        JOIN routes r ON b.route_id = r.route_id
        WHERE s.schedule_id = ?
    ");
    $stmt->execute([$schedule_id]);
    $selected_schedule = $stmt->fetch();

    if ($selected_schedule) {
        // Get all confirmed passengers for this schedule
        $passengers = $pdo->prepare("
            SELECT p.full_name, p.national_id, p.phone, 
                   b.seat_number, b.ticket_number
            FROM bookings b
            JOIN passengers p ON b.passenger_id = p.id
            WHERE b.schedule_id = ? AND b.payment_status = 'confirmed'
            ORDER BY b.seat_number ASC
        ");
        $passengers->execute([$schedule_id]);
        $manifest = $passengers->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Manifests - ZUPCO Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .form-section, .manifest-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #1a5f23; margin-top: 0; }
        select, button { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #1a5f23; color: white; cursor: pointer; }
        button:hover { background: #134a1a; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e8f5e9; color: #1a5f23; }
        .back { display: inline-block; margin-top: 15px; color: #1a5f23; text-decoration: none; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .manifest-section { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Passenger Manifests</h1>
        <a href="dashboard.php" class="logout no-print">← Dashboard</a>
    </div>

    <div class="container">
        <div class="form-section no-print">
            <h2>Generate Manifest</h2>
            <form method="GET">
                <select name="schedule_id" required>
                    <option value="">-- Select Upcoming Trip --</option>
                    <?php foreach ($schedules as $s): ?>
                        <option value="<?= $s['schedule_id'] ?>" <?= (isset($_GET['schedule_id']) && $_GET['schedule_id'] == $s['schedule_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['fleet_number']) ?> | 
                            <?= htmlspecialchars($s['departure']) ?> → <?= htmlspecialchars($s['destination']) ?> | 
                            <?= date('D, j M Y \a\t g:i A', strtotime($s['departure_time'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Generate Manifest</button>
            </form>
        </div>

        <?php if ($manifest !== null && $selected_schedule): ?>
            <div class="manifest-section">
                <div class="no-print">
                    <h2>Manifest for <?= htmlspecialchars($selected_schedule['fleet_number']) ?></h2>
                    <p><strong>Route:</strong> <?= htmlspecialchars($selected_schedule['departure']) ?> → <?= htmlspecialchars($selected_schedule['destination']) ?><br>
                    <strong>Departure:</strong> <?= date('l, F j, Y \a\t g:i A', strtotime($selected_schedule['departure_time'])) ?></p>
                    <button onclick="window.print()" class="no-print">🖨️ Print / Save as PDF</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Seat</th>
                            <th>Passenger Name</th>
                            <th>National ID</th>
                            <th>Phone</th>
                            <th>Ticket #</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($manifest): ?>
                            <?php foreach ($manifest as $p): ?>
                                <tr>
                                    <td><?= $p['seat_number'] ?></td>
                                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td><?= htmlspecialchars($p['national_id']) ?></td>
                                    <td><?= htmlspecialchars($p['phone']) ?></td>
                                    <td><?= htmlspecialchars($p['ticket_number']) ?></td>
                                    <td>Confirmed</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#666;">No passengers booked for this trip.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="no-print" style="margin-top:20px;">
                    <a href="manifests.php" class="back">← Choose Another Trip</a>
                </div>
            </div>
        <?php elseif (isset($_GET['schedule_id'])): ?>
            <p style="color:red;">Trip not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>