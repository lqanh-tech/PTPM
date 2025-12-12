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

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->createTablesIfNotExist();
    }

    /**
     * Tạo các bảng cần thiết nếu chưa tồn tại
     */
    private function createTablesIfNotExist()
    {
        try {
            // Tạo bảng PhanHeQuanLy nếu chưa tồn tại
            $sql = "CREATE TABLE IF NOT EXISTS PhanHeQuanLy (
                idPhanHe INT AUTO_INCREMENT PRIMARY KEY,
                maPhanHe VARCHAR(50) NOT NULL UNIQUE,
                tenPhanHe VARCHAR(100) NOT NULL,
                moTa TEXT,
                trangThai TINYINT(1) DEFAULT 1,
                ngayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->db->exec($sql);

            // Tạo bảng liên kết NhanVien_PhanHeQuanLy nếu chưa tồn tại
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

            // Kiểm tra xem đã có dữ liệu trong bảng PhanHeQuanLy chưa
            $stmt = $this->db->query("SELECT COUNT(*) FROM PhanHeQuanLy");
            $count = $stmt->fetchColumn();

            // Nếu chưa có dữ liệu, thêm các phần hệ mặc định
            if ($count == 0) {
                $this->insertDefaultModules();
            }

            return true;
        } catch (PDOException $e) {
            error_log("Lỗi khi tạo bảng: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Thêm các phần hệ mặc định vào bảng PhanHeQuanLy
     */
    private function insertDefaultModules()
    {
        $modules = $this->getDefaultModulesList();

        $sql = "INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($modules as $module) {
            $stmt->execute($module);
        }
    }

    /**
     * Danh sách tất cả các module mặc định - đồng bộ với menu left.php
     */
    private function getDefaultModulesList()
    {
        return [
            // Quản lý tài khoản & vai trò
            ['userview', 'Quản lý tài khoản', 'Quản lý tài khoản người dùng trong hệ thống'],
            ['vaiTroView', 'Quản lý vai trò người dùng', 'Quản lý vai trò cho người dùng'],
            ['nguoiDungVaiTroView', 'Gán vai trò người dùng', 'Gán vai trò cho người dùng trong hệ thống'],
            ['danhSachVaiTroView', 'Danh sách vai trò', 'Xem danh sách vai trò trong hệ thống'],
            ['roleview', 'Quản lý vai trò', 'Quản lý vai trò người dùng'],
            
            // Quản lý khách hàng & nhân viên
            ['khachhangview', 'Quản lý khách hàng', 'Quản lý thông tin khách hàng'],
            ['nhanvienview', 'Quản lý nhân viên', 'Quản lý thông tin nhân viên'],
            
            // Quản lý sản phẩm
            ['loaihangview', 'Quản lý loại hàng', 'Quản lý danh mục loại hàng hóa'],
            ['hanghoaview', 'Quản lý hàng hóa', 'Quản lý thông tin hàng hóa, sản phẩm'],
            ['thuoctinhhhview', 'Quản lý thuộc tính hàng hóa', 'Quản lý thuộc tính cho từng hàng hóa'],
            ['thuoctinhview', 'Quản lý thuộc tính', 'Quản lý các thuộc tính sản phẩm'],
            ['dongiaview', 'Quản lý đơn giá', 'Quản lý giá cả sản phẩm'],
            ['thuonghieuview', 'Quản lý thương hiệu', 'Quản lý thông tin thương hiệu'],
            ['donvitinhview', 'Quản lý đơn vị tính', 'Quản lý đơn vị tính cho sản phẩm'],
            ['hinhanhview', 'Quản lý hình ảnh', 'Quản lý hình ảnh sản phẩm'],
            
            // Quản lý bán hàng & đơn hàng
            ['adminGiohangView', 'Quản lý giỏ hàng', 'Quản lý giỏ hàng của khách hàng'],
            ['don_hang', 'Quản lý đơn hàng', 'Quản lý đơn đặt hàng của khách'],
            ['orders', 'Quản lý đơn hàng (API)', 'Quản lý đơn đặt hàng qua API'],
            ['cau_hinh_thanh_toan', 'Cấu hình thanh toán', 'Quản lý cấu hình phương thức thanh toán'],
            ['payment_config', 'Cấu hình thanh toán (API)', 'Quản lý cấu hình thanh toán qua API'],
            
            // Quản lý kho
            ['nhacungcapview', 'Quản lý nhà cung cấp', 'Quản lý thông tin nhà cung cấp'],
            ['mphieunhap', 'Quản lý phiếu nhập', 'Quản lý phiếu nhập hàng'],
            ['mchitietphieunhap', 'Quản lý chi tiết phiếu nhập', 'Quản lý chi tiết phiếu nhập hàng'],
            ['mtonkho', 'Quản lý tồn kho', 'Quản lý thông tin tồn kho'],
            
            // Báo cáo & thống kê
            ['baocaoview', 'Báo cáo tổng hợp', 'Xem báo cáo tổng hợp'],
            ['doanhThuView', 'Báo cáo doanh thu', 'Xem báo cáo doanh thu'],
            ['sanPhamBanChayView', 'Báo cáo sản phẩm bán chạy', 'Xem báo cáo sản phẩm bán chạy'],
            ['loiNhuanView', 'Báo cáo lợi nhuận', 'Xem báo cáo lợi nhuận'],
            ['nhatKyHoatDongTichHop', 'Thống kê hoạt động nhân viên', 'Xem thống kê và nhật ký hoạt động của nhân viên'],
            
            // Quản lý khuyến mãi & Marketing
            ['quanLySanPhamDacBiet', 'Quản Lý & Khuyến Mãi SP', 'Quản lý sản phẩm đặc biệt, khuyến mãi, nổi bật'],
            ['marketing_content', 'Nội dung Marketing', 'Quản lý nội dung marketing, banner, tin tức']
        ];
    }

    /**
     * Đồng bộ các module mới vào database (chạy khi có module mới được thêm vào menu)
     */
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

    /**
     * Lấy tất cả các phần hệ quản lý
     */
    public function getAllPhanHe()
    {
        $sql = "SELECT * FROM PhanHeQuanLy WHERE trangThai = 1 ORDER BY tenPhanHe";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lấy phần hệ quản lý theo ID
     */
    public function getPhanHeById($idPhanHe)
    {
        $sql = "SELECT * FROM PhanHeQuanLy WHERE idPhanHe = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPhanHe]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Lấy danh sách phần hệ quản lý của một nhân viên
     */
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

    /**
     * Gán phần hệ quản lý cho nhân viên
     */
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

    /**
     * Xóa phần hệ quản lý của nhân viên
     */
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

    /**
     * Xóa tất cả phần hệ quản lý của nhân viên
     */
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

    /**
     * Kiểm tra nhân viên có quyền truy cập vào phần hệ không
     */
    public function checkNhanVienHasAccess($idNhanVien, $maPhanHe)
    {
        $sql = "SELECT COUNT(*) FROM NhanVien_PhanHeQuanLy nvph
                JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
                WHERE nvph.idNhanVien = ? AND ph.maPhanHe = ? AND ph.trangThai = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idNhanVien, $maPhanHe]);
        return $stmt->fetchColumn() > 0;
    }
}
