<?php
// payment-instructions.php
session_start();
if (!isset($_SESSION['payment_instructions']) || !isset($_SESSION['booking_pending'])) {
    header("Location: payment.php");
    exit;
}

$instructions = $_SESSION['payment_instructions'];
$booking = $_SESSION['booking_pending'];
$reference = $_SESSION['paynow_reference'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Instructions - ZUPCO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a5f23; color: #fff; padding: 12px; text-align: center; border-radius: 8px 8px 0 0; }
        .instructions { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1a5f23; }
        .reference { background: #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; }
        .btn-primary { background: #1a5f23; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>EcoCash Payment Instructions</h2>
        </div>
        
        <div class="instructions">
            <h3>Check Your Phone</h3>
            <p><?= nl2br(htmlspecialchars($instructions)) ?></p>
            
            <p><strong>Reference Number:</strong></p>
            <div class="reference"><?= htmlspecialchars($reference) ?></div>
            
            <p><strong>Amount:</strong> $<?= number_format($booking['total_fare'], 2) ?></p>
        </div>

        <p>Once you complete the payment on your phone, your booking will be confirmed automatically.</p>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="check-payment.php?reference=<?= urlencode($reference) ?>" class="btn btn-primary">
                Check Payment Status
            </a>
            <a href="../dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
        
        <script>
            // Auto-check payment status every 10 seconds
            setTimeout(() => {
                window.location.href = 'check-payment.php?reference=<?= urlencode($reference) ?>';
            }, 10000);
        </script>
    </div>
</body>
</html>