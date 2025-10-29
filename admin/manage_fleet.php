<!-- admin/manage_fleet.php -->
<?php
require 'auth.php';
require '../includes/db.php';

$message = '';
$error = '';

// Fetch all routes for dropdown
$routes = $pdo->query("SELECT route_id, departure, destination FROM routes ORDER BY departure")->fetchAll();

// Handle Add Bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bus'])) {
    $fleet_number = trim($_POST['fleet_number']);
    $total_seats = (int)$_POST['total_seats'];
    $route_id = (int)$_POST['route_id'];

    if (empty($fleet_number) || $total_seats <= 0 || !$route_id) {
        $error = "All fields are required and seats must be positive.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO buses (fleet_number, total_seats, route_id) VALUES (?, ?, ?)");
            $stmt->execute([$fleet_number, $total_seats, $route_id]);
            $message = "Bus added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Fleet number already exists.";
            } else {
                $error = "Error adding bus: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Bus
if (isset($_GET['delete'])) {
    $bus_id = (int)$_GET['delete'];
    try {
        // Check if bus has active schedules
        $check = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE bus_id = ? AND departure_time > NOW()");
        $check->execute([$bus_id]);
        if ($check->fetchColumn() > 0) {
            $error = "Cannot delete bus: it has upcoming trips.";
        } else {
            $pdo->prepare("DELETE FROM buses WHERE bus_id = ?")->execute([$bus_id]);
            $message = "Bus deleted.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting bus.";
    }
}

// Handle Edit Bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_bus'])) {
    $bus_id = (int)$_POST['bus_id'];
    $fleet_number = trim($_POST['fleet_number']);
    $total_seats = (int)$_POST['total_seats'];
    $route_id = (int)$_POST['route_id'];

    if (empty($fleet_number) || $total_seats <= 0 || !$route_id) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE buses SET fleet_number = ?, total_seats = ?, route_id = ? WHERE bus_id = ?");
            $stmt->execute([$fleet_number, $total_seats, $route_id, $bus_id]);
            $message = "Bus updated successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Fleet number already in use.";
            } else {
                $error = "Error updating bus.";
            }
        }
    }
}

// Fetch all buses with route info
$buses = $pdo->query("
    SELECT b.bus_id, b.fleet_number, b.total_seats, 
           r.route_id, r.departure, r.destination
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.route_id
    ORDER BY b.fleet_number
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fleet - ZUPCO Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1100px; margin: 20px auto; padding: 0 20px; }
        .form-section, .table-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #1a5f23; margin-top: 0; }
        form { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
        input, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 8px 15px; background: #1a5f23; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #134a1a; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e8f5e9; color: #1a5f23; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
        .edit { background: #fff3e0; color: #e65100; }
        .delete { background: #ffcdd2; color: #c62828; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #e8f5e9; color: #1a5f23; }
        .error-msg { background: #ffcdd2; color: #c62828; }
        .back { display: inline-block; margin-top: 10px; color: #1a5f23; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manage Bus Fleet</h1>
        <a href="dashboard.php" class="logout">← Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Bus Form -->
        <div class="form-section">
            <h2>Add New Bus</h2>
            <form method="POST">
                <input type="text" name="fleet_number" placeholder="Fleet Number (e.g., ZUP-1234)" required>
                <input type="number" name="total_seats" min="10" max="100" placeholder="Total Seats" required>
                <select name="route_id" required>
                    <option value="">-- Assign to Route --</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['route_id'] ?>">
                            <?= htmlspecialchars($r['departure']) ?> → <?= htmlspecialchars($r['destination']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_bus">Add Bus</button>
            </form>
        </div>

        <!-- Buses Table -->
        <div class="table-section">
            <h2>Current Fleet</h2>
            <?php if ($buses): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fleet Number</th>
                            <th>Seats</th>
                            <th>Assigned Route</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $b): ?>
                            <tr>
                                <td><?= $b['bus_id'] ?></td>
                                <td><?= htmlspecialchars($b['fleet_number']) ?></td>
                                <td><?= $b['total_seats'] ?></td>
                                <td>
                                    <?php if ($b['route_id']): ?>
                                        <?= htmlspecialchars($b['departure']) ?> → <?= htmlspecialchars($b['destination']) ?>
                                    <?php else: ?>
                                        <em>Not assigned</em>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="#" class="edit" onclick="editBus(
                                        <?= $b['bus_id'] ?>,
                                        '<?= addslashes($b['fleet_number']) ?>',
                                        <?= $b['total_seats'] ?>,
                                        <?= $b['route_id'] ?? '""' ?>
                                    )">Edit</a>
                                    <a href="?delete=<?= $b['bus_id'] ?>" class="delete" onclick="return confirm('Delete this bus?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No buses in fleet.</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>

    <!-- Hidden Edit Form -->
    <div id="editForm" style="display:none; margin-top:20px; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
        <h3>Edit Bus</h3>
        <form method="POST" id="editBusForm">
            <input type="hidden" name="bus_id" id="edit_bus_id">
            <input type="text" id="edit_fleet" name="fleet_number" placeholder="Fleet Number" required>
            <input type="number" id="edit_seats" name="total_seats" min="10" max="100" placeholder="Seats" required>
            <select id="edit_route" name="route_id" required>
                <option value="">-- Assign Route --</option>
                <?php foreach ($routes as $r): ?>
                    <option value="<?= $r['route_id'] ?>">
                        <?= htmlspecialchars($r['departure']) ?> → <?= htmlspecialchars($r['destination']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="edit_bus">Update Bus</button>
            <button type="button" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
        </form>
    </div>

    <script>
        function editBus(id, fleet, seats, routeId) {
            document.getElementById('edit_bus_id').value = id;
            document.getElementById('edit_fleet').value = fleet;
            document.getElementById('edit_seats').value = seats;
            document.getElementById('edit_route').value = routeId;
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo(0, document.body.scrollHeight);
        }
    </script>
</body>
</html>