<?php

class UploadSecurity {
    
    private static $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
    ];
    
    private static $allowedExtensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx']
    ];
    
    private static $maxSizes = [
        'image' => 5 * 1024 * 1024,
        'document' => 10 * 1024 * 1024
    ];
    
    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'exe', 'sh', 'bat', 'cmd', 'com', 'scr',
        'js', 'vbs', 'wsf', 'wsh',
        'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py',
        'htaccess', 'htpasswd'
    ];
    
    public static function validate($file, $type = 'image') {
        $result = [
            'valid' => false,
            'error' => null,
            'sanitized_name' => null,
            'mime_type' => null
        ];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $result['error'] = 'Invalid file upload';
            return $result;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['error'] = 'File quá lớn';
                return $result;
            case UPLOAD_ERR_NO_FILE:
                $result['error'] = 'Không có file được upload';
                return $result;
            default:
                $result['error'] = 'Lỗi upload không xác định';
                return $result;
        }
        
        $maxSize = self::$maxSizes[$type] ?? self::$maxSizes['image'];
        if ($file['size'] > $maxSize) {
            $result['error'] = 'File quá lớn. Tối đa ' . self::formatBytes($maxSize);
            return $result;
        }
        
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if (in_array($extension, self::$dangerousExtensions)) {
            self::logSecurityEvent('dangerous_file_upload', [
                'filename' => $originalName,
                'extension' => $extension
            ]);
            $result['error'] = 'Loại file không được phép';
            return $result;
        }
        
        $allowedExt = self::$allowedExtensions[$type] ?? self::$allowedExtensions['image'];
        if (!in_array($extension, $allowedExt)) {
            $result['error'] = 'Định dạng file không hợp lệ. Cho phép: ' . implode(', ', $allowedExt);
            return $result;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = self::$allowedMimeTypes[$type] ?? self::$allowedMimeTypes['image'];
        if (!in_array($mimeType, $allowedMimes)) {
            self::logSecurityEvent('mime_type_mismatch', [
                'filename' => $originalName,
                'claimed_type' => $file['type'],
                'actual_type' => $mimeType
            ]);
            $result['error'] = 'Nội dung file không khớp với định dạng';
            return $result;
        }
        
        if ($type === 'image') {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                self::logSecurityEvent('fake_image_upload', [
                    'filename' => $originalName,
                    'mime_type' => $mimeType
                ]);
                $result['error'] = 'File không phải là hình ảnh hợp lệ';
                return $result;
            }
        }
        
        $content = file_get_contents($file['tmp_name']);
        if (self::containsPhpCode($content)) {
            self::logSecurityEvent('php_code_in_upload', [
                'filename' => $originalName
            ]);
            $result['error'] = 'File chứa mã độc hại';
            return $result;
        }
        
        $safeName = self::generateSafeFilename($originalName);
        
        $result['valid'] = true;
        $result['sanitized_name'] = $safeName;
        $result['mime_type'] = $mimeType;
        
        return $result;
    }
    
    private static function containsPhpCode($content) {
        $patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<\?[^x]/i',
            '/<script\s+language\s*=\s*["\']?php/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function generateSafeFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        $uniqueId = bin2hex(random_bytes(8));
        $timestamp = date('Ymd_His');
        
        return $timestamp . '_' . $uniqueId . '.' . $extension;
    }
    
    public static function moveUploadedFile($file, $destination, $type = 'image') {

        $validation = self::validate($file, $type);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }
        
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $finalPath = $dir . '/' . $validation['sanitized_name'];
        
        if (move_uploaded_file($file['tmp_name'], $finalPath)) {

            chmod($finalPath, 0644);
            
            return [
                'success' => true,
                'path' => $finalPath,
                'filename' => $validation['sanitized_name'],
                'mime_type' => $validation['mime_type']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Không thể lưu file'
        ];
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private static function logSecurityEvent($event, $details = []) {

        $possibleLogDirs = [
            __DIR__ . '/../logs',
            dirname(__DIR__) . '/logs',
            '/var/www/html/lequocanh/logs',
            'D:/PHP_WS/lequocanh/logs'
        ];
        
        $logDir = null;
        foreach ($possibleLogDirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $logDir = $dir;
                break;
            }
        }
        
        if (!$logDir) {
            $logDir = __DIR__ . '/../logs';
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/upload_security.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user' => $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? 'anonymous',
            'details' => $details
        ];
        
        @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
