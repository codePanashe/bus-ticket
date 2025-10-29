<?php
$ch = curl_init("https://www.paynow.co.zw/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "❌ cURL error: " . curl_error($ch);
} else {
    echo "✅ cURL working! Length: " . strlen($response);
}
curl_close($ch);
