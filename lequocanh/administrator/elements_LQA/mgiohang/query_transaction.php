<?php

header('Content-type: application/json');

$endpoint = "https://test-payment.momo.vn/v2/gateway/api/query";

$partnerCode = 'MOMO';
$accessKey = 'F8BBA842ECF85';
$secretKey = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';

$orderId = $_POST['orderId'] ?? $_GET['orderId'] ?? "";
$requestId = time() . "";

if (empty($orderId)) {
    echo json_encode([
        'resultCode' => 1,
        'message' => 'Missing orderId parameter'
    ]);
    exit;
}

$rawHash = "accessKey=" . $accessKey . "&orderId=" . $orderId . "&partnerCode=" . $partnerCode . "&requestId=" . $requestId;
$signature = hash_hmac("sha256", $rawHash, $secretKey);

$data = array(
    'partnerCode' => $partnerCode,
    'requestId' => $requestId,
    'orderId' => $orderId,
    'signature' => $signature,
    'lang' => 'vi'
);

$result = execPostRequest($endpoint, json_encode($data));
$jsonResult = json_decode($result, true);

logMoMoTransaction('QUERY_TRANSACTION', [
    'request' => $data,
    'response' => $jsonResult
]);

echo json_encode($jsonResult);

function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function logMoMoTransaction($type, $data) {
    $logFile = __DIR__ . '/../logs/momo_transactions.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}
?>