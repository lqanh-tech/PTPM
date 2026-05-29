<?php

require_once __DIR__ . '/database.php';

class PageManager {
    private $db;
    
    const TYPE_BLOG = 'blog';
    const TYPE_ABOUT = 'about';
    const TYPE_POLICY = 'policy';
    const TYPE_GUIDE = 'guide';
    
    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('blog', 'about', 'policy', 'guide') NOT NULL DEFAULT 'blog',
            slug VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            thumbnail VARCHAR(500),
            image_data LONGBLOB,
            image_type VARCHAR(100),
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

        $columns = $this->db->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('image_data', $columns)) {
            $this->db->exec("ALTER TABLE pages ADD COLUMN image_data LONGBLOB AFTER thumbnail");
        }
        if (!in_array('image_type', $columns)) {
            $this->db->exec("ALTER TABLE pages ADD COLUMN image_type VARCHAR(100) AFTER image_data");
        }
    }
    
    public function createSlug($title) {
        $slug = $this->removeVietnameseAccents($title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
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
    
    public function addPage($data) {
        try {
            $slug = $data['slug'] ?? $this->createSlug($data['title']);
            
            $sql = "INSERT INTO pages (type, slug, title, content, excerpt, thumbnail, image_data, image_type, meta_title, meta_description, status, author_id, position)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['type'] ?? self::TYPE_BLOG,
                $slug,
                $data['title'],
                $data['content'] ?? '',
                $data['excerpt'] ?? '',
                $data['thumbnail'] ?? null,
                $data['image_data'] ?? null,
                $data['image_type'] ?? null,
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
    
    public function updatePage($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['type', 'slug', 'title', 'content', 'excerpt', 'thumbnail', 'image_data', 'image_type',
                              'meta_title', 'meta_description', 'status', 'position'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
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
    
    public function deletePage($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM pages WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("PageManager deletePage error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPageById($id) {
        $stmt = $this->db->prepare("SELECT id, title, slug, content, type, status, position, created_at, updated_at FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getPageBySlug($slug) {
        $stmt = $this->db->prepare("SELECT id, title, slug, content, type, status, position, created_at, updated_at FROM pages WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($page) {
            $this->db->prepare("UPDATE pages SET view_count = view_count + 1 WHERE id = ?")->execute([$page['id']]);
        }
        
        return $page;
    }
    
    public function getPagesByType($type, $publishedOnly = false) {
        $sql = "SELECT id, title, slug, content, type, status, position, created_at, updated_at FROM pages WHERE type = ?";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY position ASC, created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllBlogs($publishedOnly = false) {
        return $this->getPagesByType(self::TYPE_BLOG, $publishedOnly);
    }
    
    public function getAllStaticPages($publishedOnly = false) {
        $sql = "SELECT id, title, slug, content, type, status, position, created_at, updated_at FROM pages WHERE type IN ('about', 'policy', 'guide')";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY type, position ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllPages() {
        $stmt = $this->db->query("SELECT id, title, slug, content, type, status, position, created_at, updated_at FROM pages ORDER BY type, position ASC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function uploadThumbnail($file) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Page upload error: Invalid file extension - " . $fileExtension);
            return null;
        }

        $imageData = file_get_contents($file['tmp_name']);
        if ($imageData === false) {
            error_log("Page upload error: Cannot read uploaded file");
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Page upload error: Invalid MIME type - " . $mimeType);
            return null;
        }

        return [
            'data' => $imageData,
            'type' => $mimeType,
            'name' => $file['name']
        ];
    }
    
    public static function getTypeLabel($type) {
        $labels = [
            'blog' => 'Bài viết Blog',
            'about' => 'Giới thiệu',
            'policy' => 'Chính sách',
            'guide' => 'Hướng dẫn'
        ];
        return $labels[$type] ?? $type;
    }
    
    public static function getStatusBadge($status) {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Nháp</span>',
            'published' => '<span class="badge bg-success">Đã xuất bản</span>',
            'hidden' => '<span class="badge bg-warning">Ẩn</span>'
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }
}
