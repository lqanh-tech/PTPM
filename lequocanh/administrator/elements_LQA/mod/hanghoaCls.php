<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/hanghoaStatusExtension.php';

class hanghoa
{
    use HanghoaStatusTrait;

    private $db;
    private static $statusColumnInfo = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function getStatusColumnInfo()
    {
        if (self::$statusColumnInfo !== null) {
            return self::$statusColumnInfo;
        }

        self::$statusColumnInfo = ['column' => null, 'type' => null];

        try {
            $checkNew = $this->db->query("SHOW COLUMNS FROM hanghoa LIKE 'trangthai'");
            if ($checkNew && $checkNew->rowCount() > 0) {
                self::$statusColumnInfo = ['column' => 'trangthai', 'type' => 'enum'];
                return self::$statusColumnInfo;
            }

            $checkLegacy = $this->db->query("SHOW COLUMNS FROM hanghoa LIKE 'trang_thai'");
            if ($checkLegacy && $checkLegacy->rowCount() > 0) {
                self::$statusColumnInfo = ['column' => 'trang_thai', 'type' => 'int'];
            }
        } catch (PDOException $e) {
            error_log('hanghoa::getStatusColumnInfo error: ' . $e->getMessage());
        }

        return self::$statusColumnInfo;
    }

    public function HanghoaGetAll()
    {
        $sql = 'SELECT h.*,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                n.tenNV AS ten_nhanvien,
                CASE 
                    WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != "" 
                    THEN 0 
                    ELSE 1 
                END as image_priority,
                -- Thêm logic xử lý giá khuyến mãi
                CASE 
                    WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                    THEN h.giakhuyenmai
                    ELSE h.giathamkhao
                END as gia_hien_thi,
                -- Tính % giảm giá
                CASE 
                    WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                    THEN ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                LEFT JOIN nhanvien n ON h.idNhanVien = n.idNhanVien
                ORDER BY image_priority ASC, h.tenhanghoa ASC';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function HanghoaAdd($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu = '')
    {
        // Tạo file log
        $log_file = dirname(__FILE__) . '/hanghoa_class_debug.log';

        try {
            // Ghi log chi tiết
            $log_data = date('Y-m-d H:i:s') . " - HanghoaAdd() được gọi với các tham số:\n";
            $log_data .= "tenhanghoa: $tenhanghoa\n";
            $log_data .= "mota: $mota\n";
            $log_data .= "giathamkhao: $giathamkhao\n";
            $log_data .= "id_hinhanh: " . ($id_hinhanh ?: "NULL") . "\n";
            $log_data .= "idloaihang: $idloaihang\n";
            $log_data .= "idThuongHieu: " . ($idThuongHieu ?: "NULL") . "\n";
            $log_data .= "idDonViTinh: " . ($idDonViTinh ?: "NULL") . "\n";
            $log_data .= "idNhanVien: " . ($idNhanVien ?: "NULL") . "\n";
            $log_data .= "ghichu: " . ($ghichu ?: "") . "\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);

            // Convert empty strings to NULL for integer fields, but use 0 for id_hinhanh
            $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh; // Use 0 instead of NULL for id_hinhanh
            $idThuongHieu = ($idThuongHieu === '' || $idThuongHieu === 0 || $idThuongHieu === '0') ? null : $idThuongHieu;
            $idDonViTinh = ($idDonViTinh === '' || $idDonViTinh === 0 || $idDonViTinh === '0') ? null : $idDonViTinh;
            $idNhanVien = ($idNhanVien === '' || $idNhanVien === 0 || $idNhanVien === '0') ? null : $idNhanVien;

            // Kiểm tra kết nối database
            if (!$this->db || !($this->db instanceof PDO)) {
                $error_msg = "Lỗi: Không có kết nối database hợp lệ";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            // Kiểm tra các tham số bắt buộc
            if (empty($tenhanghoa) || empty($giathamkhao) || empty($idloaihang)) {
                $error_msg = "Lỗi: Thiếu thông tin bắt buộc (tên hàng hóa, giá tham khảo hoặc loại hàng)";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            // Kiểm tra cấu trúc bảng hanghoa
            try {
                $checkColumns = $this->db->query("SHOW COLUMNS FROM hanghoa");
                $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
                $log_columns = date('Y-m-d H:i:s') . " - Các cột trong bảng hanghoa: " . implode(", ", $columns) . "\n";
                file_put_contents($log_file, $log_columns, FILE_APPEND);
            } catch (Exception $e) {
                $log_column_error = date('Y-m-d H:i:s') . " - Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage() . "\n";
                file_put_contents($log_file, $log_column_error, FILE_APPEND);
            }

            // Chuẩn bị câu lệnh SQL với tên cột chính xác
            // Thêm trường ghichu vào câu lệnh SQL
            $ghichu = ""; // Giá trị mặc định cho ghichu

            if (in_array('hinhanh', $columns)) {
                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            } else {
                // Nếu tên cột là id_hinhanh thay vì hinhanh
                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, id_hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            }

            // Ghi log SQL và dữ liệu
            $log_sql = date('Y-m-d H:i:s') . " - SQL: $sql\n";
            $log_sql .= "Data: " . print_r($data, true) . "\n";
            file_put_contents($log_file, $log_sql, FILE_APPEND);

            // Thực thi câu lệnh SQL
            $add = $this->db->prepare($sql);
            $result = $add->execute($data);

            // Kiểm tra kết quả
            if ($result) {
                $rowCount = $add->rowCount();
                $lastId = $this->db->lastInsertId();
                $log_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công. Rows affected: $rowCount, Last Insert ID: $lastId\n";
                file_put_contents($log_file, $log_success, FILE_APPEND);

                // Thêm vào bảng tonkho nếu có
                try {
                    $checkTonkhoTable = $this->db->query("SHOW TABLES LIKE 'tonkho'");
                    if ($checkTonkhoTable->rowCount() > 0) {
                        $insertTonkho = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)";
                        $stmtTonkho = $this->db->prepare($insertTonkho);
                        $stmtTonkho->execute([$lastId]);
                        $log_tonkho = date('Y-m-d H:i:s') . " - Đã thêm vào bảng tonkho cho hàng hóa ID: $lastId\n";
                        file_put_contents($log_file, $log_tonkho, FILE_APPEND);
                    }
                } catch (Exception $tonkhoEx) {
                    $log_tonkho_error = date('Y-m-d H:i:s') . " - Lỗi khi thêm vào bảng tonkho: " . $tonkhoEx->getMessage() . "\n";
                    file_put_contents($log_file, $log_tonkho_error, FILE_APPEND);
                }

                return $lastId; // Trả về ID của hàng hóa mới thêm
            } else {
                $error_info = print_r($add->errorInfo(), true);
                $log_error = date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại. Error info: $error_info\n";
                file_put_contents($log_file, $log_error, FILE_APPEND);

                // Thử phương án thay thế nếu có lỗi với tên cột
                if (strpos($error_info, "Unknown column") !== false) {
                    // Kiểm tra cấu trúc bảng một lần nữa
                    $describeStmt = $this->db->query("DESCRIBE hanghoa");
                    $columns = $describeStmt->fetchAll(PDO::FETCH_COLUMN);

                    // Xác định tên cột hình ảnh chính xác
                    $imageColumn = in_array('hinhanh', $columns) ? 'hinhanh' : 'id_hinhanh';

                    // Thử lại với tên cột chính xác
                    $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, $imageColumn, idloaihang, idThuongHieu, idDonViTinh, idNhanVien) VALUES (?,?,?,?,?,?,?,?)";
                    $add = $this->db->prepare($sql);
                    $result = $add->execute($data);

                    if ($result) {
                        $lastId = $this->db->lastInsertId();
                        $log_retry_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công sau khi thử lại. Last Insert ID: $lastId\n";
                        file_put_contents($log_file, $log_retry_success, FILE_APPEND);
                        return $lastId;
                    } else {
                        $retry_error_info = print_r($add->errorInfo(), true);
                        $log_retry_error = date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại sau khi thử lại. Error info: $retry_error_info\n";
                        file_put_contents($log_file, $log_retry_error, FILE_APPEND);
                    }
                }

                return false;
            }
        } catch (Exception $e) {
            $log_exception = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n";
            $log_exception .= "Stack trace: " . $e->getTraceAsString() . "\n";
            file_put_contents($log_file, $log_exception, FILE_APPEND);
            return false;
        }
    }

    public function HanghoaDelete($idhanghoa)
    {
        try {
            // Kiểm tra các ràng buộc trước khi xóa
            $relatedData = $this->checkRelatedData($idhanghoa);

            if (!empty($relatedData)) {
                // Trả về thông tin về các bảng có liên quan
                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan',
                    'related_tables' => $relatedData
                ];
            }

            // Nếu không có ràng buộc, thực hiện xóa
            $sql = "DELETE from hanghoa where idhanghoa = ?";
            $data = array($idhanghoa);

            $del = $this->db->prepare($sql);
            $del->execute($data);

            $rowCount = $del->rowCount();

            return [
                'success' => true,
                'rows_affected' => $rowCount,
                'message' => $rowCount > 0 ? 'Xóa hàng hóa thành công' : 'Không tìm thấy hàng hóa để xóa'
            ];
        } catch (PDOException $e) {
            // Xử lý lỗi foreign key constraint
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'foreign key constraint') !== false) {
                // Phân tích thông báo lỗi để lấy tên bảng
                $errorMessage = $e->getMessage();
                $tableName = 'không xác định';

                if (preg_match('/`([^`]+)`\.`([^`]+)`/', $errorMessage, $matches)) {
                    $tableName = $matches[2]; // Tên bảng
                }

                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan trong bảng: ' . $tableName,
                    'technical_error' => $errorMessage,
                    'suggested_action' => $this->getSuggestedAction($tableName)
                ];
            }

            // Lỗi khác
            return [
                'success' => false,
                'error_type' => 'database_error',
                'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
            ];
        }
    }

    public function HanghoaUpdate($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $idhanghoa, $ghichu = '')
    {
        // Convert empty strings to NULL for integer fields, but use 0 for id_hinhanh
        $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh; // Use 0 instead of NULL for id_hinhanh
        $idThuongHieu = $idThuongHieu === '' ? null : $idThuongHieu;
        $idDonViTinh = $idDonViTinh === '' ? null : $idDonViTinh;
        $idNhanVien = $idNhanVien === '' ? null : $idNhanVien;

        $sql = "UPDATE hanghoa SET tenhanghoa=?, hinhanh=?, mota=?, giathamkhao=?, idloaihang=?, idThuongHieu=?, idDonViTinh=?, idNhanVien=?, ghichu=? WHERE idhanghoa =?";
        $data = array($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu, $idhanghoa);

        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    public function HanghoaGetbyId($idhanghoa)
    {
        $sql = 'select * from hanghoa where idhanghoa=?';
        $data = array($idhanghoa);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);

        return $getOne->fetch();
    }

    public function HanghoaGetbyIdloaihang($idloaihang)
    {
        // Lọc sản phẩm: chỉ lấy những sản phẩm không bị ngừng bán
        $statusInfo = $this->getStatusColumnInfo();
        $statusCondition = '';
        if ($statusInfo['column']) {
            if ($statusInfo['column'] === 'trangthai') {
                $statusCondition = " AND ({$statusInfo['column']} IS NULL OR {$statusInfo['column']} != 'ngung_ban')";
            } else {
                $statusCondition = " AND ({$statusInfo['column']} IS NULL OR {$statusInfo['column']} != 2)";
            }
        }

        $sql = 'SELECT *,
                CASE 
                    WHEN hinhanh IS NOT NULL AND hinhanh != 0 AND hinhanh != "" 
                    THEN 0 
                    ELSE 1 
                END as image_priority,
                -- Thêm logic xử lý giá khuyến mãi
                CASE 
                    WHEN giakhuyenmai IS NOT NULL AND giakhuyenmai > 0 AND giakhuyenmai < giathamkhao
                    THEN giakhuyenmai
                    ELSE giathamkhao
                END as gia_hien_thi,
                -- Tính % giảm giá
                CASE 
                    WHEN giakhuyenmai IS NOT NULL AND giakhuyenmai > 0 AND giakhuyenmai < giathamkhao
                    THEN ROUND(((giathamkhao - giakhuyenmai) / giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa 
                WHERE idloaihang = ?' . $statusCondition . '
                ORDER BY image_priority ASC, tenhanghoa ASC';
        $data = array($idloaihang);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);

        return $getOne->fetchAll();
    }

    public function HanghoaUpdatePrice($idhanghoa, $giaban)
    {
        $sql = "UPDATE hanghoa SET giathamkhao = ? WHERE idhanghoa = ?";
        $data = array($giaban, $idhanghoa);

        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    /**
     * Enhanced search: name, description, AND attributes
     * Single query with LEFT JOIN for performance
     * 
     * @param string $keyword Search keyword
     * @return array Array of product objects
     */
    public function searchHanghoa($keyword)
    {
        try {
            // Log for debugging
            error_log("searchHanghoa - Starting search with keyword: " . $keyword);

            // Check database connection
            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("searchHanghoa - Error: No valid database connection");
                return [];
            }

            // Check if hanghoa table exists
            try {
                $checkTable = $this->db->query("SHOW TABLES LIKE 'hanghoa'");
                if ($checkTable->rowCount() == 0) {
                    error_log("searchHanghoa - hanghoa table does not exist");
                    return [];
                }
            } catch (PDOException $e) {
                error_log("searchHanghoa - Error checking hanghoa table: " . $e->getMessage());
                return [];
            }

            $searchTerm = '%' . $keyword . '%';

            // OPTIMIZED: Single query with LEFT JOIN to search in attributes
            $sql = "SELECT DISTINCT h.*,
                    CASE 
                        -- Priority 1: Exact match in product name
                        WHEN LOWER(h.tenhanghoa) LIKE LOWER(:exact_keyword) THEN 1
                        -- Priority 2: Match in product name
                        WHEN LOWER(h.tenhanghoa) LIKE LOWER(:search_term) THEN 2
                        -- Priority 3: Match in attributes
                        WHEN tt.tenThuocTinhHH IS NOT NULL AND LOWER(tt.tenThuocTinhHH) LIKE LOWER(:search_term) THEN 3
                        -- Priority 4: Match in description
                        WHEN LOWER(h.mota) LIKE LOWER(:search_term) THEN 4
                        ELSE 5
                    END as search_priority,
                    CASE 
                        WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' 
                        THEN 0 
                        ELSE 1 
                    END as image_priority,
                    -- Thêm logic xử lý giá khuyến mãi
                    CASE 
                        WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                        THEN h.giakhuyenmai
                        ELSE h.giathamkhao
                    END as gia_hien_thi,
                    -- Tính % giảm giá
                    CASE 
                        WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                        THEN ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100)
                        ELSE 0
                    END as discount_percent
                    FROM hanghoa h
                    LEFT JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
                    WHERE LOWER(h.tenhanghoa) LIKE LOWER(:search_term)
                       OR LOWER(h.mota) LIKE LOWER(:search_term)
                       OR (tt.tenThuocTinhHH IS NOT NULL AND LOWER(tt.tenThuocTinhHH) LIKE LOWER(:search_term))
                    ORDER BY search_priority ASC, image_priority ASC, h.tenhanghoa ASC
                    LIMIT 50";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search_term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':exact_keyword', $keyword, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            error_log("searchHanghoa - Found " . count($results) . " results");

            return $results;
        } catch (PDOException $e) {
            error_log("searchHanghoa - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate average rating for a product
     * Uses indexed query for performance
     * Returns 0 if no reviews
     * 
     * @param int $idhanghoa Product ID
     * @return array ['average' => float, 'count' => int]
     */
    public function getAverageRating($idhanghoa)
    {
        try {
            // Chỉ tính rating từ các bình luận visible (không bị ẩn/xóa)
            $sql = "SELECT COALESCE(AVG(rating), 0) as avg_rating,
                           COUNT(*) as review_count
                    FROM product_reviews 
                    WHERE ma_san_pham = ? 
                    AND is_approved = 1
                    AND (status = 'visible' OR status IS NULL)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return [
                'average' => round($result->avg_rating, 1),
                'count' => (int)$result->review_count
            ];
        } catch (PDOException $e) {
            error_log("Error getting average rating: " . $e->getMessage());
            return ['average' => 0, 'count' => 0];
        }
    }

    /**
     * Get review count for a product
     * Helper method for quick count retrieval
     * 
     * @param int $idhanghoa Product ID
     * @return int Number of approved reviews
     */
    public function getReviewCount($idhanghoa)
    {
        try {
            // Chỉ đếm bình luận visible (không bị ẩn/xóa)
            $sql = "SELECT COUNT(*) FROM product_reviews 
                    WHERE ma_san_pham = ? AND is_approved = 1
                    AND (status = 'visible' OR status IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting review count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Kiểm tra dữ liệu liên quan trước khi xóa hàng hóa
     */
    public function checkRelatedData($idhanghoa)
    {
        $relatedData = [];

        try {
            // Kiểm tra bảng tồn kho
            $sql = "SELECT COUNT(*) as count FROM tonkho WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['tonkho'] = [
                    'table_name' => 'tonkho',
                    'display_name' => 'Tồn kho',
                    'count' => $count,
                    'description' => 'Hàng hóa này còn có ' . $count . ' bản ghi tồn kho'
                ];
            }

            // Kiểm tra bảng chi tiết hóa đơn
            $sql = "SELECT COUNT(*) as count FROM chitiethoadon WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['chitiethoadon'] = [
                    'table_name' => 'chitiethoadon',
                    'display_name' => 'Chi tiết hóa đơn',
                    'count' => $count,
                    'description' => 'Hàng hóa này có trong ' . $count . ' hóa đơn'
                ];
            }

            // Kiểm tra bảng chi tiết phiếu nhập
            $sql = "SELECT COUNT(*) as count FROM chitietphieunhap WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['chitietphieunhap'] = [
                    'table_name' => 'chitietphieunhap',
                    'display_name' => 'Chi tiết phiếu nhập',
                    'count' => $count,
                    'description' => 'Hàng hóa này có trong ' . $count . ' phiếu nhập'
                ];
            }

            // Kiểm tra bảng thuộc tính hàng hóa
            $sql = "SELECT COUNT(*) as count FROM thuoctinhhh WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['thuoctinhhh'] = [
                    'table_name' => 'thuoctinhhh',
                    'display_name' => 'Thuộc tính hàng hóa',
                    'count' => $count,
                    'description' => 'Hàng hóa này có ' . $count . ' thuộc tính'
                ];
            }

            // Kiểm tra bảng đơn giá
            $sql = "SELECT COUNT(*) as count FROM dongia WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['dongia'] = [
                    'table_name' => 'dongia',
                    'display_name' => 'Đơn giá',
                    'count' => $count,
                    'description' => 'Hàng hóa này có ' . $count . ' mức giá'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error checking related data: " . $e->getMessage());
        }

        return $relatedData;
    }

    /**
     * Đưa ra gợi ý hành động dựa trên bảng có liên quan
     */
    private function getSuggestedAction($tableName)
    {
        $suggestions = [
            'tonkho' => 'Hãy xóa tất cả bản ghi tồn kho của hàng hóa này trước khi xóa hàng hóa.',
            'chitiethoadon' => 'Hàng hóa này đã được bán trong các hóa đơn. Không nên xóa để đảm bảo tính toàn vẹn dữ liệu.',
            'chitietphieunhap' => 'Hàng hóa này đã được nhập kho. Không nên xóa để đảm bảo tính toàn vẹn dữ liệu.',
            'thuoctinhhh' => 'Hãy xóa tất cả thuộc tính của hàng hóa này trước.',
            'dongia' => 'Hãy xóa tất cả đơn giá của hàng hóa này trước.'
        ];

        return isset($suggestions[$tableName]) ? $suggestions[$tableName] : 'Hãy xóa dữ liệu liên quan trước khi xóa hàng hóa này.';
    }

    public function CheckRelations($idhanghoa)
    {
        // Giữ lại method cũ để tương thích
        $relatedData = $this->checkRelatedData($idhanghoa);
        return array_keys($relatedData);
    }

    public function GetAllThuongHieu()
    {
        $sql = 'SELECT * FROM thuonghieu';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function GetAllDonViTinh()
    {
        $sql = 'SELECT * FROM donvitinh';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function GetAllNhanVien()
    {
        $sql = 'SELECT * FROM nhanvien';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    // Lấy thông tin thương hiệu theo ID
    public function GetThuongHieuById($idThuongHieu)
    {
        $sql = 'SELECT * FROM thuonghieu WHERE idThuongHieu = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idThuongHieu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function GetAllHinhAnh()
    {
        try {
            $sql = 'SELECT h.*,
                    (SELECT COUNT(*) FROM hanghoa WHERE hinhanh = h.id) as usage_count
                    FROM hinhanh h
                    ORDER BY h.ngay_tao DESC';
            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute();
            return $getAll->fetchAll();
        } catch (Exception $e) {
            error_log("Error in GetAllHinhAnh: " . $e->getMessage());
            return array();
        }
    }

    public function GetHinhAnhById($id)
    {
        if (!$id) return null;

        try {
            error_log("GetHinhAnhById - Bắt đầu tìm hình ảnh với ID: " . $id);

            // Kiểm tra xem bảng hinhanh có tồn tại không
            try {
                $checkTable = $this->db->query("SHOW TABLES LIKE 'hinhanh'");
                if ($checkTable->rowCount() == 0) {
                    error_log("GetHinhAnhById - Bảng hinhanh không tồn tại");
                    return null;
                }
            } catch (PDOException $e) {
                error_log("GetHinhAnhById - Lỗi khi kiểm tra bảng hinhanh: " . $e->getMessage());
            }

            // Kiểm tra cấu trúc bảng hinhanh
            try {
                $columns = $this->db->query("SHOW COLUMNS FROM hinhanh");
                $columnNames = [];
                while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                    $columnNames[] = $column['Field'];
                }
                error_log("GetHinhAnhById - Cấu trúc bảng hinhanh: " . implode(", ", $columnNames));
            } catch (PDOException $e) {
                error_log("GetHinhAnhById - Lỗi khi lấy cấu trúc bảng hinhanh: " . $e->getMessage());
            }

            $sql = 'SELECT * FROM hinhanh WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $hinhanh = $stmt->fetch(PDO::FETCH_OBJ);

            if ($hinhanh) {
                // Log đường dẫn để debug
                error_log("GetHinhAnhById - ID: " . $id . ", đường dẫn gốc: " . $hinhanh->duong_dan);
                error_log("GetHinhAnhById - Thông tin đầy đủ: " . print_r($hinhanh, true));

                // Xử lý đường dẫn hình ảnh
                if (strpos($hinhanh->duong_dan, 'data:image') === 0) {
                    // Nếu là base64, giữ nguyên
                    error_log("GetHinhAnhById - Đường dẫn là base64");
                    return $hinhanh;
                } else {
                    // Đường dẫn chính xác từ gốc web app
                    if (!empty($hinhanh->duong_dan)) {
                        // Chuẩn hóa đường dẫn
                        $hinhanh->duong_dan = str_replace('\\', '/', $hinhanh->duong_dan);

                        // Nếu đường dẫn chưa có "administrator/" ở đầu và bắt đầu bằng "uploads/"
                        if (
                            strpos($hinhanh->duong_dan, 'administrator/') !== 0 &&
                            strpos($hinhanh->duong_dan, 'uploads/') === 0
                        ) {
                            $hinhanh->duong_dan = 'administrator/' . $hinhanh->duong_dan;
                            error_log("GetHinhAnhById - Đường dẫn sau khi thêm tiền tố: " . $hinhanh->duong_dan);
                        }
                    }
                    error_log("GetHinhAnhById - Đường dẫn cuối cùng: " . $hinhanh->duong_dan);
                    return $hinhanh;
                }
            }
            error_log("GetHinhAnhById - Không tìm thấy hình ảnh với ID: " . $id);
            return null;
        } catch (PDOException $e) {
            error_log("Error in GetHinhAnhById: " . $e->getMessage());
            return null;
        }
    }

    // Phương thức tạo bảng hanghoa_hinhanh nếu chưa tồn tại
    public function CreateHanghoaHinhanhTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS hanghoa_hinhanh (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idhanghoa INT NOT NULL,
                idhinhanh INT NOT NULL,
                UNIQUE KEY (idhanghoa, idhinhanh)
            )";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in CreateHanghoaHinhanhTable: " . $e->getMessage());
            return false;
        }
    }

    // Biến static để lưu trữ trạng thái kiểm tra cột file_hash
    private static $hasCheckedFileHashColumn = false;
    private static $fileHashColumnExists = false;

    public function ThemHinhAnh($ten_file, $loai_file, $duong_dan, $file_hash = null)
    {
        try {
            // Chỉ kiểm tra cột file_hash một lần trong suốt thời gian chạy ứng dụng
            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                // Nếu chưa có cột file_hash, thêm cột này vào bảng
                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                }
            }

            // Sử dụng prepared statement được tối ưu hóa
            if ($file_hash) {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao, file_hash)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, ?)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan, $file_hash]);
            } else {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan]);
            }
        } catch (PDOException $e) {
            error_log("Error in ThemHinhAnh: " . $e->getMessage());
            return false;
        }
    }

    public function XoaHinhAnh($id)
    {
        try {
            // Xóa record trong database
            $sql = "DELETE FROM hinhanh WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in XoaHinhAnh: " . $e->getMessage());
            return false;
        }
    }

    // Áp dụng hình ảnh cho sản phẩm
    public function ApplyImageToProduct($idhanghoa, $id_hinhanh)
    {
        try {
            // Đảm bảo kết nối đang hoạt động
            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("Không có kết nối database hợp lệ");
                return false;
            }

            // Kiểm tra trạng thái transaction hiện tại và bắt đầu nếu chưa có
            try {
                // Chỉ bắt đầu transaction nếu chưa có transaction nào đang active
                if (!$this->db->inTransaction()) {
                    $this->db->beginTransaction();
                }
            } catch (PDOException $e) {
                // Ghi log lỗi và trả về false
                error_log("Lỗi transaction: " . $e->getMessage());
                return false;
            }

            // Đảm bảo bảng hanghoa_hinhanh đã được tạo
            $this->CreateHanghoaHinhanhTable();

            // Cập nhật hình ảnh chính cho sản phẩm
            $sql = 'UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?';
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_hinhanh, $idhanghoa]);

            if (!$result) {
                throw new Exception("Không thể cập nhật hình ảnh chính");
            }

            // Thêm quan hệ vào bảng hanghoa_hinhanh nếu chưa tồn tại
            $checkSql = 'SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?';
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$idhanghoa, $id_hinhanh]);
            $exists = $checkStmt->fetchColumn() > 0;

            if (!$exists) {
                $insertSql = 'INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)';
                $insertStmt = $this->db->prepare($insertSql);
                $insertResult = $insertStmt->execute([$idhanghoa, $id_hinhanh]);

                if (!$insertResult) {
                    throw new Exception("Không thể thêm quan hệ hình ảnh");
                }
            }

            // Cập nhật trạng thái hình ảnh thành đang sử dụng
            $this->UpdateImageStatus($id_hinhanh);

            // Hoàn tất giao dịch
            $this->db->commit();

            return true;
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            try {
                $this->db->rollBack();
            } catch (PDOException $rollbackException) {
                error_log("Lỗi khi rollback: " . $rollbackException->getMessage());
            }
            error_log("Error in ApplyImageToProduct: " . $e->getMessage());
            return false;
        }
    }

    public function GetProductsByImageId($imageId)
    {
        $sql = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE hinhanh = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$imageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function UpdateProductImages($oldImageId, $newImageId)
    {
        try {
            $sql = "UPDATE hanghoa SET hinhanh = ? WHERE hinhanh = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newImageId, $oldImageId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function GetImagePath($id)
    {
        $sql = 'SELECT duong_dan FROM hinhanh WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->duong_dan : null;
    }

    public function UpdateImageStatus($id)
    {
        $sql = 'UPDATE hinhanh SET trang_thai = 1 WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Tìm sản phẩm theo tên
    public function FindProductsByName($name)
    {
        $sql = 'SELECT * FROM hanghoa WHERE tenhanghoa LIKE ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%" . $name . "%"]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy ID được insert cuối cùng
    public function GetLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function FindProductsByExactName($productName)
    {
        try {
            $sql = "SELECT * FROM hanghoa WHERE tenhanghoa = :productName";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":productName", $productName);
            $cmd->execute();
            $result = $cmd->fetchAll(PDO::FETCH_OBJ);
            return $result;
        } catch (PDOException $e) {
            error_log("Error in FindProductsByExactName: " . $e->getMessage());
            return array();
        }
    }

    // Kiểm tra xem hình ảnh đã tồn tại chưa
    public function CheckImageExists($fileName)
    {
        try {
            $sql = "SELECT COUNT(*) FROM hinhanh WHERE ten_file = :fileName";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":fileName", $fileName);
            $cmd->execute();
            return $cmd->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error in CheckImageExists: " . $e->getMessage());
            return false;
        }
    }

    // Thêm phương thức kiểm tra trùng lặp ảnh bằng hash MD5
    public function CheckImageExistsByHash($fileHash)
    {
        try {
            // Sử dụng biến static đã kiểm tra từ hàm ThemHinhAnh
            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                // Nếu chưa có cột file_hash, thêm cột này vào bảng
                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                    return false; // Vì vừa thêm cột, chắc chắn chưa có dữ liệu
                }
            } else if (!self::$fileHashColumnExists) {
                return false; // Nếu đã kiểm tra trước đó và biết rằng cột không tồn tại
            }

            // Sử dụng prepared statement với tham số được bind trực tiếp
            $sql = "SELECT id FROM hinhanh WHERE file_hash = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fileHash]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ? $result->id : false;
        } catch (PDOException $e) {
            error_log("Error in CheckImageExistsByHash: " . $e->getMessage());
            return false;
        }
    }

    // Đếm số lượng hình ảnh đã áp dụng cho từng sản phẩm
    public function CountImagesForProduct($idhanghoa)
    {
        try {
            $sql = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = :idhanghoa";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":idhanghoa", $idhanghoa);
            $cmd->execute();
            return $cmd->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in CountImagesForProduct: " . $e->getMessage());
            return 0;
        }
    }

    // Lấy tất cả thông tin về hình ảnh đã áp dụng cho một sản phẩm
    public function GetAllImagesForProduct($idhanghoa)
    {
        try {
            $sql = "SELECT h.* FROM hinhanh h
                    INNER JOIN hanghoa_hinhanh hh ON h.id = hh.idhinhanh
                    WHERE hh.idhanghoa = :idhanghoa";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":idhanghoa", $idhanghoa);
            $cmd->execute();
            return $cmd->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in GetAllImagesForProduct: " . $e->getMessage());
            return [];
        }
    }

    // Thêm phương thức để cập nhật hình ảnh của sản phẩm
    public function CapNhatHinhAnhSanPham($idhanghoa, $id_hinhanh_moi)
    {
        try {
            $sql = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_hinhanh_moi, $idhanghoa]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Kiểm tra hình ảnh không khớp tên với sản phẩm
    public function GetMismatchedProductImages()
    {
        try {
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, ha.id, ha.ten_file
                   FROM hanghoa h
                   JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE ha.ten_file NOT LIKE CONCAT('%', h.tenhanghoa, '%')
                   AND ha.ten_file NOT LIKE CONCAT('%', REPLACE(h.tenhanghoa, ' ', ''), '%')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in GetMismatchedProductImages: " . $e->getMessage());
            return [];
        }
    }

    // Kiểm tra và tìm hình ảnh bị thiếu
    public function FindMissingImages()
    {
        try {
            // Tìm các hình ảnh được tham chiếu trong bảng hanghoa nhưng không tồn tại trong bảng hinhanh
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh
                   FROM hanghoa h
                   LEFT JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE h.hinhanh > 0 AND ha.id IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in FindMissingImages: " . $e->getMessage());
            return [];
        }
    }

    // Tìm hình ảnh có tên khớp chính xác với tên sản phẩm
    public function FindExactMatchImage($idhanghoa)
    {
        try {
            // Lấy thông tin sản phẩm
            $sql = "SELECT tenhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return null;
            }

            // Lấy tất cả hình ảnh
            $sql = "SELECT * FROM hinhanh";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Tìm hình ảnh khớp chính xác
            foreach ($images as $image) {
                if ($this->IsExactImageNameMatch($product->tenhanghoa, $image->ten_file)) {
                    return $image;
                }
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error in FindExactMatchImage: " . $e->getMessage());
            return null;
        }
    }

    // Áp dụng tất cả hình ảnh khớp chính xác cho sản phẩm
    public function ApplyAllExactMatchImages()
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // Lấy tất cả sản phẩm
            $sqlProducts = "SELECT idhanghoa, tenhanghoa FROM hanghoa";
            $stmtProducts = $this->db->prepare($sqlProducts);
            $stmtProducts->execute();
            $products = $stmtProducts->fetchAll(PDO::FETCH_OBJ);

            // Lấy tất cả hình ảnh
            $sqlImages = "SELECT id, ten_file FROM hinhanh";
            $stmtImages = $this->db->prepare($sqlImages);
            $stmtImages->execute();
            $images = $stmtImages->fetchAll(PDO::FETCH_OBJ);

            $matchesCount = 0;

            foreach ($products as $product) {
                foreach ($images as $image) {
                    if ($this->IsExactImageNameMatch($product->tenhanghoa, $image->ten_file)) {
                        // Cập nhật hình ảnh chính cho sản phẩm
                        $sqlUpdate = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                        $stmtUpdate->execute([$image->id, $product->idhanghoa]);

                        // Thêm liên kết vào bảng hanghoa_hinhanh
                        $checkSql = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh =?";
                        $checkStmt = $this->db->prepare($checkSql);
                        $checkStmt->execute([$product->idhanghoa, $image->id]);
                        $exists = $checkStmt->fetchColumn() > 0;

                        if (!$exists) {
                            $insertSql = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $insertStmt = $this->db->prepare($insertSql);
                            $insertStmt->execute([$product->idhanghoa, $image->id]);
                        }

                        $matchesCount++;
                        break; // Chỉ áp dụng hình ảnh đầu tiên khớp
                    }
                }
            }

            $this->db->commit();
            return $matchesCount;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in ApplyAllExactMatchImages: " . $e->getMessage());
            return 0;
        }
    }

    // Gỡ bỏ hình ảnh khỏi sản phẩm
    public function RemoveImageFromProduct($idhanghoa)
    {
        try {
            // Log cho việc debug
            error_log("RemoveImageFromProduct - Bắt đầu gỡ bỏ hình ảnh cho sản phẩm ID: " . $idhanghoa);

            // Kiểm tra xem sản phẩm có tồn tại không
            $checkProduct = "SELECT hinhanh FROM hanghoa WHERE idhanghoa = ?";
            $stmtCheckProduct = $this->db->prepare($checkProduct);
            $stmtCheckProduct->execute([$idhanghoa]);
            $currentImageId = $stmtCheckProduct->fetchColumn();

            if ($currentImageId === false) {
                error_log("RemoveImageFromProduct - Sản phẩm không tồn tại: " . $idhanghoa);
                return false;
            }

            error_log("RemoveImageFromProduct - Hình ảnh hiện tại của sản phẩm: " . ($currentImageId ?: 'NULL'));

            // Bắt đầu giao dịch nếu chưa có
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // Đặt hình ảnh về NULL cho sản phẩm
            $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([$idhanghoa]);

            if (!$result) {
                error_log("RemoveImageFromProduct - Lỗi khi cập nhật sản phẩm: " . implode(", ", $stmtUpdate->errorInfo()));
                $this->db->rollBack();
                return false;
            }

            error_log("RemoveImageFromProduct - Đã cập nhật sản phẩm thành NULL");

            // Xóa quan hệ trong bảng hanghoa_hinhanh nếu có hình ảnh cũ
            if ($currentImageId) {
                try {
                    // Kiểm tra xem bảng hanghoa_hinhanh có tồn tại không
                    $checkTableSql = "SHOW TABLES LIKE 'hanghoa_hinhanh'";
                    $checkTableStmt = $this->db->prepare($checkTableSql);
                    $checkTableStmt->execute();
                    $tableExists = $checkTableStmt->rowCount() > 0;

                    if (!$tableExists) {
                        error_log("RemoveImageFromProduct - Bảng hanghoa_hinhanh chưa tồn tại, đang tạo mới");
                        $this->CreateHanghoaHinhanhTable();
                    }

                    $sqlDeleteRelation = "DELETE FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                    $stmtDeleteRelation = $this->db->prepare($sqlDeleteRelation);
                    $resultDelete = $stmtDeleteRelation->execute([$idhanghoa, $currentImageId]);

                    if (!$resultDelete) {
                        error_log("RemoveImageFromProduct - Lỗi khi xóa quan hệ: " . implode(", ", $stmtDeleteRelation->errorInfo()));
                    } else {
                        error_log("RemoveImageFromProduct - Đã xóa quan hệ thành công");
                    }
                } catch (Exception $e) {
                    error_log("RemoveImageFromProduct - Lỗi khi xử lý bảng hanghoa_hinhanh: " . $e->getMessage());
                    // Không rollback ở đây vì việc xóa quan hệ không quan trọng bằng việc cập nhật hình ảnh chính
                }
            }

            // Hoàn tất giao dịch
            $this->db->commit();
            error_log("RemoveImageFromProduct - Gỡ bỏ hình ảnh hoàn tất thành công");

            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in RemoveImageFromProduct: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Unexpected error in RemoveImageFromProduct: " . $e->getMessage());
            return false;
        }
    }

    // Gỡ bỏ tất cả hình ảnh không khớp tên với sản phẩm
    public function RemoveAllMismatchedImages()
    {
        try {
            error_log("RemoveAllMismatchedImages - Bắt đầu gỡ bỏ tất cả hình ảnh không khớp");

            // Bắt đầu giao dịch nếu chưa có
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // Lấy danh sách sản phẩm có hình ảnh không khớp
            $mismatched = $this->GetMismatchedProductImages();
            $count = 0;

            if (empty($mismatched)) {
                error_log("RemoveAllMismatchedImages - Không tìm thấy hình ảnh không khớp nào");
                $this->db->commit();
                return 0;
            }

            error_log("RemoveAllMismatchedImages - Tìm thấy " . count($mismatched) . " hình ảnh không khớp");

            foreach ($mismatched as $item) {
                error_log("RemoveAllMismatchedImages - Đang xử lý sản phẩm ID: " . $item->idhanghoa . ", Tên: " . $item->tenhanghoa . ", Hình ảnh ID: " . $item->id);

                // Đặt hình ảnh về NULL cho sản phẩm
                $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $resultUpdate = $stmtUpdate->execute([$item->idhanghoa]);

                if (!$resultUpdate) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi cập nhật sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtUpdate->errorInfo()));
                    continue;
                }

                // Xóa quan hệ trong bảng hanghoa_hinhanh
                $sqlDeleteRelation = "DELETE FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                $stmtDeleteRelation = $this->db->prepare($sqlDeleteRelation);
                $resultDelete = $stmtDeleteRelation->execute([$item->idhanghoa, $item->id]);

                if (!$resultDelete) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi xóa quan hệ cho sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtDeleteRelation->errorInfo()));
                }

                $count++;
                error_log("RemoveAllMismatchedImages - Đã gỡ bỏ hình ảnh cho sản phẩm ID: " . $item->idhanghoa);
            }

            // Hoàn tất giao dịch
            $this->db->commit();

            error_log("RemoveAllMismatchedImages - Hoàn tất gỡ bỏ " . $count . " hình ảnh");
            return $count;
        } catch (PDOException $e) {
            // Rollback nếu có lỗi
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Bắt các exception khác
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Unexpected error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra xem tên file hình ảnh có khớp chính xác với tên sản phẩm không (phân biệt hoa thường)
    public function IsExactImageNameMatch($tenhanghoa, $ten_file)
    {
        // Tách tên file không có phần mở rộng
        $imageNameWithoutExt = pathinfo($ten_file, PATHINFO_FILENAME);

        // So sánh chính xác giữa tên sản phẩm và tên file, chỉ loại bỏ khoảng trắng đầu/cuối
        // Giữ nguyên phân biệt chữ hoa/thường để so khớp tuyệt đối
        if (trim($tenhanghoa) === trim($imageNameWithoutExt)) {
            return true;
        }

        return false;
    }

    /**
     * Filter products by price, color, size, and rating
     * 
     * @param array $filters Array containing filter criteria
     * @return array Filtered products
     */
    public function filterProducts($filters)
    {
        try {
            // Query với rating subqueries
            // Lọc sản phẩm: chỉ lấy những sản phẩm không bị ngừng bán (trang_thai != 2)
            $sql = 'SELECT DISTINCT h.*,
                    (SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.ma_san_pham = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = "visible" OR pr.status IS NULL)) as average_rating,
                    (SELECT COUNT(*) FROM product_reviews pr WHERE pr.ma_san_pham = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = "visible" OR pr.status IS NULL)) as review_count
                    FROM hanghoa h';

            $joins = [];
            $conditions = ['h.trang_thai != 2'];
            $params = [];

            // Color and Size filters using thuoctinhhh table
            if (!empty($filters['colors']) || !empty($filters['sizes'])) {
                $joins[] = 'INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa';

                $filterConditions = [];

                // Color filter: Tìm ID thuộc tính màu sắc động
                if (!empty($filters['colors'])) {
                    // Lấy ID thuộc tính màu sắc từ database
                    $colorAttrStmt = $this->db->query("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
                    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($colorAttr) {
                        $colorAttrId = $colorAttr['idThuocTinh'];
                        
                        // Mapping màu từ tiếng Anh sang tiếng Việt
                        $colorMapping = [
                            'red' => 'Đỏ',
                            'blue' => 'Xanh dương',
                            'green' => 'Xanh lá',
                            'yellow' => 'Vàng',
                            'orange' => 'Cam',
                            'purple' => 'Tím',
                            'pink' => 'Hồng',
                            'black' => 'Đen',
                            'white' => 'Trắng',
                            'gray' => 'Xám',
                            'brown' => 'Nâu',
                            'silver' => 'Bạc'
                        ];
                        
                        $colorOrConditions = [];
                        foreach ($filters['colors'] as $colorEn) {
                            $colorEn = trim($colorEn);
                            // Chuyển từ tiếng Anh sang tiếng Việt
                            $colorVi = isset($colorMapping[$colorEn]) ? $colorMapping[$colorEn] : $colorEn;
                            
                            // Tìm chính xác tên màu (không dùng LIKE để tránh lỗi)
                            $colorOrConditions[] = "LOWER(TRIM(tt.tenThuocTinhHH)) = LOWER(?)";
                            $params[] = $colorVi;
                        }
                        
                        $colorCondition = "tt.idThuocTinh = $colorAttrId AND (" . implode(' OR ', $colorOrConditions) . ")";
                        $filterConditions[] = $colorCondition;
                    }
                }

                // Size filter: check RAM (8) or Storage (9) or Battery (10)
                if (!empty($filters['sizes'])) {
                    $sizeValues = array_map(function ($s) {
                        return trim($s);
                    }, $filters['sizes']);

                    $sizeOrConditions = [];
                    foreach ($sizeValues as $size) {
                        $sizeOrConditions[] = "CONCAT(',', tt.tenThuocTinhHH, ',') LIKE ?";
                        $params[] = '%,' . $size . ',%';
                    }

                    // Check multiple size-related IDs: 8 (RAM), 9 (Storage), 10 (Battery)
                    $sizeCondition = "tt.idThuocTinh IN (8, 9, 10) AND (" . implode(' OR ', $sizeOrConditions) . ")";
                    $filterConditions[] = $sizeCondition;
                }

                if (!empty($filterConditions)) {
                    if (count($filterConditions) > 1) {
                        $conditions[] = '(' . implode(' OR ', $filterConditions) . ')';
                    } else {
                        $conditions[] = $filterConditions[0];
                    }
                }
            }

            // Price filter - lấy giá hiển thị (khuyến mại nếu có, nếu không thì giá tham khảo)
            if (isset($filters['min_price']) && isset($filters['max_price'])) {
                $conditions[] = '(CASE 
                    WHEN h.giakhuyenmai > 0 THEN h.giakhuyenmai 
                    ELSE h.giathamkhao 
                END) BETWEEN ? AND ?';
                $params[] = $filters['min_price'];
                $params[] = $filters['max_price'];
            }

            // Category filter
            if (isset($filters['category']) && $filters['category'] > 0) {
                $conditions[] = 'h.idloaihang = ?';
                $params[] = $filters['category'];
            }

            // Rating filter - lọc theo đánh giá trung bình
            if (isset($filters['min_rating']) && $filters['min_rating'] > 0) {
                // Subquery để tính rating trung bình
                $conditions[] = '(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.ma_san_pham = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = "visible" OR pr.status IS NULL)) >= ?';
                $params[] = $filters['min_rating'];
            }

            // Build final query
            if (!empty($joins)) {
                $sql .= ' ' . implode(' ', $joins);
            }

            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            // Order by: sản phẩm có ảnh lên trước, sau đó theo tên
            $sql .= ' ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != "" THEN 0 ELSE 1 END) ASC, h.tenhanghoa ASC';

            // Execute query
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stmt->setFetchMode(PDO::FETCH_OBJ);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in filterProducts: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'N/A'));
            error_log("Params: " . print_r($params ?? [], true));
            return [];
        }
    }

    /**
     * Get available filter options (colors, sizes, price range)
     * 
     * @param int|null $idloaihang Category ID to filter options by
     * @return array Available filter options
     */
    public function getFilterOptions($idloaihang = null)
    {
        try {
            $options = [
                'colors' => [],
                'sizes' => [],
                'price_range' => ['min' => 0, 'max' => 100000000]
            ];

            // Build base query for attributes
            $sql = 'SELECT DISTINCT tt.tenThuocTinhHH 
                    FROM thuoctinhhh tt
                    INNER JOIN hanghoa h ON tt.idhanghoa = h.idhanghoa';

            $params = [];

            if ($idloaihang) {
                $sql .= ' WHERE h.idloaihang = ?';
                $params[] = $idloaihang;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $attributes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Extract colors and sizes from attributes
            $colorKeywords = ['màu', 'color'];
            $sizeKeywords = ['kích', 'size', 'cỡ'];

            foreach ($attributes as $attr) {
                $attrLower = mb_strtolower($attr, 'UTF-8');

                // Check if it's a color attribute
                foreach ($colorKeywords as $keyword) {
                    if (strpos($attrLower, $keyword) !== false) {
                        // Extract color value
                        $color = $this->extractAttributeValue($attr, $colorKeywords);
                        if ($color && !in_array($color, $options['colors'])) {
                            $options['colors'][] = $color;
                        }
                        break;
                    }
                }

                // Check if it's a size attribute
                foreach ($sizeKeywords as $keyword) {
                    if (strpos($attrLower, $keyword) !== false) {
                        // Extract size value
                        $size = $this->extractAttributeValue($attr, $sizeKeywords);
                        if ($size && !in_array($size, $options['sizes'])) {
                            $options['sizes'][] = $size;
                        }
                        break;
                    }
                }
            }

            // Get price range
            $priceSql = 'SELECT MIN(giathamkhao) as min_price, MAX(giathamkhao) as max_price FROM hanghoa';

            if ($idloaihang) {
                $priceSql .= ' WHERE idloaihang = ?';
                $priceStmt = $this->db->prepare($priceSql);
                $priceStmt->execute([$idloaihang]);
            } else {
                $priceStmt = $this->db->prepare($priceSql);
                $priceStmt->execute();
            }

            $priceRange = $priceStmt->fetch(PDO::FETCH_OBJ);

            if ($priceRange) {
                $options['price_range']['min'] = (int)$priceRange->min_price;
                $options['price_range']['max'] = (int)$priceRange->max_price;
            }

            return $options;
        } catch (PDOException $e) {
            error_log("Error in getFilterOptions: " . $e->getMessage());
            return [
                'colors' => [],
                'sizes' => [],
                'price_range' => ['min' => 0, 'max' => 100000000]
            ];
        }
    }

    /**
     * Extract attribute value from attribute string
     * 
     * @param string $attribute Full attribute string
     * @param array $keywords Keywords to remove
     * @return string|null Extracted value
     */
    private function extractAttributeValue($attribute, $keywords)
    {
        $value = $attribute;

        // Remove common separators and keywords
        $value = preg_replace('/[:\-\|]/u', ' ', $value);

        foreach ($keywords as $keyword) {
            $value = preg_replace('/' . preg_quote($keyword, '/') . '/iu', '', $value);
        }

        $value = trim($value);

        // Return null if empty or too long
        if (empty($value) || mb_strlen($value, 'UTF-8') > 50) {
            return null;
        }

        return $value;
    }

    /**
     * Get related products with stricter relevance criteria
     * 
     * @param int $idhanghoa Current product ID
     * @param int $limit Maximum number of similar products to return
     * @return array Array of similar product objects
     * 
     * IMPROVED ALGORITHM (v2.0):
     * Tier 1: Cùng thương hiệu + Cùng loại hàng (ưu tiên cao nhất)
     * Tier 2: Cùng loại hàng + Giá tương tự (±30%)
     * Tier 3: Cùng thương hiệu (khác loại hàng)
     * Tier 4: Cùng loại hàng (bất kỳ giá)
     * Tier 5: Giá tương tự (fallback)
     * Tier 6: Bất kỳ sản phẩm nào
     */
    public function getRelatedProducts($idhanghoa, $limit = 6)
    {
        try {
            // Get current product info
            $current = $this->HanghoaGetbyId($idhanghoa);

            if (!$current) {
                return [];
            }

            $results = [];
            $excludeIds = [$idhanghoa]; // Always exclude current product

            // Tier 1: Cùng thương hiệu + Cùng loại hàng (BEST MATCH)
            if (!empty($current->idThuongHieu) && !empty($current->idloaihang)) {
                $tier1 = $this->getProductsSameBrandSameCategory($current, $limit, $excludeIds);
                foreach ($tier1 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            // Tier 2: Cùng loại hàng + Giá tương tự (±30%)
            if (count($results) < $limit && !empty($current->idloaihang)) {
                $remaining = $limit - count($results);
                $tier2 = $this->getProductsSameCategorySimilarPrice($current, $remaining, $excludeIds);
                foreach ($tier2 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            // Tier 3: Cùng thương hiệu (khác loại hàng)
            if (count($results) < $limit && !empty($current->idThuongHieu)) {
                $remaining = $limit - count($results);
                $tier3 = $this->getProductsSameBrandOnly($current, $remaining, $excludeIds);
                foreach ($tier3 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            // Tier 4: Cùng loại hàng (bất kỳ giá)
            if (count($results) < $limit && !empty($current->idloaihang)) {
                $remaining = $limit - count($results);
                $tier4 = $this->getProductsSameCategoryOnly($current, $remaining, $excludeIds);
                foreach ($tier4 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            // Tier 5: Giá tương tự (±30%)
            if (count($results) < $limit) {
                $remaining = $limit - count($results);
                $tier5 = $this->getSimilarPriceProducts($current, $remaining, $excludeIds);
                foreach ($tier5 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            // Tier 6: Bất kỳ sản phẩm nào (fallback)
            if (count($results) < $limit) {
                $remaining = $limit - count($results);
                $tier6 = $this->getAnyProducts($current, $remaining, $excludeIds);
                foreach ($tier6 as $p) {
                    $results[] = $p;
                }
            }

            return array_slice($results, 0, $limit);
        } catch (Exception $e) {
            error_log("Error getting related products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tier 1: Cùng thương hiệu + Cùng loại hàng
     */
    private function getProductsSameBrandSameCategory($current, $limit, $excludeIds = [])
    {
        if (empty($current->idThuongHieu) || empty($current->idloaihang)) {
            return [];
        }

        $excludeClause = "";
        $params = [$current->idhanghoa, $current->idThuongHieu, $current->idloaihang, $current->giathamkhao];
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = "AND h.idhanghoa NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idThuongHieu = ?
                AND h.idloaihang = ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                    ABS(h.giathamkhao - ?) ASC,
                    h.tenhanghoa ASC
                LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Tier 2: Cùng loại hàng + Giá tương tự (±30%)
     */
    private function getProductsSameCategorySimilarPrice($current, $limit, $excludeIds = [])
    {
        if (empty($current->idloaihang)) {
            return [];
        }

        $priceMin = $current->giathamkhao * 0.7;
        $priceMax = $current->giathamkhao * 1.3;

        $excludeClause = "";
        $params = [$current->idhanghoa, $current->idloaihang, $priceMin, $priceMax];
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = "AND h.idhanghoa NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }
        
        $params[] = $current->giathamkhao; // For ORDER BY

        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idloaihang = ?
                AND h.giathamkhao BETWEEN ? AND ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                    ABS(h.giathamkhao - ?) ASC,
                    h.tenhanghoa ASC
                LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Tier 3: Cùng thương hiệu (khác loại hàng)
     */
    private function getProductsSameBrandOnly($current, $limit, $excludeIds = [])
    {
        if (empty($current->idThuongHieu)) {
            return [];
        }

        $excludeClause = "";
        $params = [$current->idhanghoa, $current->idThuongHieu, $current->giathamkhao];
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = "AND h.idhanghoa NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idThuongHieu = ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                    ABS(h.giathamkhao - ?) ASC,
                    h.tenhanghoa ASC
                LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Tier 4: Cùng loại hàng (bất kỳ giá)
     */
    private function getProductsSameCategoryOnly($current, $limit, $excludeIds = [])
    {
        if (empty($current->idloaihang)) {
            return [];
        }

        $excludeClause = "";
        $params = [$current->idhanghoa, $current->idloaihang, $current->giathamkhao];
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = "AND h.idhanghoa NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idloaihang = ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                    ABS(h.giathamkhao - ?) ASC,
                    h.tenhanghoa ASC
                LIMIT " . intval($limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Method getSameBrandProducts đã được thay thế bởi getProductsSameBrandSameCategory và getProductsSameBrandOnly

    /**
     * Get products with similar price range
     * 
     * @param object $current Current product
     * @param int $limit Maximum number of products
     * @param array $excludeIds Product IDs to exclude
     * @return array Array of similar price products
     */
    private function getSimilarPriceProducts($current, $limit, $excludeIds = [])
    {
        // Price range: ±30% of current product price
        $priceMin = $current->giathamkhao * 0.7;
        $priceMax = $current->giathamkhao * 1.3;
        
        if (!empty($excludeIds)) {
            $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
            $sql = "SELECT h.* FROM hanghoa h
                    WHERE h.idhanghoa != ? 
                    AND h.giathamkhao BETWEEN ? AND ?
                    AND h.trang_thai != 2
                    AND h.idhanghoa NOT IN ({$placeholders})
                    ORDER BY 
                        CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                        ABS(h.giathamkhao - ?) ASC,
                        h.tenhanghoa ASC
                    LIMIT " . intval($limit);
            
            $params = array_merge(
                [$current->idhanghoa, $priceMin, $priceMax],
                $excludeIds,
                [$current->giathamkhao]
            );
        } else {
            $sql = "SELECT h.* FROM hanghoa h
                    WHERE h.idhanghoa != ? 
                    AND h.giathamkhao BETWEEN ? AND ?
                    AND h.trang_thai != 2
                    ORDER BY 
                        CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                        ABS(h.giathamkhao - ?) ASC,
                        h.tenhanghoa ASC
                    LIMIT " . intval($limit);
            
            $params = [$current->idhanghoa, $priceMin, $priceMax, $current->giathamkhao];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get any products as fallback (to ensure we always have results)
     * 
     * @param object $current Current product
     * @param int $limit Maximum number of products
     * @param array $excludeIds Product IDs to exclude
     * @return array Array of any available products
     */
    private function getAnyProducts($current, $limit, $excludeIds = [])
    {
        if (!empty($excludeIds)) {
            $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
            $sql = "SELECT h.* FROM hanghoa h
                    WHERE h.idhanghoa != ? 
                    AND h.trang_thai != 2
                    AND h.idhanghoa NOT IN ({$placeholders})
                    ORDER BY 
                        CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                        h.idhanghoa DESC
                    LIMIT " . intval($limit);
            
            $params = array_merge([$current->idhanghoa], $excludeIds);
        } else {
            $sql = "SELECT h.* FROM hanghoa h
                    WHERE h.idhanghoa != ? 
                    AND h.trang_thai != 2
                    ORDER BY 
                        CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                        h.idhanghoa DESC
                    LIMIT " . intval($limit);
            
            $params = [$current->idhanghoa];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get product status for display
     * Logic:
     * - If trang_thai = 2 (Ngừng bán): Always show "Ngừng bán"
     * - If trang_thai = 3 (Hết hàng): Show "Hết hàng"
     * - If trang_thai = 1 (Đang bán): Check quantity; if 0 show "Hết hàng", else show "Đang bán"
     * 
     * @param int $idhanghoa Product ID
     * @return string Display status (Đang bán, Ngừng bán, Hết hàng)
     */
    public function getProductStatus($idhanghoa)
    {
        try {
            $sql = "SELECT trang_thai FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return "Không xác định";
            }

            // Status values: 1=Đang bán, 2=Ngừng bán, 3=Hết hàng
            switch ((int)$product->trang_thai) {
                case 2:
                    return "Ngừng bán";
                case 3:
                    return "Hết hàng";
                case 1:
                default:
                    // For active products, check if quantity is 0
                    $quantity = $this->getProductQuantity($idhanghoa);
                    if ($quantity == 0) {
                        return "Hết hàng";
                    }
                    return "Đang bán";
            }
        } catch (PDOException $e) {
            error_log("Error getting product status: " . $e->getMessage());
            return "Không xác định";
        }
    }

    /**
     * Get product quantity from tonkho table
     * 
     * @param int $idhanghoa Product ID
     * @return int Product quantity (0 if not found)
     */
    public function getProductQuantity($idhanghoa)
    {
        try {
            // Check if tonkho table exists
            $checkTable = $this->db->query("SHOW TABLES LIKE 'tonkho'");
            if ($checkTable->rowCount() == 0) {
                return 0;
            }

            $sql = "SELECT soLuong FROM tonkho WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ? (int)$result->soLuong : 0;
        } catch (PDOException $e) {
            error_log("Error getting product quantity: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update product status
     * 
     * @param int $idhanghoa Product ID
     * @param int $status Status value (1=Đang bán, 2=Ngừng bán, 3=Hết hàng)
     * @return bool True if successful, false otherwise
     */
    public function updateProductStatus($idhanghoa, $status)
    {
        try {
            // Validate status value
            if (!in_array($status, [1, 2, 3])) {
                error_log("Invalid status value: " . $status);
                return false;
            }

            $sql = "UPDATE hanghoa SET trang_thai = ? WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $idhanghoa]);

            if ($result) {
                error_log("Product status updated - ID: $idhanghoa, Status: $status");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error updating product status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get product status value (numeric)
     * 
     * @param int $idhanghoa Product ID
     * @return int Status value (1, 2, or 3)
     */
    public function getProductStatusValue($idhanghoa)
    {
        try {
            $sql = "SELECT trang_thai FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ? (int)$result->trang_thai : 1;
        } catch (PDOException $e) {
            error_log("Error getting product status value: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get products by status
     * 
     * @param int $status Status value (1, 2, or 3)
     * @return array Array of product objects
     */
    public function getProductsByStatus($status)
    {
        try {
            // Validate status value
            if (!in_array($status, [1, 2, 3])) {
                return [];
            }

            $sql = "SELECT * FROM hanghoa WHERE trang_thai = ? ORDER BY tenhanghoa ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
            $stmt->setFetchMode(PDO::FETCH_OBJ);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting products by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all discontinued products (trang_thai = 2)
     * 
     * @return array Array of discontinued product objects
     */
    public function getDiscontinuedProducts()
    {
        return $this->getProductsByStatus(2);
    }

    /**
     * Get all out of stock products (trang_thai = 3)
     * 
     * @return array Array of out of stock product objects
     */
    public function getOutOfStockProducts()
    {
        return $this->getProductsByStatus(3);
    }

    /**
     * Get status color CSS class for display
     * 
     * @param string $displayStatus Display status string
     * @return string CSS class name
     */
    public function getStatusCssClass($displayStatus)
    {
        switch ($displayStatus) {
            case "Đang bán":
                return "status-active";
            case "Ngừng bán":
                return "status-discontinued";
            case "Hết hàng":
                return "status-outofstock";
            default:
                return "status-unknown";
        }
    }

    /**
     * Get status color for display
     * 
     * @param string $displayStatus Display status string
     * @return string Color code (hex)
     */
    public function getStatusColor($displayStatus)
    {
        switch ($displayStatus) {
            case "Đang bán":
                return "#27ae60"; // Green
            case "Ngừng bán":
                return "#e74c3c"; // Red
            case "Hết hàng":
                return "#95a5a6"; // Gray
            default:
                return "#34495e"; // Dark gray
        }
    }

    /**
     * Lấy tồn kho của sản phẩm từ bảng tonkho
     * @param int $idhanghoa - ID hàng hóa
     * @return int - Số lượng tồn kho (0 nếu không tìm thấy)
     */
    public function getTonKho($idhanghoa)
    {
        try {
            $sql = "SELECT soLuong FROM tonkho WHERE idhanghoa = ?";
            $data = array($idhanghoa);

            $stmt = $this->db->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute($data);

            $result = $stmt->fetch();

            // Trả về soLuong nếu tìm thấy, nếu không trả về 0
            return $result && isset($result->soLuong) ? (int)$result->soLuong : 0;
        } catch (Exception $e) {
            // Nếu có lỗi (bảng không tồn tại, etc), trả về 0
            return 0;
        }
    }
}