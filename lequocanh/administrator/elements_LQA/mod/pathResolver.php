<?php

if (!defined('BASE_PATH')) {
    $constantsPaths = [
        __DIR__ . '/../config/constants.php',
        '../config/constants.php',
        '../../elements_LQA/config/constants.php',
        './administrator/elements_LQA/config/constants.php'
    ];
    
    $constantsLoaded = false;
    foreach ($constantsPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $constantsLoaded = true;
            break;
        }
    }
    
    if (!$constantsLoaded) {

        define('BASE_PATH', realpath(__DIR__ . '/../../../../'));
        define('ADMIN_PATH', BASE_PATH . '/administrator');
        define('ELEMENTS_PATH', ADMIN_PATH . '/elements_LQA');
        define('MOD_PATH', ELEMENTS_PATH . '/mod');
    }
}

class PathResolver {

    private static $basePaths = [
        '../mod/',
        './elements_LQA/mod/',
        './administrator/elements_LQA/mod/',
        '../../elements_LQA/mod/'
    ];
    
    public static function requireClass($className) {

        $filename = (substr($className, -4) === '.php') ? $className : $className . '.php';
        
        if (defined('MOD_PATH')) {
            $absolutePath = MOD_PATH . '/' . basename($filename);
            if (file_exists($absolutePath)) {
                require_once $absolutePath;
                return true;
            }
        }
        
        $dirPath = __DIR__ . '/' . basename($filename);
        if (file_exists($dirPath)) {
            require_once $dirPath;
            return true;
        }
        
        foreach (self::$basePaths as $basePath) {
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
        
        $errorMsg = "Cannot find class file: $filename. Tried paths: " . 
                    implode(', ', array_merge(
                        [defined('MOD_PATH') ? MOD_PATH . '/' . basename($filename) : 'MOD_PATH not defined'],
                        [__DIR__ . '/' . basename($filename)],
                        array_map(function($p) use ($filename) { return $p . basename($filename); }, self::$basePaths)
                    ));
                    
        if (class_exists('Logger')) {
            Logger::error($errorMsg);
        } else {
            error_log($errorMsg);
        }
        
        throw new Exception($errorMsg);
    }
    
    public static function getPath($relativePath) {

        $searchPaths = [
            $relativePath,
            './elements_LQA/' . $relativePath,
            './administrator/elements_LQA/' . $relativePath,
            '../' . $relativePath,
            '../../' . $relativePath,
            __DIR__ . '/../' . $relativePath,
            defined('BASE_PATH') ? BASE_PATH . '/' . $relativePath : null,
            defined('ELEMENTS_PATH') ? ELEMENTS_PATH . '/' . $relativePath : null
        ];
        
        $searchPaths = array_filter($searchPaths);
        
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        $errorMsg = "Cannot find file: $relativePath. Tried paths: " . implode(', ', $searchPaths);
        
        if (class_exists('Logger')) {
            Logger::error($errorMsg);
        } else {
            error_log($errorMsg);
        }
        
        throw new Exception($errorMsg);
    }
    
    public static function includeFile($relativePath, $once = true) {
        $path = self::getPath($relativePath);
        
        if ($once) {
            include_once $path;
        } else {
            include $path;
        }
        
        return true;
    }
    
    public static function requireFile($relativePath, $once = true) {
        $path = self::getPath($relativePath);
        
        if ($once) {
            require_once $path;
        } else {
            require $path;
        }
        
        return true;
    }
}