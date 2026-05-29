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

function ensurePaymentConfigTable($conn) {
    $checkTableSql = "SHOW TABLES LIKE 'cau_hinh_thanh_toan'";
    $checkTableStmt = $conn->prepare($checkTableSql);
    $checkTableStmt->execute();

    if ($checkTableStmt->rowCount() == 0) {
        $createTableSql = "CREATE TABLE cau_hinh_thanh_toan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ten_ngan_hang VARCHAR(100) NOT NULL DEFAULT '',
            so_tai_khoan VARCHAR(50) NOT NULL DEFAULT '',
            ten_tai_khoan VARCHAR(100) NOT NULL DEFAULT '',
            momo_enabled TINYINT(1) NOT NULL DEFAULT 1,
            bank_transfer_enabled TINYINT(1) NOT NULL DEFAULT 1,
            cod_enabled TINYINT(1) NOT NULL DEFAULT 1,
            ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($createTableSql);
    } else {
        $columns = $conn->query("SHOW COLUMNS FROM cau_hinh_thanh_toan")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('momo_enabled', $columns)) {
            $conn->exec("ALTER TABLE cau_hinh_thanh_toan ADD COLUMN momo_enabled TINYINT(1) NOT NULL DEFAULT 1");
        }
        if (!in_array('bank_transfer_enabled', $columns)) {
            $conn->exec("ALTER TABLE cau_hinh_thanh_toan ADD COLUMN bank_transfer_enabled TINYINT(1) NOT NULL DEFAULT 1");
        }
        if (!in_array('cod_enabled', $columns)) {
            $conn->exec("ALTER TABLE cau_hinh_thanh_toan ADD COLUMN cod_enabled TINYINT(1) NOT NULL DEFAULT 1");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankName = $_POST['ten_ngan_hang'];
    $accountNumber = $_POST['so_tai_khoan'];
    $accountName = $_POST['ten_tai_khoan'];
    $momoEnabled = isset($_POST['momo_enabled']) ? 1 : 0;
    $bankTransferEnabled = isset($_POST['bank_transfer_enabled']) ? 1 : 0;
    $codEnabled = isset($_POST['cod_enabled']) ? 1 : 0;

    ensurePaymentConfigTable($conn);

    $checkConfigSql = "SELECT COUNT(*) FROM cau_hinh_thanh_toan";
    $checkConfigStmt = $conn->prepare($checkConfigSql);
    $checkConfigStmt->execute();
    $configCount = $checkConfigStmt->fetchColumn();

    if ($configCount > 0) {
        $updateSql = "UPDATE cau_hinh_thanh_toan SET ten_ngan_hang = ?, so_tai_khoan = ?, ten_tai_khoan = ?, momo_enabled = ?, bank_transfer_enabled = ?, cod_enabled = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$bankName, $accountNumber, $accountName, $momoEnabled, $bankTransferEnabled, $codEnabled]);
    } else {
        $insertSql = "INSERT INTO cau_hinh_thanh_toan (ten_ngan_hang, so_tai_khoan, ten_tai_khoan, momo_enabled, bank_transfer_enabled, cod_enabled) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([$bankName, $accountNumber, $accountName, $momoEnabled, $bankTransferEnabled, $codEnabled]);
    }

    $_SESSION['cau_hinh_thanh_toan_success'] = true;

    echo '<script>
        alert("Cấu hình thanh toán đã được lưu thành công!");
        window.location.href = "index.php?req=cau_hinh_thanh_toan";
    </script>';
    exit();
}

ensurePaymentConfigTable($conn);

$configSql = "SELECT id, ten_ngan_hang, so_tai_khoan, ten_tai_khoan, ngay_tao, ngay_cap_nhat FROM cau_hinh_thanh_toan LIMIT 1";
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
        <h5 class="mb-0">Phương thức thanh toán</h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-4">
                <h6 class="fw-bold mb-3">Bật/Tắt phương thức thanh toán</h6>
                <p class="text-muted mb-3">Chọn các phương thức thanh toán hiển thị cho khách hàng tại trang thanh toán.</p>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="momo_enabled" name="momo_enabled"
                                <?php echo ($config && isset($config['momo_enabled']) && $config['momo_enabled'] == 1) ? 'checked' : (!$config ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="momo_enabled">
                                <strong>MoMo</strong><br>
                                <small class="text-muted">Thanh toán qua ví MoMo</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="bank_transfer_enabled" name="bank_transfer_enabled"
                                <?php echo ($config && isset($config['bank_transfer_enabled']) && $config['bank_transfer_enabled'] == 1) ? 'checked' : (!$config ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="bank_transfer_enabled">
                                <strong>Chuyển khoản ngân hàng</strong><br>
                                <small class="text-muted">Thanh toán qua chuyển khoản</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cod_enabled" name="cod_enabled"
                                <?php echo ($config && isset($config['cod_enabled']) && $config['cod_enabled'] == 1) ? 'checked' : (!$config ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="cod_enabled">
                                <strong>COD</strong><br>
                                <small class="text-muted">Thanh toán khi nhận hàng</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <h6 class="fw-bold mb-3">Cấu hình tài khoản ngân hàng</h6>
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