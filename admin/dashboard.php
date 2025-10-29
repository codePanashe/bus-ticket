<!-- admin/dashboard.php -->
<?php
require 'auth.php';
require '../includes/db.php';

// Summary stats
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'confirmed'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(r.fare) FROM bookings b JOIN schedules s ON b.schedule_id = s.schedule_id JOIN buses bu ON s.bus_id = bu.bus_id JOIN routes r ON bu.route_id = r.route_id WHERE b.payment_status = 'confirmed'")->fetchColumn() ?: 0;
$total_buses = $pdo->query("SELECT COUNT(*) FROM buses")->fetchColumn();
$total_routes = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();

// Recent bookings
$recent_bookings = $pdo->query("
    SELECT b.ticket_number, p.full_name, r.departure, r.destination, b.seat_number, bu.fleet_number
    FROM bookings b
    JOIN passengers p ON b.passenger_id = p.id
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN buses bu ON s.bus_id = bu.bus_id
    JOIN routes r ON bu.route_id = r.route_id
    WHERE b.payment_status = 'confirmed'
    ORDER BY b.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ZUPCO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f6f9; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); text-align: center; min-width: 200px; }
        .stat-card h3 { margin: 0; color: #1a5f23; font-size: 24px; }
        .stat-card p { margin: 5px 0; color: #666; }
        .quick-links { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .quick-links h3 { margin-top: 0; color: #1a5f23; }
        .links { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 15px; background: #1a5f23; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #134a1a; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e8f5e9; color: #1a5f23; }
        tr:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ZUPCO Admin Dashboard</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="container">
        <!-- Summary Stats -->
        <div class="stats">
            <div class="stat-card">
                <h3><?= $total_bookings ?></h3>
                <p>Confirmed Bookings</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($total_revenue, 2) ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_buses ?></h3>
                <p>Buses in Fleet</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_routes ?></h3>
                <p>Active Routes</p>
            </div>
        </div>

        <!-- Quick Management Links -->
        <div class="quick-links">
            <h3>Management</h3>
            <div class="links">
                <a href="manage_routes.php" class="btn">Manage Routes</a>
                <a href="manage_fleet.php" class="btn">Manage Fleet</a>
                <a href="manage_schedules.php" class="btn">Manage Schedules</a>
                <a href="manifests.php" class="btn">Generate Manifests</a>
                <a href="reports.php" class="btn">View Reports</a>
            </div>
        </div>

        <!-- Recent Bookings -->
        <h3>Recent Bookings</h3>
        <?php if ($recent_bookings): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Passenger</th>
                        <th>Route</th>
                        <th>Bus</th>
                        <th>Seat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['ticket_number']) ?></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['departure']) ?> → <?= htmlspecialchars($b['destination']) ?></td>
                            <td><?= htmlspecialchars($b['fleet_number']) ?></td>
                            <td><?= $b['seat_number'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No bookings yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>