<?php
require_once __DIR__ . '/database.php';

class ThuocTinh
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Lấy tất cả các thuộc tính
    public function thuoctinhGetAll()
    {
        $sql = 'SELECT * FROM thuoctinh';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);

        if (!$getAll->execute()) {
            error_log(print_r($getAll->errorInfo(), true));
            return false;
        }

        return $getAll->fetchAll();
    }

    // Thêm thuộc tính mới
    public function thuoctinhAdd($tenThuocTinh, $ghiChu, $hinhanh)
    {
        $sql = "INSERT INTO thuoctinh (tenThuocTinh, ghiChu, hinhanh) VALUES (?, ?, ?)";
        $data = array($tenThuocTinh, $ghiChu, $hinhanh);

        $add = $this->db->prepare($sql);

        if (!$add->execute($data)) {
            error_log(print_r($add->errorInfo(), true));
            return false;
        }

        return $add->rowCount();
    }

    // Xóa thuộc tính theo ID
    public function thuoctinhDelete($idThuocTinh)
    {
        try {
            // Kiểm tra dữ liệu liên quan trước khi xóa
            $relatedData = $this->checkRelatedData($idThuocTinh);

            if (!empty($relatedData)) {
                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa thuộc tính vì còn dữ liệu liên quan',
                    'related_tables' => $relatedData
                ];
            }

            // Nếu không có ràng buộc, thực hiện xóa
            $sql = "DELETE FROM thuoctinh WHERE idThuocTinh = ?";
            $data = array($idThuocTinh);

            $del = $this->db->prepare($sql);
            $del->execute($data);

            $rowCount = $del->rowCount();

            return [
                'success' => true,
                'rows_affected' => $rowCount,
                'message' => $rowCount > 0 ? 'Xóa thuộc tính thành công' : 'Không tìm thấy thuộc tính để xóa'
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
                    'message' => 'Không thể xóa thuộc tính vì còn dữ liệu liên quan trong bảng: ' . $tableName,
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

    // Cập nhật thông tin thuộc tính
    public function thuoctinhUpdate($tenThuocTinh, $ghiChu, $hinhanh, $idThuocTinh)
    {
        try {
            $sql = "UPDATE thuoctinh
                    SET tenThuocTinh = ?, ghiChu = ?, hinhanh = ?
                    WHERE idThuocTinh = ?";
            $data = array($tenThuocTinh, $ghiChu, $hinhanh, $idThuocTinh);

            error_log("thuoctinhUpdate SQL: " . $sql);
            error_log("thuoctinhUpdate data: " . json_encode($data));

            $update = $this->db->prepare($sql);

            if (!$update->execute($data)) {
                $errorInfo = $update->errorInfo();
                error_log("thuoctinhUpdate SQL Error: " . json_encode($errorInfo));
                return false;
            }

            $rowCount = $update->rowCount();
            error_log("thuoctinhUpdate rows affected: " . $rowCount);

            return $rowCount;
        } catch (Exception $e) {
            error_log("thuoctinhUpdate Exception: " . $e->getMessage());
            throw $e;
        }
    }

    // Lấy thông tin thuộc tính theo ID
    public function thuoctinhGetById($idThuocTinh)
    {
        $sql = 'SELECT * FROM thuoctinh WHERE idThuocTinh = ?';
        $data = array($idThuocTinh);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetch();
    }

    /**
     * Kiểm tra dữ liệu liên quan trước khi xóa thuộc tính
     */
    public function checkRelatedData($idThuocTinh)
    {
        $relatedData = [];

        try {
            // Kiểm tra bảng thuoctinhhh (thuộc tính hàng hóa)
            $sql = "SELECT COUNT(*) as count FROM thuoctinhhh WHERE idThuocTinh = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idThuocTinh]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['thuoctinhhh'] = [
                    'table_name' => 'thuoctinhhh',
                    'display_name' => 'Thuộc tính hàng hóa',
                    'count' => $count,
                    'description' => 'Thuộc tính này đang được sử dụng trong ' . $count . ' hàng hóa'
                ];
            }

            // Có thể thêm kiểm tra các bảng khác nếu cần

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
            'thuoctinhhh' => 'Hãy xóa tất cả thuộc tính hàng hóa sử dụng thuộc tính này trước khi xóa thuộc tính.',
        ];

        return isset($suggestions[$tableName]) ? $suggestions[$tableName] : 'Hãy xóa dữ liệu liên quan trước khi xóa thuộc tính này.';
    }
}
