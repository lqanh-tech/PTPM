<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!$phanQuyen->checkAccess('cau_hinh_thanh_toan', $username)) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập chức năng này.</div>';
    exit();
}

require_once './elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankName = $_POST['ten_ngan_hang'];
    $accountNumber = $_POST['so_tai_khoan'];
    $accountName = $_POST['ten_tai_khoan'];

    $checkTableSql = "SHOW TABLES LIKE 'cau_hinh_thanh_toan'";
    $checkTableStmt = $conn->prepare($checkTableSql);
    $checkTableStmt->execute();

    if ($checkTableStmt->rowCount() == 0) {

        $createTableSql = "CREATE TABLE cau_hinh_thanh_toan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ten_ngan_hang VARCHAR(100) NOT NULL,
            so_tai_khoan VARCHAR(50) NOT NULL,
            ten_tai_khoan VARCHAR(100) NOT NULL,
            ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($createTableSql);
    }

    $checkConfigSql = "SELECT COUNT(*) FROM cau_hinh_thanh_toan";
    $checkConfigStmt = $conn->prepare($checkConfigSql);
    $checkConfigStmt->execute();
    $configCount = $checkConfigStmt->fetchColumn();

    if ($configCount > 0) {

        $updateSql = "UPDATE cau_hinh_thanh_toan SET ten_ngan_hang = ?, so_tai_khoan = ?, ten_tai_khoan = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$bankName, $accountNumber, $accountName]);
    } else {

        $insertSql = "INSERT INTO cau_hinh_thanh_toan (ten_ngan_hang, so_tai_khoan, ten_tai_khoan) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([$bankName, $accountNumber, $accountName]);
    }

    $_SESSION['cau_hinh_thanh_toan_success'] = true;

    echo '<script>
        alert("Cấu hình thanh toán đã được lưu thành công!");
        window.location.href = "index.php?req=cau_hinh_thanh_toan";
    </script>';
    exit();
}

$checkTableSql = "SHOW TABLES LIKE 'cau_hinh_thanh_toan'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

if ($checkTableStmt->rowCount() == 0) {

    $createTableSql = "CREATE TABLE cau_hinh_thanh_toan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ten_ngan_hang VARCHAR(100) NOT NULL,
        so_tai_khoan VARCHAR(50) NOT NULL,
        ten_tai_khoan VARCHAR(100) NOT NULL,
        ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($createTableSql);
}

$configSql = "SELECT * FROM cau_hinh_thanh_toan LIMIT 1";
$configStmt = $conn->prepare($configSql);
$configStmt->execute();
$config = $configStmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- Thêm Bootstrap CSS nếu chưa có -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<div class="admin-title">Cấu hình thanh toán</div>
<hr>

<?php if (isset($_SESSION['cau_hinh_thanh_toan_success'])): ?>
    <div class="alert alert-success">
        Cấu hình thanh toán đã được cập nhật thành công.
    </div>
    <?php unset($_SESSION['cau_hinh_thanh_toan_success']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Cấu hình tài khoản ngân hàng</h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="ten_ngan_hang" class="form-label">Tên ngân hàng</label>
                <input type="text" class="form-control" id="ten_ngan_hang" name="ten_ngan_hang"
                    value="<?php echo $config ? htmlspecialchars($config['ten_ngan_hang']) : ''; ?>" required>
                <div class="form-text">Nhập tên ngân hàng (VD: VIETCOMBANK, AGRIBANK, TECHCOMBANK, ...)</div>
            </div>
            <div class="mb-3">
                <label for="so_tai_khoan" class="form-label">Số tài khoản</label>
                <input type="text" class="form-control" id="so_tai_khoan" name="so_tai_khoan"
                    value="<?php echo $config ? htmlspecialchars($config['so_tai_khoan']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ten_tai_khoan" class="form-label">Tên chủ tài khoản</label>
                <input type="text" class="form-control" id="ten_tai_khoan" name="ten_tai_khoan"
                    value="<?php echo $config ? htmlspecialchars($config['ten_tai_khoan']) : ''; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Hướng dẫn cấu hình</h5>
    </div>
    <div class="card-body">
        <p>Để cấu hình thanh toán qua VietQR, bạn cần nhập đúng thông tin tài khoản ngân hàng:</p>
        <ul>
            <li><strong>Tên ngân hàng:</strong> Nhập chính xác tên ngân hàng (VD: VIETCOMBANK, AGRIBANK, TECHCOMBANK,
                ...)</li>
            <li><strong>Số tài khoản:</strong> Nhập số tài khoản ngân hàng của bạn</li>
            <li><strong>Tên chủ tài khoản:</strong> Nhập tên chủ tài khoản ngân hàng</li>
        </ul>
        <p>Sau khi cấu hình, hệ thống sẽ tự động tạo mã QR VietQR cho khách hàng khi thanh toán.</p>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">Quản lý đơn hàng</h5>
    </div>
    <div class="card-body">
        <p>Bạn có thể quản lý các đơn hàng và xác nhận thanh toán tại <a href="index.php?req=don_hang">Quản lý đơn
                hàng</a>.</p>
    </div>
</div>