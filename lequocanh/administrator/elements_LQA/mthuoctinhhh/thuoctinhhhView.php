<div class="admin-title">Quản lý thuộc tính hàng hóa</div>
<hr>
<?php
require_once './elements_LQA/mod/hanghoaCls.php';
require_once './elements_LQA/mod/thuoctinhCls.php';
require_once './elements_LQA/mod/thuoctinhhhCls.php';
require_once './elements_LQA/mod/csrfProtection.php';

$hangHoaObj = new HangHoa();
$list_hh = $hangHoaObj->HanghoaGetAll();

$thuocTinhObj = new ThuocTinh();
$list_lh_thuoctinh = $thuocTinhObj->thuoctinhGetAll();

$thuocTinhHHObj = new ThuocTinhHH();
$list_lh_thuoctinhhh = $thuocTinhHHObj->thuoctinhhhGetAll();
?>

<div class="admin-form">
    <h3>Thêm thuộc tính hàng hóa mới</h3>
    <form name="newthuoctinhhh" id="formaddthuoctinhhh" method="post"
        action='./elements_LQA/mthuoctinhhh/thuoctinhhhAct.php?reqact=addnew'>
        <?php

        if (class_exists('CSRFProtection')) {
            echo CSRFProtection::getHiddenField();
        }
        ?>
        <table>
            <tr>
                <td>Chọn hàng hóa:</td>
                <td>
                    <select name="idhanghoa" id="hanghoaSelect" required>
                        <option value="">-- Chọn hàng hóa --</option>
                        <?php if (!empty($list_hh)) {
                            foreach ($list_hh as $h) { ?>
                                <option value="<?php echo htmlspecialchars($h->idhanghoa); ?>">
                                    <?php echo htmlspecialchars($h->tenhanghoa); ?></option>
                        <?php }
                        } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Chọn thuộc tính:</td>
                <td>
                    <select name="idThuocTinh" id="idThuocTinh" required>
                        <?php if (!empty($list_lh_thuoctinh)) {
                            foreach ($list_lh_thuoctinh as $l) { ?>
                                <option value="<?php echo htmlspecialchars($l->idThuocTinh); ?>">
                                    <?php echo htmlspecialchars($l->tenThuocTinh); ?></option>
                        <?php }
                        } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Tên Thuộc Tính HH</td>
                <td>
                    <input type="text" name="tenThuocTinhHH" id="tenThuocTinhHH" required />
                    <!-- Color picker sẽ hiển thị khi chọn thuộc tính màu sắc -->
                    <div id="colorPickerContainer" style="display: none; margin-top: 10px;">
                        <div class="color-picker-grid"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Ghi Chú</td>
                <td><input type="text" name="ghiChu" /></td>
            </tr>
            <tr>
                <td><input type="submit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<div class="content_thuoctinhhh">
    <div class="admin-info">
        Tổng số thuộc tính hàng hóa: <b><?php echo count($list_lh_thuoctinhhh); ?></b>
    </div>

    <!-- Scrollable Table Container -->
    <div class="table-scroll-container">
        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID Hàng Hóa</th>
                    <th>ID Thuộc Tính</th>
                    <th>Tên Thuộc Tính HH</th>
                    <th>Ghi Chú</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list_lh_thuoctinhhh)) {
                    foreach ($list_lh_thuoctinhhh as $u) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u->idThuocTinhHH); ?></td>
                            <td><?php echo htmlspecialchars($u->idhanghoa); ?></td>
                            <td><?php echo htmlspecialchars($u->idThuocTinh); ?></td>
                            <td class="tenthuoctinhhh"><?php echo htmlspecialchars($u->tenThuocTinhHH); ?></td>
                            <td><?php echo htmlspecialchars($u->ghiChu ?? ""); ?></td>
                            <td align="center">
                                <?php if (isset($_SESSION['ADMIN'])) { ?>
                                    <a href="./elements_LQA/mthuoctinhhh/thuoctinhhhAct.php?reqact=deletethuoctinhhh&idThuocTinhHH=<?php echo htmlspecialchars($u->idThuocTinhHH); ?>"
                                        onclick="return confirm('Bạn có chắc muốn xóa không?');">
                                        <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                    </a>
                                <?php } else { ?>
                                    <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                <?php } ?>
                                <img src="./elements_LQA/img_LQA/Update.png"
                                    class="iconimg generic-update-btn"
                                    data-module="mthuoctinhhh"
                                    data-update-url="./elements_LQA/mthuoctinhhh/thuoctinhhhUpdate.php"
                                    data-id-param="idThuocTinhHH"
                                    data-title="Cập nhật Thuộc tính hàng hóa"
                                    data-id="<?php echo htmlspecialchars($u->idThuocTinhHH); ?>"
                                    alt="Update">
                            </td>
                        </tr>
                <?php }
                } ?>
            </tbody>
        </table>
    </div><!-- End table-scroll-container -->
</div>

<!-- Nút quay lại đầu trang -->
<div id="back-to-top" class="back-to-top-button">
    <i class="fas fa-arrow-up"></i>
    <span class="tooltip">Lên đầu trang</span>
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
    }
    
    .table-scroll-container .content-table thead th {
        background: #343a40;
        color: #fff;
        padding: 12px 10px;
        font-weight: 600;
        border-bottom: 2px solid #007bff;
    }

    .color-picker-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .color-picker-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        border: 2px solid transparent;
    }

    .color-picker-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-color: #007bff;
    }

    .color-picker-item.selected {
        border-color: #007bff;
        background: #e7f3ff;
    }

    .color-picker-swatch {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .color-picker-item.selected .color-picker-swatch {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    .color-picker-label {
        font-size: 12px;
        font-weight: 500;
        color: #495057;
        text-align: center;
    }

    .color-picker-item.selected .color-picker-label {
        color: #007bff;
        font-weight: 600;
    }

    .color-picker-checkmark {
        position: absolute;
        top: 5px;
        right: 5px;
        color: #007bff;
        font-size: 16px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .color-picker-item.selected .color-picker-checkmark {
        opacity: 1;
    }

    .color-picker-item {
        position: relative;
    }

    #tenThuocTinhHH.color-mode {
        background: #e7f3ff;
        border-color: #007bff;
    }

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

        const thuocTinhSelect = document.getElementById('idThuocTinh');
        const tenThuocTinhInput = document.getElementById('tenThuocTinhHH');
        const colorPickerContainer = document.getElementById('colorPickerContainer');
        const colorPickerGrid = colorPickerContainer.querySelector('.color-picker-grid');

        const standardColors = [{
                vi: 'Đỏ',
                en: 'red',
                hex: '#dc3545'
            },
            {
                vi: 'Xanh dương',
                en: 'blue',
                hex: '#007bff'
            },
            {
                vi: 'Xanh lá',
                en: 'green',
                hex: '#28a745'
            },
            {
                vi: 'Vàng',
                en: 'yellow',
                hex: '#ffc107'
            },
            {
                vi: 'Cam',
                en: 'orange',
                hex: '#fd7e14'
            },
            {
                vi: 'Tím',
                en: 'purple',
                hex: '#6f42c1'
            },
            {
                vi: 'Hồng',
                en: 'pink',
                hex: '#e83e8c'
            },
            {
                vi: 'Đen',
                en: 'black',
                hex: '#212529'
            },
            {
                vi: 'Trắng',
                en: 'white',
                hex: '#ffffff'
            },
            {
                vi: 'Xám',
                en: 'gray',
                hex: '#6c757d'
            },
            {
                vi: 'Nâu',
                en: 'brown',
                hex: '#8b4513'
            },
            {
                vi: 'Bạc',
                en: 'silver',
                hex: '#c0c0c0'
            }
        ];

        function renderColorPicker() {
            colorPickerGrid.innerHTML = '';
            standardColors.forEach(color => {
                const item = document.createElement('div');
                item.className = 'color-picker-item';
                item.dataset.colorVi = color.vi;
                item.dataset.colorEn = color.en;

                const borderStyle = color.en === 'white' ? 'border: 2px solid #dee2e6;' : '';

                item.innerHTML = `
                    <div class="color-picker-swatch" style="background-color: ${color.hex}; ${borderStyle}"></div>
                    <div class="color-picker-label">${color.vi}</div>
                    <i class="fas fa-check color-picker-checkmark"></i>
                `;

                item.addEventListener('click', function() {

                    document.querySelectorAll('.color-picker-item').forEach(i => i.classList.remove(
                        'selected'));

                    this.classList.add('selected');

                    tenThuocTinhInput.value = color.vi;
                    tenThuocTinhInput.classList.add('color-mode');
                });

                colorPickerGrid.appendChild(item);
            });
        }

        function checkColorAttribute() {
            const selectedOption = thuocTinhSelect.options[thuocTinhSelect.selectedIndex];
            const attributeName = selectedOption.text.toLowerCase();

            if (attributeName.includes('màu') || attributeName.includes('color')) {

                colorPickerContainer.style.display = 'block';
                tenThuocTinhInput.placeholder = 'Chọn màu từ bảng màu bên dưới';
                tenThuocTinhInput.classList.add('color-mode');
                renderColorPicker();
            } else {

                colorPickerContainer.style.display = 'none';
                tenThuocTinhInput.placeholder = '';
                tenThuocTinhInput.classList.remove('color-mode');
            }
        }

        thuocTinhSelect.addEventListener('change', checkColorAttribute);

        checkColorAttribute();

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