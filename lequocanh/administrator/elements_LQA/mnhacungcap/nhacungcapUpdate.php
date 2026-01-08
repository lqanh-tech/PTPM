<?php
require_once __DIR__ . '/../mod/nhacungcapCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idNCC = isset($_POST['idNCC']) ? $_POST['idNCC'] : (isset($_GET['idNCC']) ? $_GET['idNCC'] : (isset($_REQUEST['idNCC']) ? $_REQUEST['idNCC'] : null));

$debug['ID detected'] = $idNCC;

if (isset($_GET['debug']) || isset($_POST['debug'])) {
    echo "<pre>";
    print_r($debug);
    echo "</pre>";
}

if (!$idNCC) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID nhà cung cấp",
        'debug' => $debug
    ]);
    exit;
}

$nccObj = new nhacungcap();
$getNccUpdate = $nccObj->NhacungcapGetbyId($idNCC);

if (!$getNccUpdate) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy nhà cung cấp với ID: " . htmlspecialchars($idNCC),
        'debug' => $debug
    ]);
    exit;
}
?>

<div class="update-form">
    <h3>Cập nhật thông tin nhà cung cấp</h3>
    <form name="updatenhacungcap" id="formupdate" method="post" enctype="multipart/form-data">
        <input type="hidden" name="idNCC" value="<?php echo htmlspecialchars($getNccUpdate->idNCC); ?>" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idNCC); ?></div>
        </div>

        <div class="form-group">
            <label>Tên nhà cung cấp:</label>
            <input type="text" name="tenNCC" class="form-control" value="<?php echo htmlspecialchars($getNccUpdate->tenNCC); ?>" required />
        </div>

        <div class="form-group">
            <label>Người liên hệ:</label>
            <input type="text" name="nguoiLienHe" class="form-control" value="<?php echo htmlspecialchars($getNccUpdate->nguoiLienHe ?? ''); ?>" />
        </div>

        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="text" name="soDienThoai" class="form-control" value="<?php echo htmlspecialchars($getNccUpdate->soDienThoai ?? ''); ?>" />
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($getNccUpdate->email ?? ''); ?>" />
        </div>

        <div class="form-group">
            <label>Địa chỉ:</label>
            <textarea name="diaChi" class="form-control" rows="3"><?php echo htmlspecialchars($getNccUpdate->diaChi ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Mã số thuế:</label>
            <input type="text" name="maSoThue" class="form-control" value="<?php echo htmlspecialchars($getNccUpdate->maSoThue ?? ''); ?>" />
        </div>

        <div class="form-group">
            <label>Ghi chú:</label>
            <textarea name="ghiChu" class="form-control" rows="3"><?php echo htmlspecialchars($getNccUpdate->ghiChu ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Trạng thái:</label>
            <select name="trangThai" class="form-control">
                <option value="1" <?php echo ($getNccUpdate->trangThai == 1) ? 'selected' : ''; ?>>Hoạt động</option>
                <option value="0" <?php echo ($getNccUpdate->trangThai == 0) ? 'selected' : ''; ?>>Không hoạt động</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Cập nhật</button>
            <div id="noteForm" style="margin-top: 10px;"></div>
        </div>
    </form>
</div>

<style>
    .update-form {
        max-width: 100%;
        margin: 0;
        padding: 0;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .form-actions {
        margin-top: 20px;
        text-align: center;
    }

    .btn-primary {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .alert {
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<!-- Không cần JavaScript ở đây nữa vì đã được xử lý trong modal-handler.js -->