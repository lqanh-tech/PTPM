<?php

require_once 'MoMoConfig.php';

/**
 * MoMo Payment Integration Class
 * Class chính để xử lý tích hợp thanh toán MoMo
 */
class MoMoPayment
{
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $queryEndpoint;

    public function __construct()
    {
        $this->partnerCode = MoMoConfig::getPartnerCode();
        $this->accessKey = MoMoConfig::getAccessKey();
        $this->secretKey = MoMoConfig::getSecretKey();
        $this->endpoint = MoMoConfig::getEndpoint();
        $this->queryEndpoint = MoMoConfig::getQueryEndpoint();
    }

    /**
     * Tạo chữ ký (signature) cho request
     */
    private function generateSignature($rawData)
    {
        return hash_hmac('sha256', $rawData, $this->secretKey);
    }

    /**
     * Tạo request ID duy nhất
     */
    private function generateRequestId()
    {
        return time() . '_' . uniqid();
    }

    /**
     * Tạo order ID duy nhất
     */
    private function generateOrderId()
    {
        return 'ORDER_' . time() . '_' . rand(1000, 9999);
    }

    /**
     * Gửi HTTP POST request
     */
    private function sendPostRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }

        return json_decode($response, true);
    }

    /**
     * Tạo payment request tới MoMo
     * 
     * @param int $amount Số tiền thanh toán (VND)
     * @param string $orderInfo Thông tin đơn hàng
     * @param string $extraData Dữ liệu bổ sung (optional)
     * @return array Response từ MoMo API
     */
    public function createPayment($amount, $orderInfo, $extraData = '')
    {
        $orderId = $this->generateOrderId();
        $requestId = $this->generateRequestId();
        $returnUrl = MoMoConfig::getReturnUrl();
        $notifyUrl = MoMoConfig::getNotifyUrl();
        $requestType = 'captureWallet';

        // Tạo raw signature string theo thứ tự alphabet
        $rawSignature = "accessKey=" . $this->accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $notifyUrl .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $this->partnerCode .
            "&redirectUrl=" . $returnUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        $signature = $this->generateSignature($rawSignature);

        $requestData = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'Test',
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        try {
            // Log request data để debug
            error_log('MoMo API Request: ' . json_encode($requestData));
            error_log('MoMo API Endpoint: ' . $this->endpoint);
            
            $response = $this->sendPostRequest($this->endpoint, $requestData);
            
            // Log response để debug
            error_log('MoMo API Response: ' . json_encode($response));

            // Lưu thông tin giao dịch vào database (tạm thời comment để tránh lỗi)
            // $this->saveTransaction($orderId, $requestId, $amount, $orderInfo, 'PENDING');

            return $response;
        } catch (Exception $e) {
            error_log('MoMo API Exception: ' . $e->getMessage());
            throw new Exception('MoMo API Error: ' . $e->getMessage());
        }
    }

    /**
     * Verify callback từ MoMo
     */
    public function verifyCallback($data)
    {
        $partnerCode = $data['partnerCode'] ?? '';
        $orderId = $data['orderId'] ?? '';
        $requestId = $data['requestId'] ?? '';
        $amount = $data['amount'] ?? '';
        $orderInfo = $data['orderInfo'] ?? '';
        $orderType = $data['orderType'] ?? '';
        $transId = $data['transId'] ?? '';
        $resultCode = $data['resultCode'] ?? '';
        $message = $data['message'] ?? '';
        $payType = $data['payType'] ?? '';
        $responseTime = $data['responseTime'] ?? '';
        $extraData = $data['extraData'] ?? '';
        $signature = $data['signature'] ?? '';

        // Tạo raw signature để verify
        $rawSignature = "accessKey=" . $this->accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&message=" . $message .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&orderType=" . $orderType .
            "&partnerCode=" . $partnerCode .
            "&payType=" . $payType .
            "&requestId=" . $requestId .
            "&responseTime=" . $responseTime .
            "&resultCode=" . $resultCode .
            "&transId=" . $transId;

        $expectedSignature = $this->generateSignature($rawSignature);

        // Verify signature
        if ($signature !== $expectedSignature) {
            return [
                'success' => false,
                'message' => 'Invalid signature'
            ];
        }

        // Cập nhật trạng thái giao dịch
        $status = ($resultCode == 0) ? 'SUCCESS' : 'FAILED';
        $this->updateTransactionStatus($orderId, $status, $transId, $message);

        return [
            'success' => true,
            'resultCode' => $resultCode,
            'orderId' => $orderId,
            'transId' => $transId,
            'message' => $message
        ];
    }

    /**
     * Lưu thông tin giao dịch vào database
     */
    private function saveTransaction($orderId, $requestId, $amount, $orderInfo, $status)
    {
        // Kết nối database (sử dụng connection có sẵn)
        require_once __DIR__ . '/../administrator/elements_LQA/mPDO.php';

        try {
            $pdo = new mPDO();
            $sql = "INSERT INTO momo_transactions (order_id, request_id, amount, order_info, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $pdo->execute($sql, [$orderId, $requestId, $amount, $orderInfo, $status]);
        } catch (Exception $e) {
            error_log('Error saving transaction: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái giao dịch
     */
    private function updateTransactionStatus($orderId, $status, $transId = null, $message = null)
    {
        require_once __DIR__ . '/../administrator/elements_LQA/mPDO.php';

        try {
            $pdo = new mPDO();
            $sql = "UPDATE momo_transactions SET status = ?, trans_id = ?, message = ?, updated_at = NOW()
                    WHERE order_id = ?";
            $pdo->execute($sql, [$status, $transId, $message, $orderId]);

            // Gửi thông báo khi cập nhật trạng thái
            $this->sendNotification($orderId, $status);
        } catch (Exception $e) {
            error_log('Error updating transaction: ' . $e->getMessage());
        }
    }

    /**
     * Gửi thông báo khi có giao dịch
     */
    private function sendNotification($orderId, $status)
    {
        try {
            require_once 'NotificationManager.php';
            $notificationManager = new NotificationManager();

            // Lấy thông tin giao dịch
            $transaction = $this->getTransaction($orderId);

            if ($transaction) {
                if ($status === 'SUCCESS') {
                    $notificationManager->notifyPaymentSuccess($transaction);
                } elseif ($status === 'FAILED') {
                    $notificationManager->notifyPaymentFailed($transaction);
                }
            }
        } catch (Exception $e) {
            error_log('Error sending notification: ' . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin giao dịch theo order ID
     */
    public function getTransaction($orderId)
    {
        require_once __DIR__ . '/../administrator/elements_LQA/mPDO.php';

        try {
            $pdo = new mPDO();
            $sql = "SELECT * FROM momo_transactions WHERE order_id = ?";
            return $pdo->executeS($sql, [$orderId]);
        } catch (Exception $e) {
            error_log('Error getting transaction: ' . $e->getMessage());
            return null;
        }
    }
}
