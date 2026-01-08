<?php

echo '<link rel="stylesheet" href="./elements_LQA/mdongia/dongiaStyles.css">';
?>

<div class="admin-title">
    <i class="fas fa-tags"></i> Quản lý đơn giá
    <small class="text-muted">- Quản lý giá bán cho từng sản phẩm theo thời gian</small>
</div>
<hr>

<!-- Thông báo về logic mới -->
<div class="alert alert-info">
    <h5><i class="icon fas fa-info"></i> Tính năng mới!</h5>
    <ul class="mb-0">
        <li><strong>Chuyển đổi giá:</strong> Có thể chọn lại giữa các đơn giá cũ</li>
        <li><strong>Lịch sử giá:</strong> Theo dõi mọi thay đổi giá</li>
        <li><strong>Kiểm tra tác động:</strong> Cảnh báo ảnh hưởng đến báo cáo</li>
        <li><strong>Bảo vệ dữ liệu:</strong> Đảm bảo tính nhất quán</li>
    </ul>
</div>

<?php

if (isset($_SESSION['dongia_message'])) {
    $message = $_SESSION['dongia_message'];
    $success = isset($_SESSION['dongia_success']) ? $_SESSION['dongia_success'] : false;
    $alertClass = $success ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $alertClass . '" role="alert">' . htmlspecialchars($message) . '</div>';

    unset($_SESSION['dongia_message']);
    unset($_SESSION['dongia_success']);
}

require_once './elements_LQA/mod/dongiaCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

try {
    $lhobj = new Dongia();
    $list_lh = $lhobj->DongiaGetAll();
    $l = count($list_lh);
    error_log("DongiaView: Loaded " . $l . " prices");
} catch (Exception $e) {
    error_log("DongiaView: Error loading prices: " . $e->getMessage());
    $list_lh = [];
    $l = 0;
}

$hhobj = new Hanghoa();
$list_hh = $hhobj->HanghoaGetAll();

if (empty($list_hh)) {
    $list_hh = [];
}
?>

<div class="admin-form">
    <h3>Thêm đơn giá mới</h3>
    <form name="newdongia" id="formadddongia" method="post" action='./elements_LQA/mdongia/dongiaAct.php?reqact=addnew' enctype="multipart/form-data">
        <table>
            <tr>
                <td>Chọn hàng hóa:</td>
                <td>
                    <select name="idhanghoa" id="hanghoaSelect" onchange="updatePrice()" required>
                        <option value="">-- Chọn hàng hóa --</option>
                        <?php
                        if (!empty($list_hh)) {
                            foreach ($list_hh as $h) {
                        ?>
                                <option value="<?php echo htmlspecialchars($h->idhanghoa ?? ''); ?>"
                                    data-price="<?php echo htmlspecialchars($h->giathamkhao ?? ''); ?>">
                                    <?php echo htmlspecialchars($h->tenhanghoa ?? ''); ?>
                                </option>
                        <?php
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Giá bán</td>
                <td><input type="text" name="giaban" id="giaban" required /></td>
            </tr>
            <tr>
                <td>Tên hàng hóa</td>
                <td><input type="text" name="tenHangHoa" id="tenHangHoa" readonly /></td>
            </tr>
            <tr>
                <td>Ngày áp dụng</td>
                <td><input type="date" name="ngayapdung" required /></td>
            </tr>
            <tr>
                <td>Ngày kết thúc</td>
                <td><input type="date" name="ngayketthuc" required /></td>
            </tr>
            <tr>
                <td>Điều kiện</td>
                <td><input type="text" name="dieukien" /></td>
            </tr>
            <tr>
                <td>Ghi chú</td>
                <td><input type="text" name="ghichu" /></td>
            </tr>
            <tr>
                <td><input type="submit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<div class="content_dongia">
    <div class="admin-info">
        Tổng số đơn giá: <b><?php echo $l; ?></b>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản Phẩm</th>
                <th>Giá Bán</th>
                <th>Thời Gian Áp Dụng</th>
                <th>Điều Kiện</th>
                <th>Ghi Chú</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            echo "<!-- Debug: Found $l prices -->";
            if ($l > 0) {
                foreach ($list_lh as $u) {

                    $isActive = $u->apDung;
                    $isExpired = strtotime($u->ngayKetThuc) < time();
                    $statusClass = $isActive ? 'success' : ($isExpired ? 'danger' : 'warning');
                    $statusText = $isActive ? 'Đang áp dụng' : ($isExpired ? 'Đã hết hạn' : 'Chưa áp dụng');
                    $statusIcon = $isActive ? 'check-circle' : ($isExpired ? 'times-circle' : 'clock');
            ?>
                    <tr class="<?php echo $isActive ? 'table-success' : ($isExpired ? 'table-secondary' : ''); ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($u->idDonGia ?? ''); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($u->tenhanghoa ?? ''); ?></strong>
                                <br>
                                <small class="text-muted">ID: <?php echo htmlspecialchars($u->idHangHoa ?? ''); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="price-display">
                                <?php echo number_format($u->giaBan, 0, ',', '.'); ?> đ
                            </span>
                        </td>
                        <td>
                            <div>
                                <small><strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($u->ngayApDung)); ?></small><br>
                                <small><strong>Đến:</strong> <?php echo date('d/m/Y', strtotime($u->ngayKetThuc)); ?></small>
                                <?php if ($isExpired): ?>
                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Đã hết hạn</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($u->dieuKien)): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($u->dieuKien); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Không có</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($u->ghiChu)): ?>
                                <small><?php echo htmlspecialchars($u->ghiChu); ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $statusClass; ?>">
                                <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php if (!$isExpired): ?>
                                    <?php if (!$isActive): ?>
                                        <!-- NÚT ÁP DỤNG - ĐÂY LÀ TÍNH NĂNG CHÍNH -->
                                        <button type="button" class="btn btn-sm btn-success switch-price-btn"
                                            data-id="<?php echo $u->idDonGia; ?>"
                                            data-product="<?php echo htmlspecialchars($u->idHangHoa); ?>"
                                            data-price="<?php echo $u->giaBan; ?>"
                                            title="Áp dụng đơn giá này"
                                            style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                            <i class="fas fa-play"></i> ÁP DỤNG
                                        </button>
                                    <?php else: ?>
                                        <span class="btn btn-sm btn-success disabled" style="background: #28a745; color: white; padding: 5px 10px;">
                                            <i class="fas fa-check"></i> ĐANG ÁP DỤNG
                                        </span>
                                    <?php endif; ?>

                                    <!-- NÚT XEM LỊCH SỬ -->
                                    <button type="button" class="btn btn-sm btn-info view-history-btn"
                                        data-product="<?php echo $u->idHangHoa; ?>"
                                        title="Xem lịch sử giá"
                                        style="background: #17a2b8; color: white; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; margin-left: 2px;">
                                        <i class="fas fa-history"></i>
                                    </button>

                                    <!-- NÚT CHỈNH SỬA -->
                                    <a href="index.php?req=dongiaupdate&iddg=<?php echo $u->idDonGia; ?>"
                                        class="btn btn-sm btn-primary" title="Chỉnh sửa đơn giá"
                                        style="background: #007bff; color: white; text-decoration: none; padding: 5px 8px; border-radius: 4px; margin-left: 2px;">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- NÚT XÓA -->
                                    <button type="button" class="btn btn-sm btn-danger delete-price-btn"
                                        data-id="<?php echo $u->idDonGia; ?>"
                                        title="Xóa đơn giá"
                                        style="background: #dc3545; color: white; border: none; padding: 5px 8px; border-radius: 4px; cursor: pointer; margin-left: 2px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-clock"></i> Đã hết hạn
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        Chưa có đơn giá nào được tạo
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {

    document.querySelectorAll('.switch-price-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const idDonGia = this.dataset.id;
            const productId = this.dataset.product;
            const price = this.dataset.price;
            
            if (confirm(`Bạn có chắc muốn áp dụng đơn giá ${formatPrice(price)}đ?\n\nLưu ý: Đơn giá hiện tại sẽ bị thay thế.`)) {
                switchPrice(idDonGia);
            }
        });
    });

    document.querySelectorAll('.view-history-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.dataset.product;
            alert('Tính năng xem lịch sử giá sẽ được triển khai sau.\nProduct ID: ' + productId);
        });
    });

    document.querySelectorAll('.delete-price-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const idDonGia = this.dataset.id;
            
            if (confirm('Bạn có chắc muốn xóa đơn giá này?\n\nHành động này không thể hoàn tác.')) {
                deletePrice(idDonGia);
            }
        });
    });
});

function switchPrice(idDonGia) {
    fetch('./elements_LQA/mdongia/dongiaSwitch.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'switch_price',
            idDonGia: idDonGia
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Có lỗi xảy ra'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra khi kết nối đến server');
    });
}

function deletePrice(idDonGia) {
    fetch('./elements_LQA/mdongia/dongiaAct.php?reqact=deletedongia', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'idDonGia=' + encodeURIComponent(idDonGia)
    })
    .then(response => response.text())
    .then(data => {
        alert('✅ Xóa đơn giá thành công');
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra khi xóa đơn giá');
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

function updatePrice() {
    var select = document.getElementById("hanghoaSelect");
    if (select.selectedIndex > 0) {
        var selectedOption = select.options[select.selectedIndex];
        var price = selectedOption.getAttribute("data-price");
        var name = selectedOption.text;
        document.getElementById("giaban").value = price;
        document.getElementById("tenHangHoa").value = name;

        var today = new Date();
        var formattedDate = today.toISOString().substr(0, 10);

        var ngayApDungInput = document.querySelector('input[name="ngayapdung"]');
        if (!ngayApDungInput.value) {
            ngayApDungInput.value = formattedDate;
        }

        var nextYear = new Date();
        nextYear.setFullYear(today.getFullYear() + 1);
        var formattedNextYear = nextYear.toISOString().substr(0, 10);

        var ngayKetThucInput = document.querySelector('input[name="ngayketthuc"]');
        if (!ngayKetThucInput.value) {
            ngayKetThucInput.value = formattedNextYear;
        }
    }
}
</script>

<style>

.admin-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
}

.content-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.content-table thead th {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    font-weight: 600;
    text-align: center;
    padding: 12px 8px;
    border: none;
}

.content-table tbody td {
    padding: 10px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.content-table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-group {
    display: flex;
    gap: 2px;
}

.price-display {
    font-size: 1.1em;
    font-weight: bold;
    color: #28a745;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 500;
}

.badge-success { background: #28a745; color: white; }
.badge-danger { background: #dc3545; color: white; }
.badge-warning { background: #ffc107; color: #212529; }
.badge-info { background: #17a2b8; color: white; }

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}
</style>