<?php

$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Testing Provinces...\n";
$_GET['type'] = 'provinces';
ob_start();
require 'get_address_data.php';
$output = ob_get_clean();
$data = json_decode($output, true);
if ($data['success'] && count($data['data']) > 0) {
    echo "✓ Provinces: Found " . count($data['data']) . " records.\n";
    $firstProvince = $data['data'][0];
    echo "  Sample: " . json_encode($firstProvince, JSON_UNESCAPED_UNICODE) . "\n";
    $provinceId = $firstProvince['ProvinceID'];
} else {
    echo "✗ Provinces Failed: " . $output . "\n";
    exit(1);
}

echo "\nTesting Districts for Province ID $provinceId...\n";
$_GET['type'] = 'districts';
$_GET['province_id'] = $provinceId;
ob_start();
require 'get_address_data.php';
$output = ob_get_clean();
$data = json_decode($output, true);
if ($data['success'] && count($data['data']) > 0) {
    echo "✓ Districts: Found " . count($data['data']) . " records.\n";
    $firstDistrict = $data['data'][0];
    echo "  Sample: " . json_encode($firstDistrict, JSON_UNESCAPED_UNICODE) . "\n";
    $districtId = $firstDistrict['DistrictID'];
} else {
    echo "✗ Districts Failed: " . $output . "\n";
    exit(1);
}

echo "\nTesting Wards for District ID $districtId...\n";
$_GET['type'] = 'wards';
$_GET['district_id'] = $districtId;
ob_start();
require 'get_address_data.php';
$output = ob_get_clean();
$data = json_decode($output, true);
if ($data['success'] && count($data['data']) > 0) {
    echo "✓ Wards: Found " . count($data['data']) . " records.\n";
    $firstWard = $data['data'][0];
    echo "  Sample: " . json_encode($firstWard, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "✗ Wards Failed: " . $output . "\n";
    exit(1);
}

echo "\n✓ API Verification Successful!\n";
