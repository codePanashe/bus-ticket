<!-- select_seat.php (Fixed for Multi-Seat) -->
<?php
require 'includes/auth.php';
require 'includes/db.php';

$schedule_id = $_GET['schedule_id'] ?? null;
$fare = $_GET['fare'] ?? 0;

if (!$schedule_id || !is_numeric($fare)) {
    die("Invalid booking parameters.");
}

$stmt = $pdo->prepare("
    SELECT b.total_seats, b.fleet_number, r.departure, r.destination
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON b.route_id = r.route_id
    WHERE s.schedule_id = ?
");
$stmt->execute([$schedule_id]);
$bus_info = $stmt->fetch();

if (!$bus_info) die("Schedule not found.");

$total_seats = $bus_info['total_seats'];
$fleet_number = $bus_info['fleet_number'];
$departure = $bus_info['departure'];
$destination = $bus_info['destination'];

$booked_stmt = $pdo->prepare("SELECT seat_number FROM bookings WHERE schedule_id = ? AND payment_status = 'confirmed'");
$booked_stmt->execute([$schedule_id]);
$booked_seats = array_column($booked_stmt->fetchAll(), 'seat_number');
$booked_set = array_flip($booked_seats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?= htmlspecialchars($departure) ?> to <?= htmlspecialchars($destination) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .header { background: #1a5f23; color: white; padding: 15px; text-align: center; }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #1a5f23; text-align: center; margin-bottom: 20px; }
        .bus-layout {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .seat {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5e9;
            border: 1px solid #1a5f23;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }
        .seat.booked { background: #ffcdd2; color: #c62828; cursor: not-allowed; }
        .seat.selected { background: #a5d6a7; }
        .seat:hover:not(.booked) { background: #c8e6c9; }
        .legend {
            display: flex; justify-content: center; gap: 20px; margin: 15px 0;
            font-size: 14px;
        }
        .legend div { display: flex; align-items: center; gap: 5px; }
        .color-box { width: 15px; height: 15px; border-radius: 3px; }
        .available { background: #e8f5e9; border: 1px solid #1a5f23; }
        .booked-box { background: #ffcdd2; }
        .total-fare { font-size: 20px; font-weight: bold; color: #1a5f23; margin: 15px 0; text-align: center; }
        .max-seats { color: #666; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .btn-confirm {
            display: block; width: 200px; margin: 20px auto; padding: 12px;
            background: #1a5f23; color: white; border: none; border-radius: 6px;
            font-size: 16px; cursor: pointer;
        }
        .btn-confirm:hover { background: #134a1a; }
        .back { display: block; text-align: center; margin-top: 10px; color: #1a5f23; text-decoration: none; }
        .error { color: red; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Bus: <?= htmlspecialchars($fleet_number) ?></h2>
        <p><?= htmlspecialchars($departure) ?> → <?= htmlspecialchars($destination) ?> | Fare per seat: $<?= number_format($fare, 2) ?></p>
    </div>

    <div class="container">
        <h2>Select Seats (Max 6)</h2>
        <p class="max-seats">Click to select multiple seats. Selected: <span id="seatCount">0</span></p>
        <div class="total-fare">Total: $<span id="totalFare">0.00</span></div>

        <div class="legend">
            <div><span class="color-box available"></span> Available</div>
            <div><span class="color-box booked-box"></span> Booked</div>
            <div><span class="color-box" style="background:#a5d6a7;"></span> Selected</div>
        </div>

        <form method="POST" action="payment.php">
            <div class="bus-layout" id="seatMap">
                <?php for ($i = 1; $i <= $total_seats; $i++): ?>
                    <div class="seat <?= isset($booked_set[$i]) ? 'booked' : '' ?>" 
                         data-seat="<?= $i ?>" 
                         onclick="toggleSeat(<?= $i ?>, <?= isset($booked_set[$i]) ? 'true' : 'false' ?>)">
                        <?= $i ?>
                    </div>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
            <input type="hidden" name="fare" value="<?= $fare ?>">
            <input type="hidden" name="seats" id="selectedSeatsInput">
            <button type="submit" class="btn-confirm" id="confirmBtn" disabled>Select Seats</button>
        </form>

        <a href="book.php?route_id=<?= $_GET['route_id'] ?? '' ?>" class="back">← Back to Schedules</a>
    </div>

    <script>
        let selectedSeats = [];

        function toggleSeat(seatNum, isBooked) {
            if (isBooked) return;
            const index = selectedSeats.indexOf(seatNum);

            if (index > -1) {
                selectedSeats.splice(index, 1);
                document.querySelector(`.seat[data-seat="${seatNum}"]`).classList.remove('selected');
            } else if (selectedSeats.length < 6) {
                selectedSeats.push(seatNum);
                document.querySelector(`.seat[data-seat="${seatNum}"]`).classList.add('selected');
            }

            document.getElementById('seatCount').textContent = selectedSeats.length;
            const total = selectedSeats.length * <?= $fare ?>;
            document.getElementById('totalFare').textContent = total.toFixed(2);
            document.getElementById('selectedSeatsInput').value = selectedSeats.join(',');
            document.getElementById('confirmBtn').disabled = selectedSeats.length === 0;
        }
    </script>
</body>
</html>