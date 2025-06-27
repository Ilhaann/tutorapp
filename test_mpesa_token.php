<?php
$key = 'XyphGZAgiDrbjnWcVvwAd5sNoNscVcVQzr9kgyZF0DQYSLah';
$secret = '8u58oz1Mrrk9siFMZJiKPZs2fP3VIzL2plx3E9P9nU0M70mc4CjwnJJ27Q1vFmPA';
$credentials = base64_encode($key . ':' . $secret);

$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

echo "Response: $response\n";
echo "Error: $err\n"; 