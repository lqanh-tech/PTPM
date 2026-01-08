<?php

class Response {

    public static function json($data, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        echo json_encode($data);
        exit();
    }

    public static function success($data, $statusCode = 200, $headers = []) {
        self::json(['status' => 'success', 'data' => $data], $statusCode, $headers);
    }

    public static function error($message, $statusCode = 400, $errors = [], $headers = []) {
        self::json(['status' => 'error', 'message' => $message, 'errors' => $errors], $statusCode, $headers);
    }
}
?>