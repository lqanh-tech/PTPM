<?php

require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/elements_LQA/mod/database.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    header('Location: userLogin.php');
    exit;
}

$db = Database::getInstance()->getConnection();

$cacheDir = __DIR__ . '/../cache';