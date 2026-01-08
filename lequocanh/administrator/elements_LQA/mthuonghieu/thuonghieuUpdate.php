<?php
require_once '../../elements_LQA/mod/database.php';
require_once '../../elements_LQA/mod/thuonghieuCls.php';

$debugInfo = [
    'source' => 'thuonghieuUpdate.php',
    'post' => $_POST,
    'get' => $_GET,
    'request' => $_REQUEST
];

function sendJsonResponse($success, $message = '', $data = null, $debug = null)
{

    if (ob_get_contents()) ob_clean();

    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");

    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($debug !== null) {
        $response['debug'] = $debug;
    }

    echo json_encode($response);
    exit;
}

$idThuongHieu = null;
if (isset($_POST['idThuongHieu'])) {
    $idThuongHieu = $_POST['idThuongHieu'];
    $debugInfo['id_source'] = 'POST';
} elseif (isset($_GET['idThuongHieu'])) {
    $idThuongHieu = $_GET['idThuongHieu'];
    $debugInfo['id_source'] = 'GET';
} elseif (isset($_REQUEST['idThuongHieu'])) {
    $idThuongHieu = $_REQUEST['idThuongHieu'];
    $debugInfo['id_source'] = 'REQUEST';
}

$debugInfo['idThuongHieu'] = $idThuongHieu;

if ($idThuongHieu === null || !is_numeric($idThuongHieu)) {
    sendJsonResponse(false, 'ID thương hiệu không hợp lệ hoặc không tìm thấy', null, $debugInfo);
    exit;
}

$thuonghieu = new ThuongHieu();
$item = $thuonghieu->thuonghieuGetById($idThuongHieu);

if (!$item) {
    sendJsonResponse(false, 'Không tìm thấy thông tin thương hiệu', null, $debugInfo);
    exit;
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

}

?>
<div class="update-form-container">
    <div class="update-header">
        <h3>Cập nhật Thương hiệu</h3>
        <span class="close-btn" id="close-btn">X</span>
    </div>

    <form id="update-form" action="./elements_LQA/mthuonghieu/thuonghieuAct.php?reqact=updatethuonghieu" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="tenTH">Tên Thương Hiệu:</label>
            <input type="text" class="form-control" id="tenTH" name="tenTH" value="<?= htmlspecialchars($item->tenTH) ?>" required>
        </div>

        <div class="form-group">
            <label for="SDT">Số điện thoại:</label>
            <input type="text" class="form-control" id="SDT" name="SDT" value="<?= htmlspecialchars($item->SDT) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($item->email) ?>" required>
        </div>

        <div class="form-group">
            <label for="diaChi">Địa chỉ:</label>
            <input type="text" class="form-control" id="diaChi" name="diaChi" value="<?= htmlspecialchars($item->diaChi) ?>" required>
        </div>

        <div class="form-group">
            <label for="fileimage">Hình ảnh (để trống nếu không thay đổi):</label>
            <input type="file" class="form-control" id="fileimage" name="fileimage">
            <?php if (!empty($item->hinhanh)): ?>
                <div class="mt-2">
                    <img src="data:image/jpeg;base64,<?= $item->hinhanh ?>" alt="Hình ảnh hiện tại" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                </div>
            <?php endif; ?>
            <input type="hidden" name="hinhanh" value="<?= $item->hinhanh ?>">
        </div>

        <input type="hidden" name="idThuongHieu" id="idThuongHieu" value="<?= $item->idThuongHieu ?>">

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="btn-update">Cập nhật</button>
        </div>
    </form>
</div>

<style>
    .update-form-container {
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .update-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }

    .update-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .close-btn {
        color: #fff;
        background-color: #dc3545;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: bold;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-actions {
        text-align: center;
        margin-top: 15px;
    }
</style>

<script>
    document.getElementById('close-btn').addEventListener('click', function() {

        if (window.parent) {
            window.parent.postMessage('closeUpdateForm', '*');
        }

        var parentElement = window.frameElement && window.frameElement.parentElement;
        if (parentElement) {
            parentElement.style.display = 'none';
        }
    });

    document.getElementById('update-form').addEventListener('submit', function(e) {
        e.preventDefault();

        console.log("Form cập nhật thương hiệu được submit");

        var formData = new FormData(this);

        $.ajax({
            url: "./elements_LQA/mthuonghieu/thuonghieuAct.php?reqact=updatethuonghieu",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log("Phản hồi từ server:", response);

                $("#w_update_th").hide();

                window.location.href = "index.php?req=thuonghieuview&t=" + new Date().getTime();
            },
            error: function(xhr, status, error) {
                console.error("Lỗi khi cập nhật:", error);
                alert("Có lỗi xảy ra khi cập nhật thương hiệu: " + error);
            }
        });
    });
</script>