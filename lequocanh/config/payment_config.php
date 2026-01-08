<?php

return [
    'default_gateway' => 'momo',

    'gateways' => [
        'momo' => [
            'enabled' => true,
            'partner_code' => $_ENV['MOMO_PARTNER_CODE'] ?? 'MOMO',
            'access_key' => $_ENV['MOMO_ACCESS_KEY'] ?? 'F8BBA842ECF85',
            'secret_key' => $_ENV['MOMO_SECRET_KEY'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
            'endpoint' => $_ENV['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create',
            'return_url' => '/lequocanh/payment/return.php',
            'notify_url' => '/lequocanh/payment/notify.php',
            'timeout' => 30
        ],

        'bank_transfer' => [
            'enabled' => true,
            'bank_code' => $_ENV['BANK_CODE'] ?? 'MB',
            'account_number' => $_ENV['BANK_ACCOUNT'] ?? '0123456789',
            'account_name' => $_ENV['BANK_ACCOUNT_NAME'] ?? 'NGUYEN VAN A',
            'qr_enabled' => true
        ],

        'cod' => [
            'enabled' => true,
            'fee' => 0,
            'max_amount' => 5000000
        ]
    ],

    'security' => [
        'verify_ssl' => true,
        'webhook_secret' => $_ENV['WEBHOOK_SECRET'] ?? 'your-webhook-secret',
        'ip_whitelist' => [
            '127.0.0.1',
            'momo-payment-ips'
        ]
    ]
];
