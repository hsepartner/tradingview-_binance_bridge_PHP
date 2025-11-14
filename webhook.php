<?php
// Security verification function
function verifyWebhook() {
    // IP whitelisting (TradingView IPs)
    $allowed_ips = ['52.89.214.238', '34.212.75.30', '54.218.53.128'];
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    if (!in_array($client_ip, $allowed_ips)) {
        return false;
    }
    
    // Rate limiting
    $rate_limit_file = 'rate_limit.txt';
    $current_time = time();
    $last_request = file_exists($rate_limit_file) ? (int)file_get_contents($rate_limit_file) : 0;
    
    if (($current_time - $last_request) < 5) { // 5 seconds between requests
        return false;
    }
    
    file_put_contents($rate_limit_file, $current_time);
    return true;
}

// ✅ WEBHOOK VERIFICATION - Yeh sabse pehle run karein
if (!verifyWebhook()) {
    http_response_code(429);
    die('Rate limit exceeded or IP not allowed');
}

// GitHub secrets se API keys lena
$binance_api_key = getenv('BINANCE_API_KEY');
$binance_secret_key = getenv('BINANCE_SECRET_KEY');

// Security - TradingView webhook verification
$expected_token = getenv('WEBHOOK_TOKEN');
$received_token = $_POST['token'] ?? '';

if ($received_token !== $expected_token) {
    http_response_code(401);
    die('Unauthorized');
}

// Webhook data receive karna
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Logging
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);

// TradingView alert data process karna
if (isset($data['action']) && $data['action'] === 'BUY') {
    placeBinanceOrder($data, 'BUY', $binance_api_key, $binance_secret_key);
} elseif (isset($data['action']) && $data['action'] === 'SELL') {
    placeBinanceOrder($data, 'SELL', $binance_api_key, $binance_secret_key);
}

function placeBinanceOrder($data, $side, $api_key, $secret_key) {
    $symbol = $data['symbol'] ?? 'BTCUSDT';
    $quantity = $data['quantity'] ?? 0.001;
    
    // Binance API endpoint
    $url = "https://api.binance.com/api/v3/order";
    
    // Order parameters
    $params = [
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'MARKET',
        'quantity' => $quantity,
        'timestamp' => round(microtime(true) * 1000)
    ];
    
    // Signature create karna
    $query_string = http_build_query($params);
    $signature = hash_hmac('sha256', $query_string, $secret_key);
    $params['signature'] = $signature;
    
    // cURL request to Binance
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-MBX-APIKEY: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Response log karna
    file_put_contents('binance_response.txt', date('Y-m-d H:i:s') . " - " . $response . "\n", FILE_APPEND);
    
    return json_decode($response, true);
}

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
?>
