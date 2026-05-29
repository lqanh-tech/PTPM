<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = './elements_LQA/mod/database.php';
    if (!file_exists($f)) {
        $f = './administrator/elements_LQA/mod/database.php';
    }
}
require_once $f;

class PhanHeQuanLy
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
        $this->createTablesIfNotExist();
    }

    private function createTablesIfNotExist()
    {
        try {

            $sql = "CREATE TABLE IF NOT EXISTS PhanHeQuanLy (
                idPhanHe INT AUTO_INCREMENT PRIMARY KEY,
                maPhanHe VARCHAR(50) NOT NULL UNIQUE,
                tenPhanHe VARCHAR(100) NOT NULL,
                moTa TEXT,
                trangThai TINYINT(1) DEFAULT 1,
                ngayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->db->exec($sql);

            $sql = "CREATE TABLE IF NOT EXISTS NhanVien_PhanHeQuanLy (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idNhanVien INT NOT NULL,
                idPhanHe INT NOT NULL,
                ngayGan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_nhanvien_phanhe (idNhanVien, idPhanHe),
                FOREIGN KEY (idNhanVien) REFERENCES nhanvien(idNhanVien) ON DELETE CASCADE,
                FOREIGN KEY (idPhanHe) REFERENCES PhanHeQuanLy(idPhanHe) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->db->exec($sql);

            $stmt = $this->db->query("SELECT COUNT(*) FROM PhanHeQuanLy");
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $this->insertDefaultModules();
            }

            return true;
        } catch (PDOException $e) {
            error_log("Lỗi khi tạo bảng: " . $e->getMessage());
            return false;
        }
    }

    private function insertDefaultModules()
    {
        $modules = $this->getDefaultModulesList();

        $sql = "INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($modules as $module) {
            $stmt->execute($module);
        }
    }

    private function getDefaultModulesList()
    {
        return [

            ['userview', 'Quản lý tài khoản', 'Quản lý tài khoản người dùng trong hệ thống'],
            ['vaiTroView', 'Quản lý vai trò người dùng', 'Quản lý vai trò cho người dùng'],
            ['nguoiDungVaiTroView', 'Gán vai trò người dùng', 'Gán vai trò cho người dùng trong hệ thống'],
            ['danhSachVaiTroView', 'Danh sách vai trò', 'Xem danh sách vai trò trong hệ thống'],
            ['roleview', 'Quản lý vai trò', 'Quản lý vai trò người dùng'],
            
            ['khachhangview', 'Quản lý khách hàng', 'Quản lý thông tin khách hàng'],
            ['nhanvienview', 'Quản lý nhân viên', 'Quản lý thông tin nhân viên'],
            
            ['loaihangview', 'Quản lý loại hàng', 'Quản lý danh mục loại hàng hóa'],
            ['hanghoaview', 'Quản lý hàng hóa', 'Quản lý thông tin hàng hóa, sản phẩm'],
            ['thuoctinhhhview', 'Quản lý thuộc tính hàng hóa', 'Quản lý thuộc tính cho từng hàng hóa'],
            ['thuoctinhview', 'Quản lý thuộc tính', 'Quản lý các thuộc tính sản phẩm'],
            ['dongiaview', 'Quản lý đơn giá', 'Quản lý giá cả sản phẩm'],
            ['thuonghieuview', 'Quản lý thương hiệu', 'Quản lý thông tin thương hiệu'],
            ['donvitinhview', 'Quản lý đơn vị tính', 'Quản lý đơn vị tính cho sản phẩm'],
            ['hinhanhview', 'Quản lý hình ảnh', 'Quản lý hình ảnh sản phẩm'],
            
            ['adminGiohangView', 'Quản lý giỏ hàng', 'Quản lý giỏ hàng của khách hàng'],
            ['don_hang', 'Quản lý đơn hàng', 'Quản lý đơn đặt hàng của khách'],
            ['orders', 'Quản lý đơn hàng (API)', 'Quản lý đơn đặt hàng qua API'],
            ['cau_hinh_thanh_toan', 'Cấu hình thanh toán', 'Quản lý cấu hình phương thức thanh toán'],
            ['payment_config', 'Cấu hình thanh toán (API)', 'Quản lý cấu hình thanh toán qua API'],
            
            ['nhacungcapview', 'Quản lý nhà cung cấp', 'Quản lý thông tin nhà cung cấp'],
            ['mphieunhap', 'Quản lý phiếu nhập', 'Quản lý phiếu nhập hàng'],
            ['mchitietphieunhap', 'Quản lý chi tiết phiếu nhập', 'Quản lý chi tiết phiếu nhập hàng'],
            ['mtonkho', 'Quản lý tồn kho', 'Quản lý thông tin tồn kho'],
            
            ['baocaoview', 'Báo cáo tổng hợp', 'Xem báo cáo tổng hợp'],
            ['sanPhamBanChayView', 'Báo cáo sản phẩm bán chạy', 'Xem báo cáo sản phẩm bán chạy'],
            ['loiNhuanView', 'Báo cáo doanh thu & lợi nhuận', 'Xem báo cáo doanh thu và lợi nhuận'],
            ['nhatKyHoatDongTichHop', 'Thống kê hoạt động nhân viên', 'Xem thống kê và nhật ký hoạt động của nhân viên'],
            
            ['quanLySanPhamDacBiet', 'Quản Lý & Khuyến Mãi SP', 'Quản lý sản phẩm đặc biệt, khuyến mãi, nổi bật'],
            ['sanphamnoibat', 'Sản phẩm nổi bật', 'Quản lý sản phẩm nổi bật'],
            ['marketing_content', 'Nội dung Marketing', 'Quản lý nội dung marketing, banner, tin tức'],
            
            ['coupon', 'Mã giảm giá (Coupon)', 'Quản lý mã giảm giá'],
            ['review_management', 'Quản lý bình luận', 'Quản lý bình luận sản phẩm'],
            ['support_tickets', 'Hỗ trợ khách hàng', 'Quản lý ticket hỗ trợ khách hàng'],
            
            ['shipping_config', 'Cấu hình vận chuyển', 'Quản lý cấu hình vận chuyển'],
            ['shipping_dashboard', 'Dashboard vận chuyển', 'Xem dashboard vận chuyển'],
            ['shipping_report', 'Báo cáo vận chuyển', 'Xem báo cáo vận chuyển'],
            
            ['mphieunhapedit', 'Sửa phiếu nhập', 'Chỉnh sửa phiếu nhập'],
            ['mchitietphieunhapedit', 'Sửa chi tiết phiếu nhập', 'Chỉnh sửa chi tiết phiếu nhập'],
            ['mtonkhoedit', 'Sửa tồn kho', 'Chỉnh sửa thông tin tồn kho'],
            ['updateuser', 'Cập nhật người dùng', 'Chỉnh sửa thông tin người dùng'],
            ['userupdate', 'Sửa user', 'Sửa thông tin user'],
            ['addPromotion', 'Thêm khuyến mãi', 'Thêm khuyến mãi cho sản phẩm'],
            ['removePromotion', 'Xóa khuyến mãi', 'Xóa khuyến mãi sản phẩm'],
            ['thongKeNhanVienCaiThien', 'Thống kê nhân viên cải thiện', 'Xem thống kê hoạt động nhân viên cải thiện']
        ];
    }

    public function syncModules()
    {
        $modules = $this->getDefaultModulesList();
        $addedCount = 0;

        $sql = "INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($modules as $module) {
            try {
                $result = $stmt->execute($module);
                if ($stmt->rowCount() > 0) {
                    $addedCount++;
                }
            } catch (PDOException $e) {
                error_log("Lỗi khi thêm module {$module[0]}: " . $e->getMessage());
            }
        }

        return $addedCount;
    }

    public function syncModulesFromMenu($menuItems)
    {
        $addedCount = 0;
        $updatedCount = 0;

        $existingStmt = $this->db->prepare("SELECT idPhanHe, tenPhanHe, moTa FROM PhanHeQuanLy WHERE maPhanHe = ?");
        $insertStmt = $this->db->prepare("INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES (?, ?, ?)");
        $updateStmt = $this->db->prepare("UPDATE PhanHeQuanLy SET tenPhanHe = ?, moTa = ? WHERE maPhanHe = ?");

        foreach ($menuItems as $req => $item) {
            $text = $item['text'] ?? $req;
            $desc = "Module " . $text;

            try {
                $existingStmt->execute([$req]);
                $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    $insertStmt->execute([$req, $text, $desc]);
                    $addedCount++;
                } elseif ($existing['tenPhanHe'] !== $text) {
                    $updateStmt->execute([$text, $desc, $req]);
                    $updatedCount++;
                }
            } catch (PDOException $e) {
                error_log("Lỗi khi sync module $req: " . $e->getMessage());
            }
        }

        return ['added' => $addedCount, 'updated' => $updatedCount];
    }

    public function getAllPhanHe()
    {
        $sql = "SELECT idPhanHe, tenPhanHe, moTa, trangThai FROM PhanHeQuanLy WHERE trangThai = 1 ORDER BY tenPhanHe";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPhanHeById($idPhanHe)
    {
        $sql = "SELECT idPhanHe, tenPhanHe, moTa, trangThai FROM PhanHeQuanLy WHERE idPhanHe = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPhanHe]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getPhanHeByNhanVienId($idNhanVien)
    {
        $sql = "SELECT ph.* FROM PhanHeQuanLy ph
                JOIN NhanVien_PhanHeQuanLy nvph ON ph.idPhanHe = nvph.idPhanHe
                WHERE nvph.idNhanVien = ? AND ph.trangThai = 1
                ORDER BY ph.tenPhanHe";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idNhanVien]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function assignPhanHeToNhanVien($idNhanVien, $idPhanHe)
    {
        try {
            $sql = "INSERT INTO NhanVien_PhanHeQuanLy (idNhanVien, idPhanHe) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE ngayGan = CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idNhanVien, $idPhanHe]);
        } catch (PDOException $e) {
            error_log("Lỗi khi gán phần hệ cho nhân viên: " . $e->getMessage());
            return false;
        }
    }

    public function removePhanHeFromNhanVien($idNhanVien, $idPhanHe)
    {
        try {
            $sql = "DELETE FROM NhanVien_PhanHeQuanLy WHERE idNhanVien = ? AND idPhanHe = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idNhanVien, $idPhanHe]);
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa phần hệ của nhân viên: " . $e->getMessage());
            return false;
        }
    }

    public function removeAllPhanHeFromNhanVien($idNhanVien)
    {
        try {
            $sql = "DELETE FROM NhanVien_PhanHeQuanLy WHERE idNhanVien = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idNhanVien]);
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa tất cả phần hệ của nhân viên: " . $e->getMessage());
            return false;
        }
    }

    public function checkNhanVienHasAccess($idNhanVien, $maPhanHe)
    {
        $sql = "SELECT COUNT(*) FROM NhanVien_PhanHeQuanLy nvph
                JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
                WHERE nvph.idNhanVien = ? AND ph.maPhanHe = ? AND ph.trangThai = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idNhanVien, $maPhanHe]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Lấy idPhanHe từ maPhanHe
     */
    public function getIdByMaPhanHe($maPhanHe)
    {
        $sql = "SELECT idPhanHe FROM PhanHeQuanLy WHERE maPhanHe = ? AND trangThai = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$maPhanHe]);
        $result = $stmt->fetchColumn();
        return $result ? (int)$result : null;
    }

    /**
     * Gán phần hệ cho nhân viên bằng maPhanHe (tự động insert nếu chưa có trong DB)
     */
    public function assignPhanHeToNhanVienByMa($idNhanVien, $maPhanHe)
    {
        $idPhanHe = $this->getIdByMaPhanHe($maPhanHe);
        if (!$idPhanHe) {
            // Nếu chưa có trong DB, tự động thêm mới từ menuConfig
            require_once __DIR__ . '/menuConfig.php';
            if (isset($menu_items[$maPhanHe])) {
                $item = $menu_items[$maPhanHe];
                $sql = "INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$maPhanHe, $item['text'], 'Module ' . $item['text']]);
                $idPhanHe = (int)$this->db->lastInsertId();
            }
        }
        if ($idPhanHe) {
            return $this->assignPhanHeToNhanVien($idNhanVien, $idPhanHe);
        }
        return false;
    }

    /**
     * Xóa tất cả phần hệ của nhân viên theo maPhanHe
     */
    public function removeAllPhanHeFromNhanVienByMa($idNhanVien)
    {
        // Xóa tất cả như cũ - giữ nguyên logic
        return $this->removeAllPhanHeFromNhanVien($idNhanVien);
    }
}
