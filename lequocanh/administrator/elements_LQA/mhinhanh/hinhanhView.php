<?php
require_once __DIR__ . '/../mod/auth_check.php';
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\ProductImage;

$list_hinhanh = ProductImage::getAll();
$total = count($list_hinhanh);

$isDocker = (getenv('DOCKER_ENV') !== false) || file_exists('/.dockerenv');
$uploadDirAbsolute = $isDocker ? '/var/www/html/administrator/uploads/' : 'D:/PHP_WS/lequocanh/administrator/uploads/';
$uploadPermissionOk = true;
$permissionWarning = "";

if (!file_exists($uploadDirAbsolute)) {

    if (!is_writable(dirname($uploadDirAbsolute))) {
        $uploadPermissionOk = false;
        $permissionWarning = "Không thể tạo thư mục upload. Vui lòng đảm bảo thư mục <code>" . dirname($uploadDirAbsolute) . "</code> có quyền ghi.";
    } else {

        if (!mkdir($uploadDirAbsolute, 0777, true)) {
            $uploadPermissionOk = false;
            $permissionWarning = "Không thể tạo thư mục upload ngay cả khi có quyền ghi trên thư mục cha.";
        } else {
            chmod($uploadDirAbsolute, 0777);
        }
    }
} else {

    if (!is_writable($uploadDirAbsolute)) {
        $uploadPermissionOk = false;
        $permissionWarning = "Thư mục upload không có quyền ghi. Vui lòng cấp quyền ghi cho thư mục <code>" . $uploadDirAbsolute . "</code>";
    }
}
?>

<head>
    <link rel="stylesheet" type="text/css" href="../public_files/mycss.css">
    <style>

    .duplicate-image-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        position: relative;
        background-color: #f9f9f9;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .duplicate-image-item.processed {
        background-color: #f0f8ff;
        border-color: #b8daff;
    }

    .image-comparison {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 15px 0;
    }

    .existing-image,
    .new-image {
        flex: 1;
        min-width: 250px;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 10px;
        background-color: white;
    }

    .image-wrapper {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        position: relative;
        overflow: hidden;
    }

    .preview-image {
        max-width: 100%;
        max-height: 180px;
        object-fit: contain;
    }

    .image-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .processing-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.8);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .duplicate-image-item.processing .processing-overlay {
        display: flex;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .result-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
    }

    .result-badge.success {
        background-color: #d4edda;
        color: #155724;
    }

    .result-badge.info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .result-badge.error {
        background-color: #f8d7da;
        color: #721c24;
    }

    .duplicate-actions {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .debug-info {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        padding: 5px;
        margin-top: 5px;
        font-size: 12px;
        color: #666;
    }

    .all-processed .duplicate-warning-alert {
        display: none !important;
    }

    .all-processed .duplicate-image-item {
        display: none !important;
    }

    .all-processed .duplicate-images-container {
        display: none !important;
    }

    .all-processed .alert-success {
        display: block !important;
    }

    .all-processed .resolved-images-container {
        margin-top: 20px;
        padding: 15px;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
    }

    .delete-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .delete-btn:hover {
        background-color: #c82333;
    }

    .delete-btn:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }

    .image-checkbox {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    #delete-selected {
        margin-left: 10px;
        transition: all 0.3s ease;
        padding: 6px 14px !important;
        background-color: #dc3545 !important;
        color: #fff !important;
        border: 1px solid #dc3545 !important;
        border-radius: 4px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    #delete-selected:hover {
        background-color: #c82333 !important;
    }

    #delete-selected:disabled {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        cursor: not-allowed !important;
        opacity: 0.65 !important;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .image-in-use {
        background-color: rgba(255, 243, 205, 0.3);
    }

    .image-in-use .image-checkbox {
        cursor: not-allowed;
        opacity: 0.6;
    }
    </style>
</head>

<div class="admin-title">
    <h1>Quản lý hình ảnh</h1>
</div>

<div class="admin-content">
    <?php if (!$uploadPermissionOk): ?>
    <div class="alert alert-warning">
        <p><strong>Cảnh báo về quyền truy cập:</strong> <?php echo $permissionWarning; ?></p>
        <p>Việc tải lên hình ảnh có thể không hoạt động cho đến khi vấn đề này được giải quyết.</p>
    </div>
    <?php endif; ?>

    <!-- Hiển thị thông báo kết quả -->
    <?php if (isset($_GET['result'])): ?>
    <?php if ($_GET['result'] == 'ok'): ?>
    <div class="alert alert-success">
        <?php
                if (isset($_GET['count'])) {
                    echo 'Đã tải lên ' . $_GET['count'] . ' hình ảnh thành công.';
                } else {
                    echo 'Tải hình ảnh thành công.';
                }
                ?>
    </div>
    <?php elseif ($_GET['result'] == 'partial'): ?>
    <div class="alert alert-warning">
        Tải hình ảnh hoàn tất với một số cảnh báo:
        <?php echo $_GET['success']; ?> thành công,
        <?php echo $_GET['failed']; ?> thất bại.
        <?php if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
        <ul>
            <?php foreach ($_SESSION['upload_errors'] as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php unset($_SESSION['upload_errors']); ?>
        <?php endif; ?>
    </div>
    <?php elseif ($_GET['result'] == 'notok'): ?>
    <div class="alert alert-danger">
        <p><strong>Tải hình ảnh không thành công.</strong> Vui lòng kiểm tra lỗi dưới đây và thử lại:</p>
        <?php if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
        <ul>
            <?php foreach ($_SESSION['upload_errors'] as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php unset($_SESSION['upload_errors']); ?>
        <?php else: ?>
        <p>Không có thông tin chi tiết về lỗi.</p>
        <?php endif; ?>
    </div>
    <?php elseif ($_GET['result'] == 'nofiles'): ?>
    <div class="alert alert-warning">
        <p><strong>Không có file nào được tải lên.</strong></p>
        <?php if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
        <ul>
            <?php foreach ($_SESSION['upload_errors'] as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php unset($_SESSION['upload_errors']); ?>
        <?php endif; ?>
    </div>
    <?php elseif ($_GET['result'] == 'duplicates'): ?>
    <?php if (isset($_SESSION['duplicate_images']) && !empty($_SESSION['duplicate_images'])): ?>
    <div class="alert alert-warning alert-with-icon" id="duplicate-warning-alert">
        <i class="fas fa-exclamation-triangle alert-icon"></i>
        <div class="alert-content">
            <h4 class="alert-heading">Phát hiện ảnh trùng lặp!</h4>
            <p>Hệ thống đã phát hiện ảnh trùng lặp cho một số sản phẩm. Vui lòng chọn giữa việc sử dụng ảnh mới hoặc giữ
                nguyên ảnh hiện tại.</p>
        </div>
    </div>

    <div class="duplicate-actions">
        <button id="process-all-new" class="btn btn-success mb-3">
            <i class="fas fa-check-double"></i> Sử dụng tất cả ảnh mới
        </button>
        <button id="process-all-existing" class="btn btn-secondary mb-3 ml-2" style="margin-left: 10px;">
            <i class="fas fa-undo-alt"></i> Giữ tất cả ảnh hiện tại
        </button>
    </div>

    <div class="duplicate-images-container">
        <?php foreach ($_SESSION['duplicate_images'] as $index => $duplicate): ?>
        <?php

                        $existingImage = ProductImage::getById($duplicate['existing_image_id']);
                        ?>
        <div class="duplicate-image-item" data-index="<?php echo $index; ?>">
            <h5>
                <i class="fas fa-image"></i>
                Ảnh cho sản phẩm: <span
                    class="product-name"><?php echo htmlspecialchars($duplicate['product_name']); ?></span>
            </h5>

            <div class="image-comparison">
                <div class="existing-image">
                    <h6><i class="fas fa-history"></i> Ảnh hiện tại</h6>
                    <div class="image-wrapper">
                        <?php if (!empty($duplicate['existing_image_id'])): ?>
                        <img src="./elements_LQA/mhanghoa/displayImage.php?id=<?php echo $duplicate['existing_image_id']; ?>&t=<?php echo time(); ?>"
                            alt="Ảnh hiện tại" class="preview-image"
                            onerror="this.onerror=null; this.src='./elements_LQA/img_LQA/no-image.png';">
                        <?php else: ?>
                        <div class="no-image">Không có ảnh</div>
                        <?php endif; ?>
                    </div>
                    <p><i class="fas fa-file"></i>
                        <?php
                                        if (isset($duplicate['existing_image_info']) && $duplicate['existing_image_info']) {
                                            echo htmlspecialchars($duplicate['existing_image_info']->ten_file);
                                        } else if (isset($existingImage->ten_file)) {
                                            echo htmlspecialchars($existingImage->ten_file);
                                        } else {
                                            echo "Không có thông tin";
                                        }
                                        ?>
                    </p>
                </div>

                <div class="new-image">
                    <h6><i class="fas fa-upload"></i> Ảnh mới tải lên</h6>
                    <div class="image-wrapper">
                        <?php if (!empty($duplicate['new_image_data'])): ?>
                        <img src="<?php echo $duplicate['new_image_data']; ?>" alt="Ảnh mới" class="preview-image dynamic-image">
                        <?php else: ?>
                        <div class="no-image">Không có ảnh</div>
                        <?php endif; ?>
                    </div>
                    <p><i class="fas fa-file"></i> <?php echo htmlspecialchars($duplicate['new_image_name']); ?></p>
                </div>
            </div>

            <div class="image-actions">
                <button class="btn btn-primary use-new-image" data-index="<?php echo $index; ?>">
                    <i class="fas fa-check"></i> Sử dụng ảnh mới
                </button>
                <button class="btn btn-secondary use-existing-image" data-index="<?php echo $index; ?>">
                    <i class="fas fa-undo"></i> Giữ ảnh hiện tại
                </button>
            </div>

            <div class="processing-overlay">
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <p>Đang xử lý...</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['matched_images']) && !empty($_SESSION['matched_images'])): ?>
    <div class="alert alert-info">
        <p><strong>Kết quả khớp hình ảnh:</strong></p>
        <ul>
            <?php foreach ($_SESSION['matched_images'] as $matched): ?>
            <li>
                <?php if (isset($matched['duplicate']) && $matched['duplicate']): ?>
                Hình ảnh <strong><?php echo htmlspecialchars($matched['image_name']); ?></strong>
                đã tồn tại trong hệ thống và được áp dụng cho sản phẩm
                <strong><?php echo htmlspecialchars($matched['product_name']); ?></strong>
                <?php else: ?>
                Hình ảnh <strong><?php echo htmlspecialchars($matched['image_name']); ?></strong>
                được khớp với sản phẩm <strong><?php echo htmlspecialchars($matched['product_name']); ?></strong>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['matched_images']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['resolved_images']) && !empty($_SESSION['resolved_images'])): ?>
    <div class="alert alert-success">
        <p><strong>Đã xử lý ảnh trùng lặp:</strong></p>
        <ul>
            <?php foreach ($_SESSION['resolved_images'] as $resolved): ?>
            <li>
                <?php if ($resolved['action'] === 'used_new'): ?>
                Đã sử dụng ảnh mới <strong><?php echo htmlspecialchars($resolved['image_name']); ?></strong>
                cho sản phẩm <strong><?php echo htmlspecialchars($resolved['product_name']); ?></strong>
                <?php else: ?>
                Đã giữ nguyên ảnh hiện tại cho sản phẩm
                <strong><?php echo htmlspecialchars($resolved['product_name']); ?></strong>
                (bỏ qua ảnh mới <strong><?php echo htmlspecialchars($resolved['image_name']); ?></strong>)
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['resolved_images']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['auto_applied_images']) && !empty($_SESSION['auto_applied_images'])): ?>
    <div class="alert alert-success">
        <p><strong>Đã tự động áp dụng hình ảnh cho các sản phẩm sau:</strong></p>
        <ul>
            <?php foreach ($_SESSION['auto_applied_images'] as $applied): ?>
            <li>Hình <strong><?php echo htmlspecialchars($applied['image_name']); ?></strong>
                đã áp dụng cho sản phẩm <strong><?php echo htmlspecialchars($applied['product_name']); ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['auto_applied_images']); ?>
    <?php endif; ?>

    <!-- Form upload hình ảnh -->
    <div class="admin-form">
        <h3>Upload hình ảnh</h3>

        <form method="post" action="" enctype="multipart/form-data"
            id="uploadForm">
            <div class="input-group">
                <label for="fileHinhanh">Chọn hình ảnh (có thể chọn nhiều file):</label>
                <input type="file" name="fileHinhanh[]" id="fileHinhanh" multiple accept="image/*" required>
                <small class="form-text">Định dạng cho phép: JPG, PNG, GIF. Kích thước tối đa: 5MB/file</small>
            </div>

            <div class="input-group">
                <label>
                    <input type="checkbox" name="auto_match" id="auto_match" value="1" checked>
                    Tự động khớp hình ảnh với sản phẩm dựa trên tên file
                </label>
                <small class="form-text">Ví dụ: "iPhone 15 Pro.png" sẽ tự động khớp với sản phẩm "iPhone 15 Pro"</small>
            </div>

            <div class="form-actions">
                <button type="submit" name="btnsubmit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Tải lên
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Làm mới
                </button>
            </div>
        </form>
    </div>

    <!-- Danh sách hình ảnh -->
    <div class="admin-table">
        <h3>Danh sách hình ảnh (<?php echo $total; ?> ảnh)</h3>
        
        <div class="table-actions">
            <button id="select-all" class="btn btn-sm btn-secondary">
                <i class="fas fa-check-square"></i> Chọn tất cả
            </button>
            <button id="delete-selected" class="btn btn-sm btn-danger" disabled>
                <i class="fas fa-trash"></i> Xóa đã chọn (<span id="selected-count">0</span>)
            </button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th width="50"><input type="checkbox" id="select-all-checkbox"></th>
                    <th width="80">ID</th>
                    <th width="150">Hình ảnh</th>
                    <th>Tên file</th>
                    <th width="150">Kích thước</th>
                    <th width="150">Ngày tải lên</th>
                    <th width="100">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0): ?>
                    <?php foreach ($list_hinhanh as $hinhanh): ?>
                    <tr id="image-row-<?php echo $hinhanh->id; ?>" 
                        class="<?php echo $hinhanh->usage_count > 0 ? 'image-in-use' : ''; ?>">
                        <td>
                            <input type="checkbox" 
                                   class="image-checkbox" 
                                   data-id="<?php echo $hinhanh->id; ?>"
                                   <?php echo $hinhanh->usage_count > 0 ? 'disabled title="Ảnh đang được sử dụng"' : ''; ?>>
                        </td>
                        <td><?php echo $hinhanh->id; ?></td>
                        <td>
                            <img src="./elements_LQA/mhanghoa/displayImage.php?id=<?php echo $hinhanh->id; ?>" 
                                 alt="<?php echo htmlspecialchars($hinhanh->ten_file); ?>"
                                 style="max-width: 100px; max-height: 100px; object-fit: contain;"
                                 onerror="this.src='./elements_LQA/img_LQA/no-image.png';">
                        </td>
                        <td><?php echo htmlspecialchars($hinhanh->ten_file); ?></td>
                        <td><?php echo isset($hinhanh->file_size) ? number_format($hinhanh->file_size / 1024, 2) . ' KB' : 'N/A'; ?></td>
                        <td><?php echo isset($hinhanh->ngay_tao) ? date('d/m/Y H:i', strtotime($hinhanh->ngay_tao)) : 'N/A'; ?></td>
                        <td>
                            <?php if ($hinhanh->usage_count == 0): ?>
                            <button class="delete-btn" 
                                    onclick="deleteImage(<?php echo $hinhanh->id; ?>)"
                                    title="Xóa ảnh">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted" title="Ảnh đang được sử dụng">
                                <i class="fas fa-lock"></i>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Chưa có hình ảnh nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Set form action dynamically to avoid relative URL issues
(function() {
    var base = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : '';
    var form = document.getElementById('uploadForm');
    if (form) {
        form.action = base + '/lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=addnew';
    }
})();

// Handle image error fallback
function handleImageError(img) {
    const altSrc1 = img.getAttribute('data-alt-src1');
    const altSrc2 = img.getAttribute('data-alt-src2');
    
    if (altSrc1 && img.src !== altSrc1) {
        img.src = altSrc1;
    } else if (altSrc2 && img.src !== altSrc2) {
        img.src = altSrc2;
    } else {
        img.src = './elements_LQA/img_LQA/no-image.png';
    }
}

// Show debug info
document.querySelectorAll('.show-debug').forEach(btn => {
    btn.addEventListener('click', function() {
        const debugInfo = this.closest('.image-wrapper').querySelector('.debug-info');
        if (debugInfo) {
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
        }
    });
});

// Handle duplicate image resolution
document.querySelectorAll('.use-new-image').forEach(btn => {
    btn.addEventListener('click', function() {
        const index = this.getAttribute('data-index');
        resolveDuplicate(index, 'use_new');
    });
});

document.querySelectorAll('.use-existing-image').forEach(btn => {
    btn.addEventListener('click', function() {
        const index = this.getAttribute('data-index');
        resolveDuplicate(index, 'use_existing');
    });
});

// Process all duplicates
document.getElementById('process-all-new')?.addEventListener('click', function() {
    if (confirm('Bạn có chắc muốn sử dụng tất cả ảnh mới?')) {
        processAllDuplicates('use_new');
    }
});

document.getElementById('process-all-existing')?.addEventListener('click', function() {
    if (confirm('Bạn có chắc muốn giữ tất cả ảnh hiện tại?')) {
        processAllDuplicates('use_existing');
    }
});

function resolveDuplicate(index, action) {
    const item = document.querySelector(`.duplicate-image-item[data-index="${index}"]`);
    if (!item) return;
    
    item.classList.add('processing');
    
    fetchAct('resolve_duplicate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `index=${index}&action=${action}`
    })
    .then(data => {
        item.classList.remove('processing');
        
        if (data.success) {
            item.classList.add('processed');
            const badge = document.createElement('div');
            badge.className = 'result-badge success';
            badge.textContent = action === 'use_new' ? 'Đã sử dụng ảnh mới' : 'Đã giữ ảnh hiện tại';
            item.appendChild(badge);
            
            setTimeout(() => {
                item.style.display = 'none';
                checkAllProcessed();
            }, 1500);
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể xử lý'));
        }
    })
    .catch(error => {
        item.classList.remove('processing');
        alert('Lỗi kết nối: ' + error.message);
    });
}

function processAllDuplicates(action) {
    const items = document.querySelectorAll('.duplicate-image-item:not(.processed)');
    items.forEach(item => {
        const index = item.getAttribute('data-index');
        resolveDuplicate(index, action);
    });
}

function checkAllProcessed() {
    const remaining = document.querySelectorAll('.duplicate-image-item:not(.processed)').length;
    if (remaining === 0) {
        document.querySelector('.admin-content').classList.add('all-processed');
        location.reload();
    }
}

// Select all checkbox
document.getElementById('select-all-checkbox')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.image-checkbox:not([disabled])');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

document.getElementById('select-all')?.addEventListener('click', function() {
    const checkbox = document.getElementById('select-all-checkbox');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change'));
    }
});

// Update selected count
document.querySelectorAll('.image-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selected = document.querySelectorAll('.image-checkbox:checked').length;
    document.getElementById('selected-count').textContent = selected;
    document.getElementById('delete-selected').disabled = selected === 0;
}

// Delete selected images
document.getElementById('delete-selected')?.addEventListener('click', function() {
    const selected = Array.from(document.querySelectorAll('.image-checkbox:checked'))
        .map(cb => cb.getAttribute('data-id'));
    
    if (selected.length === 0) return;
    
    if (confirm(`Bạn có chắc muốn xóa ${selected.length} ảnh đã chọn?`)) {
        deleteMultipleImages(selected);
    }
});

function getActUrl(reqact) {
    var base = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : '';
    return base + '/lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=' + reqact;
}

function fetchAct(reqact, options) {
    return fetch(getActUrl(reqact), options).then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        var contentType = response.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') === -1) {
            return response.text().then(function(text) {
                throw new Error('Server trả về HTML thay vì JSON: ' + text.substring(0, 200));
            });
        }
        return response.json();
    });
}

function deleteImage(id) {
    if (!confirm('Bạn có chắc muốn xóa ảnh này?')) return;

    fetchAct('deleteimage', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(data => {
        if (data.success) {
            document.getElementById(`image-row-${id}`).remove();
            alert('Đã xóa ảnh thành công');
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể xóa ảnh'));
        }
    })
    .catch(error => {
        alert('Lỗi kết nối: ' + error.message);
    });
}

function deleteMultipleImages(ids) {
    fetchAct('deletemultiple', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(data => {
        if (data.success) {
            ids.forEach(id => {
                document.getElementById(`image-row-${id}`)?.remove();
            });
            alert(`Đã xóa ${data.deleted} ảnh thành công`);
            updateSelectedCount();
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể xóa ảnh'));
        }
    })
    .catch(error => {
        alert('Lỗi kết nối: ' + error.message);
    });
}
</script>