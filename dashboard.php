<!-- dashboard.php -->
<?php
require 'includes/auth.php';
require 'includes/db.php';

// Fetch available routes
$stmt = $pdo->query("SELECT route_id, departure, destination, fare FROM routes ORDER BY departure");
$routes = $stmt->fetchAll();
?>

<?php if (!empty($_SESSION['error'])): ?>
    <div style="color:red; text-align:center; margin:10px;"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['success'])): ?>
    <div style="color:green; text-align:center; margin:10px;"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ZUPCO</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .header {
            background: #1a5f23;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: #d32f2f;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .logout:hover {
            background: #b71c1c;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        h2 {
            color: #1a5f23;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 500;
        }

        .route-card {
            background: white;
            margin: 15px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .route-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .route-info {
            flex: 1;
        }

        .route-info h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .route-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }

        .fare {
            font-size: 20px;
            font-weight: bold;
            color: #1a5f23;
            margin-right: 15px;
        }

        .btn-book {
            background: #1a5f23;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-book:hover {
            background: #134a1a;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 20px auto;
            }

            .route-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .route-info {
                margin-bottom: 10px;
            }

            .fare {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .btn-book {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to ZUPCO Pre-Booking</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="container">
        <h2>Available Routes</h2>
        <?php if (empty($routes)): ?>
            <p>No routes available at the moment.</p>
        <?php else: ?>
            <?php foreach ($routes as $route): ?>
                <div class="route-card">
                    <div class="route-info">
                        <h3><?= htmlspecialchars($route['departure']) ?> → <?= htmlspecialchars($route['destination']) ?></h3>
                        <p>Route ID: <?= $route['route_id'] ?></p>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <div class="fare">$<?= number_format($route['fare'], 2) ?></div>
                        <a href="book.php?route_id=<?= $route['route_id'] ?>" class="btn-book">Book Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>