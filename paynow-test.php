<?php
require_once __DIR__ . '/vendor/autoload.php';
use Paynow\Payments\Paynow;

$integrationId  = '22367';
$integrationKey = 'eeb9c2e0-b1d6-4e4c-8e51-64aedda8ab59';

$returnUrl = 'http://localhost/bus-prebooking/payment/payment-return.php';
$resultUrl = 'http://localhost/bus-prebooking/payment/payment-result.php';

$paynow = new Paynow($integrationId, $integrationKey, $returnUrl, $resultUrl);

$bookingId = $booking['schedule_id']; // or your actual booking identifier
$payment = $paynow->createPayment("Booking #$bookingId", "mubangopanashe20@gmail.com");

$payment->add('Bus Ticket', 1.00);

$response = $paynow->send($payment);

if ($response->success()) {
    echo "<p>✅ Redirect user to: <a href='".$response->redirectUrl()."'>".$response->redirectUrl()."</a></p>";
    echo "<p>Poll URL: ".$response->pollUrl()."</p>";
} else {
    echo "<h3>❌ Payment Init Failed</h3>";
    echo "<pre>";
    var_dump($response);
    echo "</pre>";
}