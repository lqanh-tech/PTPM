<?php

declare(strict_types=1);

// Load mock Database class BEFORE autoload (prevents real DB connections)
require_once __DIR__ . '/mocks/Database.php';

// Bootstrap for unit tests  
require_once __DIR__ . '/../lequocanh/app/autoload.php';
require_once __DIR__ . '/../lequocanh/includes/helpers.php';
