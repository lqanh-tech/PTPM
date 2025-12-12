<?php
/**
 * Shipping Management Class
 * 
 * Main class for handling all shipping-related operations
 * Coordinates between GHN API and fallback pricing
 * 
 * @author LQA E-commerce System
 * @version 1.0
 */

require_once __DIR__ . '/GHNService.php';
require_once __DIR__ . '/database.php';

class Shipping
{
    private $db;
    private $ghnService;
    private $enableGHN = true;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ghnService = new GHNService();
        $this->enableGHN = true; // Always enabled (will use mock if no API token)
    }

    /**
     * Calculate shipping fee with automatic fallback
     * 
     * @param array $params
     *   - to_district_id (required for GHN)
     *   - to_ward_code (required for GHN)
     *   - to_province_name (required for fallback)
     *   - latitude (optional for fallback)
     *   - longitude (optional for fallback)
     *   - weight (optional): Package weight in grams
     *   - insurance_value (optional): Insurance value
     * 
     * @return array [
     *   'success' => bool,
     *   'shipping_fee' => float,
     *   'method' => 'GHN' | 'FALLBACK',
     *   'estimated_days' => int,
     *   'distance_km' => float (if fallback),
     *   'message' => string
     * ]
     */
    public function calculateShippingFee($params)
    {
        // Try GHN Service first
        if ($this->enableGHN && !empty($params['to_district_id']) && !empty($params['to_ward_code'])) {
            $ghnResult = $this->ghnService->calculateShippingComplete([
                'to_district_id' => $params['to_district_id'],
                'to_ward_code' => $params['to_ward_code'],
                'weight' => $params['weight'] ?? 1000,
                'insurance_value' => $params['insurance_value'] ?? 0,
            ]);

            if ($ghnResult['success']) {
                return [
                    'success' => true,
                    'shipping_fee' => $ghnResult['shipping_fee'] ?? 0,
                    'service_fee' => $ghnResult['service_fee'] ?? 0,
                    'insurance_fee' => $ghnResult['insurance_fee'] ?? 0,
                    'method' => $ghnResult['method'],
                    'method_name' => $ghnResult['method_name'],
                    'estimated_days' => $ghnResult['estimated_days'],
                    'estimated_delivery' => $ghnResult['estimated_delivery'],
                    'message' => 'Calculated via GHN' . ($this->ghnService->isUsingMock() ? ' (Mock)' : ''),
                    'using_mock' => $this->ghnService->isUsingMock()
                ];
            } else {
                error_log('GHN Service failed, falling back: ' . ($ghnResult['message'] ?? 'Unknown error'));
            }
        }

        // Fallback to distance-based calculation
        return $this->calculateFallbackFee($params);
    }

    /**
     * Get estimated delivery time
     * 
     * @param array $params Same as calculateShippingFee
     * @return array [
     *   'success' => bool,
     *   'estimated_days' => int,
     *   'estimated_delivery' => DateTime,
     *   'leadtime_seconds' => int
     * ]
     */
    public function getDeliveryTime($params)
    {
        // Try GHN Service first
        if ($this->enableGHN && !empty($params['to_district_id']) && !empty($params['to_ward_code'])) {
            $ghnResult = $this->ghnService->calculateShippingComplete([
                'to_district_id' => $params['to_district_id'],
                'to_ward_code' => $params['to_ward_code'],
                'weight' => $params['weight'] ?? 1000,
                'insurance_value' => $params['insurance_value'] ?? 0,
            ]);

            if ($ghnResult['success']) {
                return [
                    'success' => true,
                    'estimated_days' => $ghnResult['estimated_days'],
                    'estimated_delivery' => $ghnResult['estimated_delivery'],
                    'method' => $ghnResult['method']
                ];
            }
        }

        // Fallback estimate
        $estimatedDays = $this->estimateFallbackDeliveryDays($params);
        
        return [
            'success' => true,
            'estimated_days' => $estimatedDays,
            'estimated_delivery' => date('Y-m-d H:i:s', strtotime("+{$estimatedDays} days")),
            'method' => 'FALLBACK'
        ];
    }

    /**
     * Calculate both fee and delivery time in one call
     * 
     * @param array $params
     * @return array Combined result
     */
    public function calculateShippingComplete($params)
    {
        $feeResult = $this->calculateShippingFee($params);
        $timeResult = $this->getDeliveryTime($params);

        return [
            'success' => $feeResult['success'],
            'shipping_fee' => $feeResult['shipping_fee'] ?? 0,
            'shipping_fee_formatted' => number_format($feeResult['shipping_fee'] ?? 0, 0, ',', '.') . ' ₫',
            'service_fee' => $feeResult['service_fee'] ?? 0,
            'insurance_fee' => $feeResult['insurance_fee'] ?? 0,
            'method' => $feeResult['method'],
            'method_name' => $feeResult['method_name'] ?? 'Tự vận chuyển',
            'estimated_days' => $timeResult['estimated_days'] ?? 3,
            'estimated_delivery' => $timeResult['estimated_delivery'] ?? null,
            'leadtime_seconds' => $timeResult['leadtime_seconds'] ?? null,
            'distance_km' => $feeResult['distance_km'] ?? null,
            'message' => $feeResult['message'] ?? 'Success'
        ];
    }

    /**
     * Create shipping order
     * 
     * @param array $orderData
     *   - order_id: Internal order ID
     *   - order_code: Order code for reference
     *   - receiver_name
     *   - receiver_phone
     *   - receiver_address (full address string)
     *   - to_province_id, to_district_id, to_ward_code
     *   - weight
     *   - cod_amount (optional)
     *   - items (optional)
     * 
     * @return array Result with tracking_number
     */
    public function createShippingOrder($orderData)
    {
        // Try GHN if available
        if ($this->enableGHN && !empty($orderData['to_district_id']) && !empty($orderData['to_ward_code'])) {
            $ghnResult = $this->ghnApi->createShippingOrder([
                'receiver_name' => $orderData['receiver_name'],
                'receiver_phone' => $orderData['receiver_phone'],
                'receiver_address' => $orderData['receiver_address'],
                'to_district_id' => $orderData['to_district_id'],
                'to_ward_code' => $orderData['to_ward_code'],
                'weight' => $orderData['weight'] ?? 1000,
                'cod_amount' => $orderData['cod_amount'] ?? 0,
                'content' => $orderData['content'] ?? 'Hàng hóa',
                'items' => $orderData['items'] ?? [],
                'note' => $orderData['note'] ?? '',
                'insurance_value' => $orderData['insurance_value'] ?? 0,
            ]);

            if ($ghnResult['success']) {
                $ghnOrderData = $ghnResult['data'];
                
                // Save to database
                $trackingId = $this->saveShippingTracking([
                    'order_id' => $orderData['order_id'] ?? 0,
                    'order_code' => $orderData['order_code'],
                    'shipping_method_code' => 'GHN',
                    'carrier_order_code' => $ghnOrderData['order_code'] ?? null,
                    'tracking_number' => $ghnOrderData['order_code'] ?? null,
                    'to_name' => $orderData['receiver_name'],
                    'to_phone' => $orderData['receiver_phone'],
                    'to_address' => $orderData['receiver_address'],
                    'to_district_id' => $orderData['to_district_id'],
                    'to_ward_code' => $orderData['to_ward_code'],
                    'shipping_fee' => $ghnOrderData['total_fee'] ?? 0,
                    'weight' => $orderData['weight'] ?? 1000,
                    'status' => 'pending',
                ]);

                return [
                    'success' => true,
                    'tracking_id' => $trackingId,
                    'tracking_number' => $ghnOrderData['order_code'],
                    'carrier_order_code' => $ghnOrderData['order_code'],
                    'method' => 'GHN',
                    'expected_delivery_time' => $ghnOrderData['expected_delivery_time'] ?? null,
                ];
            }
        }

        // Fallback: Create manual tracking
        $trackingNumber = $this->generateTrackingNumber($orderData['order_code']);
        
        $trackingId = $this->saveShippingTracking([
            'order_id' => $orderData['order_id'] ?? 0,
            'order_code' => $orderData['order_code'],
            'shipping_method_code' => 'MANUAL',
            'tracking_number' => $trackingNumber,
            'to_name' => $orderData['receiver_name'],
            'to_phone' => $orderData['receiver_phone'],
            'to_address' => $orderData['receiver_address'],
            'shipping_fee' => $orderData['shipping_fee'] ?? 0,
            'weight' => $orderData['weight'] ?? 1000,
            'status' => 'pending',
        ]);

        return [
            'success' => true,
            'tracking_id' => $trackingId,
            'tracking_number' => $trackingNumber,
            'method' => 'MANUAL',
        ];
    }

    /**
     * Save shipping tracking to database
     */
    private function saveShippingTracking($data)
    {
        try {
            // Get shipping method ID
            $methodSql = "SELECT id FROM shipping_methods WHERE code = ? LIMIT 1";
            $methodStmt = $this->db->prepare($methodSql);
            $methodStmt->execute([$data['shipping_method_code']]);
            $methodId = $methodStmt->fetchColumn();

            $sql = "INSERT INTO order_shipping_tracking (
                        order_id, order_code, shipping_method_id, shipping_method_code,
                        tracking_number, carrier_order_code, shipping_fee, insurance_fee,
                        cod_amount, total_fee, to_name, to_phone, to_address,
                        to_province_id, to_district_id, to_ward_code, distance_km,
                        estimated_delivery, actual_delivery, estimated_days, status,
                        current_location, weight, note, customer_note, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['order_id'] ?? 0,
                $data['order_code'] ?? '',
                $methodId,
                $data['shipping_method_code'] ?? 'MANUAL',
                $data['tracking_number'] ?? null,
                $data['carrier_order_code'] ?? null,
                $data['shipping_fee'] ?? 0,
                $data['insurance_fee'] ?? 0,
                $data['cod_amount'] ?? 0,
                ($data['shipping_fee'] ?? 0) + ($data['insurance_fee'] ?? 0),
                $data['to_name'] ?? '',
                $data['to_phone'] ?? '',
                $data['to_address'] ?? '',
                $data['to_province_id'] ?? null,
                $data['to_district_id'] ?? null,
                $data['to_ward_code'] ?? null,
                $data['distance_km'] ?? null,
                $data['estimated_delivery'] ?? null,
                $data['actual_delivery'] ?? null,
                $data['estimated_days'] ?? null,
                $data['status'] ?? 'pending',
                $data['current_location'] ?? null,
                $data['weight'] ?? 1000,
                $data['note'] ?? null,
                $data['customer_note'] ?? null,
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Failed to save shipping tracking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Track shipment by tracking number or order code
     */
    public function trackShipment($trackingNumberOrOrderCode)
    {
        // First check our database
        $localTracking = $this->getLocalTracking($trackingNumberOrOrderCode);
        
        if (!$localTracking) {
            return ['success' => false, 'message' => 'Tracking number not found'];
        }

        // If using GHN and have carrier code, get live tracking
        if ($localTracking['shipping_method_code'] === 'GHN' && !empty($localTracking['carrier_order_code'])) {
            $ghnTracking = $this->ghnApi->trackOrder($localTracking['carrier_order_code']);
            
            if ($ghnTracking['success']) {
                // Update local database with latest info
                $this->updateTrackingFromGHN($localTracking['id'], $ghnTracking['data']);
                
                return [
                    'success' => true,
                    'tracking_info' => array_merge($localTracking, $ghnTracking['data']),
                    'source' => 'GHN_LIVE'
                ];
            }
        }

        // Return local tracking data
        return [
            'success' => true,
            'tracking_info' => $localTracking,
            'source' => 'LOCAL'
        ];
    }

    /**
     * Get local tracking from database
     */
    private function getLocalTracking($trackingNumberOrOrderCode)
    {
        try {
            $sql = "SELECT * FROM order_shipping_tracking 
                    WHERE tracking_number = ? OR order_code = ? OR carrier_order_code = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$trackingNumberOrOrderCode, $trackingNumberOrOrderCode, $trackingNumberOrOrderCode]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to get local tracking: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update tracking info from GHN response
     */
    private function updateTrackingFromGHN($trackingId, $ghnData)
    {
        try {
            $sql = "UPDATE order_shipping_tracking SET 
                    status = ?,
                    current_location = ?,
                    tracking_history = ?,
                    last_sync_at = NOW(),
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->mapGHNStatus($ghnData['status'] ?? ''),
                $ghnData['current_warehouse'] ?? null,
                json_encode($ghnData['log'] ?? []),
                $trackingId
            ]);
        } catch (PDOException $e) {
            error_log('Failed to update tracking: ' . $e->getMessage());
        }
    }

    /**
     * Map GHN status to our status
     */
    private function mapGHNStatus($ghnStatus)
    {
        $statusMap = [
            'ready_to_pick' => 'pending',
            'picking' => 'picked_up',
            'cancel' => 'cancelled',
            'money_collect_picking' => 'picked_up',
            'picked' => 'picked_up',
            'storing' => 'in_transit',
            'transporting' => 'in_transit',
            'sorting' => 'in_transit',
            'delivering' => 'out_for_delivery',
            'delivered' => 'delivered',
            'delivery_fail' => 'failed',
            'waiting_to_return' => 'returning',
            'return' => 'returned',
            'return_transporting' => 'returning',
            'return_sorting' => 'returning',
            'returning' => 'returning',
            'return_fail' => 'return_failed',
            'returned' => 'returned',
            'exception' => 'exception',
            'damage' => 'exception',
            'lost' => 'lost',
        ];

        return $statusMap[$ghnStatus] ?? 'unknown';
    }

    // ========================================
    // FALLBACK METHODS
    // ========================================

    /**
     * Calculate shipping fee using fallback method (database configuration)
     */
    private function calculateFallbackFee($params)
    {
        require_once __DIR__ . '/ShippingFeeService.php';
        $feeService = new ShippingFeeService();

        $provinceId = $params['to_province_id'] ?? 0;
        $districtId = $params['to_district_id'] ?? 0;
        $weight = $params['weight'] ?? 1000;
        $orderTotal = $params['insurance_value'] ?? 0; // Using insurance value as order total proxy

        $result = $feeService->calculateFee($provinceId, $districtId, $weight, $orderTotal);

        return [
            'success' => true,
            'shipping_fee' => $result['fee'],
            'method' => 'STANDARD', // Default method code
            'method_name' => $result['name'],
            'distance_km' => 0, // Not calculating distance anymore
            'message' => $result['is_free'] ? $result['message'] : 'Calculated based on shipping configuration'
        ];
    }

    /**
     * Estimate delivery days for fallback
     */
    private function estimateFallbackDeliveryDays($params)
    {
        // Simple logic: 1 day for same city, 2-3 days for other cities
        // This could be improved by adding delivery_time to shipping_fees table
        $toProvinceId = $params['to_province_id'] ?? 0;
        
        // Hanoi (ID 1) and HCM (ID 50 - example)
        if ($toProvinceId == 1 || $toProvinceId == 79) { // 79 is HCM usually
            return 1;
        } else {
            return 3;
        }
    }



    /**
     * Calculate distance between two GPS coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber($orderCode)
    {
        return 'LQA' . strtoupper($orderCode) . rand(1000, 9999);
    }

    /**
     * Get config from database
     */
    private function getConfig($key, $default = null)
    {
        return $this->ghnApi->getConfigValue($key, $default);
    }

    /**
     * Get GHN API instance
     */
    public function getGHNApi()
    {
        return $this->ghnApi;
    }
}
