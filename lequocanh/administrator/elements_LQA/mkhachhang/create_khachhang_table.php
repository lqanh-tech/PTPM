<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$paths = [
    '../mod/database.php',
    './elements_LQA/mod/database.php',
    './administrator/elements_LQA/mod/database.php',
    '../../mod/database.php'
];

$loaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        echo "Loaded database.php from: $path<br>";
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die("Không thể tải file database.php. Vui lòng kiểm tra lại đường dẫn.");
}

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>Thông tin về quản lý khách hàng</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<p>Hệ thống quản lý khách hàng hiện tại lấy dữ liệu trực tiếp từ bảng <strong>user</strong>.</p>";
echo "<p>Khách hàng được xác định là những người dùng:</p>";
echo "<ul>";
echo "<li>Không phải là admin (username khác 'admin')</li>";
echo "<li>Không phải là nhân viên (không có trong bảng nhanvien)</li>";
echo "</ul>";
echo "<p>Việc này giúp duy trì dữ liệu nhất quán và không cần phải đồng bộ giữa nhiều bảng.</p>";
echo "</div>";

echo "<h2>Kiểm tra bảng user</h2>";
$stmt = $conn->query("SHOW TABLES LIKE 'user'");
if ($stmt->rowCount() > 0) {
    echo "Bảng user đã tồn tại.<br>";

    echo "<h3>Cấu trúc bảng user</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    $stmt = $conn->query("DESCRIBE user");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmt = $conn->query("SELECT COUNT(*) as count FROM user");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Số lượng bản ghi trong bảng user: $count<br>";
} else {
    echo "Bảng user chưa tồn tại. Vui lòng kiểm tra lại cấu trúc cơ sở dữ liệu.<br>";
}

echo "<h2>Kiểm tra bảng nhanvien</h2>";
$stmt = $conn->query("SHOW TABLES LIKE 'nhanvien'");
if ($stmt->rowCount() > 0) {
    echo "Bảng nhanvien đã tồn tại.<br>";

    echo "<h3>Cấu trúc bảng nhanvien</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    $stmt = $conn->query("DESCRIBE nhanvien");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmt = $conn->query("SELECT COUNT(*) as count FROM nhanvien");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Số lượng bản ghi trong bảng nhanvien: $count<br>";
} else {
    echo "Bảng nhanvien chưa tồn tại. Vui lòng kiểm tra lại cấu trúc cơ sở dữ liệu.<br>";
}

echo "<h2>Danh sách khách hàng</h2>";

$nhanvienUserIds = [];
try {
    $stmt = $conn->query("SELECT iduser FROM nhanvien WHERE iduser IS NOT NULL");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nhanvienUserIds[] = $row['iduser'];
    }
    echo "Đã tìm thấy " . count($nhanvienUserIds) . " nhân viên.<br>";
} catch (PDOException $e) {
    echo "Lỗi khi lấy danh sách nhân viên: " . $e->getMessage() . "<br>";
}

try {
    if (empty($nhanvienUserIds)) {
        $sql = "SELECT * FROM user WHERE username != 'admin'";
        $stmt = $conn->query($sql);
    } else {
        $placeholders = implode(',', array_fill(0, count($nhanvienUserIds), '?'));
        $sql = "SELECT * FROM user WHERE username != 'admin' AND iduser NOT IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($nhanvienUserIds);
    }

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Đã tìm thấy " . count($customers) . " khách hàng.<br>";

    if (count($customers) > 0) {
        echo "<table border='1'>";

        echo "<tr>";
        foreach (array_keys($customers[0]) as $column) {
            echo "<th>" . $column . "</th>";
        }
        echo "</tr>";

        foreach ($customers as $customer) {
            echo "<tr>";
            foreach ($customer as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "Không tìm thấy khách hàng nào.<br>";
    }
} catch (PDOException $e) {
    echo "Lỗi khi lấy danh sách khách hàng: " . $e->getMessage() . "<br>";
}

echo "<h2>Kiểm tra bảng khachhang cũ</h2>";
$stmt = $conn->query("SHOW TABLES LIKE 'khachhang'");
if ($stmt->rowCount() > 0) {
    echo "Bảng khachhang cũ vẫn tồn tại. Bạn có thể xóa bảng này nếu không còn sử dụng.<br>";
    echo "<pre>DROP TABLE khachhang;</pre>";

    $stmt = $conn->query("SELECT COUNT(*) as count FROM khachhang");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Số lượng bản ghi trong bảng khachhang cũ: $count<br>";
} else {
    echo "Bảng khachhang cũ không tồn tại.<br>";
}

echo "<br><a href='javascript:history.back()'>Quay lại</a>";
?>
