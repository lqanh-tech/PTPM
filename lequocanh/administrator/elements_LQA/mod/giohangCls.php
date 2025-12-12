<?php
require_once 'database.php';

class GioHang
{
    private $db;
    private $cart_cache = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function getUserId()
    {
        if (isset($_SESSION['USER'])) {
            error_log("User logged in as: " . $_SESSION['USER']);
            return $_SESSION['USER'];
        } elseif (isset($_SESSION['ADMIN'])) {
            error_log("Admin logged in as: " . $_SESSION['ADMIN']);
            return $_SESSION['ADMIN'];
        }
        error_log("No user logged in");
        return null;
    }

    // Kiểm tra xem người dùng đã đăng nhập chưa
    public function isUserLoggedIn()
    {
        return isset($_SESSION['USER']); // Chỉ tính người dùng thường, không tính admin
    }

    // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không (chỉ user thường)
    public function canUseCart()
    {
        return isset($_SESSION['USER']);
    }

    // Kiểm tra xem người dùng có phải là admin không
    public function isAdmin()
    {
        return isset($_SESSION['ADMIN']);
    }

    // Thêm phương thức để lấy session ID hiện tại
    private function getSessionId()
    {
        return session_id();
    }

    public function addToCart($productId, $quantity = 1)
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể thêm vào giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();
        $sessionId = $this->getSessionId();
        error_log("Adding to cart - UserID: " . $userId . ", SessionID: " . $sessionId . ", ProductID: " . $productId . ", Quantity: " . $quantity);

        try {
            // Kiểm tra sản phẩm có tồn tại trong bảng hanghoa không
            $checkProduct = "SELECT idhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmtProduct = $this->db->prepare($checkProduct);
            $stmtProduct->execute([$productId]);

            if (!$stmtProduct->fetch()) {
                error_log("Product does not exist: " . $productId);
                return false;
            }

            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $checkSql = "SELECT quantity FROM tbl_giohang WHERE user_id = ? AND product_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId, $productId]);
            $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Nếu sản phẩm đã tồn tại, cập nhật số lượng
                $sql = "UPDATE tbl_giohang SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$quantity, $userId, $productId]);
            } else {
                // Nếu sản phẩm chưa tồn tại, thêm mới
                $sql = "INSERT INTO tbl_giohang (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$userId, $sessionId, $productId, $quantity]);
            }

            // Xóa cache khi thêm sản phẩm mới
            $this->clearCartCache();
            return $result;
        } catch (PDOException $e) {
            error_log("Error in cart operation: " . $e->getMessage());
            return false;
        }
    }

    public function removeFromCart($productId)
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể xóa khỏi giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();

        try {
            $sql = "DELETE FROM tbl_giohang WHERE user_id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $productId]);

            // Xóa cache khi xóa sản phẩm
            $this->clearCartCache();

            return $result;
        } catch (PDOException $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            return false;
        }
    }

    public function updateCart($productId, $quantity)
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể cập nhật giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();

        try {
            if ($quantity > 0) {
                $sql = "UPDATE tbl_giohang SET quantity = ? WHERE user_id = ? AND product_id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$quantity, $userId, $productId]);
            } else {
                return $this->removeFromCart($productId);
            }
        } catch (PDOException $e) {
            error_log("Error updating cart: " . $e->getMessage());
            return false;
        }
    }

    public function getCart()
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể lấy giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return [];
        }

        // Nếu đã có cache, trả về từ cache
        if ($this->cart_cache !== null) {
            return $this->cart_cache;
        }

        $userId = $this->getUserId();

        try {
            // Sử dụng LEFT JOIN thay vì INNER JOIN
            // LEFT JOIN sẽ lấy tất cả các mục trong giỏ hàng, ngay cả khi không tìm thấy sản phẩm
            // ƯU TIÊN giakhuyenmai nếu có, nếu không dùng giathamkhao
            $sql = "SELECT g.product_id, g.quantity, h.tenhanghoa, 
                   h.giathamkhao,
                   h.giakhuyenmai,
                   h.hinhanh,
                   CASE 
                       WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao 
                       THEN h.giakhuyenmai 
                       ELSE h.giathamkhao 
                   END as gia_hien_tai
                   FROM tbl_giohang g
                   LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
                   WHERE g.user_id = ?";

            error_log("User: Executing cart query with userId: " . $userId);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            $cart = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Debug log để kiểm tra dữ liệu hình ảnh
                error_log("Cart item: Product ID=" . $row['product_id'] . ", Image ID=" . ($row['hinhanh'] ?? 'NULL') . ", Name=" . ($row['tenhanghoa'] ?? 'Unknown'));

                // Kiểm tra và chuyển đổi hinhanh thành số nguyên nếu có giá trị
                $hinhanhValue = null;
                if (isset($row['hinhanh']) && $row['hinhanh'] !== null && $row['hinhanh'] !== '') {
                    $hinhanhValue = (int)$row['hinhanh'];
                    error_log("Đã chuyển đổi hinhanh thành số nguyên: " . $hinhanhValue);
                }

                // Xác định giá hiện tại (ưu tiên giá khuyến mãi)
                $giaHienTai = $row['gia_hien_tai'] ?? $row['giathamkhao'] ?? 0;
                $hasDiscount = isset($row['giakhuyenmai']) && $row['giakhuyenmai'] > 0 && $row['giakhuyenmai'] < $row['giathamkhao'];

                // Include cart items even if some fields are NULL
                $cart[] = [
                    'product_id' => $row['product_id'],
                    'tenhanghoa' => $row['tenhanghoa'] ?? 'Unknown Product',
                    'giathamkhao' => $row['giathamkhao'] ?? 0,
                    'giakhuyenmai' => $row['giakhuyenmai'] ?? null,
                    'gia_hien_tai' => $giaHienTai,
                    'has_discount' => $hasDiscount,
                    'quantity' => $row['quantity'],
                    'hinhanh' => $hinhanhValue,
                    'name' => $row['tenhanghoa'] ?? 'Unknown Product' // Thêm trường name để đảm bảo tương thích
                ];
            }

            // Lưu vào cache
            $this->cart_cache = $cart;

            return $cart;
        } catch (PDOException $e) {
            error_log("Error getting cart: " . $e->getMessage());
            return [];
        }
    }

    public function clearCart()
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể xóa giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            error_log("Session USER: " . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'NOT SET'));
            error_log("Session ADMIN: " . (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : 'NOT SET'));
            return false;
        }

        $userId = $this->getUserId();
        error_log("Attempting to clear cart for user: $userId");

        try {
            $sql = "DELETE FROM tbl_giohang WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId]);
            
            $rowsDeleted = $stmt->rowCount();
            error_log("Cart clear result: " . ($result ? 'success' : 'failed') . ", rows deleted: $rowsDeleted");

            // Xóa cache khi xóa giỏ hàng
            $this->clearCartCache();

            return $result;
        } catch (PDOException $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }

    public function getCartItemCount()
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            return 0;
        }

        $userId = $this->getUserId();

        // Nếu có user đăng nhập, đếm từ database
        if ($userId) {
            try {
                $sql = "SELECT SUM(quantity) as total FROM tbl_giohang WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);

                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['total'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error getting cart count: " . $e->getMessage());
                return 0;
            }
        } else {
            // Nếu không đăng nhập, đếm từ session cart
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $total = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $total += $item['quantity'] ?? 1;
                }
                return $total;
            }
            return 0;
        }
    }

    // Phương thức mới để chuyển giỏ hàng từ session sang database khi đăng nhập
    public function migrateSessionCartToDatabase($username)
    {
        // Kiểm tra xem người dùng có phải là user thường không
        if ($username && !isset($_SESSION['ADMIN'])) {
            if (isset($_SESSION['cart']['guest_' . session_id()])) {
                $sessionCart = $_SESSION['cart']['guest_' . session_id()];
                foreach ($sessionCart as $productId => $quantity) {
                    $this->addToCart($productId, $quantity);
                }
                unset($_SESSION['cart']['guest_' . session_id()]);
            }
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
        if (!$this->canUseCart()) {
            error_log("Không thể cập nhật số lượng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();

        try {
            if ($quantity > 0) {
                $sql = "UPDATE tbl_giohang SET quantity = ? WHERE user_id = ? AND product_id = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$quantity, $userId, $productId]);

                // Xóa cache khi cập nhật số lượng
                $this->clearCartCache();

                return $result;
            } else {
                return $this->removeFromCart($productId);
            }
        } catch (PDOException $e) {
            error_log("Error updating cart: " . $e->getMessage());
            return false;
        }
    }

    public function getCartByUserId($userId)
    {
        try {
            $sql = "SELECT g.product_id, g.quantity, h.tenhanghoa, h.giathamkhao, h.hinhanh
                   FROM tbl_giohang g
                   INNER JOIN hanghoa h ON g.product_id = h.idhanghoa
                   WHERE g.user_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            $cart = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cart[] = [
                    'product_id' => $row['product_id'],
                    'tenhanghoa' => $row['tenhanghoa'],
                    'giathamkhao' => $row['giathamkhao'],
                    'quantity' => $row['quantity'],
                    'hinhanh' => $row['hinhanh']
                ];
            }
            return $cart;
        } catch (PDOException $e) {
            error_log("Error getting cart for user $userId: " . $e->getMessage());
            return [];
        }
    }

    // Thêm phương thức để xóa cache khi giỏ hàng thay đổi
    private function clearCartCache()
    {
        $this->cart_cache = null;
    }

    // Alias methods để tương thích với test
    public function getCartItems()
    {
        return $this->getCart();
    }

    public function removeItem($productId)
    {
        return $this->removeFromCart($productId);
    }
}
