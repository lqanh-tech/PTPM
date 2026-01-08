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

    public function isUserLoggedIn()
    {
        return isset($_SESSION['USER']);
    }

    public function canUseCart()
    {
        return isset($_SESSION['USER']);
    }

    public function isAdmin()
    {
        return isset($_SESSION['ADMIN']);
    }

    private function getSessionId()
    {
        return session_id();
    }

    public function addToCart($productId, $quantity = 1)
    {

        if (!$this->canUseCart()) {
            error_log("Không thể thêm vào giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();
        $sessionId = $this->getSessionId();
        error_log("Adding to cart - UserID: " . $userId . ", SessionID: " . $sessionId . ", ProductID: " . $productId . ", Quantity: " . $quantity);

        try {

            $checkProduct = "SELECT idhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmtProduct = $this->db->prepare($checkProduct);
            $stmtProduct->execute([$productId]);

            if (!$stmtProduct->fetch()) {
                error_log("Product does not exist: " . $productId);
                return false;
            }

            $checkSql = "SELECT quantity FROM tbl_giohang WHERE user_id = ? AND product_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId, $productId]);
            $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {

                $sql = "UPDATE tbl_giohang SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$quantity, $userId, $productId]);
            } else {

                $sql = "INSERT INTO tbl_giohang (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$userId, $sessionId, $productId, $quantity]);
            }

            $this->clearCartCache();
            return $result;
        } catch (PDOException $e) {
            error_log("Error in cart operation: " . $e->getMessage());
            return false;
        }
    }

    public function removeFromCart($productId)
    {

        if (!$this->canUseCart()) {
            error_log("Không thể xóa khỏi giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return false;
        }

        $userId = $this->getUserId();

        try {
            $sql = "DELETE FROM tbl_giohang WHERE user_id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $productId]);

            $this->clearCartCache();

            return $result;
        } catch (PDOException $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            return false;
        }
    }

    public function updateCart($productId, $quantity)
    {

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

        if (!$this->canUseCart()) {
            error_log("Không thể lấy giỏ hàng: Người dùng không có quyền hoặc chưa đăng nhập");
            return [];
        }

        if ($this->cart_cache !== null) {
            return $this->cart_cache;
        }

        $userId = $this->getUserId();

        try {

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

                error_log("Cart item: Product ID=" . $row['product_id'] . ", Image ID=" . ($row['hinhanh'] ?? 'NULL') . ", Name=" . ($row['tenhanghoa'] ?? 'Unknown'));

                $hinhanhValue = null;
                if (isset($row['hinhanh']) && $row['hinhanh'] !== null && $row['hinhanh'] !== '') {
                    $hinhanhValue = (int)$row['hinhanh'];
                    error_log("Đã chuyển đổi hinhanh thành số nguyên: " . $hinhanhValue);
                }

                $giaHienTai = $row['gia_hien_tai'] ?? $row['giathamkhao'] ?? 0;
                $hasDiscount = isset($row['giakhuyenmai']) && $row['giakhuyenmai'] > 0 && $row['giakhuyenmai'] < $row['giathamkhao'];

                $cart[] = [
                    'product_id' => $row['product_id'],
                    'tenhanghoa' => $row['tenhanghoa'] ?? 'Unknown Product',
                    'giathamkhao' => $row['giathamkhao'] ?? 0,
                    'giakhuyenmai' => $row['giakhuyenmai'] ?? null,
                    'gia_hien_tai' => $giaHienTai,
                    'has_discount' => $hasDiscount,
                    'quantity' => $row['quantity'],
                    'hinhanh' => $hinhanhValue,
                    'name' => $row['tenhanghoa'] ?? 'Unknown Product'
                ];
            }

            $this->cart_cache = $cart;

            return $cart;
        } catch (PDOException $e) {
            error_log("Error getting cart: " . $e->getMessage());
            return [];
        }
    }

    public function clearCart()
    {

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

            $this->clearCartCache();

            return $result;
        } catch (PDOException $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }

    public function getCartItemCount()
    {

        if (!$this->canUseCart()) {
            return 0;
        }

        $userId = $this->getUserId();

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

    public function migrateSessionCartToDatabase($username)
    {

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

    private function clearCartCache()
    {
        $this->cart_cache = null;
    }

    public function getCartItems()
    {
        return $this->getCart();
    }

    public function removeItem($productId)
    {
        return $this->removeFromCart($productId);
    }
}
