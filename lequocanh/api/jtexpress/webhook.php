<?php
declare(strict_types=1);

// api/jtexpress/webhook.php
require_once __DIR__ . '/../../app/autoload.php';

use App\Controllers\JTWebhookController;

header('Content-Type: application/json');

$controller = new JTWebhookController();
$response = $controller->handle();

echo json_encode($response);