<?php

function safeRequireClass($className, $additionalPaths = []) {

    $filename = (substr($className, -4) === '.php') ? $className : $className . '.php';
    
    $basePaths = [
        '../mod/',
        './elements_LQA/mod/',
        './administrator/elements_LQA/mod/',
        '../../elements_LQA/mod/',
        __DIR__ . '/'
    ];
    
    if (!empty($additionalPaths)) {
        $basePaths = array_merge($basePaths, $additionalPaths);
    }
    
    foreach ($basePaths as $basePath) {
        $fullPath = $basePath . basename($filename);
        if (file_exists($fullPath)) {
            require_once $fullPath;
            if (class_exists('Logger')) {
                Logger::debug("Successfully loaded class file", ['class' => $className, 'path' => $fullPath]);
            }
            return true;
        }
        
        $dirRelativePath = __DIR__ . '/' . $basePath . basename($filename);
        if (file_exists($dirRelativePath)) {
            require_once $dirRelativePath;
            if (class_exists('Logger')) {
                Logger::debug("Successfully loaded class file", ['class' => $className, 'path' => $dirRelativePath]);
            }
            return true;
        }
    }
    
    $errorMsg = "Cannot find class file: $filename. Tried multiple paths.";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    throw new Exception($errorMsg);
}

function safeGetPath($relativePath, $additionalPaths = []) {

    $searchPaths = [
        $relativePath,
        './elements_LQA/' . $relativePath,
        './administrator/elements_LQA/' . $relativePath,
        '../' . $relativePath,
        '../../' . $relativePath,
        __DIR__ . '/../' . $relativePath
    ];
    
    if (!empty($additionalPaths)) {
        $searchPaths = array_merge($searchPaths, $additionalPaths);
    }
    
    foreach ($searchPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    $errorMsg = "Cannot find file: $relativePath. Tried multiple paths.";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    throw new Exception($errorMsg);
}

function safeIncludeFile($relativePath, $once = true, $additionalPaths = []) {
    $path = safeGetPath($relativePath, $additionalPaths);
    
    if ($once) {
        include_once $path;
    } else {
        include $path;
    }
    
    return true;
}

function safeRequireFile($relativePath, $once = true, $additionalPaths = []) {
    $path = safeGetPath($relativePath, $additionalPaths);
    
    if ($once) {
        require_once $path;
    } else {
        require $path;
    }
    
    return true;
}

function safeSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {

        if (headers_sent($file, $line)) {
            if (class_exists('Logger')) {
                Logger::warning('Cannot start session - headers already sent', [
                    'file' => $file,
                    'line' => $line
                ]);
            } else {
                error_log("Cannot start session - headers already sent in $file on line $line");
            }
            return false;
        }
        
        try {
            session_start();
            return true;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Failed to start session', ['error' => $e->getMessage()]);
            } else {
                error_log('Failed to start session: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    return true;
}

function safeRedirect($url, $statusCode = 302) {

    $url = filter_var($url, FILTER_SANITIZE_URL);
    
    if (headers_sent($file, $line)) {
        if (class_exists('Logger')) {
            Logger::warning('Headers already sent, using JavaScript redirect', [
                'url' => $url,
                'file' => $file,
                'line' => $line
            ]);
        } else {
            error_log("Headers already sent in $file on line $line, using JavaScript redirect to $url");
        }
        
        echo "<script type='text/javascript'>";
        echo "window.location.href = '" . addslashes($url) . "';";
        echo "</script>";
        echo "<noscript>";
        echo "<meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'>";
        echo "</noscript>";
        echo "<p>Redirecting to <a href='" . htmlspecialchars($url) . "'>" . htmlspecialchars($url) . "</a>...</p>";
        exit();
    }
    
    try {
        http_response_code($statusCode);
        header("Location: $url");
        exit();
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::error('Failed to send redirect', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        } else {
            error_log('Failed to send redirect: ' . $e->getMessage());
        }
        
        echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
        exit();
    }
}

function safeJsonResponse($data, $statusCode = 200) {
    if (headers_sent($file, $line)) {
        if (class_exists('Logger')) {
            Logger::error('Cannot send JSON response - headers already sent', [
                'file' => $file,
                'line' => $line
            ]);
        } else {
            error_log("Cannot send JSON response - headers already sent in $file on line $line");
        }
        return false;
    }
    
    try {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::error('Failed to send JSON response', [
                'error' => $e->getMessage()
            ]);
        } else {
            error_log('Failed to send JSON response: ' . $e->getMessage());
        }
        return false;
    }
}

function safeJsonSuccess($message = 'Success', $data = null, $statusCode = 200) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    return safeJsonResponse($response, $statusCode);
}

function safeJsonError($message = 'Error', $data = null, $statusCode = 400) {
    $response = ['success' => false, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    return safeJsonResponse($response, $statusCode);
}