<?php

require_once __DIR__ . '/GHNMockService.php';

class GHNService {
    
    private $apiToken;
    private $shopId;
    private $apiEndpoint;
    private $useMock = false;
    private $mockService;
    
    private $fromDistrictId = 1001;
    private $fromWardCode = '10001';
    
    public function __construct() {

        $this->apiToken = getenv('GHN_API_TOKEN') ?: '';
        $this->shopId = getenv('GHN_SHOP_ID') ?: '';
        $this->apiEndpoint = getenv('GHN_API_ENDPOINT') ?: 'https://dev-online-gateway.ghn.vn/shiip/public-api/v2';
        
        if (empty($this->apiToken) || $this->apiToken === 'your_ghn_api_token_here') {
            $this->useMock = true;
            $this->mockService = new GHNMockService();
        }
    }
    
    public function isUsingMock() {
        return $this->useMock;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        if ($this->useMock) {
            return $this->handleMockRequest($endpoint, $method, $data);
        }
        
        $url = $this->apiEndpoint . $endpoint;
        
        $headers = [
            'Token: ' . $this->apiToken,
            'Content-Type: application/json'
        ];
        
        if ($this->shopId) {
            $headers[] = 'ShopId: ' . $this->shopId;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'code' => 500,
                'message' => 'cURL Error: ' . $error,
                'data' => null
            ];
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            return [
                'code' => $httpCode,
                'message' => 'Invalid JSON response',
                'data' => null
            ];
        }
        
        return $result;
    }
    
    private function handleMockRequest($endpoint, $method, $data) {

        if (strpos($endpoint, '/master-data/province') !== false) {
            return $this->mockService->getProvinces();
        } elseif (strpos($endpoint, '/master-data/district') !== false) {
            $provinceId = $data['province_id'] ?? 0;
            return $this->mockService->getDistricts($provinceId);
        } elseif (strpos($endpoint, '/master-data/ward') !== false) {
            $districtId = $data['district_id'] ?? 0;
            return $this->mockService->getWards($districtId);
        } elseif (strpos($endpoint, '/shipping-order/fee') !== false) {
            return $this->mockService->calculateFee($data);
        } elseif (strpos($endpoint, '/shipping-order/available-services') !== false) {
            return $this->mockService->getAvailableServices($data);
        } elseif (strpos($endpoint, '/shipping-order/create') !== false) {
            return $this->mockService->createOrder($data);
        } elseif (strpos($endpoint, '/shipping-order/detail') !== false) {
            return $this->mockService->getOrderInfo($data['order_code'] ?? '');
        } elseif (strpos($endpoint, '/switch-status/cancel') !== false) {
            return $this->mockService->cancelOrder($data['order_codes'] ?? []);
        }
        
        return [
            'code' => 404,
            'message' => 'Mock endpoint not found',
            'data' => null
        ];
    }
    
    public function getProvinces() {
        return $this->makeRequest('/master-data/province', 'GET');
    }
    
    public function getDistricts($provinceId) {
        return $this->makeRequest('/master-data/district', 'POST', [
            'province_id' => $provinceId
        ]);
    }
    
    public function getWards($districtId) {
        return $this->makeRequest('/master-data/ward', 'POST', [
            'district_id' => $districtId
        ]);
    }
    
    public function getAvailableServices($toDistrictId) {
        return $this->makeRequest('/shipping-order/available-services', 'POST', [
            'shop_id' => (int)$this->shopId,
            'from_district' => $this->fromDistrictId,
            'to_district' => (int)$toDistrictId
        ]);
    }
    
    public function calculateShippingFee($params) {
        $serviceTypeId = $params['service_type_id'] ?? 2;
        
        $requestData = [
            'service_type_id' => $serviceTypeId,
            'from_district_id' => $this->fromDistrictId,
            'from_ward_code' => $this->fromWardCode,
            'to_district_id' => (int)$params['to_district_id'],
            'to_ward_code' => (string)$params['to_ward_code'],
            'weight' => (int)($params['weight'] ?? 1000),
            'insurance_value' => (int)($params['insurance_value'] ?? 0),
            'coupon' => null
        ];
        
        if (!empty($this->shopId)) {
            $requestData['shop_id'] = (int)$this->shopId;
        }
        
        return $this->makeRequest('/shipping-order/fee', 'POST', $requestData);
    }
    
    public function createShippingOrder($orderData) {
        $requestData = [
            'payment_type_id' => $orderData['payment_type_id'] ?? 1,
            'note' => $orderData['note'] ?? '',
            'required_note' => $orderData['required_note'] ?? 'KHONGCHOXEMHANG',
            'from_name' => $orderData['from_name'] ?? 'Shop',
            'from_phone' => $orderData['from_phone'] ?? '0123456789',
            'from_address' => $orderData['from_address'] ?? 'Hanoi',
            'from_ward_name' => $orderData['from_ward_name'] ?? 'Phường Phúc Xá',
            'from_district_name' => $orderData['from_district_name'] ?? 'Ba Đình',
            'from_province_name' => $orderData['from_province_name'] ?? 'Hà Nội',
            'to_name' => $orderData['to_name'],
            'to_phone' => $orderData['to_phone'],
            'to_address' => $orderData['to_address'],
            'to_ward_code' => $orderData['to_ward_code'],
            'to_district_id' => (int)$orderData['to_district_id'],
            'cod_amount' => (int)($orderData['cod_amount'] ?? 0),
            'content' => $orderData['content'] ?? 'Hàng hóa',
            'weight' => (int)($orderData['weight'] ?? 1000),
            'length' => (int)($orderData['length'] ?? 20),
            'width' => (int)($orderData['width'] ?? 20),
            'height' => (int)($orderData['height'] ?? 10),
            'insurance_value' => (int)($orderData['insurance_value'] ?? 0),
            'service_type_id' => (int)($orderData['service_type_id'] ?? 2),
            'items' => $orderData['items'] ?? []
        ];
        
        if (!empty($this->shopId)) {
            $requestData['shop_id'] = (int)$this->shopId;
        }
        
        return $this->makeRequest('/shipping-order/create', 'POST', $requestData);
    }
    
    public function getOrderInfo($orderCode) {
        return $this->makeRequest('/shipping-order/detail', 'POST', [
            'order_code' => $orderCode
        ]);
    }
    
    public function cancelOrder($orderCodes) {
        if (!is_array($orderCodes)) {
            $orderCodes = [$orderCodes];
        }
        
        return $this->makeRequest('/switch-status/cancel', 'POST', [
            'order_codes' => $orderCodes
        ]);
    }
    
    public function getPrintToken($orderCodes) {
        if (!is_array($orderCodes)) {
            $orderCodes = [$orderCodes];
        }
        
        return $this->makeRequest('/a5/gen-token', 'POST', [
            'order_codes' => $orderCodes
        ]);
    }
    
    public function calculateShippingComplete($params) {
        try {
            $result = $this->calculateShippingFee($params);
            
            if ($result['code'] !== 200 || empty($result['data'])) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to calculate shipping fee',
                    'using_mock' => $this->useMock
                ];
            }
            
            $data = $result['data'];
            
            return [
                'success' => true,
                'shipping_fee' => $data['total'] ?? 0,
                'service_fee' => $data['service_fee'] ?? 0,
                'insurance_fee' => $data['insurance_fee'] ?? 0,
                'method' => 'GHN',
                'method_name' => 'Giao Hàng Nhanh (GHN)',
                'estimated_delivery' => $data['expected_delivery_time'] ?? null,
                'estimated_days' => $this->calculateEstimatedDays($data['expected_delivery_time'] ?? null),
                'distance_km' => null,
                'message' => 'Calculated successfully',
                'using_mock' => $this->useMock
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'using_mock' => $this->useMock
            ];
        }
    }
    
    private function calculateEstimatedDays($deliveryTime) {
        if (empty($deliveryTime)) {
            return 3;
        }
        
        try {
            $deliveryDate = new DateTime($deliveryTime);
            $now = new DateTime();
            $diff = $now->diff($deliveryDate);
            return max(1, $diff->days);
        } catch (Exception $e) {
            return 3;
        }
    }
    
    public function setShopLocation($districtId, $wardCode) {
        $this->fromDistrictId = $districtId;
        $this->fromWardCode = $wardCode;
    }
}
