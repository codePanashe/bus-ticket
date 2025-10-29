<?php
// test_email.php
require_once 'includes/email.php';

$to = "panashemubango20@gmail.com"; // Your real Gmail
$subject = "ZUPCO: Test Email";
$body = "This is a real email from your ZUPCO system!";

if (sendZUPCOEmail($to, $subject, $body)) {
    echo "<h2>✅ Email sent successfully!</h2>";
    echo "<p>Check your Gmail inbox (and Spam folder).</p>";
} else {
    echo "<h2>❌ Failed to send email.</h2>";
    echo "<p>Check error.log for details.</p>";
}
?>