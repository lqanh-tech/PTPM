<?php
require_once __DIR__ . '/../mod/loaihangCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idloaihang = isset($_POST['idloaihang']) ? $_POST['idloaihang'] : (isset($_GET['idloaihang']) ? $_GET['idloaihang'] : (isset($_REQUEST['idloaihang']) ? $_REQUEST['idloaihang'] : null));

$debug['ID detected'] = $idloaihang;

if (isset($_GET['debug']) || isset($_POST['debug'])) {
    echo "<pre>";
    print_r($debug);
    echo "</pre>";
}

if (!$idloaihang) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID loại hàng",
        'debug' => $debug
    ]);
    exit;
}

$lhobj = new loaihang();
$getLhUpdate = $lhobj->LoaihangGetbyId($idloaihang);

if (!$getLhUpdate) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy loại hàng với ID: " . htmlspecialchars($idloaihang),
        'debug' => $debug
    ]);
    exit;
}
?>

<div class="update-form">
    <h3>Cập nhật loại hàng</h3>
    <form name="updateloaihang" id="formupdatelh" method="post" enctype="multipart/form-data">
        <input type="hidden" name="idloaihang" value="<?php echo $getLhUpdate->idloaihang; ?>" />
        <input type="hidden" name="hinhanh" value="<?php echo $getLhUpdate->hinhanh; ?>" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idloaihang); ?></div>
        </div>

        <div class="form-group">
            <label>Tên loại hàng:</label>
            <input type="text" name="tenloaihang" value="<?php echo htmlspecialchars($getLhUpdate->tenloaihang); ?>" required />
        </div>

        <div class="form-group">
            <label>Mô tả:</label>
            <input type="text" name="mota" value="<?php echo htmlspecialchars($getLhUpdate->mota); ?>" />
        </div>

        <div class="form-group">
            <label>Hình ảnh hiện tại:</label>
            <div class="current-image">
                <img width="150" src="data:image/png;base64,<?php echo $getLhUpdate->hinhanh ?>" alt="Current image">
            </div>
            <label>Chọn hình ảnh mới (nếu muốn thay đổi):</label>