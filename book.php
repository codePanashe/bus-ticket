<!-- book.php -->
<?php
require 'includes/auth.php';
require 'includes/db.php';

$route_id = $_GET['route_id'] ?? null;
if (!$route_id) {
    die("Route not specified.");
}

// Get route info
$route = $pdo->prepare("SELECT * FROM routes WHERE route_id = ?");
$route->execute([$route_id]);
$route_data = $route->fetch();
if (!$route_data) {
    die("Invalid route.");
}

// Get available schedules for this route
$stmt = $pdo->prepare("
    SELECT s.schedule_id, s.departure_time, s.available_seats, b.fleet_number
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    WHERE b.route_id = ? AND s.departure_time > NOW()
    ORDER BY s.departure_time ASC
");
$stmt->execute([$route_id]);
$schedules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Trip - <?= htmlspecialchars($route_data['departure']) ?> to <?= htmlspecialchars($route_data['destination']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; }
        .container { max-width: 800px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #1a5f23; }
        .schedule-item {
            padding: 15px;
            border: 1px solid #ddd;
            margin: 10px 0;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-select {
            background: #1a5f23;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-select:hover { background: #134a1a; }
        .back { margin-top: 20px; display: inline-block; color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Book Your Trip</h2>
    </div>

    <div class="container">
        <h3><?= htmlspecialchars($route_data['departure']) ?> → <?= htmlspecialchars($route_data['destination']) ?></h3>
        <p><strong>Fare:</strong> $<?= number_format($route_data['fare'], 2) ?></p>

        <h4>Select Departure Time</h4>
        <?php if (empty($schedules)): ?>
            <p>No upcoming departures available for this route.</p>
        <?php else: ?>
            <?php foreach ($schedules as $s): ?>
                <div class="schedule-item">
                    <div>
                        <strong><?= date('D, M j, Y \a\t g:i A', strtotime($s['departure_time'])) ?></strong><br>
                        Bus: <?= htmlspecialchars($s['fleet_number']) ?> | Seats: <?= $s['available_seats'] ?>
                    </div>
                    <?php if ($s['available_seats'] > 0): ?>
                        <a href="select_seat.php?schedule_id=<?= $s['schedule_id'] ?>&fare=<?= $route_data['fare'] ?>" class="btn-select">Select Seat</a>
                    <?php else: ?>
                        <span style="color: red;">Fully Booked</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="dashboard.php" class="back">← Back to Routes</a>
    </div>
</body>
</html>