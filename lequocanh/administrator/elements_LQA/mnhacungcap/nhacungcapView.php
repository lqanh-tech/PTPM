<div class="admin-title">Quản lý nhà cung cấp</div>
<hr>
<div class="admin-form">
    <h3>Thêm nhà cung cấp mới</h3>
    <form name="newnhacungcap" id="formaddncc" method="post"
        action='./elements_LQA/mnhacungcap/nhacungcapAct.php?reqact=addnew'>
        <table>
            <tr>
                <td>Tên nhà cung cấp</td>
                <td><input type="text" name="tenNCC" required /></td>
            </tr>
            <tr>
                <td>Người liên hệ</td>
                <td><input type="text" name="nguoiLienHe" /></td>
            </tr>
            <tr>
                <td>Số điện thoại</td>
                <td><input type="text" name="soDienThoai" /></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><input type="email" name="email" /></td>
            </tr>
            <tr>
                <td>Địa chỉ</td>
                <td><textarea name="diaChi" rows="3"></textarea></td>
            </tr>
            <tr>
                <td>Mã số thuế</td>
                <td><input type="text" name="maSoThue" /></td>
            </tr>
            <tr>
                <td>Ghi chú</td>
                <td><textarea name="ghiChu" rows="3"></textarea></td>
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
require_once './elements_LQA/mod/nhacungcapCls.php';
$nccObj = new nhacungcap();
$list_ncc = $nccObj->NhacungcapGetAll();
$l = count($list_ncc);
?>
<div class="admin-content">
    <div class="admin-info">
        Tổng số nhà cung cấp: <b><?php echo $l; ?></b>
    </div>

    <div class="search-box">
        <form id="search-form" class="search-form">
            <input type="text" id="search-input" placeholder="Tìm kiếm nhà cung cấp..." />
            <button type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
        </form>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên nhà cung cấp</th>
                <th>Người liên hệ</th>
                <th>Số điện thoại</th>
                <th>Email</th>
                <th>Địa chỉ</th>
                <th>Mã số thuế</th>
                <th>Ghi chú</th>
                <th>Trạng thái</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody id="supplier-list">
            <?php
            if ($l > 0) {
                foreach ($list_ncc as $ncc) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ncc->idNCC); ?></td>
                        <td><?php echo htmlspecialchars($ncc->tenNCC); ?></td>
                        <td><?php echo htmlspecialchars($ncc->nguoiLienHe ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ncc->soDienThoai ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ncc->email ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ncc->diaChi ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ncc->maSoThue ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ncc->ghiChu ?? ''); ?></td>
                        <td align="center">
                            <?php if ($ncc->trangThai == 1): ?>
                                <span class="badge bg-success">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Không hoạt động</span>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <?php
                            if (isset($_SESSION['ADMIN'])) {
                            ?>
                                <a href="./elements_LQA/mnhacungcap/nhacungcapAct.php?reqact=deletenhacungcap&idNCC=<?php echo $ncc->idNCC; ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa nhà cung cấp này?');">
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
                                data-module="mnhacungcap"
                                data-update-url="./elements_LQA/mnhacungcap/nhacungcapUpdate.php"
                                data-id-param="idNCC"
                                data-title="Cập nhật Nhà cung cấp"
                                data-id="<?php echo htmlspecialchars($ncc->idNCC); ?>"
                                alt="Update">
                        </td>
                    </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<style>
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .bg-success {
        background-color: #28a745 !important;
        color: white;
    }

    .bg-secondary {
        background-color: #6c757d !important;
        color: white;
    }

    .search-box {
        margin-bottom: 20px;
    }

    .search-form {
        display: flex;
        gap: 10px;
    }

    .search-form input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .search-form button {
        padding: 8px 15px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .search-form button:hover {
        background-color: #0056b3;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim().toLowerCase();

            const rows = document.querySelectorAll('#supplier-list tr');
            rows.forEach(row => {
                let matchFound = false;
                const cells = row.querySelectorAll('td');

                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        matchFound = true;
                    }
                });

                row.style.display = matchFound ? '' : 'none';
            });
        });

        searchInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                const rows = document.querySelectorAll('#supplier-list tr');
                rows.forEach(row => {
                    row.style.display = '';
                });
            }
        });
    });
</script>

<!-- Script debug và sửa lỗi nút cập nhật -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Debug script for nhacungcap loaded");

        const updateButtons = document.querySelectorAll('.generic-update-btn');
        console.log("Found update buttons:", updateButtons.length);

        updateButtons.forEach((btn, index) => {
            console.log(`Button ${index+1} data:`, {
                module: btn.dataset.module,
                url: btn.dataset.updateUrl,
                idParam: btn.dataset.idParam,
                id: btn.dataset.id
            });

            btn.addEventListener('click', function(e) {
                console.log("Direct click handler called for button", index + 1);

                if (btn.dataset.module === 'mnhacungcap') {

                    const id = btn.dataset.id;
                    const updateUrl = btn.dataset.updateUrl;
                    const idParam = btn.dataset.idParam;
                    const title = btn.dataset.title;

                    console.log("Direct handling for nhacungcap button");

                    if (document.getElementById('dynamic-update-modal')) {
                        document.getElementById('dynamic-update-modal').remove();
                    }

                    const modalHtml = `
                        <div id="dynamic-update-modal" style="
                          display: block;
                          position: fixed;
                          z-index: 10000;
                          top: 50%;
                          left: 50%;
                          transform: translate(-50%, -50%);
                          background: white;
                          padding: 25px;
                          border-radius: 8px;
                          box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                          width: 600px;
                          max-height: 90vh;
                          overflow-y: auto;
                          border: 2px solid #3498db;">
                          <button id="dynamic-close-btn" style="
                            position: absolute;
                            top: 10px;
                            right: 10px;
                            background: #f44336;
                            color: white;
                            border: none;
                            width: 30px;
                            height: 30px;
                            border-radius: 50%;
                            font-weight: bold;
                            cursor: pointer;">X</button>
                          <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">${title}</h3>
                          <div id="dynamic-update-form" style="margin-top: 15px;"></div>
                        </div>
                    `;

                    document.body.insertAdjacentHTML('beforeend', modalHtml);

                    document.getElementById('dynamic-close-btn').addEventListener('click', function() {
                        document.getElementById('dynamic-update-modal').remove();
                    });

                    const formData = new FormData();
                    formData.append(idParam, id);

                    fetch(updateUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('dynamic-update-form').innerHTML = html;
                            console.log("Form loaded successfully");

                            const form = document.getElementById('formupdate');
                            if (form) {
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    const formData = new FormData(this);

                                    console.log("Submitting form data:");
                                    for (let [key, value] of formData.entries()) {
                                        console.log(key + ": " + value);
                                    }

                                    fetch('./elements_LQA/mnhacungcap/nhacungcapAct.php?reqact=updatenhacungcap', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => {
                                            console.log("Update successful");

                                            window.location.href = 'index.php?req=nhacungcapview&t=' + new Date().getTime();
                                        })
                                        .catch(error => {
                                            console.error("Error updating:", error);
                                            document.getElementById('noteForm').innerHTML =
                                                '<div style="color: red; font-weight: bold;">Lỗi: ' + error + '</div>';
                                        });
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error loading form:", error);
                            document.getElementById('dynamic-update-form').innerHTML =
                                '<div style="color: red">Lỗi tải biểu mẫu: ' + error + '</div>';
                        });

                    e.stopPropagation();
                }
            });
        });
    });
</script>