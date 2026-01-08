<?php

session_start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập!</div>';
    exit;
}

require_once '../mod/nhatKyHoatDongCls.php';

$activityId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activityId <= 0) {
    echo '<div class="alert alert-danger">ID nhật ký không hợp lệ!</div>';
    exit;
}

try {

    $nhatKyObj = new NhatKyHoatDong();

    $activity = $nhatKyObj->getActivityById($activityId);

    if (!$activity) {
        echo '<div class="alert alert-warning">Không tìm thấy thông tin nhật ký!</div>';
        exit;
    }

?>
    <div class="activity-detail">
        <table class="detail-table">
            <tr>
                <th><i class="fas fa-hashtag"></i> ID Nhật ký</th>
                <td><?php echo $activity['id']; ?></td>
            </tr>
            <tr>
                <th><i class="fas fa-user"></i> Người dùng</th>
                <td>
                    <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                    <?php if (!empty($activity['ten_nhan_vien'])): ?>
                        <br><small class="text-muted">Tên: <?php echo htmlspecialchars($activity['ten_nhan_vien']); ?></small>
                    <?php elseif (!empty($activity['tenNhanVien_join'])): ?>
                        <br><small class="text-muted">Tên: <?php echo htmlspecialchars($activity['tenNhanVien_join']); ?></small>
                    <?php elseif (!empty($activity['hoten'])): ?>
                        <br><small class="text-muted">Tên: <?php echo htmlspecialchars($activity['hoten']); ?></small>
                    <?php elseif (!empty($activity['hoten_join'])): ?>
                        <br><small class="text-muted">Tên: <?php echo htmlspecialchars($activity['hoten_join']); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-cogs"></i> Hành động</th>
                <td>
                    <span class="badge badge-action"><?php echo htmlspecialchars($activity['hanh_dong']); ?></span>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-cube"></i> Đối tượng</th>
                <td>
                    <span class="badge badge-object"><?php echo htmlspecialchars($activity['doi_tuong']); ?></span>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-key"></i> ID Đối tượng</th>
                <td><?php echo $activity['doi_tuong_id'] ? htmlspecialchars($activity['doi_tuong_id']) : '<em>Không có</em>'; ?></td>
            </tr>
            <tr>
                <th><i class="fas fa-info-circle"></i> Chi tiết</th>
                <td>
                    <?php
                    $chiTiet = '';
                    if (!empty($activity['chi_tiet'])) {
                        $chiTiet = $activity['chi_tiet'];
                    } elseif (!empty($activity['noi_dung'])) {
                        $chiTiet = $activity['noi_dung'];
                    }

                    if (!empty($chiTiet)): ?>
                        <div class="detail-content"><?php echo nl2br(htmlspecialchars($chiTiet)); ?></div>
                    <?php else: ?>
                        <em>Không có thông tin chi tiết</em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-layer-group"></i> Module</th>
                <td>
                    <span class="badge badge-module"><?php echo htmlspecialchars($activity['mo_dun']); ?></span>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-globe"></i> Địa chỉ IP</th>
                <td>
                    <code><?php echo htmlspecialchars($activity['ip_address']); ?></code>
                </td>
            </tr>
            <tr>
                <th><i class="fas fa-clock"></i> Thời gian</th>
                <td>
                    <strong><?php echo date('d/m/Y H:i:s', strtotime($activity['thoi_gian'])); ?></strong>
                    <br><small class="text-muted">
                        <?php
                        $timeAgo = time() - strtotime($activity['thoi_gian']);
                        if ($timeAgo < 60) {
                            echo $timeAgo . ' giây trước';
                        } elseif ($timeAgo < 3600) {
                            echo floor($timeAgo / 60) . ' phút trước';
                        } elseif ($timeAgo < 86400) {
                            echo floor($timeAgo / 3600) . ' giờ trước';
                        } else {
                            echo floor($timeAgo / 86400) . ' ngày trước';
                        }
                        ?>
                    </small>
                </td>
            </tr>
        </table>

        <!-- Thông tin bổ sung -->
        <div class="additional-info">
            <h4><i class="fas fa-chart-line"></i> Thông tin bổ sung</h4>
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="info-content">
                        <h5>Ngày trong tuần</h5>
                        <p><?php echo date('l', strtotime($activity['thoi_gian'])); ?></p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h5>Giờ trong ngày</h5>
                        <p><?php echo date('H:i', strtotime($activity['thoi_gian'])); ?></p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="info-content">
                        <h5>Phiên làm việc</h5>
                        <p><?php
                            $hour = date('H', strtotime($activity['thoi_gian']));
                            if ($hour >= 6 && $hour < 12) echo 'Buổi sáng';
                            elseif ($hour >= 12 && $hour < 18) echo 'Buổi chiều';
                            elseif ($hour >= 18 && $hour < 22) echo 'Buổi tối';
                            else echo 'Buổi đêm';
                            ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-action {
            background-color: #007bff;
            color: white;
        }

        .badge-object {
            background-color: #28a745;
            color: white;
        }

        .badge-module {
            background-color: #6f42c1;
            color: white;
        }

        .detail-content {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }

        .additional-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }

        .additional-info h4 {
            color: #495057;
            margin-bottom: 15px;
        }

        .info-cards {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .info-card {
            flex: 1;
            min-width: 150px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            color: #007bff;
            font-size: 20px;
        }

        .info-content h5 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #6c757d;
        }

        .info-content p {
            margin: 0;
            font-weight: 600;
            color: #495057;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .text-muted {
            color: #6c757d !important;
        }

        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>

<?php

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Lỗi khi tải thông tin: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>