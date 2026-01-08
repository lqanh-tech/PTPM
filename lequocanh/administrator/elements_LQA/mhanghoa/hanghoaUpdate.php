<?php
require_once __DIR__ . '/../mod/hanghoaCls.php';
require_once __DIR__ . '/../mod/loaihangCls.php';
require_once __DIR__ . '/../mod/thuonghieuCls.php';
require_once __DIR__ . '/../mod/donvitinhCls.php';
require_once __DIR__ . '/../mod/nhanvienCls.php';

function write_debug_log($message, $data = null)
{
    $log_file = __DIR__ . '/debug_log.txt';
    $log_data = date('Y-m-d H:i:s') . " - " . $message . "\n";

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_data .= print_r($data, true) . "\n";
        } else {
            $log_data .= $data . "\n";
        }
    }

    $log_data .= "--------------------------------------\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idhanghoa = isset($_POST['idhanghoa']) ? $_POST['idhanghoa'] : (isset($_GET['idhanghoa']) ? $_GET['idhanghoa'] : (isset($_REQUEST['idhanghoa']) ? $_REQUEST['idhanghoa'] : null));

if (!$idhanghoa) {
    if (isset($_POST['data-id'])) {
        $idhanghoa = $_POST['data-id'];
    } elseif (isset($_GET['data-id'])) {
        $idhanghoa = $_GET['data-id'];
    }
}

$debug['ID detected'] = $idhanghoa;

if (isset($_GET['debug_output']) || isset($_POST['debug_output'])) {
    echo "<div style='background-color: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;'>";
    echo "<h4>Debug Information</h4>";
    echo "<pre>";
    print_r($debug);
    echo "</pre>";
    echo "</div>";
}

if (isset($_POST['debug_log']) || isset($_GET['debug_log'])) {
    write_debug_log("Carga de formulario de actualización", [
        'idhanghoa' => $idhanghoa,
        'debug' => $debug
    ]);
}

if (!$idhanghoa) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID hàng hóa",
        'debug' => $debug
    ]);
    exit;
}

$hangHoaObj = new HangHoa();
$getHangHoaUpdate = $hangHoaObj->hangHoaGetbyId($idhanghoa);

if (!$getHangHoaUpdate) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy hàng hóa với ID: " . htmlspecialchars($idhanghoa),
        'debug' => $debug
    ]);
    exit;
}

if (isset($_POST['debug_log']) || isset($_GET['debug_log'])) {
    write_debug_log("Datos recuperados para el formulario", [
        'getHangHoaUpdate' => $getHangHoaUpdate,
        'tieneLoaiHang' => !empty($loaiHangList),
        'tieneThuongHieu' => !empty($thuongHieuList),
        'tieneDonViTinh' => !empty($donViTinhList)
    ]);
}

$loaiHangObj = new LoaiHang();
$loaiHangList = $loaiHangObj->loaihangGetAll();

$thuongHieuObj = new ThuongHieu();
$thuongHieuList = $thuongHieuObj->thuonghieuGetAll();

$donViTinhObj = new DonViTinh();
$donViTinhList = $donViTinhObj->donvitinhGetAll();

$nhanVienObj = new NhanVien();
$nhanVienList = $nhanVienObj->nhanvienGetAll();
?>

<div class="update-form-container">
    <div class="update-header">
        <h3>Cập nhật hàng hóa</h3>
        <span class="close-btn" id="close-btn">X</span>
    </div>

    <form name="updatehanghoa" id="updatehanghoa" method="post"
        action="./elements_LQA/mhanghoa/hanghoaAct.php?reqact=updatehanghoa"
        enctype="multipart/form-data">
        <input type="hidden" name="idhanghoa" value="<?php echo htmlspecialchars($idhanghoa); ?>" />
        <input type="hidden" name="debug_log" value="true" />
        <input type="hidden" name="ajax" value="false" />
        <input type="hidden" name="redirect" value="./index.php?req=hanghoaview" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idhanghoa); ?></div>
        </div>

        <div class="form-group">
            <label>Tên hàng hóa:</label>
            <input type="text" class="form-control editable-input" name="tenhanghoa" value="<?php echo htmlspecialchars($getHangHoaUpdate->tenhanghoa ?? ''); ?>" required />
        </div>

        <div class="form-group">
            <label>Mô tả:</label>
            <textarea name="mota" class="form-control editable-input" rows="3"><?php echo htmlspecialchars($getHangHoaUpdate->mota ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Giá tham khảo:</label>
            <input type="number" class="form-control editable-input" name="giathamkhao" value="<?php echo htmlspecialchars($getHangHoaUpdate->giathamkhao ?? ''); ?>" />
        </div>

        <div class="form-group">
            <label>Hình ảnh ID:</label>
            <input type="number" class="form-control editable-input" name="id_hinhanh" value="<?php echo htmlspecialchars($getHangHoaUpdate->hinhanh ?? '0'); ?>" min="0" />

            <?php if (!empty($getHangHoaUpdate->hinhanh)): ?>
                <div class="mt-2">
                    <img src="./elements_LQA/mhanghoa/displayImage.php?id=<?php echo $getHangHoaUpdate->hinhanh; ?>" class="img-thumbnail" style="max-width: 100px; max-height: 100px;" alt="Hình ảnh hiện tại" onerror="this.src='./img_LQA/no-image.png';" />
                    <p>Hình ảnh hiện tại (ID: <?php echo $getHangHoaUpdate->hinhanh; ?>)</p>
                    <button type="button" id="remove-image-btn" class="btn btn-danger btn-sm mt-2" data-id="<?php echo $idhanghoa; ?>">
                        <i class="fas fa-trash"></i> Xóa hình ảnh
                    </button>
                </div>
            <?php endif; ?>
            <p class="hint">Nhập ID hình ảnh từ quản lý hình ảnh (để 0 nếu không có hình ảnh)</p>
        </div>

        <div class="form-group">
            <label>Loại hàng:</label>
            <select name="idloaihang" class="form-control editable-input">
                <?php foreach ($loaiHangList as $loaiHang): ?>
                    <option value="<?php echo htmlspecialchars($loaiHang->idloaihang ?? ''); ?>"
                        <?php echo isset($getHangHoaUpdate->idloaihang) && isset($loaiHang->idloaihang) && $loaiHang->idloaihang == $getHangHoaUpdate->idloaihang ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loaiHang->tenloaihang ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Thương hiệu:</label>
            <select name="idThuongHieu" class="form-control editable-input">
                <option value="">-- Chọn thương hiệu --</option>
                <?php foreach ($thuongHieuList as $thuongHieu): ?>
                    <option value="<?php echo htmlspecialchars($thuongHieu->idThuongHieu ?? ''); ?>"
                        <?php echo isset($getHangHoaUpdate->idThuongHieu) && isset($thuongHieu->idThuongHieu) && $thuongHieu->idThuongHieu == $getHangHoaUpdate->idThuongHieu ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($thuongHieu->tenTH ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Đơn vị tính:</label>
            <select name="idDonViTinh" class="form-control editable-input">
                <option value="">-- Chọn đơn vị tính --</option>
                <?php foreach ($donViTinhList as $donViTinh): ?>
                    <option value="<?php echo htmlspecialchars($donViTinh->idDonViTinh ?? ''); ?>"
                        <?php echo isset($getHangHoaUpdate->idDonViTinh) && isset($donViTinh->idDonViTinh) && $donViTinh->idDonViTinh == $getHangHoaUpdate->idDonViTinh ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($donViTinh->tenDonViTinh ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Nhân viên:</label>
            <select name="idNhanVien" class="form-control editable-input">
                <option value="">-- Chọn nhân viên --</option>
                <?php foreach ($nhanVienList as $nhanVien): ?>
                    <option value="<?php echo htmlspecialchars($nhanVien->idNhanVien ?? ''); ?>"
                        <?php echo isset($getHangHoaUpdate->idNhanVien) && isset($nhanVien->idNhanVien) && $nhanVien->idNhanVien == $getHangHoaUpdate->idNhanVien ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nhanVien->tenNV ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Trạng thái sản phẩm: <span class="text-danger">*</span></label>
            <select name="trang_thai" class="form-control editable-input" required>
                <option value="1" <?php echo (isset($getHangHoaUpdate->trang_thai) && $getHangHoaUpdate->trang_thai == 1) ? 'selected' : (empty($getHangHoaUpdate->trang_thai) ? 'selected' : ''); ?>>
                    ✅ Đang bán (có sẵn)
                </option>
                <option value="2" <?php echo (isset($getHangHoaUpdate->trang_thai) && $getHangHoaUpdate->trang_thai == 2) ? 'selected' : ''; ?>>
                    ⛔ Ngừng bán (không kinh doanh)
                </option>
                <option value="3" <?php echo (isset($getHangHoaUpdate->trang_thai) && $getHangHoaUpdate->trang_thai == 3) ? 'selected' : ''; ?>>
                    📦 Hết hàng (tạm hết)
                </option>
            </select>
            <p class="hint">
                <small>
                    <strong>Đang bán (1):</strong> Sản phẩm hiển thị bình thường và khách hàng có thể mua (nếu có hàng)<br>
                    <strong>Ngừng bán (2):</strong> Sản phẩm được ẩn khỏi danh sách, không cho phép mua<br>
                    <strong>Hết hàng (3):</strong> Sản phẩm hiển thị nhưng có nhãn "Hết hàng", không thể mua
                </small>
            </p>
        </div>

        <?php

        if (isset($getHangHoaUpdate->idhanghoa)) {
            $tonKho = $hangHoaObj->getTonKho($getHangHoaUpdate->idhanghoa);
        ?>
            <div class="form-group">
                <label>Tồn kho hiện tại:</label>
                <div class="alert <?php echo $tonKho > 0 ? 'alert-success' : 'alert-warning'; ?>">
                    <strong><?php echo $tonKho; ?></strong> sản phẩm
                    <?php if ($tonKho == 0): ?>
                        <br><small>⚠️ Sản phẩm đang hết hàng. Hãy nhập thêm hàng hoặc đặt trạng thái "Hết hàng".</small>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
</div>

<style>

    * {
        pointer-events: auto;
    }

    .editable-input {
        pointer-events: auto !important;
        cursor: text !important;
    }

    .update-form-container {
        padding: 15px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        position: relative;
        z-index: 9999;
        pointer-events: auto;
    }

    .update-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        position: relative;
        z-index: 9999;
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
        pointer-events: auto !important;
    }

    .form-group {
        position: relative;
        z-index: 9999;
        margin-bottom: 15px;
        pointer-events: auto;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: text !important;
        pointer-events: auto !important;
    }

    .form-actions {
        text-align: center;
        margin-top: 15px;
        pointer-events: auto;
    }

    .btn-primary {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        pointer-events: auto !important;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .hint {
        margin: 5px 0;
        font-size: 0.9em;
        color: #666;
    }
</style>

<script>

    window.onload = function() {

        setTimeout(function() {
            var inputs = document.querySelectorAll('input.form-control');
            if (inputs.length > 0) {
                inputs[0].focus();
            }
        }, 500);
    }

    document.querySelectorAll('input.form-control, textarea.form-control, select.form-control').forEach(function(input) {
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            this.focus();
        });
    });

    document.getElementById('close-btn').addEventListener('click', function(e) {
        e.stopPropagation();

        if (window.parent) {
            window.parent.postMessage('closeUpdateForm', '*');
        }

        var parentElement = window.frameElement && window.frameElement.parentElement;
        if (parentElement) {
            parentElement.style.display = 'none';
        }
    });

    const removeImageBtn = document.getElementById('remove-image-btn');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này khỏi sản phẩm không?')) {

                const idhanghoa = this.getAttribute('data-id');

                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';
                this.disabled = true;

                fetch('./elements_LQA/mhanghoa/hanghoaAct.php?reqact=remove_image&idhanghoa=' + idhanghoa, {
                        method: 'GET'
                    })
                    .then(response => {

                        if (response.ok) {

                            const imageContainer = this.closest('.mt-2');
                            imageContainer.innerHTML = '<p class="text-success">Đã xóa hình ảnh thành công!</p>';

                            document.querySelector('input[name="id_hinhanh"]').value = '0';
                        } else {

                            alert('Có lỗi xảy ra khi xóa hình ảnh. Vui lòng thử lại.');
                            this.innerHTML = '<i class="fas fa-trash"></i> Xóa hình ảnh';
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xóa hình ảnh. Vui lòng thử lại.');
                        this.innerHTML = '<i class="fas fa-trash"></i> Xóa hình ảnh';
                        this.disabled = false;
                    });
            }
        });
    }

    document.getElementById('updatehanghoa').addEventListener('submit', function(e) {

        const idHinhanhField = document.querySelector('input[name="id_hinhanh"]');
        if (idHinhanhField.value === '' || isNaN(parseInt(idHinhanhField.value))) {
            idHinhanhField.value = '0';
        }

        const submitBtn = document.querySelector('.btn-primary');
        submitBtn.textContent = "Đang gửi...";
        submitBtn.disabled = true;

        e.preventDefault();

        var formData = new FormData(this);

        fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response:", data);
                if (data.success) {

                    window.location.href = "../../index.php?req=hanghoaview";
                } else {

                    alert("Lỗi: " + data.message);
                    submitBtn.textContent = "Cập nhật";
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error("Error:", error);

                window.top.location.href = "/administrator/index.php?req=hanghoaview";
            });

        return false;
    });
</script>