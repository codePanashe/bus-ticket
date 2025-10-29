<!-- admin/manage_routes.php -->
<?php
require 'auth.php';
require '../includes/db.php';

$message = '';
$error = '';

// Handle Add Route
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_route'])) {
    $departure = trim($_POST['departure']);
    $destination = trim($_POST['destination']);
    $fare = floatval($_POST['fare']);

    if (empty($departure) || empty($destination) || $fare <= 0) {
        $error = "All fields are required and fare must be positive.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO routes (departure, destination, fare) VALUES (?, ?, ?)");
            $stmt->execute([$departure, $destination, $fare]);
            $message = "Route added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding route: " . $e->getMessage();
        }
    }
}

// Handle Delete Route
if (isset($_GET['delete'])) {
    $route_id = (int)$_GET['delete'];
    try {
        // Check if route is in use
        $check = $pdo->prepare("SELECT COUNT(*) FROM buses WHERE route_id = ?");
        $check->execute([$route_id]);
        if ($check->fetchColumn() > 0) {
            $error = "Cannot delete route: it is assigned to one or more buses.";
        } else {
            $pdo->prepare("DELETE FROM routes WHERE route_id = ?")->execute([$route_id]);
            $message = "Route deleted.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting route.";
    }
}

// Handle Edit Route (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_route'])) {
    $route_id = (int)$_POST['route_id'];
    $departure = trim($_POST['departure']);
    $destination = trim($_POST['destination']);
    $fare = floatval($_POST['fare']);

    if (empty($departure) || empty($destination) || $fare <= 0) {
        $error = "All fields are required and fare must be positive.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE routes SET departure = ?, destination = ?, fare = ? WHERE route_id = ?");
            $stmt->execute([$departure, $destination, $fare, $route_id]);
            $message = "Route updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating route.";
        }
    }
}

// Fetch all routes
$routes = $pdo->query("SELECT * FROM routes ORDER BY departure, destination")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - ZUPCO Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .logout { color: white; text-decoration: none; background: #d32f2f; padding: 6px 12px; border-radius: 4px; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
        .form-section, .table-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #1a5f23; margin-top: 0; }
        form { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
        input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
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
        <h1>Manage Routes</h1>
        <a href="dashboard.php" class="logout">← Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Route Form -->
        <div class="form-section">
            <h2>Add New Route</h2>
            <form method="POST">
                <input type="text" name="departure" placeholder="Departure (e.g., Harare)" required>
                <input type="text" name="destination" placeholder="Destination (e.g., Bulawayo)" required>
                <input type="number" name="fare" step="0.01" min="0.01" placeholder="Fare (USD)" required>
                <button type="submit" name="add_route">Add Route</button>
            </form>
        </div>

        <!-- Routes Table -->
        <div class="table-section">
            <h2>Existing Routes</h2>
            <?php if ($routes): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Departure</th>
                            <th>Destination</th>
                            <th>Fare (USD)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td><?= $r['route_id'] ?></td>
                                <td><?= htmlspecialchars($r['departure']) ?></td>
                                <td><?= htmlspecialchars($r['destination']) ?></td>
                                <td>$<?= number_format($r['fare'], 2) ?></td>
                                <td class="actions">
                                    <a href="#" class="edit" onclick="editRoute(<?= $r['route_id'] ?>, '<?= addslashes($r['departure']) ?>', '<?= addslashes($r['destination']) ?>', <?= $r['fare'] ?>)">Edit</a>
                                    <a href="?delete=<?= $r['route_id'] ?>" class="delete" onclick="return confirm('Are you sure? This cannot be undone.')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No routes found.</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>

    <!-- Hidden Edit Form (revealed via JS) -->
    <div id="editForm" style="display:none; margin-top:20px; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
        <h3>Edit Route</h3>
        <form method="POST" id="editRouteForm">
            <input type="hidden" name="route_id" id="edit_route_id">
            <input type="text" id="edit_departure" name="departure" placeholder="Departure" required>
            <input type="text" id="edit_destination" name="destination" placeholder="Destination" required>
            <input type="number" id="edit_fare" name="fare" step="0.01" min="0.01" placeholder="Fare" required>
            <button type="submit" name="edit_route">Update Route</button>
            <button type="button" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
        </form>
    </div>

    <script>
        function editRoute(id, dep, dest, fare) {
            document.getElementById('edit_route_id').value = id;
            document.getElementById('edit_departure').value = dep;
            document.getElementById('edit_destination').value = dest;
            document.getElementById('edit_fare').value = fare;
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo(0, document.body.scrollHeight);
        }
    </script>
</body>
</html>