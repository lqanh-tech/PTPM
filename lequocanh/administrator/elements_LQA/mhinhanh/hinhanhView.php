<?php
require_once("./elements_LQA/mod/hanghoaCls.php");
$hanghoa = new hanghoa();
$list_hinhanh = $hanghoa->GetAllHinhAnh();
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

                        $existingImage = $hanghoa->GetHinhAnhById($duplicate['existing_image_id']);
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
                        <?php if (!empty($duplicate['relative_path'])): ?>
                        <!-- Debug data -->
                        <div class="debug-info" style="display: none;">
                            <pre><?php print_r($duplicate); ?></pre>
                        </div>
                        <?php

                                            $relativePath = $duplicate['relative_path'];
                                            $ts = isset($duplicate['upload_timestamp']) ? $duplicate['upload_timestamp'] : time();
                                            $imagePath1 = $duplicate['new_image_path'] . '?t=' . $ts;
                                            $imagePath2 = '../../' . $relativePath . '?t=' . $ts;
                                            $imagePath3 = '../../../' . $relativePath . '?t=' . $ts;
                                            ?>
                        <img src="<?php echo $imagePath1; ?>" data-alt-src1="<?php echo $imagePath2; ?>"
                            data-alt-src2="<?php echo $imagePath3; ?>" alt="Ảnh mới" class="preview-image dynamic-image"
                            onerror="this.onerror=null; handleImageError(this);">
                        <button type="button" class="btn btn-sm btn-info show-debug">
                            <i class="fas fa-bug"></i> Debug
                        </button>
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

        <form method="post" action="elements_LQA/mhinhanh/hinhanhAct.php?reqact=addnew" enctype="multipart/form-data"
            id="uploadForm">
            <div class="input-group">