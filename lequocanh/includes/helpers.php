<?php
/**
 * Consolidated helper functions for LQA Shop.
 *
 * Replaces: autoRequireFix.php, autoSessionFix.php, pathResolverHelper.php
 * Those files now just include this one for backward compatibility.
 */

// ─── File Loading ───────────────────────────────────────────────

if (!function_exists('safeRequire')) {
    /**
     * Require a file by name and type, searching multiple paths.
     */
    function safeRequire($filename, $type = 'mod', $once = true)
    {
        if (class_exists('PathResolver')) {
            try {
                if ($type === 'mod') {
                    $className = (substr($filename, -4) === '.php') ? substr($filename, 0, -4) : $filename;
                    PathResolver::requireClass($className);
                    return true;
                }
                PathResolver::requireFile("$type/$filename");
                return true;
            } catch (Exception $e) {
                // fall through to manual search
            }
        }

        $paths = [];
        switch ($type) {
            case 'mod':
                $paths = [
                    "../mod/$filename",
                    "../../elements_LQA/mod/$filename",
                    "./elements_LQA/mod/$filename",
                    __DIR__ . "/$filename",
                    __DIR__ . "/../mod/$filename"
                ];
                break;
            case 'config':
                $paths = [
                    "../config/$filename",
                    "../../elements_LQA/config/$filename",
                    "./elements_LQA/config/$filename",
                    __DIR__ . "/../config/$filename"
                ];
                break;
            default:
                $paths = [
                    "../$type/$filename",
                    "../../elements_LQA/$type/$filename",
                    "./elements_LQA/$type/$filename",
                    __DIR__ . "/../$type/$filename"
                ];
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $once ? require_once $path : require $path;
                return true;
            }
        }

        error_log("Cannot find file: $filename of type $type");
        return false;
    }
}

if (!function_exists('safeInclude')) {
    function safeInclude($filename, $type = 'includes', $once = true)
    {
        return safeRequire($filename, $type, $once);
    }
}

if (!function_exists('safePath')) {
    function safePath($filename, $type = 'config')
    {
        if (class_exists('PathResolver')) {
            try {
                return PathResolver::getPath("$type/$filename");
            } catch (Exception $e) {
                // fall through
            }
        }

        $paths = ($type === 'config')
            ? ["../config/$filename", "../../elements_LQA/config/$filename", "./elements_LQA/config/$filename", __DIR__ . "/../config/$filename"]
            : ["../$type/$filename", "../../elements_LQA/$type/$filename", "./elements_LQA/$type/$filename", __DIR__ . "/../$type/$filename"];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }
}

if (!function_exists('safeRequireClass')) {
    function safeRequireClass($className, $additionalPaths = [])
    {
        $filename = (substr($className, -4) === '.php') ? $className : $className . '.php';
        $basePaths = array_merge([
            '../mod/', './elements_LQA/mod/', './administrator/elements_LQA/mod/',
            '../../elements_LQA/mod/', __DIR__ . '/'
        ], $additionalPaths);

        foreach ($basePaths as $basePath) {
            $fullPath = $basePath . basename($filename);
            if (file_exists($fullPath)) {
                require_once $fullPath;
                return true;
            }
            $dirRelativePath = __DIR__ . '/' . $basePath . basename($filename);
            if (file_exists($dirRelativePath)) {
                require_once $dirRelativePath;
                return true;
            }
        }

        throw new Exception("Cannot find class file: $filename");
    }
}

if (!function_exists('safeGetPath')) {
    function safeGetPath($relativePath, $additionalPaths = [])
    {
        $searchPaths = array_merge([
            $relativePath,
            './elements_LQA/' . $relativePath,
            './administrator/elements_LQA/' . $relativePath,
            '../' . $relativePath,
            '../../' . $relativePath,
            __DIR__ . '/../' . $relativePath
        ], $additionalPaths);

        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        throw new Exception("Cannot find file: $relativePath");
    }
}

if (!function_exists('safeIncludeFile')) {
    function safeIncludeFile($relativePath, $once = true, $additionalPaths = [])
    {
        $path = safeGetPath($relativePath, $additionalPaths);
        $once ? include_once $path : include $path;
        return true;
    }
}

if (!function_exists('safeRequireFile')) {
    function safeRequireFile($relativePath, $once = true, $additionalPaths = [])
    {
        $path = safeGetPath($relativePath, $additionalPaths);
        $once ? require_once $path : require $path;
        return true;
    }
}

// ─── Session ────────────────────────────────────────────────────

if (!function_exists('safeSessionStart')) {
    function safeSessionStart()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return true;
        }
        if (headers_sent($file, $line)) {
            error_log("Cannot start session - headers already sent in $file on line $line");
            return false;
        }
        session_start();
        return true;
    }
}

// ─── Response Helpers ───────────────────────────────────────────

if (!function_exists('safeRedirect')) {
    function safeRedirect($url, $statusCode = 302)
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (headers_sent($file, $line)) {
            echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
            exit;
        }
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
}

if (!function_exists('safeJsonResponse')) {
    function safeJsonResponse($data, $statusCode = 200)
    {
        if (headers_sent()) {
            error_log('Cannot send JSON response - headers already sent');
            return false;
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('safeJsonSuccess')) {
    function safeJsonSuccess($message = 'Success', $data = null, $statusCode = 200)
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return safeJsonResponse($response, $statusCode);
    }
}

if (!function_exists('safeJsonError')) {
    function safeJsonError($message = 'Error', $data = null, $statusCode = 400)
    {
        $response = ['success' => false, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return safeJsonResponse($response, $statusCode);
    }
}

// ─── Logging ────────────────────────────────────────────────────

if (!function_exists('safeLog')) {
    function safeLog($message, $level = 'info', $context = [])
    {
        if (class_exists('Logger')) {
            Logger::$level($message, $context);
        } else {
            $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
            error_log("[$level] $message$contextStr");
        }
    }
}
