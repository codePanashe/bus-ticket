<!-- admin/manage_schedules.php -->
<?php
require 'auth.php';
require '../includes/db.php';

$message = '';
$error = '';

// Fetch buses that are assigned to routes (for dropdown)
$buses = $pdo->query("
    SELECT b.bus_id, b.fleet_number, r.departure, r.destination
    FROM buses b
    JOIN routes r ON b.route_id = r.route_id
    ORDER BY b.fleet_number
")->fetchAll();

// Handle Add Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $bus_id = (int)$_POST['bus_id'];
    $departure_time = $_POST['departure_time']; // Format: YYYY-MM-DDTHH:MM
    $total_seats = (int)$_POST['total_seats'];

    if (!$bus_id || !$departure_time || $total_seats <= 0) {
        $error = "All fields are required.";
    } else {
        try {
            $mysql_time = str_replace('T', ' ', $departure_time) . ':00';
            $stmt = $pdo->prepare("INSERT INTO schedules (bus_id, departure_time, available_seats) VALUES (?, ?, ?)");
            $stmt->execute([$bus_id, $mysql_time, $total_seats]);
            $message = "Schedule added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding schedule: " . $e->getMessage();
        }
    }
}

// Handle Delete Schedule (manual - allow ANY schedule deletion)
if (isset($_GET['delete'])) {
    $schedule_id = (int)$_GET['delete'];
    try {
        // Delete schedule regardless of bookings or expiration
        $pdo->prepare("DELETE FROM schedules WHERE schedule_id = ?")->execute([$schedule_id]);
        $message = "Schedule deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting schedule: " . $e->getMessage();
    }
}

// Handle Edit Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $schedule_id = (int)$_POST['schedule_id'];
    $bus_id = (int)$_POST['bus_id'];
    $departure_time = $_POST['departure_time'];
    $available_seats = (int)$_POST['available_seats'];

    if (!$bus_id || !$departure_time || $available_seats < 0) {
        $error = "All fields are required.";
    } else {
        try {
            $mysql_time = str_replace('T', ' ', $departure_time) . ':00';
            $stmt = $pdo->prepare("UPDATE schedules SET bus_id = ?, departure_time = ?, available_seats = ? WHERE schedule_id = ?");
            $stmt->execute([$bus_id, $mysql_time, $available_seats, $schedule_id]);
            $message = "Schedule updated!";
        } catch (PDOException $e) {
            $error = "Error updating schedule.";
        }
    }
}

// ✅ Fetch ALL schedules (including expired) for admin management
$schedules = $pdo->query("
    SELECT s.schedule_id, s.departure_time, s.available_seats,
           b.bus_id, b.fleet_number,
           r.departure, r.destination
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON b.route_id = r.route_id
    ORDER BY s.departure_time DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - ZUPCO Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .form-section, .table-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #1a5f23; margin-top: 0; }
        form { display: flex; gap: 10px; flex-wrap: wrap; align-items:end; }
        input, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 8px 15px; background: #1a5f23; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #134a1a; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e8f5e9; color: #1a5f23; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
        .edit { background: #fff3e0; color: #e65100; }
        .delete { background: #ffcdd2; color: #c62828; }
        .expired { background: #ffebee; color: #c62828; font-weight: bold; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #e8f5e9; color: #1a5f23; }
        .error-msg { background: #ffcdd2; color: #c62828; }
        .back { display: inline-block; margin-top: 10px; color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manage Bus Schedules</h1>
        <a href="dashboard.php" class="logout">← Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Schedule Form -->
        <div class="form-section">
            <h2>Add New Schedule</h2>
            <form method="POST">
                <select name="bus_id" required>
                    <option value="">-- Select Bus --</option>
                    <?php foreach ($buses as $b): ?>
                        <option value="<?= $b['bus_id'] ?>">
                            <?= htmlspecialchars($b['fleet_number']) ?> (<?= htmlspecialchars($b['departure']) ?> → <?= htmlspecialchars($b['destination']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="datetime-local" name="departure_time" required>
                <input type="number" name="total_seats" min="1" placeholder="Available Seats" required>
                <button type="submit" name="add_schedule">Add Schedule</button>
            </form>
        </div>

        <!-- Schedules Table -->
        <div class="table-section">
            <h2>All Schedules (Including Expired)</h2>
            <?php if ($schedules): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure Time</th>
                            <th>Available Seats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= $s['schedule_id'] ?></td>
                                <td><?= htmlspecialchars($s['fleet_number']) ?></td>
                                <td><?= htmlspecialchars($s['departure']) ?> → <?= htmlspecialchars($s['destination']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($s['departure_time'])) ?></td>
                                <td><?= $s['available_seats'] ?></td>
                                <td>
                                    <?php if (strtotime($s['departure_time']) < time()): ?>
                                        <span class="expired">EXPIRED</span>
                                    <?php else: ?>
                                        Active
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="#" class="edit" onclick="editSchedule(
                                        <?= $s['schedule_id'] ?>,
                                        <?= $s['bus_id'] ?>,
                                        '<?= date('Y-m-d\TH:i', strtotime($s['departure_time'])) ?>',
                                        <?= $s['available_seats'] ?>
                                    )">Edit</a>
                                    <a href="?delete=<?= $s['schedule_id'] ?>" class="delete" onclick="return confirm('Delete this schedule? This cannot be undone.')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No schedules found.</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>

    <!-- Hidden Edit Form -->
    <div id="editForm" style="display:none; margin-top:20px; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
        <h3>Edit Schedule</h3>
        <form method="POST" id="editScheduleForm">
            <input type="hidden" name="schedule_id" id="edit_schedule_id">
            <select id="edit_bus" name="bus_id" required>
                <option value="">-- Select Bus --</option>
                <?php foreach ($buses as $b): ?>
                    <option value="<?= $b['bus_id'] ?>">
                        <?= htmlspecialchars($b['fleet_number']) ?> (<?= htmlspecialchars($b['departure']) ?> → <?= htmlspecialchars($b['destination']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="datetime-local" id="edit_time" name="departure_time" required>
            <input type="number" id="edit_seats" name="available_seats" min="0" placeholder="Available Seats" required>
            <button type="submit" name="edit_schedule">Update Schedule</button>
            <button type="button" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
        </form>
    </div>

    <script>
        function editSchedule(id, busId, time, seats) {
            document.getElementById('edit_schedule_id').value = id;
            document.getElementById('edit_bus').value = busId;
            document.getElementById('edit_time').value = time;
            document.getElementById('edit_seats').value = seats;
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo(0, document.body.scrollHeight);
        }
    </script>
</body>
</html>