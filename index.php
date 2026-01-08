<?php

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/error.log");
error_reporting(E_ALL);

require __DIR__ . '/router.php';
