<?php

require_once __DIR__ . '/database.php';

class BannerManager
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function getActiveBanners()
    {
        try {
            $sql = "SELECT id, tieude, hinhanh, lienket, trangthai, position, ngay_tao FROM banners WHERE is_active = 1 ORDER BY position ASC, created_at DESC";
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
            $sql = "SELECT id, tieude, hinhanh, lienket, trangthai, position, ngay_tao FROM banners ORDER BY position ASC, created_at DESC";
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
            $sql = "SELECT id, tieude, hinhanh, lienket, trangthai, position, ngay_tao FROM banners WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting banner by ID: " . $e->getMessage());
            return null;
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

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Banner upload error: Invalid file extension - " . $fileExtension);
            return false;
        }

        $imageData = file_get_contents($file['tmp_name']);
        if ($imageData === false) {
            error_log("Banner upload error: Cannot read uploaded file");
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Banner upload error: Invalid MIME type - " . $mimeType);
            return false;
        }

        return [
            'data' => $imageData,
            'type' => $mimeType,
            'name' => $file['name']
        ];
    }

    public function addBanner($title, $description, $image_url, $link_url, $position, $is_active, $image_data = null, $image_type = null)
    {
        try {
            $sql = "INSERT INTO banners (title, description, image_url, image_data, image_type, link_url, position, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $image_url, $image_data, $image_type, $link_url, $position, $is_active]);
        } catch (Exception $e) {
            error_log("Error adding banner: " . $e->getMessage());
            return false;
        }
    }

    public function updateBanner($id, $title, $description, $image_url, $link_url, $position, $is_active, $image_data = null, $image_type = null)
    {
        try {
            if ($image_data !== null) {
                $sql = "UPDATE banners SET title = ?, description = ?, image_url = ?, image_data = ?, image_type = ?,
                               link_url = ?, position = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$title, $description, $image_url, $image_data, $image_type, $link_url, $position, $is_active, $id]);
            } else {
                $sql = "UPDATE banners SET title = ?, description = ?, image_url = ?,
                               link_url = ?, position = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$title, $description, $image_url, $link_url, $position, $is_active, $id]);
            }
        } catch (Exception $e) {
            error_log("Error updating banner: " . $e->getMessage());
            return false;
        }
    }
}