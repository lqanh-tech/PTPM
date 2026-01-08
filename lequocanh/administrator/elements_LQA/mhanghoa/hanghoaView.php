<div class="admin-title">Quản lý hàng hóa</div>
<hr>
<?php
require_once './elements_LQA/mod/loaihangCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

$lhobj = new loaihang();
$hanghoaObj = new hanghoa();

$list_lh = $lhobj->LoaihangGetAll();
$list_thuonghieu = $hanghoaObj->GetAllThuongHieu();
$list_donvitinh = $hanghoaObj->GetAllDonViTinh();
$list_nhanvien = $hanghoaObj->GetAllNhanVien();
$list_hinhanh = $hanghoaObj->GetAllHinhAnh();

$hanghoaObj->CreateHanghoaHinhanhTable();

if (isset($_GET['result'])) {
    if ($_GET['result'] == 'ok') {
        echo '<div class="alert alert-success">';
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] == 'removed_mismatched' && isset($_GET['count'])) {
                echo '<strong>Thành công!</strong> Đã gỡ bỏ ' . $_GET['count'] . ' hình ảnh không khớp tên.';
            } else if ($_GET['msg'] == 'image_removed') {
                echo '<strong>Thành công!</strong> Đã gỡ bỏ hình ảnh khỏi sản phẩm.';
            } else if ($_GET['msg'] == 'image_applied') {
                echo '<strong>Thành công!</strong> Đã áp dụng hình ảnh cho sản phẩm.';
            } else if ($_GET['msg'] == 'all_images_applied' && isset($_GET['count'])) {
                echo '<strong>Thành công!</strong> Đã áp dụng ' . $_GET['count'] . ' hình ảnh cho các sản phẩm.';
            } else {
                echo '<strong>Thành công!</strong> Thao tác đã được thực hiện.';
            }
        } else {
            echo '<strong>Thành công!</strong> Thao tác đã được thực hiện.';
        }
        echo '</div>';
    } else if ($_GET['result'] == 'notok') {
        echo '<div class="alert alert-danger">';

        if (isset($_GET['error_type']) && $_GET['error_type'] == 'foreign_key_constraint') {
            echo '<div class="foreign-key-error">';
            echo '<h4><i class="fas fa-exclamation-triangle"></i> Không thể xóa hàng hóa</h4>';

            if (isset($_GET['message'])) {
                echo '<p><strong>Lý do:</strong> ' . htmlspecialchars(urldecode($_GET['message'])) . '</p>';
            }

            if (isset($_GET['related_tables'])) {
                $relatedTables = json_decode(urldecode($_GET['related_tables']), true);
                if (!empty($relatedTables)) {
                    echo '<div class="related-data-info">';
                    echo '<h5>📋 Dữ liệu liên quan:</h5>';
                    echo '<ul>';
                    foreach ($relatedTables as $table) {
                        echo '<li>';
                        echo '<strong>' . htmlspecialchars($table['display_name']) . ':</strong> ';
                        echo htmlspecialchars($table['description']);
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }

            if (isset($_GET['suggested_action'])) {
                echo '<div class="suggested-action">';
                echo '<h5>💡 Hướng dẫn khắc phục:</h5>';
                echo '<p>' . htmlspecialchars(urldecode($_GET['suggested_action'])) . '</p>';
                echo '</div>';
            }

            echo '<div class="action-steps">';
            echo '<h5>🔧 Các bước thực hiện:</h5>';
            echo '<ol>';
            echo '<li>Kiểm tra và xóa dữ liệu liên quan trong các bảng được liệt kê ở trên</li>';
            echo '<li>Hoặc liên hệ quản trị viên để được hỗ trợ</li>';
            echo '<li>Sau khi xóa dữ liệu liên quan, bạn có thể thử xóa hàng hóa này lại</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
        } else if (isset($_GET['msg'])) {

            if ($_GET['msg'] == 'remove_failed') {
                echo '<strong>Lỗi!</strong> Không thể gỡ bỏ hình ảnh. Vui lòng thử lại.';
            } else if ($_GET['msg'] == 'no_images_removed') {
                echo '<strong>Thông báo:</strong> Không có hình ảnh nào được gỡ bỏ.';
            } else if ($_GET['msg'] == 'image_removal_failed') {
                echo '<strong>Lỗi!</strong> Không thể gỡ bỏ hình ảnh khỏi sản phẩm. Vui lòng thử lại.';
            } else if ($_GET['msg'] == 'image_not_applied') {
                echo '<strong>Lỗi!</strong> Không thể áp dụng hình ảnh cho sản phẩm. Vui lòng thử lại.';
            } else if ($_GET['msg'] == 'some_images_not_applied') {
                echo '<strong>Cảnh báo:</strong> Một số hình ảnh không thể được áp dụng.';
            } else {
                echo '<strong>Lỗi!</strong> Thao tác thất bại. Vui lòng thử lại.';
            }
        } else {
            echo '<strong>Lỗi!</strong> Thao tác thất bại. Vui lòng thử lại.';
        }
        echo '</div>';
    }
}

if (isset($_SESSION['matched_images']) && !empty($_SESSION['matched_images'])) {
    echo '<div class="alert-success">';
    echo '<strong>Phát hiện hình ảnh phù hợp với sản phẩm:</strong><br>';
    foreach ($_SESSION['matched_images'] as $match) {
        echo 'Hình ảnh <strong>' . htmlspecialchars($match['image_name']) . '</strong> phù hợp với sản phẩm <strong>' . htmlspecialchars($match['product_name']) . '</strong><br>';
    }
    echo 'Bạn có thể nhấn nút "Áp dụng" ở cột hình ảnh tương ứng để áp dụng hình ảnh cho sản phẩm.';
    echo '</div>';

    unset($_SESSION['matched_images']);
}

$mismatched_images = $hanghoaObj->GetMismatchedProductImages();
$missing_images = $hanghoaObj->FindMissingImages();
?>

<head>
    <link rel="stylesheet" type="text/css" href="../public_files/mycss.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="admin-form">
    <h3>Thêm hàng hóa mới</h3>
    <form name="newhanghoa" id="formaddhanghoa" method="post"
        action='./elements_LQA/mhanghoa/hanghoaAct.php?reqact=addnew' enctype="multipart/form-data">
        <table>
            <tr>
                <td>Tên hàng hóa</td>
                <td><input type="text" name="tenhanghoa" required /></td>
            </tr>
            <tr>
                <td>Giá tham khảo</td>
                <td><input type="number" name="giathamkhao" required /></td>
            </tr>
            <tr>
                <td>Mô tả</td>
                <td><input type="text" name="mota" /></td>
            </tr>
            <tr>
                <td>Ghi chú</td>
                <td><input type="text" name="ghichu" /></td>
            </tr>
            <tr>
                <td>Hình ảnh</td>
                <td>
                    <select name="id_hinhanh" id="imageSelector">
                        <option value="0">-- Chọn hình ảnh (không bắt buộc) --</option>
                        <?php
                        foreach ($list_hinhanh as $img) {
                        ?>
                            <option value="<?php echo $img->id; ?>">
                                <?php echo htmlspecialchars($img->ten_file); ?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                    <div class="image-preview">
                        <?php
                        foreach ($list_hinhanh as $img) {
                        ?>
                            <div class="preview-item" onclick="selectImage(<?php echo $img->id; ?>)">
                                <?php

                                $imageSrc = "./elements_LQA/mhanghoa/displayImage.php?id=" . $img->id;
                                ?>
                                <img src="<?php echo $imageSrc; ?>&t=<?php echo time(); ?>" class="preview-img" data-id="<?php echo $img->id; ?>"
                                    alt="<?php echo htmlspecialchars($img->ten_file); ?>"
                                    title="<?php echo htmlspecialchars($img->ten_file); ?>"
                                    onerror="this.onerror=null; this.src='./elements_LQA/img_LQA/no-image.png'">
                                <div class="preview-info">
                                    <span class="preview-name"><?php echo htmlspecialchars($img->ten_file); ?></span>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Chọn loại hàng:</td>
                <td>
                    <?php
                    if (!empty($list_lh)) {
                        foreach ($list_lh as $l) {
                    ?>
                            <input type="radio" name="idloaihang" value="<?php echo $l->idloaihang; ?>" required>
                            <img class="iconbutton" src="data:image/png;base64,<?php echo $l->hinhanh; ?>">
                            <br>
                    <?php
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Chọn thương hiệu:</td>
                <td>
                    <select name="idThuongHieu">
                        <option value="">-- Chọn thương hiệu --</option>
                        <?php
                        foreach ($list_thuonghieu as $th) {
                        ?>
                            <option value="<?php echo $th->idThuongHieu; ?>"><?php echo $th->tenTH; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Chọn đơn vị tính:</td>
                <td>
                    <select name="idDonViTinh">
                        <option value="">-- Chọn đơn vị tính --</option>
                        <?php
                        foreach ($list_donvitinh as $dvt) {
                        ?>
                            <option value="<?php echo $dvt->idDonViTinh; ?>"><?php echo $dvt->tenDonViTinh; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Chọn nhân viên:</td>
                <td>
                    <select name="idNhanVien">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php
                        foreach ($list_nhanvien as $nv) {
                        ?>
                            <option value="<?php echo $nv->idNhanVien; ?>"><?php echo $nv->tenNV; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><input type="submit" id="btnsubmit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<?php
$list_hanghoa = $hanghoaObj->HanghoaGetAll();
$l = count($list_hanghoa);
?>
<div class="content_hanghoa">
    <div class="admin-info">
        Tổng số hàng hóa: <b><?php echo $l; ?></b>

        <?php

        $allImages = $hanghoaObj->GetAllHinhAnh();
        $totalImages = count($allImages);

        $productsWithImages = 0;
        foreach ($list_hanghoa as $product) {
            if (isset($product->hinhanh) && $product->hinhanh > 0) {
                $productsWithImages++;
            }
        }

        echo ' | Tổng số hình ảnh đã áp dụng: <b>' . $totalImages . '</b>';
        echo ' | Số sản phẩm có hình ảnh: <b>' . $productsWithImages . '/' . $l . '</b>';
        ?>
    </div>

    <?php

    $searchFormId = 'product-search';
    $tableBodyId = 'product-list';
    $placeholderText = 'Tìm kiếm hàng hóa...';
    include './elements_LQA/includes/search-box.php';
    ?>

    <?php

    if (!empty($mismatched_images)) {
        echo '<div class="alert alert-warning">';
        echo '<div class="alert-header">';
        echo '<h4><i class="fas fa-exclamation-triangle"></i> Lưu ý: Có ' . count($mismatched_images) . ' sản phẩm có hình ảnh không khớp với tên sản phẩm</h4>';
        echo '</div>';
        echo '<ul class="mismatched-list">';
        foreach ($mismatched_images as $item) {
            echo '<li>';
            echo 'Sản phẩm "' . htmlspecialchars($item->tenhanghoa) . '" (ID: ' . $item->idhanghoa . ') ';
            echo 'đang sử dụng hình ảnh "' . htmlspecialchars($item->ten_file) . '" (ID: ' . $item->id . ') ';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p><em>Lưu ý: Đây chỉ là thông báo, bạn có thể kiểm tra và sửa thủ công nếu cần.</em></p>';
        echo '</div>';
    }

    if (!empty($missing_images)) {
        echo '<div class="alert alert-danger">';
        echo '<h4><i class="fas fa-exclamation-circle"></i> Cảnh báo: Có ' . count($missing_images) . ' sản phẩm đang tham chiếu đến hình ảnh không tồn tại</h4>';
        echo '<ul class="missing-list">';
        foreach ($missing_images as $item) {
            echo '<li>';
            echo 'Sản phẩm "' . htmlspecialchars($item->tenhanghoa) . '" (ID: ' . $item->idhanghoa . ') ';
            echo 'đang tham chiếu đến hình ảnh không tồn tại (ID: ' . $item->hinhanh . ')';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p><em>Khuyến nghị: Hãy chọn hình ảnh khác cho các sản phẩm này.</em></p>';
        echo '</div>';
    }
    ?>

    <!-- Scrollable Table Container -->
    <div class="table-scroll-container">
        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên hàng hóa</th>
                    <th>Giá tham khảo</th>
                    <th>Mô tả</th>
                    <th>Hình ảnh</th>
                    <th>Thương Hiệu</th>
                    <th>Đơn Vị Tính</th>
                    <th>Nhân Viên</th>
                    <th>Trạng thái</th>
                    <th>Chức năng</th>
                </tr>
            </thead>
            <tbody id="product-list">
            <?php
            if ($l > 0) {
                foreach ($list_hanghoa as $u) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u->idhanghoa); ?></td>
                        <td><?php echo htmlspecialchars($u->tenhanghoa); ?></td>
                        <td><?php echo number_format($u->giathamkhao, 0, ',', '.'); ?> đ</td>
                        <td><?php echo htmlspecialchars($u->mota); ?></td>
                        <td align="center">
                            <?php
                            if (is_numeric($u->hinhanh) && $u->hinhanh > 0) {

                                $imageSrc = "./elements_LQA/mhanghoa/displayImage.php?id=" . $u->hinhanh;
                            ?>
                                <div class="product-image-container">
                                    <img class="iconbutton product-image" src="<?php echo $imageSrc; ?>&t=<?php echo time(); ?>" alt="Product Image"
                                        onerror="this.onerror=null; this.src='./elements_LQA/img_LQA/no-image.png'">
                                    <div class="image-actions">
                                        <button type="button" class="btn btn-danger btn-sm remove-image-btn" data-id="<?php echo $u->idhanghoa; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php
                            } else {
                                echo '<img class="iconbutton" src="./elements_LQA/img_LQA/no-image.png" alt="No image">';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($u->ten_thuonghieu ?? 'Chưa chọn'); ?></td>
                        <td><?php echo htmlspecialchars($u->ten_donvitinh ?? 'Chưa chọn'); ?></td>
                        <td><?php echo htmlspecialchars($u->ten_nhanvien ?? 'Chưa chọn'); ?></td>
                        <td align="center">
                            <?php
                            $displayStatus = $hanghoaObj->getProductStatus($u->idhanghoa);
                            $statusClass = $hanghoaObj->getStatusCssClass($displayStatus);
                            $statusColor = $hanghoaObj->getStatusColor($displayStatus);
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>" style="background-color: <?php echo $statusColor; ?>; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; font-weight: bold;">
                                <?php echo $displayStatus; ?>
                            </span>
                        </td>
                        <td align="center">
                            <?php
                            if (isset($_SESSION['ADMIN'])) {
                            ?>
                                <a
                                    href="./elements_LQA/mhanghoa/hanghoaAct.php?reqact=deletehanghoa&idhanghoa=<?php echo $u->idhanghoa; ?>">
                                    <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                </a>
                            <?php
                            } else {
                            ?>
                                <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                            <?php
                            }
                            ?>
                            <img src="./elements_LQA/img_LQA/Update.png"
                                class="iconimg generic-update-btn"
                                data-module="mhanghoa"
                                data-update-url="./elements_LQA/mhanghoa/hanghoaUpdate.php"
                                data-id-param="idhanghoa"
                                data-title="Cập nhật Hàng hóa"
                                data-id="<?php echo htmlspecialchars($u->idhanghoa); ?>"
                                alt="Update">
                        </td>
                    </tr>
            <?php
                }
            }
            ?>
            </tbody>
        </table>
    </div><!-- End table-scroll-container -->
</div>

<hr />

<!-- Popup container cho cập nhật hàng hóa -->
<div id="w_update_hh">
    <div class="update-popup-wrapper">
        <span id="w_close_btn_hh">X</span>
        <div id="w_update_form_hh"></div>
    </div>
</div>

<style>

    .table-scroll-container {
        max-height: 60vh;
        min-height: 300px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-top: 15px;
        background: #fff;
    }
    
    .table-scroll-container::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 5px;
    }
    
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 5px;
    }
    
    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }
    
    .table-scroll-container .content-table {
        margin-bottom: 0;
        width: 100%;
    }
    
    .table-scroll-container .content-table thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa;
    }
    
    .table-scroll-container .content-table thead th {
        background: #343a40;
        color: #fff;
        padding: 12px 10px;
        font-weight: 600;
        border-bottom: 2px solid #007bff;
    }

    #w_update_hh {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        border: 2px solid #3498db;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        border-radius: 5px;
        padding: 15px;
        z-index: 9999;
        display: none;
        width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .update-popup-wrapper {
        position: relative;
    }

    #w_close_btn_hh {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #f44336;
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        text-align: center;
        line-height: 25px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
    }

    .product-image-container {
        position: relative;
        display: inline-block;
    }

    .product-image {
        max-width: 100px;
        max-height: 100px;
    }

    .image-actions {
        position: absolute;
        bottom: 0;
        right: 0;
        display: none;
    }

    .product-image-container:hover .image-actions {
        display: block;
    }

    .remove-image-btn {
        padding: 2px 5px;
        font-size: 12px;
        background-color: rgba(220, 53, 69, 0.8);
        border: none;
    }

    .remove-image-btn:hover {
        background-color: #dc3545;
    }

    .foreign-key-error {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin: 15px 0;
    }

    .foreign-key-error h4 {
        color: #856404;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .foreign-key-error h5 {
        color: #856404;
        margin: 15px 0 10px 0;
        font-size: 14px;
        font-weight: bold;
    }

    .related-data-info {
        background: #f8f9fa;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .related-data-info ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .related-data-info li {
        margin: 8px 0;
        line-height: 1.4;
    }

    .suggested-action {
        background: #e7f3ff;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .action-steps {
        background: #f0f9ff;
        border-left: 4px solid #17a2b8;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .action-steps ol {
        margin: 10px 0;
        padding-left: 20px;
    }

    .action-steps li {
        margin: 8px 0;
        line-height: 1.4;
    }

    .status-badge {
        font-size: 13px;
        font-weight: bold;
        padding: 6px 12px;
        border-radius: 20px;
        display: inline-block;
        text-align: center;
        min-width: 100px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .status-active {
        background-color: #27ae60 !important;
    }

    .status-discontinued {
        background-color: #e74c3c !important;
    }

    .status-outofstock {
        background-color: #95a5a6 !important;
    }

    .status-unknown {
        background-color: #34495e !important;
    }
</style>

<script>

    function selectImage(imageId) {

        const imageSelector = document.getElementById('imageSelector');

        imageSelector.value = imageId;

        const allPreviewItems = document.querySelectorAll('.preview-item');
        allPreviewItems.forEach(item => {
            item.classList.remove('selected');
        });

        const selectedItem = document.querySelector(`.preview-item img[data-id="${imageId}"]`).parentNode;
        selectedItem.classList.add('selected');
    }

    document.addEventListener('DOMContentLoaded', function() {

        document.getElementById('imageSelector').addEventListener('change', function() {
            const selectedValue = this.value;

            const allPreviewItems = document.querySelectorAll('.preview-item');
            allPreviewItems.forEach(item => {
                item.classList.remove('selected');
            });

            if (selectedValue) {
                const selectedItem = document.querySelector(`.preview-item img[data-id="${selectedValue}"]`)
                    .parentNode;
                selectedItem.classList.add('selected');
            }
        });

        document.querySelectorAll('.remove-image-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này khỏi sản phẩm không?')) {

                    const idhanghoa = this.getAttribute('data-id');

                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.disabled = true;

                    const button = this;

                    fetch('./elements_LQA/mhanghoa/hanghoaAct.php?reqact=remove_image&idhanghoa=' + idhanghoa, {
                            method: 'GET'
                        })
                        .then(response => {
                            if (response.ok) {

                                const imageContainer = button.closest('.product-image-container');
                                imageContainer.innerHTML = '<img class="iconbutton" src="./elements_LQA/img_LQA/no-image.png" alt="No image">';
                            } else {

                                alert('Có lỗi xảy ra khi xóa hình ảnh. Vui lòng thử lại.');
                                button.innerHTML = '<i class="fas fa-trash"></i>';
                                button.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Có lỗi xảy ra khi xóa hình ảnh. Vui lòng thử lại.');
                            button.innerHTML = '<i class="fas fa-trash"></i>';
                            button.disabled = false;
                        });
                }
            });
        });
    });
</script>

<script src="./js_LQA/test-search.js"></script>

<hr />

<!-- Nút quay lại đầu trang -->
<div id="back-to-top" class="back-to-top-button">
    <i class="fas fa-arrow-up"></i>
    <span class="tooltip">Lên đầu trang</span>
</div>

<style>
    .back-to-top-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: #007bff;
        color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        font-size: 20px;
    }

    .back-to-top-button:hover {
        background-color: #0056b3;
        transform: translateY(-3px);
    }

    .back-to-top-button.visible {
        opacity: 1;
        visibility: visible;
    }

    .back-to-top-button .tooltip {
        position: absolute;
        top: -40px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 14px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .back-to-top-button .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }

    .back-to-top-button:hover .tooltip {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const backToTopButton = document.getElementById('back-to-top');

        checkScrollPosition();

        window.addEventListener('scroll', checkScrollPosition);

        backToTopButton.addEventListener('click', function() {

            if ('scrollBehavior' in document.documentElement.style) {

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {

                smoothScrollToTop();
            }
        });

        function checkScrollPosition() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        }

        function smoothScrollToTop() {
            const currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
            if (currentScroll > 0) {
                window.requestAnimationFrame(smoothScrollToTop);
                window.scrollTo(0, currentScroll - currentScroll / 8);
            }
        }
    });
</script>