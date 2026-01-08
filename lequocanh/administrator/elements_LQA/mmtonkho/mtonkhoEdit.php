<div class="admin-title">Cập nhật thông tin tồn kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mtonkhoCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

$tonkhoObj = new MTonKho();
$hanghoaObj = new hanghoa();

if (isset($_GET['idtk'])) {
    $idTonKho = $_GET['idtk'];

    $list_tonkho = $tonkhoObj->getAllTonKho();

    $tonkho = null;
    foreach ($list_tonkho as $item) {
        if ($item->idTonKho == $idTonKho) {
            $tonkho = $item;
            break;
        }
    }

    if (!$tonkho) {
        echo "<div class='alert alert-danger'>Không tìm thấy thông tin tồn kho!</div>";
        echo "<a href='index.php?req=mtonkho' class='btn btn-primary'>Quay lại</a>";
        exit;
    }
} else {
    header("Location: index.php?req=mtonkho");
    exit;
}
?>

<div class="admin-form">
    <h3>Cập nhật thông tin tồn kho</h3>
    <form name="edittonkho" id="formedittonkho" method="post" action='./elements_LQA/mmtonkho/mtonkhoAct.php?reqact=update'>
        <input type="hidden" name="idTonKho" value="<?php echo $tonkho->idTonKho; ?>" />
        <table>
            <tr>
                <td>Sản phẩm</td>
                <td><input type="text" value="<?php echo $tonkho->tenhanghoa; ?>" disabled /></td>
            </tr>
            <tr>
                <td>Đơn vị tính</td>
                <td><input type="text" value="<?php echo $tonkho->tenDonViTinh ?? 'N/A'; ?>" disabled /></td>
            </tr>
            <tr>
                <td>Số lượng</td>
                <td>
                    <input type="number" name="soLuong" min="0" value="<?php echo $tonkho->soLuong; ?>" readonly />
                    <small class="form-text text-muted">Số lượng chỉ có thể được cập nhật thông qua phiếu nhập kho hoặc xuất kho</small>
                </td>
            </tr>
            <tr>
                <td>Số lượng tối thiểu</td>
                <td><input type="number" name="soLuongToiThieu" min="0" value="<?php echo $tonkho->soLuongToiThieu; ?>" required /></td>
            </tr>
            <tr>
                <td>Vị trí</td>
                <td><input type="text" name="viTri" value="<?php echo $tonkho->viTri; ?>" /></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="Cập nhật" />
                    <a href="index.php?req=mtonkho" class="btn btn-secondary">Hủy</a>
                </td>
            </tr>
        </table>
    </form>
</div>

<style>
    .admin-form {
        max-width: 600px;
        margin: 0 auto;
    }

    .admin-form table {
        width: 100%;
    }

    .admin-form table td {
        padding: 8px;
    }

    .admin-form input[type="text"],
    .admin-form input[type="number"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .admin-form input[type="submit"],
    .btn {
        padding: 8px 16px;
        margin: 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .admin-form input[type="submit"] {
        background-color: #3498db;
        color: white;
    }

    .btn-secondary {
        background-color: #95a5a6;
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        display: inline-block;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        display: inline-block;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .alert-danger {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }

    .form-text {
        display: block;
        margin-top: 5px;
        font-size: 0.85em;
    }

    .text-muted {
        color: #6c757d;
    }
</style>