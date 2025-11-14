<?php
// Manual testing ke liye
$webhook_url = "https://yourdomain.com/webhook.php";
$test_data = [
    'action' => 'BUY',
    'symbol' => 'BTCUSDT',
    'quantity' => 0.001,
    'token' => 'your_secure_token_here'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
echo $response;
curl_close($ch);
?>
