<?php
/**
 * GHN API Integration Class
 * 
 * Official Documentation: https://api.ghn.vn/home/docs/detail
 * 
 * Features:
 * - Calculate shipping fee
 * - Get delivery time estimation
 * - Create shipping orders
 * - Track shipments
 * - Get Vietnam address data (Province/District/Ward)
 * 
 * @author LQA E-commerce System
 * @version 1.0
 * @license MIT
 */

class GHNApi
{
    private $apiUrl = 'https://online-gateway.ghn.vn/shiip/public-api/v2';
    private $token;
    private $shopId;
    private $fromDistrictId;
    private $db;
    private $enableCache = true;
    private $cacheExpiry = 86400; // 24 hours

    /**
     * Constructor
     * Initialize GHN API with credentials from database config
     */
    public function __construct()
    {
        require_once __DIR__ . '/database.php';
        $this->db = Database::getInstance()->getConnection();

        // Load configuration from database
        $this->loadConfig();
    }

    /**
     * Load configuration from shipping_config table
     */
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

            // Log if not configured
            if (empty($this->token) || empty($this->shopId)) {
                error_log('GHN API: Token or Shop ID not configured. Using fallback pricing.');
            }
        } catch (PDOException $e) {
            error_log('GHN API: Failed to load config - ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    /**
     * Check if GHN API is enabled and configured
     */
    public function isEnabled()
    {
        return $this->enabled && !empty($this->token) && !empty($this->shopId);
    }

    /**
     * Calculate shipping fee
     * 
     * @param array $params
     *   - to_district_id (required): Destination district ID
     *   - to_ward_code (required): Destination ward code
     *   - weight (optional): Package weight in grams (default: 1000)
     *   - insurance_value (optional): Insurance value in VND
     *   - coupon (optional): Coupon code
     *   - service_type_id (optional): Service type (default: 2 = E-commerce)
     * 
     * @return array Result with success, data, message
     */
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
            'service_type_id' => $params['service_type_id'] ?? 2, // 2: E-commerce Delivery
            'from_district_id' => $this->fromDistrictId,
            'to_district_id' => intval($params['to_district_id']),
            'to_ward_code' => strval($params['to_ward_code']),
            'weight' => intval($params['weight'] ?? 1000), // grams
            'insurance_value' => intval($params['insurance_value'] ?? 0),
        ];

        // Add optional coupon
        if (!empty($params['coupon'])) {
            $data['coupon'] = $params['coupon'];
        }

        return $this->callApi($endpoint, $data);
    }

    /**
     * Get estimated delivery time
     * 
     * @param array $params
     *   - to_district_id (required)
     *   - to_ward_code (required)
     *   - service_id (optional): Service ID (default: 53320)
     * 
     * @return array Result with leadtime in seconds
     */
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
            'service_id' => intval($params['service_id'] ?? 53320), // Standard service
        ];

        return $this->callApi($endpoint, $data);
    }

    /**
     * Create shipping order with GHN
     * 
     * @param array $orderData
     *   - receiver_name (required)
     *   - receiver_phone (required)
     *   - receiver_address (required)
     *   - to_ward_code (required)
     *   - to_district_id (required)
     *   - weight (required): in grams
     *   - cod_amount (optional): COD amount
     *   - content (optional): Package content description
     *   - items (optional): Array of items
     * 
     * @return array Result with order_code from GHN
     */
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
            'payment_type_id' => intval($orderData['payment_type_id'] ?? 1), // 1: Người gửi trả, 2: Người nhận trả
            'required_note' => $orderData['required_note'] ?? 'CHOTHUHANG', // CHOTHUHANG, CHOXEMHANGKHONGTHU, KHONGCHOXEMHANG
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
            'service_type_id' => 2, // E-commerce
        ];

        // Add items if provided
        if (!empty($orderData['items']) && is_array($orderData['items'])) {
            $data['items'] = $orderData['items'];
        }

        // Add note if provided
        if (!empty($orderData['note'])) {
            $data['note'] = $orderData['note'];
        }

        return $this->callApi($endpoint, $data);
    }

    /**
     * Track order by GHN order code
     * 
     * @param string $orderCode GHN order code
     * @return array Tracking information
     */
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

    /**
     * Cancel shipping order
     * 
     * @param array $orderCodes Array of order codes to cancel
     * @return array Result
     */
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

    // ========================================
    // MASTER DATA APIs (Vietnam Address Data)
    // ========================================

    /**
     * Get list of provinces
     * Results are cached in database
     */
    public function getProvinces($forceRefresh = false)
    {
        // Try cache first
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

        // Cache to database
        if ($result['success'] && !empty($result['data'])) {
            $this->cacheProvinces($result['data']);
        }

        return $result;
    }

    /**
     * Get list of districts by province ID
     */
    public function getDistricts($provinceId, $forceRefresh = false)
    {
        // Try cache first
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

        // Cache to database
        if ($result['success'] && !empty($result['data'])) {
            $this->cacheDistricts($result['data'], $provinceId);
        }

        return $result;
    }

    /**
     * Get list of wards by district ID
     */
    public function getWards($districtId, $forceRefresh = false)
    {
        // Try cache first
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

        // Cache to database
        if ($result['success'] && !empty($result['data'])) {
            $this->cacheWards($result['data'], $districtId);
        }

        return $result;
    }

    /**
     * Get available services
     */
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

    // ========================================
    // CACHE METHODS
    // ========================================

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

    // ========================================
    // CORE API CALL METHOD
    // ========================================

    /**
     * Make API call to GHN
     * 
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @param string $method HTTP method (POST or GET)
     * @return array Response with success, data, message
     */
    private function callApi($endpoint, $data = [], $method = 'POST')
    {
        $curl = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->token,
        ];

        // Add ShopId header for some endpoints
        if ($this->shopId) {
            $headers[] = 'ShopId: ' . $this->shopId;
        }

        $options = [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // For development
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

        // Handle curl error
        if ($error) {
            error_log("GHN API Error: $error");
            return [
                'success' => false,
                'error' => $error,
                'message' => 'Connection error to GHN API'
            ];
        }

        // Parse response
        $result = json_decode($response, true);

        // GHN API returns code 200 for success
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

    /**
     * Get config value from database
     */
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

    /**
     * Update config value in database
     */
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
