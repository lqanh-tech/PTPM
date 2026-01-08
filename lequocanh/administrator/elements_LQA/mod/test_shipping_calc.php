<?php

require_once __DIR__ . '/ShippingCls.php';

echo "Testing Shipping Calculation...\n";

$shipping = new Shipping();

$params = [
    'to_province_id' => 1,
    'to_district_id' => 1,
    'to_ward_code' => '1A0101',
    'weight' => 1000,
    'insurance_value' => 200000
];

echo "Calculating fee for 1kg package to Hanoi...\n";
$result = $shipping->calculateShippingComplete($params);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($result['success'] && $result['shipping_fee'] == 30000) {
    echo "✓ Default fee calculation correct (30,000 VND)\n";
} else {
    echo "✗ Fee calculation mismatch. Expected 30000, got " . ($result['shipping_fee'] ?? 'null') . "\n";
}

$params['insurance_value'] = 600000;
echo "\nCalculating fee for > 500k order (Free Ship)...\n";
$result = $shipping->calculateShippingComplete($params);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($result['success'] && $result['shipping_fee'] == 0) {
    echo "✓ Free shipping calculation correct\n";
} else {
    echo "✗ Free shipping mismatch. Expected 0, got " . ($result['shipping_fee'] ?? 'null') . "\n";
}
