<?php

class GHNApi
{
    private $apiUrl = 'https://online-gateway.ghn.vn/shiip/public-api/v2';
    private $token;
    private $shopId;
    private $fromDistrictId;
    private $db;
    private $enableCache = true;
    private $cacheExpiry = 86400;

    public function __construct()
    {
        require_once __DIR__ . '/database.php';
        $this->db = Database::getInstance()->getConnection();

        $this->loadConfig();
    }

    private function loadConfig()
    {
        try {
            $sql = "SELECT config_key, config_value FROM shipping_config 
                    WHERE config_key IN ('ghn_api_token', 'ghn_shop_id', 'ghn_from_district_id', 'enable_ghn_api')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->token = $configs['ghn_api_token'] ?? '';
            $this->shopId = $configs['ghn_shop_id'] ?? '';
            $this->fromDistrictId = intval($configs['ghn_from_district_id'] ?? 1542);
            $this->enabled = (bool)($configs['enable_ghn_api'] ?? false);

            if (empty($this->token) || empty($this->shopId)) {
                error_log('GHN API: Token or Shop ID not configured. Using fallback pricing.');
            }
        } catch (PDOException $e) {
            error_log('GHN API: Failed to load config - ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    public function isEnabled()
    {
        return $this->enabled && !empty($this->token) && !empty($this->shopId);
    }

    public function calculateShippingFee($params)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'GHN API not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = $this->apiUrl . '/shipping-order/fee';

        $data = [
            'shop_id' => intval($this->shopId),
            'service_type_id' => $params['service_type_id'] ?? 2,
            'from_district_id' => $this->fromDistrictId,
            'to_district_id' => intval($params['to_district_id']),
            'to_ward_code' => strval($params['to_ward_code']),
            'weight' => intval($params['weight'] ?? 1000),
            'insurance_value' => intval($params['insurance_value'] ?? 0),
        ];

        if (!empty($params['coupon'])) {
            $data['coupon'] = $params['coupon'];
        }

        return $this->callApi($endpoint, $data);
    }

    public function getDeliveryTime($params)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'GHN API not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = $this->apiUrl . '/shipping-order/leadtime';

        $data = [
            'shop_id' => intval($this->shopId),
            'from_district_id' => $this->fromDistrictId,
            'to_district_id' => intval($params['to_district_id']),
            'to_ward_code' => strval($params['to_ward_code']),
            'service_id' => intval($params['service_id'] ?? 53320),
        ];

        return $this->callApi($endpoint, $data);
    }

    public function createShippingOrder($orderData)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'GHN API not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = $this->apiUrl . '/shipping-order/create';

        $data = [
            'shop_id' => intval($this->shopId),
            'payment_type_id' => intval($orderData['payment_type_id'] ?? 1),
            'required_note' => $orderData['required_note'] ?? 'CHOTHUHANG',
            'to_name' => $orderData['receiver_name'],
            'to_phone' => $orderData['receiver_phone'],
            'to_address' => $orderData['receiver_address'],
            'to_ward_code' => strval($orderData['to_ward_code']),
            'to_district_id' => intval($orderData['to_district_id']),
            'cod_amount' => intval($orderData['cod_amount'] ?? 0),
            'content' => $orderData['content'] ?? 'Hàng hóa',
            'weight' => intval($orderData['weight']),
            'length' => intval($orderData['length'] ?? 0),
            'width' => intval($orderData['width'] ?? 0),
            'height' => intval($orderData['height'] ?? 0),
            'insurance_value' => intval($orderData['insurance_value'] ?? 0),
            'service_type_id' => 2,
        ];

        if (!empty($orderData['items']) && is_array($orderData['items'])) {
            $data['items'] = $orderData['items'];
        }

        if (!empty($orderData['note'])) {
            $data['note'] = $orderData['note'];
        }

        return $this->callApi($endpoint, $data);
    }

    public function trackOrder($orderCode)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'GHN API not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = $this->apiUrl . '/shipping-order/detail';

        $data = ['order_code' => $orderCode];

        return $this->callApi($endpoint, $data);
    }

    public function cancelOrder($orderCodes)
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'GHN API not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = $this->apiUrl . '/shipping-order/cancel';

        $data = ['order_codes' => (array)$orderCodes];

        return $this->callApi($endpoint, $data);
    }

    public function getProvinces($forceRefresh = false)
    {

        if (!$forceRefresh && $this->enableCache) {
            $cached = $this->getCachedProvinces();
            if (!empty($cached)) {
                return ['success' => true, 'data' => $cached];
            }
        }

        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'GHN API not configured'];
        }

        $endpoint = $this->apiUrl . '/master-data/province';
        $result = $this->callApi($endpoint, [], 'GET');

        if ($result['success'] && !empty($result['data'])) {
            $this->cacheProvinces($result['data']);
        }

        return $result;
    }

    public function getDistricts($provinceId, $forceRefresh = false)
    {

        if (!$forceRefresh && $this->enableCache) {
            $cached = $this->getCachedDistricts($provinceId);
            if (!empty($cached)) {
                return ['success' => true, 'data' => $cached];
            }
        }

        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'GHN API not configured'];
        }

        $endpoint = $this->apiUrl . '/master-data/district';
        $result = $this->callApi($endpoint, ['province_id' => intval($provinceId)]);

        if ($result['success'] && !empty($result['data'])) {
            $this->cacheDistricts($result['data'], $provinceId);
        }

        return $result;
    }

    public function getWards($districtId, $forceRefresh = false)
    {

        if (!$forceRefresh && $this->enableCache) {
            $cached = $this->getCachedWards($districtId);
            if (!empty($cached)) {
                return ['success' => true, 'data' => $cached];
            }
        }

        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'GHN API not configured'];
        }

        $endpoint = $this->apiUrl . '/master-data/ward';
        $result = $this->callApi($endpoint, ['district_id' => intval($districtId)]);

        if ($result['success'] && !empty($result['data'])) {
            $this->cacheWards($result['data'], $districtId);
        }

        return $result;
    }

    public function getAvailableServices($toDistrictId)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'GHN API not configured'];
        }

        $endpoint = $this->apiUrl . '/shipping-order/available-services';

        $data = [
            'shop_id' => intval($this->shopId),
            'from_district' => $this->fromDistrictId,
            'to_district' => intval($toDistrictId),
        ];

        return $this->callApi($endpoint, $data);
    }

    private function getCachedProvinces()
    {
        try {
            $sql = "SELECT province_id, province_name, province_code, name_extensions 
                    FROM vietnam_provinces WHERE status = 1 ORDER BY province_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to get cached provinces: ' . $e->getMessage());
            return [];
        }
    }

    private function getCachedDistricts($provinceId)
    {
        try {
            $sql = "SELECT district_id, district_name, district_code, name_extensions 
                    FROM vietnam_districts WHERE province_id = ? AND status = 1 ORDER BY district_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([intval($provinceId)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to get cached districts: ' . $e->getMessage());
            return [];
        }
    }

    private function getCachedWards($districtId)
    {
        try {
            $sql = "SELECT ward_code, ward_name, name_extensions 
                    FROM vietnam_wards WHERE district_id = ? AND status = 1 ORDER BY ward_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([intval($districtId)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to get cached wards: ' . $e->getMessage());
            return [];
        }
    }

    private function cacheProvinces($provinces)
    {
        try {
            $sql = "INSERT INTO vietnam_provinces (province_id, province_name, province_code, name_extensions, can_update_cod, status) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    province_name=VALUES(province_name), 
                    province_code=VALUES(province_code),
                    name_extensions=VALUES(name_extensions),
                    updated_at=CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($sql);

            foreach ($provinces as $province) {
                $stmt->execute([
                    $province['ProvinceID'],
                    $province['ProvinceName'],
                    $province['Code'] ?? null,
                    json_encode($province['NameExtension'] ?? []),
                    $province['CanUpdateCOD'] ?? 1,
                    $province['Status'] ?? 1
                ]);
            }
        } catch (PDOException $e) {
            error_log('Failed to cache provinces: ' . $e->getMessage());
        }
    }

    private function cacheDistricts($districts, $provinceId)
    {
        try {
            $sql = "INSERT INTO vietnam_districts (district_id, province_id, district_name, district_code, name_extensions, can_update_cod, status, support_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    district_name=VALUES(district_name),
                    district_code=VALUES(district_code),
                    name_extensions=VALUES(name_extensions),
                    updated_at=CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($sql);

            foreach ($districts as $district) {
                $stmt->execute([
                    $district['DistrictID'],
                    $provinceId,
                    $district['DistrictName'],
                    $district['Code'] ?? null,
                    json_encode($district['NameExtension'] ?? []),
                    $district['CanUpdateCOD'] ?? 1,
                    $district['Status'] ?? 1,
                    $district['SupportType'] ?? 3
                ]);
            }
        } catch (PDOException $e) {
            error_log('Failed to cache districts: ' . $e->getMessage());
        }
    }

    private function cacheWards($wards, $districtId)
    {
        try {
            $sql = "INSERT INTO vietnam_wards (ward_code, district_id, ward_name, name_extensions, can_update_cod, status, support_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    ward_name=VALUES(ward_name),
                    name_extensions=VALUES(name_extensions),
                    updated_at=CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($sql);

            foreach ($wards as $ward) {
                $stmt->execute([
                    $ward['WardCode'],
                    $districtId,
                    $ward['WardName'],
                    json_encode($ward['NameExtension'] ?? []),
                    $ward['CanUpdateCOD'] ?? 1,
                    $ward['Status'] ?? 1,
                    $ward['SupportType'] ?? 3
                ]);
            }
        } catch (PDOException $e) {
            error_log('Failed to cache wards: ' . $e->getMessage());
        }
    }

    private function callApi($endpoint, $data = [], $method = 'POST')
    {
        $curl = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->token,
        ];

        if ($this->shopId) {
            $headers[] = 'ShopId: ' . $this->shopId;
        }

        $options = [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $options[CURLOPT_URL] .= '?' . http_build_query($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            error_log("GHN API Error: $error");
            return [
                'success' => false,
                'error' => $error,
                'message' => 'Connection error to GHN API'
            ];
        }

        $result = json_decode($response, true);

        $isSuccess = $httpCode === 200 && isset($result['code']) && $result['code'] === 200;

        return [
            'success' => $isSuccess,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? ($isSuccess ? 'Success' : 'Unknown error'),
            'code' => $result['code'] ?? $httpCode,
            'http_code' => $httpCode,
            'raw_response' => $result
        ];
    }

    public function getConfigValue($key, $default = null)
    {
        try {
            $sql = "SELECT config_value FROM shipping_config WHERE config_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (PDOException $e) {
            error_log('Failed to get config: ' . $e->getMessage());
            return $default;
        }
    }

    public function updateConfig($key, $value)
    {
        try {
            $sql = "UPDATE shipping_config SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE config_key = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$value, $key]);
        } catch (PDOException $e) {
            error_log('Failed to update config: ' . $e->getMessage());
            return false;
        }
    }
}
