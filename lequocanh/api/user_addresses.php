<?php
declare(strict_types=1);

if (session_status() == PHP_SESSION_NONE) session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["USER"]) && !isset($_SESSION["ADMIN"])) {
    echo json_encode(["success" => false, "message" => "Chưa đăng nhập"]);
    exit;
}

require_once __DIR__ . "/../administrator/elements_LQA/mod/database.php";
$db = Database::getInstance()->getConnection();

try {
    $action = $_REQUEST["action"] ?? "";
    $username = $_SESSION["USER"] ?? $_SESSION["ADMIN"] ?? "";
    
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

        case "get_addresses_admin":
            if (!isset($_SESSION["ADMIN"])) {
                echo json_encode(["success" => false, "message" => "Không có quyền admin"]);
                exit;
            }
            $targetUserId = intval($_REQUEST["user_id"] ?? 0);
            if ($targetUserId <= 0) {
                echo json_encode(["success" => false, "message" => "Thiếu user_id"]);
                exit;
            }
            $stmt = $db->prepare("SELECT id, user_id, ho_ten, so_dien_thoai, dia_chi, phuong_xa, quan_huyen, tinh_thanh, is_default, created_at FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
            $stmt->execute([$targetUserId]);
            $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            // Lấy tên tỉnh/quận/phường
            $provinceName = '';
            $districtName = '';
            $wardName = '';
            
            if ($provinceId > 0) {
                $pStmt = $db->prepare("SELECT province_name FROM vietnam_provinces WHERE province_id = ?");
                $pStmt->execute([$provinceId]);
                $provinceName = $pStmt->fetchColumn() ?: '';
            }
            if ($districtId > 0) {
                $dStmt = $db->prepare("SELECT district_name FROM vietnam_districts WHERE district_id = ?");
                $dStmt->execute([$districtId]);
                $districtName = $dStmt->fetchColumn() ?: '';
            }
            if (!empty($wardCode)) {
                $wStmt = $db->prepare("SELECT ward_name FROM vietnam_wards WHERE ward_code = ?");
                $wStmt->execute([$wardCode]);
                $wardName = $wStmt->fetchColumn() ?: '';
            }
            
            $stmt = $db->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, phone_number, province, district, ward, street_address, province_id, district_id, ward_code, address_detail, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $recipientName, $phone, $phone, $provinceName, $districtName, $wardName, $addressDetail, $provinceId, $districtId, $wardCode ?: null, $addressDetail, $isDefault]);
            
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
}