<?php

/**
 * Legacy App Configuration - Backward Compatibility
 * This file is kept for backward compatibility with existing code
 * New code should use ConfigManager::getInstance() instead
 */

// Load the new configuration system
require_once __DIR__ . '/ConfigManager.php';

// Get configuration instance
$config = ConfigManager::getInstance();

// Define BASE_URL constant for legacy code
define('BASE_URL', $config->getBaseUrl());

// Export legacy payment configuration
$paymentConfig = $config->getLegacyConfig();

// Backward compatibility for old payment config access
if (!isset($GLOBALS['paymentConfig'])) {
    $GLOBALS['paymentConfig'] = $paymentConfig;
}