<?php
require_once __DIR__ . '/../mod/thuoctinhCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idThuocTinh = $_POST['idThuocTinh'] ?? $_GET['idThuocTinh'] ?? $_REQUEST['idThuocTinh'] ?? $_REQUEST['id'] ?? $_GET['id'] ?? null;

if (!$idThuocTinh) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID thuộc tính",
        'debug' => $debug
    ]);
    exit;
}

$thuocTinhObj = new ThuocTinh();
$item = $thuocTinhObj->thuoctinhGetbyId($idThuocTinh);

if (!$item) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy thuộc tính với ID: " . htmlspecialchars($idThuocTinh),
        'debug' => $debug
    ]);
    exit;
}
?>

<div class="update-form">
    <button id="close-btn-tt" class="close-btn" type="button">×</button>
    <h3>Cập nhật thuộc tính</h3>
    <form name="updatethuoctinh" id="update-form" method="post">
        <input type="hidden" name="idThuocTinh" value="<?php echo $item->idThuocTinh; ?>" />
        <input type="hidden" name="hinhanh" value="<?php echo $item->hinhanh; ?>" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idThuocTinh); ?></div>
        </div>

        <div class="form-group">
            <label>Tên Thuộc Tính:</label>
            <input type="text" class="form-control" name="tenThuocTinh" value="<?php echo htmlspecialchars($item->tenThuocTinh); ?>" required />
        </div>

        <div class="form-group">
            <label>Ghi Chú:</label>
            <input type="text" class="form-control" name="ghiChu" value="<?php echo htmlspecialchars($item->ghiChu); ?>" />
        </div>

        <div class="form-group">
            <label>Hình ảnh hiện tại:</label>
            <?php if ($item->hinhanh): ?>
                <div class="image-preview">
                    <img src="data:image/png;base64,<?php echo $item->hinhanh; ?>" alt="Current image" class="img-thumbnail">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Chọn hình ảnh mới (nếu muốn thay đổi):</label>