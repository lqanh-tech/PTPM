<?php

header('Content-Type: application/json');
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log("MoMo Process - Input: " . $input);

    if (!$data) {
        throw new Exception('Invalid JSON input');
    }

    if (!isset($data['action'])) {
        throw new Exception('Missing action parameter');
    }

    require_once '../config/payment_config.php';
    require_once 'MoMoPayment.php';

    $momo = new MoMoPayment();

    switch ($data['action']) {
        case 'test':

            echo json_encode([
                'success' => true,
                'message' => 'MoMo process hoạt động!',
                'timestamp' => time(),
                'data' => $data
            ]);
            break;

        case 'create_payment':

            if (!isset($data['amount']) || !isset($data['orderInfo'])) {
                throw new Exception('Missing required payment data');
            }

            $paymentData = [
                'amount' => (int)$data['amount'],
                'orderInfo' => $data['orderInfo'],
                'orderId' => 'ORDER_' . time() . '_' . rand(1000, 9999),
                'returnUrl' => $paymentConfig['momo']['return_url'],
                'notifyUrl' => $paymentConfig['momo']['notify_url']
            ];

            if (isset($data['shippingAddress'])) {
                $paymentData['extraData'] = base64_encode(json_encode($data['shippingAddress']));
            }

            $result = $momo->createPayment($paymentData['amount'], $paymentData['orderInfo'], $paymentData['extraData'] ?? '');

            if ($result && isset($result['payUrl'])) {
                echo json_encode([
                    'success' => true,
                    'payUrl' => $result['payUrl'],
                    'orderId' => $paymentData['orderId']
                ]);
            } else {
                throw new Exception('Failed to create MoMo payment: ' . json_encode($result));
            }
            break;

        default:
            throw new Exception('Unknown action: ' . $data['action']);
    }
} catch (Exception $e) {
    error_log("MoMo Process Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
