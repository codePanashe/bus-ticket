<!-- admin/reports.php -->
<?php
require 'auth.php';
require '../includes/db.php';

// Helper: Format currency
function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

// TODAY
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// THIS WEEK (Monday to Sunday)
$week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
$week_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));

// TOTAL REVENUE & BOOKINGS
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'confirmed'")->fetchColumn();
$total_revenue = $pdo->query("
    SELECT SUM(r.fare) 
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.schedule_id 
    JOIN buses bu ON s.bus_id = bu.bus_id 
    JOIN routes r ON bu.route_id = r.route_id 
    WHERE b.payment_status = 'confirmed'
")->fetchColumn() ?: 0;

// TODAY
$today_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE payment_status = 'confirmed' AND created_at BETWEEN ? AND ?");
$today_bookings->execute([$today_start, $today_end]);
$today_count = $today_bookings->fetchColumn();

$today_revenue = $pdo->prepare("
    SELECT SUM(r.fare) 
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.schedule_id 
    JOIN buses bu ON s.bus_id = bu.bus_id 
    JOIN routes r ON bu.route_id = r.route_id 
    WHERE b.payment_status = 'confirmed' AND b.created_at BETWEEN ? AND ?
");
$today_revenue->execute([$today_start, $today_end]);
$today_rev = $today_revenue->fetchColumn() ?: 0;

// THIS WEEK
$week_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE payment_status = 'confirmed' AND created_at BETWEEN ? AND ?");
$week_bookings->execute([$week_start, $week_end]);
$week_count = $week_bookings->fetchColumn();

$week_revenue = $pdo->prepare("
    SELECT SUM(r.fare) 
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.schedule_id 
    JOIN buses bu ON s.bus_id = bu.bus_id 
    JOIN routes r ON bu.route_id = r.route_id 
    WHERE b.payment_status = 'confirmed' AND b.created_at BETWEEN ? AND ?
");
$week_revenue->execute([$week_start, $week_end]);
$week_rev = $week_revenue->fetchColumn() ?: 0;

// TOP ROUTES
$top_routes = $pdo->query("
    SELECT r.departure, r.destination, COUNT(b.booking_id) as bookings, SUM(r.fare) as revenue
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN buses bu ON s.bus_id = bu.bus_id
    JOIN routes r ON bu.route_id = r.route_id
    WHERE b.payment_status = 'confirmed'
    GROUP BY r.route_id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

// OCCUPANCY REPORT (last 7 days)
$occupancy = $pdo->query("
    SELECT 
        b.fleet_number,
        r.departure,
        r.destination,
        s.departure_time,
        b.total_seats,
        COUNT(bk.booking_id) as booked_seats,
        ROUND((COUNT(bk.booking_id) / b.total_seats) * 100, 1) as occupancy_rate
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON b.route_id = r.route_id
    JOIN bookings bk ON s.schedule_id = bk.schedule_id AND bk.payment_status = 'confirmed'
    WHERE s.departure_time BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
    GROUP BY s.schedule_id
    ORDER BY occupancy_rate DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - ZUPCO Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .summary-cards { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); min-width: 200px; text-align: center; }
        .card h3 { margin: 0 0 10px; color: #1a5f23; }
        .card .value { font-size: 24px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e8f5e9; color: #1a5f23; }
        .back { display: inline-block; margin-top: 10px; color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reports & Analytics</h1>
        <a href="dashboard.php" class="logout">← Dashboard</a>
    </div>

    <div class="container">
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="card">
                <h3>Total Revenue</h3>
                <div class="value"><?= formatMoney($total_revenue) ?></div>
            </div>
            <div class="card">
                <h3>Total Bookings</h3>
                <div class="value"><?= $total_bookings ?></div>
            </div>
            <div class="card">
                <h3>Today’s Revenue</h3>
                <div class="value"><?= formatMoney($today_rev) ?></div>
            </div>
            <div class="card">
                <h3>This Week</h3>
                <div class="value"><?= $week_count ?> bookings</div>
                <div><?= formatMoney($week_rev) ?></div>
            </div>
        </div>

        <!-- Top Routes -->
        <h2>Top Performing Routes</h2>
        <?php if ($top_routes): ?>
            <table>
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Bookings</th>
                        <th>Revenue</th>
                        <th>Est. Profit*</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_routes as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['departure']) ?> → <?= htmlspecialchars($r['destination']) ?></td>
                            <td><?= $r['bookings'] ?></td>
                            <td><?= formatMoney($r['revenue']) ?></td>
                            <td><?= formatMoney($r['revenue'] * 0.7) ?></td> <!-- 30% cost assumption -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><em>*Profit estimated at 70% of revenue (30% operational cost)</em></p>
        <?php else: ?>
            <p>No booking data available.</p>
        <?php endif; ?>

        <!-- Occupancy Report -->
        <h2>Recent Occupancy Rates (Last 7 Days)</h2>
        <?php if ($occupancy): ?>
            <table>
                <thead>
                    <tr>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Booked / Total</th>
                        <th>Occupancy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($occupancy as $o): ?>
                        <tr>
                            <td><?= htmlspecialchars($o['fleet_number']) ?></td>
                            <td><?= htmlspecialchars($o['departure']) ?> → <?= htmlspecialchars($o['destination']) ?></td>
                            <td><?= date('M j, g:i A', strtotime($o['departure_time'])) ?></td>
                            <td><?= $o['booked_seats'] ?> / <?= $o['total_seats'] ?></td>
                            <td>
                                <strong><?= $o['occupancy_rate'] ?>%</strong>
                                <?php if ($o['occupancy_rate'] > 90): ?>
                                    <span style="color:green;">✓ High</span>
                                <?php elseif ($o['occupancy_rate'] < 50): ?>
                                    <span style="color:orange;">⚠️ Low</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent trips found.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>
</body>
</html>