<?php
/**
 * API Danh sách yêu thích (Wishlist)
 * Cho phép user lưu sản phẩm để mua sau
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

class WishlistAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Đảm bảo bảng wishlist tồn tại
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist (user_id, product_id),
            INDEX idx_user (user_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
    }
    
    /**
     * Lấy danh sách yêu thích của user
     */
    public function getWishlist() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            
            $sql = "SELECT w.id, w.product_id, w.created_at,
                           h.tenhanghoa, h.giathamkhao as gia, h.giakhuyenmai, h.hinhanh,
                           h.trang_thai as product_status,
                           COALESCE(t.soLuong, 0) as stock,
                           lh.tenloaihang as category
                    FROM wishlist w
                    INNER JOIN hanghoa h ON w.product_id = h.idhanghoa
                    LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                    LEFT JOIN tonkho t ON h.idhanghoa = t.idhanghoa
                    WHERE w.user_id = ?
                    ORDER BY w.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Xử lý URL hình ảnh và trạng thái
            foreach ($items as &$item) {
                if ($item['hinhanh']) {
                    // Sử dụng displayImage.php để hiển thị hình ảnh (giống các file khác)
                    $item['hinhanh_url'] = 'administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $item['hinhanh'];
                } else {
                    $item['hinhanh_url'] = null;
                }
                // Giá hiển thị
                $item['display_price'] = $item['giakhuyenmai'] > 0 ? $item['giakhuyenmai'] : $item['gia'];
                $item['has_discount'] = $item['giakhuyenmai'] > 0 && $item['giakhuyenmai'] < $item['gia'];
                
                // Xử lý trạng thái sản phẩm
                // trang_thai: 1 = Đang bán, 2 = Ngưng bán, 0 hoặc null = Không xác định
                $productStatus = intval($item['product_status'] ?? 1);
                $stockQty = intval($item['stock']);
                
                // Xác định trạng thái hiển thị
                if ($productStatus == 2) {
                    // Ngưng bán
                    $item['status_code'] = 'discontinued';
                    $item['status_text'] = 'Ngưng bán';
                    $item['can_buy'] = false;
                } elseif ($stockQty <= 0) {
                    // Hết hàng
                    $item['status_code'] = 'out_of_stock';
                    $item['status_text'] = 'Hết hàng';
                    $item['can_buy'] = false;
                } else {
                    // Còn hàng
                    $item['status_code'] = 'in_stock';
                    $item['status_text'] = 'Còn hàng';
                    $item['can_buy'] = true;
                }
            }
            
            return $this->success([
                'items' => $items,
                'count' => count($items)
            ]);
            
        } catch (Exception $e) {
            error_log("Get wishlist error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    /**
     * Thêm sản phẩm vào wishlist
     */
    public function addToWishlist() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Thiếu product_id');
            }
            
            // Kiểm tra sản phẩm tồn tại
            $checkSql = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return $this->error('Sản phẩm không tồn tại');
            }
            
            // Thêm vào wishlist (IGNORE nếu đã tồn tại)
            $sql = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $productId]);
            
            // Đếm số lượng trong wishlist (chỉ sản phẩm còn tồn tại)
            $countSql = "SELECT COUNT(*) as count FROM wishlist w 
                         INNER JOIN hanghoa h ON w.product_id = h.idhanghoa 
                         WHERE w.user_id = ?";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->success([
                'message' => 'Đã thêm vào danh sách yêu thích',
                'product_name' => $product['tenhanghoa'],
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Add to wishlist error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    /**
     * Xóa sản phẩm khỏi wishlist
     */
    public function removeFromWishlist() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $productId = $_POST['product_id'] ?? $_GET['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Thiếu product_id');
            }
            
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $productId]);
            
            // Đếm số lượng còn lại (chỉ sản phẩm còn tồn tại)
            $countSql = "SELECT COUNT(*) as count FROM wishlist w 
                         INNER JOIN hanghoa h ON w.product_id = h.idhanghoa 
                         WHERE w.user_id = ?";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->success([
                'message' => 'Đã xóa khỏi danh sách yêu thích',
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Remove from wishlist error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    /**
     * Kiểm tra sản phẩm có trong wishlist không
     */
    public function checkInWishlist() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->success(['in_wishlist' => false, 'count' => 0]);
            }
            
            $userId = $_SESSION['USER'];
            $productId = $_GET['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Thiếu product_id');
            }
            
            $sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $productId]);
            $inWishlist = $stmt->rowCount() > 0;
            
            // Đếm tổng số (chỉ sản phẩm còn tồn tại)
            $countSql = "SELECT COUNT(*) as count FROM wishlist w 
                         INNER JOIN hanghoa h ON w.product_id = h.idhanghoa 
                         WHERE w.user_id = ?";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->success([
                'in_wishlist' => $inWishlist,
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Check wishlist error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    /**
     * Lấy số lượng sản phẩm trong wishlist (chỉ đếm sản phẩm còn tồn tại)
     */
    public function getCount() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->success(['count' => 0]);
            }
            
            $userId = $_SESSION['USER'];
            
            // Chỉ đếm những sản phẩm còn tồn tại trong hanghoa
            $sql = "SELECT COUNT(*) as count FROM wishlist w 
                    INNER JOIN hanghoa h ON w.product_id = h.idhanghoa 
                    WHERE w.user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->success(['count' => $count]);
            
        } catch (Exception $e) {
            error_log("Get wishlist count error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    /**
     * Toggle wishlist (thêm nếu chưa có, xóa nếu đã có)
     */
    public function toggleWishlist() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Thiếu product_id');
            }
            
            // Kiểm tra đã có trong wishlist chưa
            $checkSql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([$userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                // Đã có -> xóa
                $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $productId]);
                $action = 'removed';
                $message = 'Đã xóa khỏi danh sách yêu thích';
            } else {
                // Chưa có -> thêm
                $sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $productId]);
                $action = 'added';
                $message = 'Đã thêm vào danh sách yêu thích';
            }
            
            // Đếm số lượng (chỉ sản phẩm còn tồn tại)
            $countSql = "SELECT COUNT(*) as count FROM wishlist w 
                         INNER JOIN hanghoa h ON w.product_id = h.idhanghoa 
                         WHERE w.user_id = ?";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->success([
                'action' => $action,
                'message' => $message,
                'in_wishlist' => $action === 'added',
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Toggle wishlist error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    private function success($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Router
$api = new WishlistAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $api->getWishlist();
        break;
    case 'add':
        $api->addToWishlist();
        break;
    case 'remove':
        $api->removeFromWishlist();
        break;
    case 'check':
        $api->checkInWishlist();
        break;
    case 'count':
        $api->getCount();
        break;
    case 'toggle':
        $api->toggleWishlist();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
