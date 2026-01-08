<div class="admin-title">Chi tiết phiếu nhập kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mphieunhapCls.php';
require_once './elements_LQA/mod/mchitietphieunhapCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';
require_once './elements_LQA/mod/mtonkhoCls.php';

$phieuNhapObj = new MPhieuNhap();
$chiTietObj = new MChiTietPhieuNhap();
$hanghoaObj = new hanghoa();
$tonkhoObj = new MTonKho();

if (isset($_GET['idpn'])) {
    $idPhieuNhap = $_GET['idpn'];
    $phieuNhap = $phieuNhapObj->getPhieuNhapById($idPhieuNhap);

    if (!$phieuNhap) {
        echo "<div class='alert alert-danger'>Không tìm thấy phiếu nhập!</div>";
        echo "<a href='index.php?req=mphieunhap' class='btn btn-primary'>Quay lại</a>";
        exit;
    }

    $list_chitiet = $chiTietObj->getChiTietByPhieuNhapId($idPhieuNhap);

    $list_hanghoa = $hanghoaObj->HanghoaGetAll();
} else {
    header("Location: index.php?req=mphieunhap");
    exit;
}

$trangThai = "";
$trangThaiClass = "";
switch ($phieuNhap->trangThai) {
    case 0:
        $trangThai = "Chờ duyệt";
        $trangThaiClass = "status-pending";
        break;
    case 1:
        $trangThai = "Đã duyệt";
        $trangThaiClass = "status-approved";
        break;
    case 2:
        $trangThai = "Đã hủy";
        $trangThaiClass = "status-canceled";
        break;
}
?>

<div class="phieunhap-info">
    <h3>Thông tin phiếu nhập</h3>
    <table class="info-table">
        <tr>
            <td><strong>Mã phiếu nhập:</strong></td>
            <td><?php echo $phieuNhap->maPhieuNhap; ?></td>
            <td><strong>Ngày nhập:</strong></td>
            <td><?php echo date('d/m/Y H:i', strtotime($phieuNhap->ngayNhap)); ?></td>
        </tr>
        <tr>
            <td><strong>Nhà cung cấp:</strong></td>
            <td><?php echo $phieuNhap->tenNCC ?? 'N/A'; ?></td>
            <td><strong>Nhân viên:</strong></td>
            <td><?php echo $phieuNhap->tenNV ?? 'N/A'; ?></td>
        </tr>
        <tr>
            <td><strong>Tổng tiền:</strong></td>
            <td><?php echo number_format($phieuNhap->tongTien, 0, ',', '.') . ' VNĐ'; ?></td>
            <td><strong>Trạng thái:</strong></td>
            <td class="<?php echo $trangThaiClass; ?>"><?php echo $trangThai; ?></td>
        </tr>
        <tr>
            <td><strong>Ghi chú:</strong></td>
            <td colspan="3"><?php echo $phieuNhap->ghiChu ?? 'Không có ghi chú'; ?></td>
        </tr>
    </table>
</div>

<?php if ($phieuNhap->trangThai == 0) {
?>
    <div class="admin-form">
        <h3>Thêm sản phẩm vào phiếu nhập</h3>
        <form name="addchitiet" id="formaddchitiet" method="post" action='./elements_LQA/mmphieunhap/mchitietphieunhapAct.php?reqact=addnew'>
            <input type="hidden" name="idPhieuNhap" value="<?php echo $idPhieuNhap; ?>" />
            <table>
                <tr>
                    <td>Sản phẩm</td>
                    <td>
                        <select name="idhanghoa" id="idhanghoa" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php
                            if (!empty($list_hanghoa)) {
                                foreach ($list_hanghoa as $hh) {

                                    $tonkhoInfo = $tonkhoObj->getTonKhoByIdHangHoa($hh->idhanghoa);
                                    $soLuongTonKho = $tonkhoInfo ? $tonkhoInfo->soLuong : 0;

                                    echo "<option value='{$hh->idhanghoa}' data-gia='{$hh->giathamkhao}' data-tonkho='{$soLuongTonKho}'>{$hh->tenhanghoa} (Tồn kho: {$soLuongTonKho})</option>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Số lượng</td>
                    <td><input type="number" name="soLuong" id="soLuong" min="1" value="1" required onchange="tinhThanhTien()" /></td>
                </tr>
                <tr>
                    <td>Đơn giá tham khảo</td>
                    <td><input type="number" name="donGia" id="donGia" min="0" value="0" required readonly /></td>
                </tr>
                <tr>
                    <td>Giá nhập</td>
                    <td><input type="number" name="giaNhap" id="giaNhap" min="0" value="0" required onchange="tinhThanhTien()" /></td>
                </tr>
                <tr>
                    <td>Thành tiền</td>
                    <td><input type="number" name="thanhTien" id="thanhTien" min="0" value="0" readonly /></td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" value="Thêm sản phẩm" />
                        <input type="reset" value="Nhập lại" onclick="resetForm()" />
                    </td>
                </tr>
            </table>
        </form>
    </div>
<?php } ?>

<div class="admin-content-panel">
    <h3>Danh sách sản phẩm trong phiếu nhập</h3>
    <table class="content-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Sản phẩm</th>
                <th>Đơn vị tính</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Giá nhập</th>
                <th>Thành tiền</th>
                <?php if ($phieuNhap->trangThai == 0) {
                ?>
                    <th>Thao tác</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($list_chitiet)) {
                $stt = 1;
                foreach ($list_chitiet as $ct) {
            ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><?php echo $ct->tenhanghoa ?? 'N/A'; ?></td>
                        <td><?php echo $ct->tenDonViTinh ?? 'N/A'; ?></td>
                        <td><?php echo $ct->soLuong; ?></td>
                        <td><?php echo number_format($ct->donGia, 0, ',', '.') . ' VNĐ'; ?></td>
                        <td><?php echo number_format($ct->giaNhap, 0, ',', '.') . ' VNĐ'; ?></td>
                        <td><?php echo number_format($ct->thanhTien, 0, ',', '.') . ' VNĐ'; ?></td>
                        <?php if ($phieuNhap->trangThai == 0) {
                        ?>
                            <td align="center">
                                <!-- Sửa chi tiết phiếu nhập -->
                                <a href="index.php?req=mchitietphieunhapedit&idct=<?php echo $ct->idCTPN; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>

                                <!-- Xóa chi tiết phiếu nhập -->
                                <a href="./elements_LQA/mmphieunhap/mchitietphieunhapAct.php?reqact=delete&idct=<?php echo $ct->idCTPN; ?>&idpn=<?php echo $idPhieuNhap; ?>"
                                    class="btn-delete" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        <?php } ?>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="' . ($phieuNhap->trangThai == 0 ? '8' : '7') . '" align="center">Chưa có sản phẩm nào trong phiếu nhập</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<div class="button-group">
    <a href="index.php?req=mphieunhap" class="btn btn-secondary">Quay lại danh sách phiếu nhập</a>

    <?php if ($phieuNhap->trangThai == 0) {
    ?>
        <a href="./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=approve&idpn=<?php echo $idPhieuNhap; ?>"
            class="btn btn-approve" onclick="return confirm('Bạn có chắc muốn duyệt phiếu nhập này?')">
            <i class="fas fa-check"></i> Duyệt phiếu nhập
        </a>

        <a href="./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=cancel&idpn=<?php echo $idPhieuNhap; ?>"
            class="btn btn-cancel" onclick="return confirm('Bạn có chắc muốn hủy phiếu nhập này?')">
            <i class="fas fa-times"></i> Hủy phiếu nhập
        </a>
    <?php } ?>
</div>

<script>

    var tonkhoInfoElement = document.createElement('div');
    tonkhoInfoElement.id = 'tonkho-info';
    tonkhoInfoElement.style.marginTop = '5px';
    tonkhoInfoElement.style.fontWeight = 'bold';
    document.querySelector('#idhanghoa').parentNode.appendChild(tonkhoInfoElement);

    document.getElementById('idhanghoa').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var gia = selectedOption.getAttribute('data-gia');
        var tonkho = selectedOption.getAttribute('data-tonkho');

        document.getElementById('donGia').value = gia || 0;
        document.getElementById('giaNhap').value = gia || 0;

        if (selectedOption.value) {
            var tenSanPham = selectedOption.text.split(' (Tồn kho:')[0];
            tonkhoInfoElement.innerHTML = 'Sản phẩm: <span style="color: #2980b9;">' + tenSanPham + '</span> - Số lượng tồn kho: <span style="color: ' + (tonkho > 0 ? '#27ae60' : '#e74c3c') + ';">' + tonkho + '</span>';
        } else {
            tonkhoInfoElement.innerHTML = '';
        }

        tinhThanhTien();
    });

    function tinhThanhTien() {
        var soLuong = document.getElementById('soLuong').value || 0;
        var giaNhap = document.getElementById('giaNhap').value || 0;
        var thanhTien = soLuong * giaNhap;
        document.getElementById('thanhTien').value = thanhTien;
    }

    function resetForm() {
        document.getElementById('donGia').value = 0;
        document.getElementById('giaNhap').value = 0;
        document.getElementById('thanhTien').value = 0;
        document.getElementById('tonkho-info').innerHTML = '';
    }
</script>

<style>
    .phieunhap-info {
        margin-bottom: 20px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .info-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .admin-form {
        max-width: 600px;
        margin: 0 auto 20px;
    }

    .admin-form table {
        width: 100%;
    }

    .admin-form table td {
        padding: 8px;
    }

    .admin-form input[type="text"],
    .admin-form input[type="number"],
    .admin-form select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .admin-content-panel {
        margin-bottom: 20px;
    }

    .content-table {
        width: 100%;
        border-collapse: collapse;
    }

    .content-table th,
    .content-table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .content-table th {
        background-color: #f2f2f2;
    }

    .button-group {
        margin-top: 20px;
    }

    .btn {
        padding: 8px 16px;
        margin-right: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary {
        background-color: #95a5a6;
        color: white;
    }

    .btn-approve {
        background-color: #27ae60;
        color: white;
    }

    .btn-cancel {
        background-color: #e74c3c;
        color: white;
    }

    .btn-edit,
    .btn-delete {
        display: inline-block;
        margin: 2px;
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
    }

    .btn-edit {
        background-color: #f39c12;
    }

    .btn-delete {
        background-color: #c0392b;
    }

    .status-pending {
        color: #f39c12;
        font-weight: bold;
    }

    .status-approved {
        color: #27ae60;
        font-weight: bold;
    }

    .status-canceled {
        color: #e74c3c;
        font-weight: bold;
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

    #tonkho-info {
        background-color: #f8f9fa;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
        margin-top: 8px;
        font-size: 14px;
    }
</style>