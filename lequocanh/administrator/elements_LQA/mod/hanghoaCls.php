<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/hanghoaStatusExtension.php';

class hanghoa
{
    use HanghoaStatusTrait;

    private $db;
    private static $statusColumnInfo = null;
    private $lastFilterDebug = [];

    private function buildStatusCondition($alias = '')
    {
        $info = $this->getStatusColumnInfo();
        if (!$info['column']) {
            return '';
        }

        $prefix = $alias ? $alias . '.' : '';
        if ($info['column'] === 'trangthai') {
            return "({$prefix}{$info['column']} IS NULL OR {$prefix}{$info['column']} != 'ngung_ban')";
        }

        return "({$prefix}{$info['column']} IS NULL OR {$prefix}{$info['column']} != 2)";
    }

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

        $log_file = dirname(__FILE__) . '/hanghoa_class_debug.log';

        try {

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

            $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh;
            $idThuongHieu = ($idThuongHieu === '' || $idThuongHieu === 0 || $idThuongHieu === '0') ? null : $idThuongHieu;
            $idDonViTinh = ($idDonViTinh === '' || $idDonViTinh === 0 || $idDonViTinh === '0') ? null : $idDonViTinh;
            $idNhanVien = ($idNhanVien === '' || $idNhanVien === 0 || $idNhanVien === '0') ? null : $idNhanVien;

            if (!$this->db || !($this->db instanceof PDO)) {
                $error_msg = "Lỗi: Không có kết nối database hợp lệ";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            if (empty($tenhanghoa) || empty($giathamkhao) || empty($idloaihang)) {
                $error_msg = "Lỗi: Thiếu thông tin bắt buộc (tên hàng hóa, giá tham khảo hoặc loại hàng)";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            try {
                $checkColumns = $this->db->query("SHOW COLUMNS FROM hanghoa");
                $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
                $log_columns = date('Y-m-d H:i:s') . " - Các cột trong bảng hanghoa: " . implode(", ", $columns) . "\n";
                file_put_contents($log_file, $log_columns, FILE_APPEND);
            } catch (Exception $e) {
                $log_column_error = date('Y-m-d H:i:s') . " - Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage() . "\n";
                file_put_contents($log_file, $log_column_error, FILE_APPEND);
            }

            $ghichu = "";

            if (in_array('hinhanh', $columns)) {
                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            } else {

                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, id_hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            }

            $log_sql = date('Y-m-d H:i:s') . " - SQL: $sql\n";
            $log_sql .= "Data: " . print_r($data, true) . "\n";
            file_put_contents($log_file, $log_sql, FILE_APPEND);

            $add = $this->db->prepare($sql);
            $result = $add->execute($data);

            if ($result) {
                $rowCount = $add->rowCount();
                $lastId = $this->db->lastInsertId();
                $log_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công. Rows affected: $rowCount, Last Insert ID: $lastId\n";
                file_put_contents($log_file, $log_success, FILE_APPEND);

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

                return $lastId;
            } else {
                $error_info = print_r($add->errorInfo(), true);
                $log_error = date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại. Error info: $error_info\n";
                file_put_contents($log_file, $log_error, FILE_APPEND);

                if (strpos($error_info, "Unknown column") !== false) {

                    $describeStmt = $this->db->query("DESCRIBE hanghoa");
                    $columns = $describeStmt->fetchAll(PDO::FETCH_COLUMN);

                    $imageColumn = in_array('hinhanh', $columns) ? 'hinhanh' : 'id_hinhanh';

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

            $relatedData = $this->checkRelatedData($idhanghoa);

            if (!empty($relatedData)) {

                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan',
                    'related_tables' => $relatedData
                ];
            }

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

            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'foreign key constraint') !== false) {

                $errorMessage = $e->getMessage();
                $tableName = 'không xác định';

                if (preg_match('/`([^`]+)`\.`([^`]+)`/', $errorMessage, $matches)) {
                    $tableName = $matches[2];
                }

                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan trong bảng: ' . $tableName,
                    'technical_error' => $errorMessage,
                    'suggested_action' => $this->getSuggestedAction($tableName)
                ];
            }

            return [
                'success' => false,
                'error_type' => 'database_error',
                'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
            ];
        }
    }

    public function HanghoaUpdate($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $idhanghoa, $ghichu = '')
    {

        $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh;
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

    public function searchHanghoa($keyword)
    {
        try {

            error_log("searchHanghoa - Starting search with keyword: " . $keyword);

            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("searchHanghoa - Error: No valid database connection");
                return [];
            }

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

    public function getAverageRating($idhanghoa)
    {
        try {

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

    public function getReviewCount($idhanghoa)
    {
        try {

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

    public function checkRelatedData($idhanghoa)
    {
        $relatedData = [];

        try {

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

            try {
                $checkTable = $this->db->query("SHOW TABLES LIKE 'hinhanh'");
                if ($checkTable->rowCount() == 0) {
                    error_log("GetHinhAnhById - Bảng hinhanh không tồn tại");
                    return null;
                }
            } catch (PDOException $e) {
                error_log("GetHinhAnhById - Lỗi khi kiểm tra bảng hinhanh: " . $e->getMessage());
            }

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

                error_log("GetHinhAnhById - ID: " . $id . ", đường dẫn gốc: " . $hinhanh->duong_dan);
                error_log("GetHinhAnhById - Thông tin đầy đủ: " . print_r($hinhanh, true));

                if (strpos($hinhanh->duong_dan, 'data:image') === 0) {

                    error_log("GetHinhAnhById - Đường dẫn là base64");
                    return $hinhanh;
                } else {

                    if (!empty($hinhanh->duong_dan)) {

                        $hinhanh->duong_dan = str_replace('\\', '/', $hinhanh->duong_dan);

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

    private static $hasCheckedFileHashColumn = false;
    private static $fileHashColumnExists = false;

    public function ThemHinhAnh($ten_file, $loai_file, $duong_dan, $file_hash = null)
    {
        try {

            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                }
            }

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

            $sql = "DELETE FROM hinhanh WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in XoaHinhAnh: " . $e->getMessage());
            return false;
        }
    }

    public function ApplyImageToProduct($idhanghoa, $id_hinhanh)
    {
        try {

            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("Không có kết nối database hợp lệ");
                return false;
            }

            try {

                if (!$this->db->inTransaction()) {
                    $this->db->beginTransaction();
                }
            } catch (PDOException $e) {

                error_log("Lỗi transaction: " . $e->getMessage());
                return false;
            }

            $this->CreateHanghoaHinhanhTable();

            $sql = 'UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?';
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_hinhanh, $idhanghoa]);

            if (!$result) {
                throw new Exception("Không thể cập nhật hình ảnh chính");
            }

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

            $this->UpdateImageStatus($id_hinhanh);

            $this->db->commit();

            return true;
        } catch (Exception $e) {

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

    public function FindProductsByName($name)
    {
        $sql = 'SELECT * FROM hanghoa WHERE tenhanghoa LIKE ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%" . $name . "%"]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

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

    public function CheckImageExistsByHash($fileHash)
    {
        try {

            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                    return false;
                }
            } else if (!self::$fileHashColumnExists) {
                return false;
            }

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

    public function FindMissingImages()
    {
        try {

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

    public function FindExactMatchImage($idhanghoa)
    {
        try {

            $sql = "SELECT tenhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return null;
            }

            $sql = "SELECT * FROM hinhanh";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_OBJ);

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

    public function ApplyAllExactMatchImages()
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            $sqlProducts = "SELECT idhanghoa, tenhanghoa FROM hanghoa";
            $stmtProducts = $this->db->prepare($sqlProducts);
            $stmtProducts->execute();
            $products = $stmtProducts->fetchAll(PDO::FETCH_OBJ);

            $sqlImages = "SELECT id, ten_file FROM hinhanh";
            $stmtImages = $this->db->prepare($sqlImages);
            $stmtImages->execute();
            $images = $stmtImages->fetchAll(PDO::FETCH_OBJ);

            $matchesCount = 0;

            foreach ($products as $product) {
                foreach ($images as $image) {
                    if ($this->IsExactImageNameMatch($product->tenhanghoa, $image->ten_file)) {

                        $sqlUpdate = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                        $stmtUpdate->execute([$image->id, $product->idhanghoa]);

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
                        break;
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

    public function RemoveImageFromProduct($idhanghoa)
    {
        try {

            error_log("RemoveImageFromProduct - Bắt đầu gỡ bỏ hình ảnh cho sản phẩm ID: " . $idhanghoa);

            $checkProduct = "SELECT hinhanh FROM hanghoa WHERE idhanghoa = ?";
            $stmtCheckProduct = $this->db->prepare($checkProduct);
            $stmtCheckProduct->execute([$idhanghoa]);
            $currentImageId = $stmtCheckProduct->fetchColumn();

            if ($currentImageId === false) {
                error_log("RemoveImageFromProduct - Sản phẩm không tồn tại: " . $idhanghoa);
                return false;
            }

            error_log("RemoveImageFromProduct - Hình ảnh hiện tại của sản phẩm: " . ($currentImageId ?: 'NULL'));

            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([$idhanghoa]);

            if (!$result) {
                error_log("RemoveImageFromProduct - Lỗi khi cập nhật sản phẩm: " . implode(", ", $stmtUpdate->errorInfo()));
                $this->db->rollBack();
                return false;
            }

            error_log("RemoveImageFromProduct - Đã cập nhật sản phẩm thành NULL");

            if ($currentImageId) {
                try {

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

                }
            }

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

    public function RemoveAllMismatchedImages()
    {
        try {
            error_log("RemoveAllMismatchedImages - Bắt đầu gỡ bỏ tất cả hình ảnh không khớp");

            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

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

                $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $resultUpdate = $stmtUpdate->execute([$item->idhanghoa]);

                if (!$resultUpdate) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi cập nhật sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtUpdate->errorInfo()));
                    continue;
                }

                $sqlDeleteRelation = "DELETE FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                $stmtDeleteRelation = $this->db->prepare($sqlDeleteRelation);
                $resultDelete = $stmtDeleteRelation->execute([$item->idhanghoa, $item->id]);

                if (!$resultDelete) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi xóa quan hệ cho sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtDeleteRelation->errorInfo()));
                }

                $count++;
                error_log("RemoveAllMismatchedImages - Đã gỡ bỏ hình ảnh cho sản phẩm ID: " . $item->idhanghoa);
            }

            $this->db->commit();

            error_log("RemoveAllMismatchedImages - Hoàn tất gỡ bỏ " . $count . " hình ảnh");
            return $count;
        } catch (PDOException $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        } catch (Exception $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Unexpected error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        }
    }

    public function IsExactImageNameMatch($tenhanghoa, $ten_file)
    {

        $imageNameWithoutExt = pathinfo($ten_file, PATHINFO_FILENAME);

        if (trim($tenhanghoa) === trim($imageNameWithoutExt)) {
            return true;
        }

        return false;
    }

    public function filterProducts($filters)
    {
        try {
            $ratingSelect = '0 as average_rating';
            $reviewCountSelect = '0 as review_count';

            try {
                $checkReviews = $this->db->query("SHOW TABLES LIKE 'product_reviews'");
                if ($checkReviews && $checkReviews->rowCount() > 0) {
                    $hasRatingCol = $this->db->query("SHOW COLUMNS FROM product_reviews LIKE 'rating'");
                    if ($hasRatingCol && $hasRatingCol->rowCount() > 0) {

                        $hasProductId = $this->db->query("SHOW COLUMNS FROM product_reviews LIKE 'product_id'");
                        $hasMaSanPham = $this->db->query("SHOW COLUMNS FROM product_reviews LIKE 'ma_san_pham'");
                        
                        if ($hasProductId && $hasProductId->rowCount() > 0) {
                            $productCol = 'product_id';
                        } elseif ($hasMaSanPham && $hasMaSanPham->rowCount() > 0) {
                            $productCol = 'ma_san_pham';
                        } else {
                            $productCol = null;
                        }
                        
                        if ($productCol) {
                            $ratingSelect = "(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND (pr.status = 'approved' OR pr.is_approved = 1)) as average_rating";
                            $reviewCountSelect = "(SELECT COUNT(*) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND (pr.status = 'approved' OR pr.is_approved = 1)) as review_count";
                        }
                    }
                }
            } catch (PDOException $e) {
                $ratingSelect = '0 as average_rating';
                $reviewCountSelect = '0 as review_count';
            }

            $sql = "SELECT DISTINCT h.*,\n                    $ratingSelect,\n                    $reviewCountSelect\n                    FROM hanghoa h";

            $joins = [];
            $statusCondition = $this->buildStatusCondition('h');
            $conditions = $statusCondition ? [$statusCondition] : [];
            $params = [];

            if (!empty($filters['colors']) || !empty($filters['sizes'])) {
                $joins[] = 'INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa';

                $filterConditions = [];

                if (!empty($filters['colors'])) {

                    $colorAttrStmt = $this->db->query("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
                    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($colorAttr) {
                        $colorAttrId = $colorAttr['idThuocTinh'];
                        
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

                            $colorVi = isset($colorMapping[$colorEn]) ? $colorMapping[$colorEn] : $colorEn;
                            
                            $colorOrConditions[] = "LOWER(TRIM(tt.tenThuocTinhHH)) = LOWER(?)";
                            $params[] = $colorVi;
                        }
                        
                        $colorCondition = "tt.idThuocTinh = $colorAttrId AND (" . implode(' OR ', $colorOrConditions) . ")";
                        $filterConditions[] = $colorCondition;
                    }
                }

                if (!empty($filters['sizes'])) {
                    $sizeValues = array_map(function ($s) {
                        return trim($s);
                    }, $filters['sizes']);

                    $sizeOrConditions = [];
                    foreach ($sizeValues as $size) {
                        $sizeOrConditions[] = "CONCAT(',', tt.tenThuocTinhHH, ',') LIKE ?";
                        $params[] = '%,' . $size . ',%';
                    }

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

            if (isset($filters['min_price']) && isset($filters['max_price'])) {
                $conditions[] = '(CASE 
                    WHEN h.giakhuyenmai > 0 THEN h.giakhuyenmai 
                    ELSE h.giathamkhao 
                END) BETWEEN ? AND ?';
                $params[] = $filters['min_price'];
                $params[] = $filters['max_price'];
            }

            if (isset($filters['category']) && $filters['category'] > 0) {
                $conditions[] = 'h.idloaihang = ?';
                $params[] = $filters['category'];
            }

            if (isset($filters['min_rating']) && $filters['min_rating'] > 0) {

                $ratingThreshold = $filters['min_rating'] - 0.5;
                if ($ratingThreshold < 0.5) $ratingThreshold = 0.5;
                
                $hasProductId = $this->db->query("SHOW COLUMNS FROM product_reviews LIKE 'product_id'");
                $filterProductCol = ($hasProductId && $hasProductId->rowCount() > 0) ? 'product_id' : 'ma_san_pham';
                
                $conditions[] = "(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.$filterProductCol = h.idhanghoa AND (pr.status = 'approved' OR pr.is_approved = 1)) >= ?";
                $params[] = $ratingThreshold;
                
                $conditions[] = "(SELECT COUNT(*) FROM product_reviews pr WHERE pr.$filterProductCol = h.idhanghoa AND (pr.status = 'approved' OR pr.is_approved = 1)) > 0";
            }

            if (!empty($joins)) {
                $sql .= ' ' . implode(' ', $joins);
            }

            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $sql .= ' ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != "" THEN 0 ELSE 1 END) ASC, h.tenhanghoa ASC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $this->lastFilterDebug = ['sql' => $sql, 'params' => $params];

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in filterProducts: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'N/A'));
            error_log("Params: " . print_r($params ?? [], true));
            return [];
        }
    }

    public function getLastFilterDebug()
    {
        return $this->lastFilterDebug;
    }

    public function getFilterOptions($idloaihang = null)
    {
        try {
            $options = [
                'colors' => [],
                'sizes' => [],
                'price_range' => ['min' => 0, 'max' => 100000000]
            ];

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

            $colorKeywords = ['màu', 'color'];
            $sizeKeywords = ['kích', 'size', 'cỡ'];

            foreach ($attributes as $attr) {
                $attrLower = mb_strtolower($attr, 'UTF-8');

                foreach ($colorKeywords as $keyword) {
                    if (strpos($attrLower, $keyword) !== false) {

                        $color = $this->extractAttributeValue($attr, $colorKeywords);
                        if ($color && !in_array($color, $options['colors'])) {
                            $options['colors'][] = $color;
                        }
                        break;
                    }
                }

                foreach ($sizeKeywords as $keyword) {
                    if (strpos($attrLower, $keyword) !== false) {

                        $size = $this->extractAttributeValue($attr, $sizeKeywords);
                        if ($size && !in_array($size, $options['sizes'])) {
                            $options['sizes'][] = $size;
                        }
                        break;
                    }
                }
            }

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

    private function extractAttributeValue($attribute, $keywords)
    {
        $value = $attribute;

        $value = preg_replace('/[:\-\|]/u', ' ', $value);

        foreach ($keywords as $keyword) {
            $value = preg_replace('/' . preg_quote($keyword, '/') . '/iu', '', $value);
        }

        $value = trim($value);

        if (empty($value) || mb_strlen($value, 'UTF-8') > 50) {
            return null;
        }

        return $value;
    }

    public function getRelatedProducts($idhanghoa, $limit = 6)
    {
        try {

            $current = $this->HanghoaGetbyId($idhanghoa);

            if (!$current) {
                return [];
            }

            $results = [];
            $excludeIds = [$idhanghoa];

            if (!empty($current->idThuongHieu) && !empty($current->idloaihang)) {
                $tier1 = $this->getProductsSameBrandSameCategory($current, $limit, $excludeIds);
                foreach ($tier1 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            if (count($results) < $limit && !empty($current->idloaihang)) {
                $remaining = $limit - count($results);
                $tier2 = $this->getProductsSameCategorySimilarPrice($current, $remaining, $excludeIds);
                foreach ($tier2 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            if (count($results) < $limit && !empty($current->idThuongHieu)) {
                $remaining = $limit - count($results);
                $tier3 = $this->getProductsSameBrandOnly($current, $remaining, $excludeIds);
                foreach ($tier3 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            if (count($results) < $limit && !empty($current->idloaihang)) {
                $remaining = $limit - count($results);
                $tier4 = $this->getProductsSameCategoryOnly($current, $remaining, $excludeIds);
                foreach ($tier4 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

            if (count($results) < $limit) {
                $remaining = $limit - count($results);
                $tier5 = $this->getSimilarPriceProducts($current, $remaining, $excludeIds);
                foreach ($tier5 as $p) {
                    $results[] = $p;
                    $excludeIds[] = $p->idhanghoa;
                }
            }

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
        
        $params[] = $current->giathamkhao;

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

    private function getSimilarPriceProducts($current, $limit, $excludeIds = [])
    {

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

            switch ((int)$product->trang_thai) {
                case 2:
                    return "Ngừng bán";
                case 3:
                    return "Hết hàng";
                case 1:
                default:

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

    public function getProductQuantity($idhanghoa)
    {
        try {

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

    public function updateProductStatus($idhanghoa, $status)
    {
        try {

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

    public function getProductsByStatus($status)
    {
        try {

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

    public function getDiscontinuedProducts()
    {
        return $this->getProductsByStatus(2);
    }

    public function getOutOfStockProducts()
    {
        return $this->getProductsByStatus(3);
    }

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

    public function getStatusColor($displayStatus)
    {
        switch ($displayStatus) {
            case "Đang bán":
                return "#27ae60";
            case "Ngừng bán":
                return "#e74c3c";
            case "Hết hàng":
                return "#95a5a6";
            default:
                return "#34495e";
        }
    }

    public function getTonKho($idhanghoa)
    {
        try {
            $sql = "SELECT soLuong FROM tonkho WHERE idhanghoa = ?";
            $data = array($idhanghoa);

            $stmt = $this->db->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute($data);

            $result = $stmt->fetch();

            return $result && isset($result->soLuong) ? (int)$result->soLuong : 0;
        } catch (Exception $e) {

            return 0;
        }
    }
}