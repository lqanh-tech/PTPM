<?php

function safeRequire($filename, $type = 'mod', $once = true) {

    if (class_exists('PathResolver')) {
        try {
            if ($type === 'mod') {

                $className = (substr($filename, -4) === '.php') ? substr($filename, 0, -4) : $filename;
                PathResolver::requireClass($className);
                return true;
            } else {
                PathResolver::requireFile("$type/$filename");
                return true;
            }
        } catch (Exception $e) {

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
            if ($once) {
                require_once $path;
            } else {
                require $path;
            }
            return true;
        }
    }
    
    $errorMsg = "Cannot find file: $filename of type $type";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    return false;
}

function safeInclude($filename, $type = 'includes', $once = true) {
    return safeRequire($filename, $type, $once);
}

function safePath($filename, $type = 'config') {

    if (class_exists('PathResolver')) {
        try {
            return PathResolver::getPath("$type/$filename");
        } catch (Exception $e) {

        }
    }
    
    $paths = [];
    
    switch ($type) {
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
            return $path;
        }
    }
    
    return false;
}