<?php
require_once __DIR__ . '/../mod/thuoctinhhhCls.php';
require_once __DIR__ . '/../mod/thuoctinhCls.php';
require_once __DIR__ . '/../mod/hanghoaCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idThuocTinhHH = isset($_POST['idThuocTinhHH']) ? $_POST['idThuocTinhHH'] : (isset($_GET['idThuocTinhHH']) ? $_GET['idThuocTinhHH'] : (isset($_REQUEST['idThuocTinhHH']) ? $_REQUEST['idThuocTinhHH'] : null));

if (!$idThuocTinhHH) {
    if (isset($_POST['data-id'])) {
        $idThuocTinhHH = $_POST['data-id'];
    } elseif (isset($_GET['data-id'])) {
        $idThuocTinhHH = $_GET['data-id'];
    }
}

$debug['ID detected'] = $idThuocTinhHH;

if (isset($_GET['debug']) || isset($_POST['debug'])) {
    echo "<pre>";
    print_r($debug);
    echo "</pre>";
}

if (!$idThuocTinhHH) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID thuộc tính hàng hóa",
        'debug' => $debug
    ]);
    exit;
}

$thuocTinhHHObj = new ThuocTinhHH();
$getThuocTinhHHUpdate = $thuocTinhHHObj->thuoctinhhhGetbyId($idThuocTinhHH);

if (!$getThuocTinhHHUpdate) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy thuộc tính hàng hóa với ID: " . htmlspecialchars($idThuocTinhHH),
        'debug' => $debug
    ]);
    exit;
}

$thuocTinhObj = new ThuocTinh();
$thuocTinhList = $thuocTinhObj->thuoctinhGetAll();

$hangHoaObj = new HangHoa();
$hangHoaList = $hangHoaObj->hanghoaGetAll();
?>

<div class="update-form">
    <h3>Cập nhật thuộc tính hàng hóa</h3>
    <form name="updatethuoctinhhh" id="updatethuoctinhhh" method="post"
        action="/administrator/elements_LQA/mthuoctinhhh/thuoctinhhhAct.php?reqact=updatethuoctinhhh">
        <input type="hidden" name="idThuocTinhHH" value="<?php echo $getThuocTinhHHUpdate->idThuocTinhHH ?? ''; ?>" />
        <input type="hidden" name="debug_log" value="true" />
        <input type="hidden" name="ajax" value="true" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idThuocTinhHH); ?></div>
        </div>

        <div class="form-group">
            <label>Hàng hóa:</label>
            <select name="idhanghoa" class="form-control">
                <?php foreach ($hangHoaList as $hangHoa): ?>
                    <option value="<?php echo htmlspecialchars($hangHoa->idhanghoa ?? ''); ?>"
                        <?php echo isset($hangHoa->idhanghoa) && isset($getThuocTinhHHUpdate->idhanghoa) && $hangHoa->idhanghoa == $getThuocTinhHHUpdate->idhanghoa ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($hangHoa->tenhanghoa ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Thuộc tính:</label>
            <select name="idThuocTinh" class="form-control">
                <?php foreach ($thuocTinhList as $thuocTinh): ?>
                    <option value="<?php echo htmlspecialchars($thuocTinh->idThuocTinh ?? ''); ?>"
                        <?php echo isset($thuocTinh->idThuocTinh) && isset($getThuocTinhHHUpdate->idThuocTinh) && $thuocTinh->idThuocTinh == $getThuocTinhHHUpdate->idThuocTinh ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($thuocTinh->tenThuocTinh ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tên thuộc tính hàng hóa:</label>
            <input type="text" name="tenThuocTinhHH" class="form-control" value="<?php echo htmlspecialchars($getThuocTinhHHUpdate->tenThuocTinhHH ?? ''); ?>" required />
        </div>

        <div class="form-actions">
            <input type="submit" id="btnsubmit" value="Cập nhật" class="btn-update" />
            <b id="noteForm"></b>
        </div>
    </form>
</div>

<style>
    .update-form {
        max-width: 100%;
        margin: 0;
        padding: 10px;
        background-color: #fff;
        border-radius: 5px;
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
    .form-group select,
    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .form-actions {
        margin-top: 20px;
        text-align: center;
    }

    .btn-update {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-update:hover {
        background-color: #0056b3;
    }

    #noteForm {
        display: block;
        margin-top: 10px;
        color: #666;
    }
</style>

<script>

    document.getElementById('updatethuoctinhhh').addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('btnsubmit');
        const originalText = submitBtn.value;
        submitBtn.value = "Đang gửi...";
        submitBtn.disabled = true;

        const formData = new FormData(this);

        fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response:", data);
                if (data.success) {

                    window.top.location.href = "/administrator/index.php?req=thuoctinhhhview";
                } else {

                    document.getElementById('noteForm').innerHTML = '<span style="color:red">' + (data.message || 'Cập nhật thất bại') + '</span>';
                    submitBtn.value = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error("Error:", error);

                document.getElementById('noteForm').innerHTML = '<span style="color:red">Lỗi kết nối: Vui lòng thử lại</span>';
                submitBtn.value = originalText;
                submitBtn.disabled = false;

                setTimeout(() => {
                    window.top.location.href = "/administrator/index.php?req=thuoctinhhhview";
                }, 2000);
            });
    });
</script>