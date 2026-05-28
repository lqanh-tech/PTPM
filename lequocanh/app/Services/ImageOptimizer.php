<?php

declare(strict_types=1);

/**
 * Image Optimizer - Tối ưu hình ảnh
 * - Convert to WebP
 * - Resize images
 * - Compress quality
 */

class ImageOptimizer
{
    private int $quality = 85;
    private int $maxWidth = 1200;
    private int $maxHeight = 1200;
    
    public function __construct(int $quality = 85, int $maxWidth = 1200, int $maxHeight = 1200)
    {
        $this->quality = $quality;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }
    
    /**
     * Convert image to WebP format
     */
    public function convertToWebP(string $sourcePath, ?string $destPath = null): ?string
    {
        if (!file_exists($sourcePath)) {
            return null;
        }
        
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return null;
        }
        
        $destPath = $destPath ?: pathinfo($sourcePath, PATHINFO_DIRNAME) . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';
        
        $imageData = file_get_contents($sourcePath);
        $image = imagecreatefromstring($imageData);
        
        if (!$image) {
            return null;
        }
        
        // Resize if needed
        $image = $this->resize($image);
        
        imagewebp($image, $destPath, $this->quality);
        imagedestroy($image);
        
        return file_exists($destPath) ? $destPath : null;
    }
    
    /**
     * Get optimized image path (WebP if available, fallback to original)
     */
    public function getOptimizedPath(string $originalPath): string
    {
        $webpPath = pathinfo($originalPath, PATHINFO_DIRNAME) . '/' . pathinfo($originalPath, PATHINFO_FILENAME) . '.webp';
        
        if (file_exists($webpPath)) {
            return $webpPath;
        }
        
        // Auto-convert on first request
        if ($this->convertToWebP($originalPath, $webpPath)) {
            return $webpPath;
        }
        
        return $originalPath;
    }
    
    /**
     * Resize image maintaining aspect ratio
     */
    private function resize($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return $image;
        }
        
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        
        return $newImage;
    }
    
    /**
     * Generate srcset for responsive images
     */
    public function generateSrcSet(string $basePath): string
    {
        $sizes = [320, 640, 768, 1024, 1200];
        $srcset = [];
        
        foreach ($sizes as $size) {
            $resizedPath = pathinfo($basePath, PATHINFO_DIRNAME) . '/' . pathinfo($basePath, PATHINFO_FILENAME) . "-{$size}w.webp";
            
            if (!file_exists($resizedPath)) {
                $this->resizeImage($basePath, $resizedPath, $size);
            }
            
            if (file_exists($resizedPath)) {
                $srcset[] = "{$resizedPath} {$size}w";
            }
        }
        
        return implode(', ', $srcset);
    }
    
    /**
     * Resize image to specific width
     */
    private function resizeImage(string $sourcePath, string $destPath, int $targetWidth): bool
    {
        $imageData = file_get_contents($sourcePath);
        $image = imagecreatefromstring($imageData);
        
        if (!$image) {
            return false;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width <= $targetWidth) {
            imagewebp($image, $destPath, $this->quality);
            imagedestroy($image);
            return true;
        }
        
        $ratio = $targetWidth / $width;
        $newHeight = (int)($height * $ratio);
        
        $newImage = imagecreatetruecolor($targetWidth, $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $targetWidth, $newHeight, $width, $height);
        
        imagewebp($newImage, $destPath, $this->quality);
        imagedestroy($image);
        imagedestroy($newImage);
        
        return true;
    }
}