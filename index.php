<?php
// Ghi lỗi vào file error.log để gỡ lỗi
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/error.log");
error_reporting(E_ALL);

// Use the router for clean URLs
require __DIR__ . '/router.php';
