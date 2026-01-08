<?php

require_once __DIR__ . '/ConfigManager.php';

$config = ConfigManager::getInstance();

define('BASE_URL', $config->getBaseUrl());

$paymentConfig = $config->getLegacyConfig();

if (!isset($GLOBALS['paymentConfig'])) {
    $GLOBALS['paymentConfig'] = $paymentConfig;
}