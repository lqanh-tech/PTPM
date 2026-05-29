<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();
$results = [];

try {
    // Tạo bảng user_addresses
    $check = $db->query("SHOW TABLES LIKE 'user_addresses'")->rowCount();
    if ($check == 0) {
        $db->exec("CREATE TABLE user_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            recipient_name VARCHAR(255) NOT NULL COMMENT 'Tên người nhận',
            phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại',
            province_id INT NOT NULL COMMENT 'ID tỉnh/thành',
            district_id INT NOT NULL COMMENT 'ID quận/huyện',
            ward_code VARCHAR(20) DEFAULT NULL COMMENT 'Mã phường/xã',
            address_detail VARCHAR(255) NOT NULL COMMENT 'Số nhà, tên đường',
            is_default TINYINT(1) DEFAULT 0 COMMENT '1=Địa chỉ mặc định',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(iduser) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_default (user_id, is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng user_addresses";
    } else {
        // Kiểm tra và thêm cột thiếu
        $columns = $db->query("SHOW COLUMNS FROM user_addresses")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('recipient_name', $columns)) {
            $db->exec("ALTER TABLE user_addresses ADD COLUMN recipient_name VARCHAR(255) NOT NULL AFTER user_id");
            $results[] = "✓ Thêm cột recipient_name";
        }
        if (!in_array('phone', $columns)) {
            $db->exec("ALTER TABLE user_addresses ADD COLUMN phone VARCHAR(20) NOT NULL AFTER recipient_name");
            $results[] = "✓ Thêm cột phone";
        }
        if (!in_array('is_default', $columns)) {
            $db->exec("ALTER TABLE user_addresses ADD COLUMN is_default TINYINT(1) DEFAULT 0");
            $results[] = "✓ Thêm cột is_default";
        }
        $results[] = "Bảng user_addresses đã tồn tại";
    }

    // Tạo API quản lý địa chỉ
    $apiDir = __DIR__ . '/../api';
    if (!is_dir($apiDir)) mkdir($apiDir, 0755, true);
    
    $apiContent = '<?php
if (session_status() == PHP_SESSION_NONE) session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["USER"])) {
    echo json_encode(["success" => false, "message" => "Chưa đăng nhập"]);
    exit;
}

require_once __DIR__ . "/../administrator/elements_LQA/mod/database.php";
$db = Database::getInstance()->getConnection();

try {
    $action = $_REQUEST["action"] ?? "";
    $username = $_SESSION["USER"];
    
    // Lấy user_id
    $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        echo json_encode(["success" => false, "message" => "User không tồn tại"]);
        exit;
    }
    
    switch ($action) {
        case "get_addresses":
            $stmt = $db->prepare("SELECT id, user_id, ho_ten, so_dien_thoai, dia_chi, phuong_xa, quan_huyen, tinh_thanh, is_default, created_at FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
            $stmt->execute([$userId]);
            $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Thêm tên tỉnh/quận/phường
            foreach ($addresses as &$addr) {
                $p = $db->prepare("SELECT province_name FROM vietnam_provinces WHERE province_id = ?");
                $p->execute([$addr["province_id"]]);
                $addr["province_name"] = $p->fetchColumn() ?: "";
                
                $d = $db->prepare("SELECT district_name FROM vietnam_districts WHERE district_id = ?");
                $d->execute([$addr["district_id"]]);
                $addr["district_name"] = $d->fetchColumn() ?: "";
                
                if (!empty($addr["ward_code"])) {
                    $w = $db->prepare("SELECT ward_name FROM vietnam_wards WHERE ward_code = ?");
                    $w->execute([$addr["ward_code"]]);
                    $addr["ward_name"] = $w->fetchColumn() ?: "";
                } else {
                    $addr["ward_name"] = "";
                }
                
                $addr["full_address"] = $addr["address_detail"];
                if (!empty($addr["ward_name"])) $addr["full_address"] .= ", " . $addr["ward_name"];
                if (!empty($addr["district_name"])) $addr["full_address"] .= ", " . $addr["district_name"];
                if (!empty($addr["province_name"])) $addr["full_address"] .= ", " . $addr["province_name"];
            }
            unset($addr);
            
            echo json_encode(["success" => true, "addresses" => $addresses]);
            break;
            
        case "add_address":
            $recipientName = trim($_POST["recipient_name"] ?? "");
            $phone = trim($_POST["phone"] ?? "");
            $provinceId = intval($_POST["province_id"] ?? 0);
            $districtId = intval($_POST["district_id"] ?? 0);
            $wardCode = trim($_POST["ward_code"] ?? "");
            $addressDetail = trim($_POST["address_detail"] ?? "");
            $isDefault = intval($_POST["is_default"] ?? 0);
            
            if (empty($recipientName) || empty($phone) || $provinceId <= 0 || $districtId <= 0 || empty($addressDetail)) {
                echo json_encode(["success" => false, "message" => "Thiếu thông tin bắt buộc"]);
                exit;
            }
            
            // Nếu đặt mặc định, bỏ mặc định cũ
            if ($isDefault) {
                $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }
            
            // Nếu đây là địa chỉ đầu tiên, tự động đặt mặc định
            $count = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $count->execute([$userId]);
            if ($count->fetchColumn() == 0) $isDefault = 1;
            
            $stmt = $db->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, province_id, district_id, ward_code, address_detail, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $recipientName, $phone, $provinceId, $districtId, $wardCode ?: null, $addressDetail, $isDefault]);
            
            echo json_encode(["success" => true, "message" => "Đã thêm địa chỉ", "id" => $db->lastInsertId()]);
            break;
            
        case "update_address":
            $id = intval($_POST["id"] ?? 0);
            $recipientName = trim($_POST["recipient_name"] ?? "");
            $phone = trim($_POST["phone"] ?? "");
            $provinceId = intval($_POST["province_id"] ?? 0);
            $districtId = intval($_POST["district_id"] ?? 0);
            $wardCode = trim($_POST["ward_code"] ?? "");
            $addressDetail = trim($_POST["address_detail"] ?? "");
            
            if ($id <= 0 || empty($recipientName) || empty($phone) || $provinceId <= 0 || $districtId <= 0 || empty($addressDetail)) {
                echo json_encode(["success" => false, "message" => "Thiếu thông tin bắt buộc"]);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE user_addresses SET recipient_name=?, phone=?, province_id=?, district_id=?, ward_code=?, address_detail=? WHERE id=? AND user_id=?");
            $stmt->execute([$recipientName, $phone, $provinceId, $districtId, $wardCode ?: null, $addressDetail, $id, $userId]);
            
            echo json_encode(["success" => true, "message" => "Đã cập nhật địa chỉ"]);
            break;
            
        case "delete_address":
            $id = intval($_POST["id"] ?? 0);
            if ($id <= 0) {
                echo json_encode(["success" => false, "message" => "ID không hợp lệ"]);
                exit;
            }
            
            // Kiểm tra có phải mặc định không
            $stmt = $db->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $addr = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$addr) {
                echo json_encode(["success" => false, "message" => "Địa chỉ không tồn tại"]);
                exit;
            }
            
            $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            
            // Nếu xóa địa chỉ mặc định, đặt địa chỉ khác làm mặc định
            if ($addr["is_default"]) {
                $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE user_id = ? ORDER BY id DESC LIMIT 1")->execute([$userId]);
            }
            
            echo json_encode(["success" => true, "message" => "Đã xóa địa chỉ"]);
            break;
            
        case "set_default":
            $id = intval($_POST["id"] ?? 0);
            if ($id <= 0) {
                echo json_encode(["success" => false, "message" => "ID không hợp lệ"]);
                exit;
            }
            
            $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            
            echo json_encode(["success" => true, "message" => "Đã đặt làm mặc định"]);
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Action không hợp lệ"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}';
    
    file_put_contents($apiDir . '/user_addresses.php', $apiContent);
    $results[] = "✓ Đã tạo API user_addresses.php";
    
    $results[] = "✅ HOÀN TẤT!";

} catch (Exception $e) {
    $results[] = "✗ LỖI: " . $e->getMessage();
}

foreach ($results as $r) echo $r . "<br>";
