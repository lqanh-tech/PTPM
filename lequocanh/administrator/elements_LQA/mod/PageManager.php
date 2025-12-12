<?php
/**
 * PageManager - Quản lý Blog và Trang tĩnh
 */
require_once __DIR__ . '/database.php';

class PageManager {
    private $db;
    
    // Các loại trang
    const TYPE_BLOG = 'blog';
    const TYPE_ABOUT = 'about';
    const TYPE_POLICY = 'policy';
    const TYPE_GUIDE = 'guide';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Tạo bảng nếu chưa tồn tại
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('blog', 'about', 'policy', 'guide') NOT NULL DEFAULT 'blog',
            slug VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            thumbnail VARCHAR(500),
            meta_title VARCHAR(255),
            meta_description TEXT,
            status ENUM('draft', 'published', 'hidden') DEFAULT 'draft',
            author_id VARCHAR(50),
            view_count INT DEFAULT 0,
            position INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Tạo slug từ tiêu đề
     */
    public function createSlug($title) {
        $slug = $this->removeVietnameseAccents($title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Kiểm tra trùng lặp
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Bỏ dấu tiếng Việt
     */
    private function removeVietnameseAccents($str) {
        $accents = [
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
            'Đ'
        ];
        $noAccents = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
            'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
            'E','E','E','E','E','E','E','E','E','E','E',
            'I','I','I','I','I',
            'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
            'U','U','U','U','U','U','U','U','U','U','U',
            'Y','Y','Y','Y','Y',
            'D'
        ];
        return str_replace($accents, $noAccents, $str);
    }
    
    /**
     * Kiểm tra slug đã tồn tại
     */
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM pages WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Thêm trang mới
     */
    public function addPage($data) {
        try {
            $slug = $data['slug'] ?? $this->createSlug($data['title']);
            
            $sql = "INSERT INTO pages (type, slug, title, content, excerpt, thumbnail, meta_title, meta_description, status, author_id, position)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['type'] ?? self::TYPE_BLOG,
                $slug,
                $data['title'],
                $data['content'] ?? '',
                $data['excerpt'] ?? '',
                $data['thumbnail'] ?? null,
                $data['meta_title'] ?? $data['title'],
                $data['meta_description'] ?? '',
                $data['status'] ?? 'draft',
                $data['author_id'] ?? null,
                $data['position'] ?? 0
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("PageManager addPage error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật trang
     */
    public function updatePage($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['type', 'slug', 'title', 'content', 'excerpt', 'thumbnail', 
                              'meta_title', 'meta_description', 'status', 'position'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($fields)) return false;
            
            $params[] = $id;
            $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("PageManager updatePage error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa trang
     */
    public function deletePage($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM pages WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("PageManager deletePage error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy trang theo ID
     */
    public function getPageById($id) {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy trang theo slug
     */
    public function getPageBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tăng view count
        if ($page) {
            $this->db->prepare("UPDATE pages SET view_count = view_count + 1 WHERE id = ?")->execute([$page['id']]);
        }
        
        return $page;
    }
    
    /**
     * Lấy tất cả trang theo loại
     */
    public function getPagesByType($type, $publishedOnly = false) {
        $sql = "SELECT * FROM pages WHERE type = ?";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY position ASC, created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy tất cả bài blog
     */
    public function getAllBlogs($publishedOnly = false) {
        return $this->getPagesByType(self::TYPE_BLOG, $publishedOnly);
    }
    
    /**
     * Lấy tất cả trang tĩnh (about, policy, guide)
     */
    public function getAllStaticPages($publishedOnly = false) {
        $sql = "SELECT * FROM pages WHERE type IN ('about', 'policy', 'guide')";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY type, position ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy tất cả trang
     */
    public function getAllPages() {
        $stmt = $this->db->query("SELECT * FROM pages ORDER BY type, position ASC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Upload thumbnail
     */
    public function uploadThumbnail($file) {
        $uploadDir = __DIR__ . '/../../uploads/pages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'page_' . time() . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/pages/' . $filename;
        }
        
        return null;
    }
    
    /**
     * Lấy label cho loại trang
     */
    public static function getTypeLabel($type) {
        $labels = [
            'blog' => 'Bài viết Blog',
            'about' => 'Giới thiệu',
            'policy' => 'Chính sách',
            'guide' => 'Hướng dẫn'
        ];
        return $labels[$type] ?? $type;
    }
    
    /**
     * Lấy badge class cho status
     */
    public static function getStatusBadge($status) {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Nháp</span>',
            'published' => '<span class="badge bg-success">Đã xuất bản</span>',
            'hidden' => '<span class="badge bg-warning">Ẩn</span>'
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }
}
