<?php
/**
 * GHN Mock Service - For Testing Without Real API
 * 
 * This service simulates GHN API responses for development and testing
 * No real API token needed
 */

class GHNMockService {
    
    private $mockProvinces = [];
    private $mockDistricts = [];
    private $mockWards = [];
    
    public function __construct() {
        $this->initMockData();
    }
    
    /**
     * Initialize mock data
     */
    private function initMockData() {
        // Mock provinces (major cities)
        $this->mockProvinces = [
            ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'Code' => 'HN'],
            ['ProvinceID' => 202, 'ProvinceName' => 'Hồ Chí Minh', 'Code' => 'HCM'],
            ['ProvinceID' => 203, 'ProvinceName' => 'Đà Nẵng', 'Code' => 'DN'],
            ['ProvinceID' => 204, 'ProvinceName' => 'Hải Phòng', 'Code' => 'HP'],
            ['ProvinceID' => 205, 'ProvinceName' => 'Cần Thơ', 'Code' => 'CT'],
        ];
        
        // Mock districts for Hanoi
        $this->mockDistricts = [
            ['DistrictID' => 1001, 'DistrictName' => 'Ba Đình', 'ProvinceID' => 201],
            ['DistrictID' => 1002, 'DistrictName' => 'Hoàn Kiếm', 'ProvinceID' => 201],
            ['DistrictID' => 1003, 'DistrictName' => 'Cầu Giấy', 'ProvinceID' => 201],
            ['DistrictID' => 1004, 'DistrictName' => 'Đống Đa', 'ProvinceID' => 201],
            // HCM
            ['DistrictID' => 2001, 'DistrictName' => 'Quận 1', 'ProvinceID' => 202],
            ['DistrictID' => 2002, 'DistrictName' => 'Quận 3', 'ProvinceID' => 202],
            ['DistrictID' => 2003, 'DistrictName' => 'Quận 5', 'ProvinceID' => 202],
            ['DistrictID' => 2004, 'DistrictName' => 'Quận 10', 'ProvinceID' => 202],
        ];
        
        // Mock wards
        $this->mockWards = [
            ['WardCode' => '10001', 'WardName' => 'Phường Phúc Xá', 'DistrictID' => 1001],
            ['WardCode' => '10002', 'WardName' => 'Phường Trúc Bạch', 'DistrictID' => 1001],
            ['WardCode' => '10003', 'WardName' => 'Phường Hàng Bài', 'DistrictID' => 1002],
            ['WardCode' => '10004', 'WardName' => 'Phường Hàng Gai', 'DistrictID' => 1002],
        ];
    }
    
    /**
     * Get list of provinces
     */
    public function getProvinces() {
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => $this->mockProvinces
        ];
    }
    
    /**
     * Get districts by province
     */
    public function getDistricts($provinceId) {
        $districts = array_filter($this->mockDistricts, function($d) use ($provinceId) {
            return $d['ProvinceID'] == $provinceId;
        });
        
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => array_values($districts)
        ];
    }
    
    /**
     * Get wards by district
     */
    public function getWards($districtId) {
        $wards = array_filter($this->mockWards, function($w) use ($districtId) {
            return $w['DistrictID'] == $districtId;
        });
        
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => array_values($wards)
        ];
    }
    
    /**
     * Calculate shipping fee (Mock)
     */
    public function calculateFee($params) {
        $toDistrictId = $params['to_district_id'] ?? 0;
        $weight = $params['weight'] ?? 1000; // grams
        $insuranceValue = $params['insurance_value'] ?? 0;
        
        // Mock calculation based on district
        $baseFee = 30000;
        
        // Different fees for different districts
        if (in_array($toDistrictId, [1001, 1002, 2001, 2002])) {
            // Inner city - cheaper
            $baseFee = 25000;
        } elseif (in_array($toDistrictId, [1003, 1004, 2003, 2004])) {
            // Outer city
            $baseFee = 35000;
        } else {
            // Other areas
            $baseFee = 45000;
        }
        
        // Add weight fee (10,000 VND per kg over 1kg)
        $weightKg = $weight / 1000;
        if ($weightKg > 1) {
            $baseFee += ($weightKg - 1) * 10000;
        }
        
        // Add insurance fee (0.5% of value)
        $insuranceFee = 0;
        if ($insuranceValue > 0) {
            $insuranceFee = $insuranceValue * 0.005;
        }
        
        $totalFee = $baseFee + $insuranceFee;
        
        // Mock service type
        $serviceTypeId = 2; // Standard
        
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'total' => (int)$totalFee,
                'service_fee' => (int)$baseFee,
                'insurance_fee' => (int)$insuranceFee,
                'service_type_id' => $serviceTypeId,
                'expected_delivery_time' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ]
        ];
    }
    
    /**
     * Get available services (Mock)
     */
    public function getAvailableServices($params) {
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                [
                    'service_id' => 53320,
                    'short_name' => 'Nhanh',
                    'service_type_id' => 2,
                    'name' => 'Giao hàng nhanh'
                ],
                [
                    'service_id' => 53321,
                    'short_name' => 'Tiêu chuẩn',
                    'service_type_id' => 1,
                    'name' => 'Giao hàng tiêu chuẩn'
                ]
            ]
        ];
    }
    
    /**
     * Create shipping order (Mock)
     */
    public function createOrder($orderData) {
        // Generate mock order code
        $orderCode = 'MOCK' . strtoupper(substr(md5(time()), 0, 8));
        
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'order_code' => $orderCode,
                'sort_code' => '100-' . rand(1000, 9999),
                'trans_type' => 'truck',
                'ward_encode' => '',
                'district_encode' => '',
                'fee' => [
                    'main_service' => 30000,
                    'insurance' => 0,
                    'cod_fee' => 0,
                    'station_do' => 0,
                    'station_pu' => 0,
                    'return' => 0,
                    'r2s' => 0,
                    'coupon' => 0,
                    'total' => 30000
                ],
                'total_fee' => 30000,
                'expected_delivery_time' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ]
        ];
    }
    
    /**
     * Get order info (Mock)
     */
    public function getOrderInfo($orderCode) {
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'order_code' => $orderCode,
                'status' => 'ready_to_pick',
                'status_name' => 'Chờ lấy hàng',
                'created_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s'),
                'expected_delivery_time' => date('Y-m-d H:i:s', strtotime('+3 days')),
                'log' => [
                    [
                        'status' => 'ready_to_pick',
                        'updated_date' => date('Y-m-d H:i:s'),
                        'description' => 'Đơn hàng đã được tạo'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Cancel order (Mock)
     */
    public function cancelOrder($orderCodes) {
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'order_code' => is_array($orderCodes) ? $orderCodes[0] : $orderCodes,
                'result' => true,
                'message' => 'Đơn hàng đã được hủy'
            ]
        ];
    }
    
    /**
     * Print order (Mock)
     */
    public function printOrder($orderCodes) {
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'token' => 'mock_token_' . time(),
                'url' => 'https://example.com/mock-print-label.pdf'
            ]
        ];
    }
    
    /**
     * Get shipping fee with all details (Mock)
     */
    public function getShippingFeeDetail($params) {
        $feeResult = $this->calculateFee($params);
        
        if ($feeResult['code'] !== 200) {
            return $feeResult;
        }
        
        $data = $feeResult['data'];
        
        return [
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'total' => $data['total'],
                'service_fee' => $data['service_fee'],
                'insurance_fee' => $data['insurance_fee'],
                'pick_station_fee' => 0,
                'coupon_value' => 0,
                'r2s_fee' => 0,
                'return_again' => 0,
                'document_return' => 0,
                'double_check' => 0,
                'cod_fee' => 0,
                'pick_shift_fee' => 0,
                'delivery_shift_fee' => 0,
                'expected_delivery_time' => $data['expected_delivery_time']
            ]
        ];
    }
}
