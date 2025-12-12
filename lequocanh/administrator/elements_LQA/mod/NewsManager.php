<?php

/**
 * News Manager
 * Quản lý tin tức
 */

require_once 'database.php';

class NewsManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lấy tất cả tin tức đang được xuất bản
     */
    public function getPublishedNews($limit = 10)
    {
        try {
            $limit = (int)$limit;
            $sql = "SELECT id, title, slug, summary, content, featured_image, author_id, is_published, published_date, created_at, updated_at FROM news WHERE is_published = 1 ORDER BY published_date DESC LIMIT " . $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting published news: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tất cả tin tức (cho admin)
     */
    public function getAllNews()
    {
        try {
            $sql = "SELECT id, title, slug, summary, content, featured_image, author_id, is_published, published_date, created_at, updated_at FROM news ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all news: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tin tức theo ID
     */
    public function getNewsById($id)
    {
        try {
            $sql = "SELECT id, title, slug, summary, content, featured_image, author_id, is_published, published_date, created_at, updated_at FROM news WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting news by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Thêm tin tức mới
     */
    public function addNews($title, $content, $image_url, $author, $is_published, $published_at = null)
    {
        try {
            // Kiểm tra title và content không trống
            if (empty($title) || empty($content)) {
                error_log("Error adding news: Title or content is empty");
                return false;
            }

            // Tạo slug từ title
            $slug = $this->createSlug($title);

            // Chuẩn bị summary từ content
            $summary = substr(strip_tags($content), 0, 200);

            // SQL insert - sử dụng cấu trúc bảng thực tế
            // Bảng news có: id, title, slug, summary, content, featured_image, author_id, category, tags, published_date, is_published, view_count, created_at, updated_at
            $sql = "INSERT INTO news (title, slug, summary, content, featured_image, is_published, published_date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);

            $params = [$title, $slug, $summary, $content, $image_url, $is_published];

            error_log("Executing addNews with params: " . json_encode($params));

            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error adding news - SQL Error: " . json_encode($errorInfo));
                return false;
            }

            error_log("News added successfully. ID: " . $this->db->lastInsertId());
            return $result;
        } catch (Exception $e) {
            error_log("Exception in addNews: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Cập nhật tin tức
     */
    public function updateNews($id, $title, $content, $image_url, $author, $is_published, $published_at = null)
    {
        try {
            // Lấy tin tức hiện tại để giữ slug
            $current = $this->getNewsById($id);
            if (!$current) {
                return false;
            }

            // Nếu title thay đổi, tạo slug mới
            $slug = ($title !== $current['title']) ? $this->createSlug($title) : $current['slug'];

            // Chuẩn bị summary từ content
            $summary = substr(strip_tags($content), 0, 200);

            // Update - sử dụng cấu trúc bảng thực tế (không có cột 'author')
            $sql = "UPDATE news SET title = ?, slug = ?, summary = ?, content = ?, featured_image = ?,
                           is_published = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute([$title, $slug, $summary, $content, $image_url, $is_published, $id]);
        } catch (Exception $e) {
            error_log("Error updating news: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa tin tức
     */
    public function deleteNews($id)
    {
        try {
            $sql = "DELETE FROM news WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting news: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload hình ảnh tin tức
     */
    public function uploadNewsImage($file)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("News upload error: Invalid file extension - " . $fileExtension);
            return false;
        }

        $newFileName = 'news_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadDir = __DIR__ . '/../../../administrator/uploads/';
        $uploadPath = $uploadDir . $newFileName;

        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("News upload error: Cannot create upload directory - " . $uploadDir);
                return false;
            }
        }

        // Kiểm tra quyền ghi
        if (!is_writable($uploadDir)) {
            error_log("News upload error: Upload directory is not writable - " . $uploadDir);
            return false;
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return '/lequocanh/administrator/uploads/' . $newFileName;
        }

        error_log("News upload error: Cannot move uploaded file to - " . $uploadPath);
        return false;
    }

    /**
     * Tạo slug từ tiêu đề
     */
    private function createSlug($title)
    {
        try {
            // Chuyển về chữ thường
            $slug = mb_strtolower($title, 'UTF-8');

            // Thay thế ký tự có dấu
            $replacements = [
                'à' => 'a',
                'á' => 'a',
                'ả' => 'a',
                'ã' => 'a',
                'ạ' => 'a',
                'ă' => 'a',
                'ằ' => 'a',
                'ắ' => 'a',
                'ẳ' => 'a',
                'ẵ' => 'a',
                'ặ' => 'a',
                'â' => 'a',
                'ầ' => 'a',
                'ấ' => 'a',
                'ẩ' => 'a',
                'ẫ' => 'a',
                'ậ' => 'a',
                'đ' => 'd',
                'è' => 'e',
                'é' => 'e',
                'ẻ' => 'e',
                'ẽ' => 'e',
                'ẹ' => 'e',
                'ê' => 'e',
                'ề' => 'e',
                'ế' => 'e',
                'ể' => 'e',
                'ễ' => 'e',
                'ệ' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'ỉ' => 'i',
                'ĩ' => 'i',
                'ị' => 'i',
                'ò' => 'o',
                'ó' => 'o',
                'ỏ' => 'o',
                'õ' => 'o',
                'ọ' => 'o',
                'ô' => 'o',
                'ồ' => 'o',
                'ố' => 'o',
                'ổ' => 'o',
                'ỗ' => 'o',
                'ộ' => 'o',
                'ơ' => 'o',
                'ờ' => 'o',
                'ớ' => 'o',
                'ở' => 'o',
                'ỡ' => 'o',
                'ợ' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'ủ' => 'u',
                'ũ' => 'u',
                'ụ' => 'u',
                'ư' => 'u',
                'ừ' => 'u',
                'ứ' => 'u',
                'ử' => 'u',
                'ữ' => 'u',
                'ự' => 'u',
                'ỳ' => 'y',
                'ý' => 'y',
                'ỷ' => 'y',
                'ỹ' => 'y',
                'ỵ' => 'y',
            ];

            $slug = strtr($slug, $replacements);

            // Thay thế khoảng trắng và ký tự đặc biệt bằng dấu gạch ngang
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');

            // Nếu slug trống, dùng time()
            if (empty($slug)) {
                $slug = 'news-' . time();
            }

            error_log("Generated slug: '$slug' from title: '$title'");

            // Thêm unique ID nếu slug đã tồn tại
            $originalSlug = $slug;
            $counter = 1;
            $maxAttempts = 100;

            while ($this->slugExists($slug) && $counter < $maxAttempts) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            if ($counter >= $maxAttempts) {
                error_log("Warning: Max attempts to create unique slug reached");
                // Dùng timestamp để đảm bảo unique
                $slug = $originalSlug . '-' . time();
            }

            error_log("Final slug: '$slug'");
            return $slug;
        } catch (Exception $e) {
            error_log("Error in createSlug: " . $e->getMessage());
            // Fallback: dùng timestamp
            return 'news-' . time();
        }
    }

    /**
     * Kiểm tra slug đã tồn tại chưa
     */
    private function slugExists($slug)
    {
        try {
            $sql = "SELECT COUNT(*) FROM news WHERE slug = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $result = $stmt->fetchColumn();
            error_log("slugExists check for '$slug': " . ($result > 0 ? "exists" : "not exists"));
            return $result > 0;
        } catch (Exception $e) {
            error_log("Error in slugExists: " . $e->getMessage());
            return false;
        }
    }
}
