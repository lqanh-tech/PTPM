<?php

require_once 'database.php';

class BannerManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getActiveBanners()
    {
        try {
            $sql = "SELECT * FROM banners WHERE is_active = 1 ORDER BY position ASC, created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active banners: " . $e->getMessage());
            return [];
        }
    }

    public function getAllBanners()
    {
        try {
            $sql = "SELECT * FROM banners ORDER BY position ASC, created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all banners: " . $e->getMessage());
            return [];
        }
    }

    public function getBannerById($id)
    {
        try {
            $sql = "SELECT * FROM banners WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting banner by ID: " . $e->getMessage());
            return null;
        }
    }

    public function addBanner($title, $description, $image_url, $link_url, $position, $is_active)
    {
        try {
            $sql = "INSERT INTO banners (title, description, image_url, link_url, position, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $image_url, $link_url, $position, $is_active]);
        } catch (Exception $e) {
            error_log("Error adding banner: " . $e->getMessage());
            return false;
        }
    }

    public function updateBanner($id, $title, $description, $image_url, $link_url, $position, $is_active)
    {
        try {
            $sql = "UPDATE banners SET title = ?, description = ?, image_url = ?, 
                           link_url = ?, position = ?, is_active = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $image_url, $link_url, $position, $is_active, $id]);
        } catch (Exception $e) {
            error_log("Error updating banner: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBanner($id)
    {
        try {
            $sql = "DELETE FROM banners WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting banner: " . $e->getMessage());
            return false;
        }
    }

    public function uploadBannerImage($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("Banner upload error: " . $file['error']);
            return false;
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            error_log("Banner upload error: Invalid file type - " . $file['type']);
            return false;
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Banner upload error: Invalid file extension - " . $fileExtension);
            return false;
        }

        $newFileName = 'banner_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadDir = __DIR__ . '/../../../administrator/uploads/';
        $uploadPath = $uploadDir . $newFileName;

        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Banner upload error: Cannot create upload directory - " . $uploadDir);
                return false;
            }
        }

        if (!is_writable($uploadDir)) {
            error_log("Banner upload error: Upload directory is not writable - " . $uploadDir);
            return false;
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return '/lequocanh/administrator/uploads/' . $newFileName;
        }

        error_log("Banner upload error: Cannot move uploaded file to - " . $uploadPath);
        return false;
    }
}