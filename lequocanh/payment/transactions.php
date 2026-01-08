<?php

require_once 'MoMoPayment.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$statusFilter = $_GET['status'] ?? '';

try {
    require_once '../administrator/elements_LQA/mPDO.php';
    $pdo = new mPDO();
    
    $countSql = "SELECT COUNT(*) as total FROM momo_transactions";
    $countParams = [];
    
    if ($statusFilter) {
        $countSql .= " WHERE status = ?";
        $countParams[] = $statusFilter;
    }
    
    $totalResult = $pdo->executeS($countSql, $countParams);
    $total = $totalResult['total'] ?? 0;
    $totalPages = ceil($total / $limit);
    
    $sql = "SELECT * FROM momo_transactions";
    $params = [];
    
    if ($statusFilter) {
        $sql .= " WHERE status = ?";
        $params[] = $statusFilter;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $transactions = $pdo->executeS($sql, $params, true) ?: [];
    
} catch (Exception $e) {
    $error = 'Lỗi kết nối database: ' . $e->getMessage();
    $transactions = [];
    $total = 0;
    $totalPages = 0;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Giao Dịch MoMo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .transactions-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }
        .table-responsive {
            border-radius: 10px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .momo-header {
            background: linear-gradient(135deg, #a50064, #d82d8b);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="transactions-card">
        <div class="momo-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-history me-2"></i>
                        Lịch Sử Giao Dịch MoMo
                    </h2>
                    <p class="mb-0">Tổng cộng: <?= number_format($total) ?> giao dịch</p>
                </div>
                <a href="demo.php" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i>Thanh toán mới
                </a>
            </div>
        </div>
        
        <div class="p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Bộ lọc -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Đang xử lý</option>
                            <option value="SUCCESS" <?= $statusFilter === 'SUCCESS' ? 'selected' : '' ?>>Thành công</option>
                            <option value="FAILED" <?= $statusFilter === 'FAILED' ? 'selected' : '' ?>>Thất bại</option>
                            <option value="CANCELLED" <?= $statusFilter === 'CANCELLED' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <?php if ($statusFilter): ?>
                            <a href="transactions.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có giao dịch nào</h5>
                    <p class="text-muted">Hãy thực hiện thanh toán đầu tiên của bạn!</p>
                    <a href="demo.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thanh toán ngay
                    </a>
                </div>
            <?php else: ?>
                <!-- Bảng giao dịch -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Thông tin</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($transaction['order_id']) ?></strong>
                                        <?php if ($transaction['trans_id']): ?>
                                            <br><small class="text-muted">MoMo: <?= htmlspecialchars($transaction['trans_id']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($transaction['order_info']) ?>
                                        <?php if ($transaction['message']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($transaction['message']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= number_format($transaction['amount']) ?> VND</strong>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'PENDING' => 'bg-warning',
                                            'SUCCESS' => 'bg-success',
                                            'FAILED' => 'bg-danger',
                                            'CANCELLED' => 'bg-secondary'
                                        ];
                                        $statusText = [
                                            'PENDING' => 'Đang xử lý',
                                            'SUCCESS' => 'Thành công',
                                            'FAILED' => 'Thất bại',
                                            'CANCELLED' => 'Đã hủy'
                                        ];
                                        ?>
                                        <span class="badge status-badge <?= $statusClass[$transaction['status']] ?? 'bg-secondary' ?>">
                                            <?= $statusText[$transaction['status']] ?? $transaction['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($transaction['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('H:i:s', strtotime($transaction['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="showTransactionDetail('<?= htmlspecialchars(json_encode($transaction)) ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Phân trang -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Phân trang giao dịch">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal chi tiết giao dịch -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi Tiết Giao Dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetail">
                <!-- Nội dung sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showTransactionDetail(transactionJson) {
    const transaction = JSON.parse(transactionJson);
    
    const statusClass = {
        'PENDING': 'warning',
        'SUCCESS': 'success',
        'FAILED': 'danger',
        'CANCELLED': 'secondary'
    };
    
    const statusText = {
        'PENDING': 'Đang xử lý',
        'SUCCESS': 'Thành công',
        'FAILED': 'Thất bại',
        'CANCELLED': 'Đã hủy'
    };
    
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thông tin cơ bản</h6>
                <table class="table table-sm">
                    <tr><td><strong>Mã đơn hàng:</strong></td><td>${transaction.order_id}</td></tr>
                    <tr><td><strong>Request ID:</strong></td><td>${transaction.request_id}</td></tr>
                    <tr><td><strong>Số tiền:</strong></td><td><strong>${parseInt(transaction.amount).toLocaleString()} VND</strong></td></tr>
                    <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-${statusClass[transaction.status] || 'secondary'}">${statusText[transaction.status] || transaction.status}</span></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Thông tin MoMo</h6>
                <table class="table table-sm">
                    <tr><td><strong>Trans ID:</strong></td><td>${transaction.trans_id || 'N/A'}</td></tr>
                    <tr><td><strong>Thông báo:</strong></td><td>${transaction.message || 'N/A'}</td></tr>
                    <tr><td><strong>Tạo lúc:</strong></td><td>${new Date(transaction.created_at).toLocaleString('vi-VN')}</td></tr>
                    <tr><td><strong>Cập nhật:</strong></td><td>${transaction.updated_at ? new Date(transaction.updated_at).toLocaleString('vi-VN') : 'N/A'}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Thông tin đơn hàng</h6>
                <p class="border p-3 rounded bg-light">${transaction.order_info}</p>
            </div>
        </div>
    `;
    
    document.getElementById('transactionDetail').innerHTML = html;
    new bootstrap.Modal(document.getElementById('transactionModal')).show();
}
</script>

</body>
</html>
