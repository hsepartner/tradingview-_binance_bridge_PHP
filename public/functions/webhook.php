<?php
// Netlify function for TradingView webhook

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
    
    if (($current_time - $last_request) < 5) {
        return false;
    }
    
    file_put_contents($rate_limit_file, $current_time);
    return true;
}

// Main webhook logic
if (!verifyWebhook()) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded or IP not allowed']);
    return;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Logging (Netlify compatible)
error_log("Webhook received: " . $input);

// Process TradingView alert
if (isset($data['action'])) {
    $response = [
        'status' => 'success',
        'message' => 'Webhook processed on Netlify',
        'action' => $data['action']
    ];
    
    http_response_code(200);
    echo json_encode($response);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
}
?>
