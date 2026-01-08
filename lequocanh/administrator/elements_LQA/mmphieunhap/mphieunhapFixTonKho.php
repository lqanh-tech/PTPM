<?php

session_start();
if (!isset($_SESSION['USER']) || !isset($_SESSION['ADMIN'])) {
    header("Location: login.php");
    exit;
}

require_once './elements_LQA/mod/mphieunhapCls.php';
require_once './elements_LQA/mod/mtonkhoCls.php';

$phieunhapObj = new MPhieuNhap();

$message = '';
$messageType = '';

if (isset($_POST['fix_tonkho'])) {
    $idPhieuNhap = $_POST['idPhieuNhap'];
    
    $result = $phieunhapObj->forceUpdateTonKho($idPhieuNhap);
    
    if ($result) {
        $message = "Đã cập nhật tồn kho thành công cho phiếu nhập #" . $idPhieuNhap;
        $messageType = "success";
    } else {
        $message = "Không thể cập nhật tồn kho cho phiếu nhập #" . $idPhieuNhap . ". Vui lòng kiểm tra lại.";
        $messageType = "error";
    }
}

$list_phieunhap = $phieunhapObj->getPhieuNhapByTrangThai(1);
?>

<div class="admin-title">Cập nhật tồn kho cho phiếu nhập đã duyệt</div>
<hr>

<?php if (!empty($message)) : ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="admin-content-panel">
    <p>Công cụ này giúp cập nhật lại tồn kho cho các phiếu nhập đã được duyệt nhưng chưa được cập nhật vào bảng tồn kho.</p>
    
    <h3>Danh sách phiếu nhập đã duyệt</h3>
    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã phiếu nhập</th>
                <th>Ngày nhập</th>
                <th>Nhà cung cấp</th>
                <th>Nhân viên</th>
                <th>Tổng tiền</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($list_phieunhap)) {
                foreach ($list_phieunhap as $pn) {
            ?>
                    <tr>
                        <td><?php echo $pn->idPhieuNhap; ?></td>
                        <td><?php echo $pn->maPhieuNhap; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($pn->ngayNhap)); ?></td>
                        <td><?php echo $pn->tenNCC ?? 'N/A'; ?></td>
                        <td><?php echo $pn->tenNhanVien ?? 'N/A'; ?></td>
                        <td><?php echo number_format($pn->tongTien, 0, ',', '.') . ' VND'; ?></td>
                        <td align="center">
                            <form method="post" action="">
                                <input type="hidden" name="idPhieuNhap" value="<?php echo $pn->idPhieuNhap; ?>" />
                                <button type="submit" name="fix_tonkho" class="btn-fix" onclick="return confirm('Bạn có chắc muốn cập nhật tồn kho cho phiếu nhập này?')">
                                    <i class="fas fa-sync-alt"></i> Cập nhật tồn kho
                                </button>
                            </form>
                            
                            <!-- Xem chi tiết phiếu nhập -->
                            <a href="index.php?req=mchitietphieunhap&idpn=<?php echo $pn->idPhieuNhap; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="7" align="center">Không có phiếu nhập đã duyệt</td></tr>';
            }
            ?>
        </tbody>
    </table>
    
    <div class="button-group">
        <a href="index.php?req=mphieunhap" class="btn btn-secondary">Quay lại danh sách phiếu nhập</a>
    </div>
</div>

<style>
    .admin-content-panel {
        margin-bottom: 30px;
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .content-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .content-table th, .content-table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .content-table th {
        background-color: #f2f2f2;
    }
    .btn-fix {
        display: inline-block;
        margin: 2px;
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
        background-color: #3498db;
        border: none;
        cursor: pointer;
    }
    .btn-view {
        display: inline-block;
        margin: 2px;
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
        background-color: #2ecc71;
    }
    .btn-secondary {
        display: inline-block;
        margin: 2px;
        padding: 8px 15px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
        background-color: #7f8c8d;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 3px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .button-group {
        margin-top: 20px;
    }
</style>
