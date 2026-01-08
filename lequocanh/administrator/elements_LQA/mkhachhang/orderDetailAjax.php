<?php

require_once '../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo '<div class="alert alert-danger">ID đơn hàng không hợp lệ.</div>';
    exit;
}

try {

    $orderSql = "SELECT dh.*, u.hoten, u.username, u.dienthoai, u.diachi
                 FROM don_hang dh
                 LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci
                 WHERE dh.id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$order_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo '<div class="alert alert-danger">Không tìm thấy đơn hàng.</div>';
        exit;
    }

    $itemsSql = "SELECT ctdh.*, hh.tenhanghoa, hh.hinhanh, hh.giathamkhao
                 FROM chi_tiet_don_hang ctdh
                 LEFT JOIN hanghoa hh ON ctdh.ma_san_pham = hh.idhanghoa
                 WHERE ctdh.ma_don_hang = ?
                 ORDER BY ctdh.id";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    function formatStatus($status)
    {
        switch ($status) {
            case 'pending':
                return '<span class="badge bg-warning">Chờ xử lý</span>';
            case 'approved':
                return '<span class="badge bg-info">Đang giao hàng</span>';
            case 'delivered':
                return '<span class="badge bg-primary">Đã giao hàng</span>';
            case 'completed':
                return '<span class="badge bg-success">Hoàn tất</span>';
            case 'cancelled':
                return '<span class="badge bg-danger">Đã hủy</span>';
            default:
                return '<span class="badge bg-secondary">Không xác định</span>';
        }
    }

    function formatPaymentStatus($status)
    {
        switch ($status) {
            case 'pending':
                return '<span class="badge bg-warning">Chờ thanh toán</span>';
            case 'paid':
                return '<span class="badge bg-success">Đã thanh toán</span>';
            case 'failed':
                return '<span class="badge bg-danger">Thanh toán thất bại</span>';
            default:
                return '<span class="badge bg-secondary">Không xác định</span>';
        }
    }

    function formatPaymentMethod($method)
    {
        switch ($method) {
            case 'bank_transfer':
                return 'Chuyển khoản ngân hàng';
            case 'cash':
                return 'Tiền mặt';
            case 'credit_card':
                return 'Thẻ tín dụng';
            default:
                return 'Không xác định';
        }
    }

?>
    <div class="container-fluid">
        <!-- Header thông tin đơn hàng -->
        <div class="order-detail-card mb-4">
            <div class="order-detail-header">
                <h5 class="mb-0">
                    <i class="fas fa-receipt me-2"></i>
                    ĐƠN HÀNG: <?php echo htmlspecialchars($order['ma_don_hang_text']); ?>
                </h5>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin đơn hàng -->
            <div class="col-md-6">
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Thông tin đơn hàng</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Mã đơn hàng:</strong></td>
                                <td><span class="badge bg-info fs-6"><?php echo htmlspecialchars($order['ma_don_hang_text']); ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Ngày tạo:</strong></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($order['ngay_tao'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Trạng thái:</strong></td>
                                <td><?php echo formatStatus($order['trang_thai']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Thanh toán:</strong></td>
                                <td>
                                    <?php echo formatPaymentStatus($order['trang_thai_thanh_toan']); ?><br>
                                    <small><?php echo formatPaymentMethod($order['phuong_thuc_thanh_toan']); ?></small>
                                </td>
                            </tr>
                            <?php if (!empty($order['dia_chi_giao_hang'])): ?>
                                <tr>
                                    <td><strong>Địa chỉ giao hàng:</strong></td>
                                    <td><?php echo htmlspecialchars($order['dia_chi_giao_hang']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($order['shipping_method_name']) && !empty($order['shipping_method_name'])): ?>
                                <tr>
                                    <td><strong>Phương thức vận chuyển:</strong></td>
                                    <td><?php echo htmlspecialchars($order['shipping_method_name']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($order['estimated_delivery']) && !empty($order['estimated_delivery'])): ?>
                                <tr>
                                    <td><strong>Dự kiến giao hàng:</strong></td>
                                    <td><span class="text-success"><?php echo htmlspecialchars($order['estimated_delivery']); ?></span></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <!-- Thông tin khách hàng -->
                    <div class="col-md-6">
                        <h6>👤 Thông tin khách hàng</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Họ tên:</strong></td>
                                <td><?php echo htmlspecialchars($order['hoten'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td><code><?php echo htmlspecialchars($order['username'] ?? $order['ma_nguoi_dung']); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Điện thoại:</strong></td>
                                <td><?php echo htmlspecialchars($order['dienthoai'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Ngày đăng ký:</strong></td>
                                <td><?php echo htmlspecialchars($order['ngaydangki'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Địa chỉ:</strong></td>
                                <td><?php echo htmlspecialchars($order['diachi'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Chi tiết sản phẩm -->
                <div class="card border-warning mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Chi tiết sản phẩm</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Không có sản phẩm nào trong đơn hàng này.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tên sản phẩm</th>
                                            <th style="width: 100px;" class="text-center">Số lượng</th>
                                            <th style="width: 120px;" class="text-end">Đơn giá</th>
                                            <th style="width: 120px;" class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $tongTien = 0;
                                        foreach ($items as $item):
                                            $thanhTien = $item['so_luong'] * $item['gia'];
                                            $tongTien += $thanhTien;
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['tenhanghoa'] ?? 'Sản phẩm không tồn tại'); ?></strong>
                                                    <br><small class="text-muted">ID: <?php echo $item['ma_san_pham']; ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $item['so_luong']; ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <?php echo number_format($item['gia'], 0, ',', '.'); ?> đ
                                                </td>
                                                <td class="text-end">
                                                    <strong><?php echo number_format($thanhTien, 0, ',', '.'); ?> đ</strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end">Tạm tính:</td>
                                            <td class="text-end">
                                                <?php echo number_format($tongTien, 0, ',', '.'); ?> đ
                                            </td>
                                        </tr>
                                        <?php if (isset($order['thue']) && $order['thue'] > 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-end">Thuế VAT (10%):</td>
                                            <td class="text-end">
                                                <?php echo number_format($order['thue'], 0, ',', '.'); ?> đ
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (isset($order['phi_van_chuyen']) && $order['phi_van_chuyen'] > 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-end">Phí vận chuyển:</td>
                                            <td class="text-end">
                                                <?php echo number_format($order['phi_van_chuyen'], 0, ',', '.'); ?> đ
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tfoot>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                            <td class="text-end">
                                                <strong class="text-warning"><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Thông tin bổ sung -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <small>
                                        <strong>📝 Ghi chú:</strong>
                                        Đơn hàng được tạo lúc <?php echo date('d/m/Y H:i:s', strtotime($order['ngay_tao'])); ?>
                                        <?php if ($order['ngay_cap_nhat'] != $order['ngay_tao']): ?>
                                            và được cập nhật lần cuối lúc <?php echo date('d/m/Y H:i:s', strtotime($order['ngay_cap_nhat'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                    <?php

                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Lỗi khi tải chi tiết đơn hàng: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                    ?>