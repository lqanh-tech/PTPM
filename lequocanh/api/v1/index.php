<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../Response.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$basePath = '/lequocanh/api/v1';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

switch ($requestUri) {
    case '/products':
        if ($requestMethod === 'GET') {

            Response::json(['message' => 'List of products'], 200);
        } else {
            Response::json(['message' => 'Method Not Allowed'], 405);
        }
        break;
    case '/users':
        if ($requestMethod === 'GET') {

            Response::json(['message' => 'List of users'], 200);
        } else if ($requestMethod === 'POST') {

            Response::json(['message' => 'User created'], 201);
        } else {
            Response::json(['message' => 'Method Not Allowed'], 405);
        }
        break;
    default:
        Response::json(['message' => 'Not Found'], 404);
        break;
}
?>