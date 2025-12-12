<?php
// Xử lý thông báo
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Xóa thông báo sau khi hiển thị
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Lấy thông tin tìm kiếm
$searchKeyword = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$searchField = isset($_GET['field']) ? $_GET['field'] : 'all';
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, new, frequent, potential

// Lấy thống kê khách hàng
require_once __DIR__ . '/../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Thống kê khách hàng
$stats = [
    'total' => 0,
    'new' => 0,        // Đăng ký trong 30 ngày gần đây
    'frequent' => 0,   // Có >= 3 đơn hàng
    'potential' => 0,  // Khách hàng tiềm năng
    'active' => 0      // Có đơn hàng trong 30 ngày gần đây
];

try {
    // Tổng số khách hàng
    $stats['total'] = count($customers);
    
    // Khách hàng mới (đăng ký trong 30 ngày)
    $newCustomersSql = "SELECT COUNT(DISTINCT u.iduser) as count 
                        FROM user u 
                        LEFT JOIN nhanvien nv ON nv.iduser = u.iduser
                        WHERE u.username != 'admin' 
                        AND nv.idnhanvien IS NULL
                        AND u.ngaydangki >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->query($newCustomersSql);
    $stats['new'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Khách hàng thường xuyên (có >= 3 đơn hàng đã duyệt)
    $frequentSql = "SELECT COUNT(*) as count FROM (
                        SELECT dh.ma_nguoi_dung, COUNT(*) as order_count
                        FROM don_hang dh
                        WHERE dh.trang_thai = 'approved'
                        GROUP BY dh.ma_nguoi_dung
                        HAVING order_count >= 3
                    ) as frequent_customers";
    $stmt = $conn->query($frequentSql);
    $stats['frequent'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Khách hàng tiềm năng:
    // - Có 1-2 đơn hàng thành công (chưa phải thường xuyên)
    // - Tổng chi tiêu >= 500.000đ
    // - Có hoạt động trong 60 ngày gần đây
    $potentialSql = "SELECT COUNT(*) as count FROM (
                        SELECT dh.ma_nguoi_dung, 
                               COUNT(*) as order_count,
                               SUM(dh.tong_tien) as total_spent,
                               MAX(dh.ngay_tao) as last_order
                        FROM don_hang dh
                        WHERE dh.trang_thai = 'approved'
                        GROUP BY dh.ma_nguoi_dung
                        HAVING order_count BETWEEN 1 AND 2
                        AND total_spent >= 500000
                        AND last_order >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                    ) as potential_customers";
    $stmt = $conn->query($potentialSql);
    $stats['potential'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Khách hàng hoạt động (có đơn hàng trong 30 ngày)
    $activeSql = "SELECT COUNT(DISTINCT ma_nguoi_dung) as count 
                  FROM don_hang 
                  WHERE ngay_tao >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->query($activeSql);
    $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    error_log("Error getting customer stats: " . $e->getMessage());
}

// Lọc khách hàng theo loại
$filteredCustomers = $customers;
if ($filterType !== 'all') {
    $filteredCustomers = [];
    foreach ($customers as $customer) {
        $username = $customer['username'];
        
        if ($filterType === 'new') {
            // Khách hàng mới: đăng ký trong 30 ngày
            $regDate = strtotime($customer['ngaytao']);
            $thirtyDaysAgo = strtotime('-30 days');
            if ($regDate >= $thirtyDaysAgo) {
                $filteredCustomers[] = $customer;
            }
        } elseif ($filterType === 'frequent') {
            // Khách hàng thường xuyên: >= 3 đơn hàng
            $orderCountSql = "SELECT COUNT(*) as count FROM don_hang WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
            $stmt = $conn->prepare($orderCountSql);
            $stmt->execute([$username]);
            $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($orderCount >= 3) {
                $filteredCustomers[] = $customer;
            }
        } elseif ($filterType === 'potential') {
            // Khách hàng tiềm năng: 1-2 đơn, chi tiêu >= 500k, hoạt động trong 60 ngày
            $potentialSql = "SELECT COUNT(*) as order_count, 
                                    COALESCE(SUM(tong_tien), 0) as total_spent,
                                    MAX(ngay_tao) as last_order
                             FROM don_hang 
                             WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
            $stmt = $conn->prepare($potentialSql);
            $stmt->execute([$username]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $orderCount = $data['order_count'];
            $totalSpent = $data['total_spent'];
            $lastOrder = $data['last_order'];
            
            $sixtyDaysAgo = strtotime('-60 days');
            $lastOrderTime = $lastOrder ? strtotime($lastOrder) : 0;
            
            if ($orderCount >= 1 && $orderCount <= 2 && $totalSpent >= 500000 && $lastOrderTime >= $sixtyDaysAgo) {
                $filteredCustomers[] = $customer;
            }
        }
    }
}
?>

<style>
.admin-content {
    padding: 20px;
}

.admin-title {
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #3498db;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.stat-card.active {
    border-color: #3498db;
    background: #f0f8ff;
}

.stat-card .icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stat-card.total .icon { color: #3498db; }
.stat-card.new .icon { color: #27ae60; }
.stat-card.frequent .icon { color: #f39c12; }
.stat-card.potential .icon { color: #e74c3c; }
.stat-card.active .icon { color: #9b59b6; }

.stat-card h3 {
    font-size: 2rem;
    margin: 10px 0;
    color: #333;
}

.stat-card p {
    color: #666;
    margin: 0;
    font-size: 0.9rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.card {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border: none;
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0 !important;
}

.customer-row {
    cursor: pointer;
    transition: background 0.2s;
}

.customer-row:hover {
    background: #f0f8ff !important;
}

.customer-type-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 10px;
    margin-left: 5px;
}

.badge-new {
    background: #d4edda;
    color: #155724;
}

.badge-frequent {
    background: #fff3cd;
    color: #856404;
}

.badge-potential {
    background: #fadbd8;
    color: #922b21;
}

/* Modal styles */
.customer-detail-modal .modal-dialog {
    max-width: 900px;
}

.customer-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.info-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 5px;
    display: block;
}

.info-item span {
    color: #333;
    font-size: 1rem;
}

.order-history-table {
    font-size: 0.9rem;
}

.order-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-approved { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }
.status-shipping { background: #cce5ff; color: #004085; }

.customer-stats-mini {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.mini-stat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    text-align: center;
    min-width: 150px;
}

.mini-stat h4 {
    font-size: 1.5rem;
    margin: 0;
}

.mini-stat p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.85rem;
}
</style>

<div class="admin-content">
    <h3 class="admin-title"><i class="fas fa-users"></i> Quản lý khách hàng</h3>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Thống kê khách hàng -->
    <div class="stats-cards">
        <div class="stat-card total <?php echo $filterType === 'all' ? 'active' : ''; ?>" onclick="filterCustomers('all')">
            <div class="icon"><i class="fas fa-users"></i></div>
            <h3><?php echo $stats['total']; ?></h3>
            <p>Tổng khách hàng</p>
        </div>
        <div class="stat-card new <?php echo $filterType === 'new' ? 'active' : ''; ?>" onclick="filterCustomers('new')">
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            <h3><?php echo $stats['new']; ?></h3>
            <p>Khách hàng mới</p>
            <small class="text-muted">(30 ngày gần đây)</small>
        </div>
        <div class="stat-card frequent <?php echo $filterType === 'frequent' ? 'active' : ''; ?>" onclick="filterCustomers('frequent')">
            <div class="icon"><i class="fas fa-crown"></i></div>
            <h3><?php echo $stats['frequent']; ?></h3>
            <p>Khách hàng thường xuyên</p>
            <small class="text-muted">(≥ 3 đơn hàng)</small>
        </div>
        <div class="stat-card potential <?php echo $filterType === 'potential' ? 'active' : ''; ?>" onclick="filterCustomers('potential')">
            <div class="icon"><i class="fas fa-gem"></i></div>
            <h3><?php echo $stats['potential']; ?></h3>
            <p>Khách hàng tiềm năng</p>
            <small class="text-muted">(1-2 đơn, ≥500k)</small>
        </div>
        <div class="stat-card" style="cursor: default;">
            <div class="icon" style="color: #9b59b6;"><i class="fas fa-chart-line"></i></div>
            <h3><?php echo $stats['active']; ?></h3>
            <p>Đang hoạt động</p>
            <small class="text-muted">(30 ngày gần đây)</small>
        </div>
    </div>

    <!-- Thanh tìm kiếm -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" class="d-flex">
                <input type="hidden" name="req" value="khachhangView">
                <input type="hidden" name="type" value="<?php echo $filterType; ?>">
                <select name="field" class="form-select me-2" style="width: auto;">
                    <option value="all" <?php echo $searchField == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="hoten" <?php echo $searchField == 'hoten' ? 'selected' : ''; ?>>Họ tên</option>
                    <option value="dienthoai" <?php echo $searchField == 'dienthoai' ? 'selected' : ''; ?>>Điện thoại</option>
                    <option value="diachi" <?php echo $searchField == 'diachi' ? 'selected' : ''; ?>>Địa chỉ</option>
                </select>
                <input type="text" name="search" class="form-control me-2" placeholder="Nhập từ khóa tìm kiếm..." value="<?php echo $searchKeyword; ?>">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <?php if (!empty($searchKeyword) || $filterType !== 'all'): ?>
                    <a href="?req=khachhangView" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách khách hàng -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Danh sách khách hàng
                <?php if ($filterType === 'new'): ?>
                    <span class="badge bg-success ms-2">Khách hàng mới</span>
                <?php elseif ($filterType === 'frequent'): ?>
                    <span class="badge bg-warning ms-2">Khách hàng thường xuyên</span>
                <?php elseif ($filterType === 'potential'): ?>
                    <span class="badge bg-danger ms-2">Khách hàng tiềm năng</span>
                <?php endif; ?>
                <?php if (!empty($searchKeyword)): ?>
                    <small class="text-muted">(Kết quả: "<?php echo $searchKeyword; ?>")</small>
                <?php endif; ?>
                <span class="badge bg-primary ms-2"><?php echo count($filteredCustomers); ?> khách hàng</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($filteredCustomers)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Họ tên</th>
                                <th>Giới tính</th>
                                <th>Điện thoại</th>
                                <th>Ngày đăng ký</th>
                                <th>Đơn hàng</th>
                                <th>Tổng chi tiêu</th>
                                <th>Loại KH</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredCustomers as $customer): 
                                // Lấy thông tin đơn hàng
                                $orderCountSql = "SELECT COUNT(*) as count FROM don_hang WHERE ma_nguoi_dung = ?";
                                $stmt = $conn->prepare($orderCountSql);
                                $stmt->execute([$customer['username']]);
                                $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                
                                $totalSpentSql = "SELECT COALESCE(SUM(tong_tien), 0) as total FROM don_hang WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
                                $stmt = $conn->prepare($totalSpentSql);
                                $stmt->execute([$customer['username']]);
                                $totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                
                                // Lấy thêm thông tin để xác định tiềm năng
                                $lastOrderSql = "SELECT MAX(ngay_tao) as last_order FROM don_hang WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
                                $stmt = $conn->prepare($lastOrderSql);
                                $stmt->execute([$customer['username']]);
                                $lastOrderData = $stmt->fetch(PDO::FETCH_ASSOC);
                                $lastOrderTime = $lastOrderData['last_order'] ? strtotime($lastOrderData['last_order']) : 0;
                                
                                // Đếm đơn hàng approved
                                $approvedCountSql = "SELECT COUNT(*) as count FROM don_hang WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
                                $stmt = $conn->prepare($approvedCountSql);
                                $stmt->execute([$customer['username']]);
                                $approvedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                
                                // Xác định loại khách hàng
                                $isNew = strtotime($customer['ngaytao']) >= strtotime('-30 days');
                                $isFrequent = $approvedCount >= 3;
                                $sixtyDaysAgo = strtotime('-60 days');
                                $isPotential = ($approvedCount >= 1 && $approvedCount <= 2 && $totalSpent >= 500000 && $lastOrderTime >= $sixtyDaysAgo);
                            ?>
                                <tr class="customer-row" onclick="showCustomerDetail('<?php echo $customer['username']; ?>')">
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($customer['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($customer['hoten']); ?></td>
                                    <td><?php echo KhachHang::formatGender($customer['gioitinh']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['dienthoai'] ?? ''); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($customer['ngaytao'])); ?></td>
                                    <td><span class="badge bg-info"><?php echo $orderCount; ?></span></td>
                                    <td><strong class="text-success"><?php echo number_format($totalSpent, 0, ',', '.'); ?>đ</strong></td>
                                    <td>
                                        <?php if ($isFrequent): ?>
                                            <span class="customer-type-badge badge-frequent"><i class="fas fa-crown"></i> Thường xuyên</span>
                                        <?php elseif ($isPotential): ?>
                                            <span class="customer-type-badge badge-potential"><i class="fas fa-gem"></i> Tiềm năng</span>
                                        <?php elseif ($isNew): ?>
                                            <span class="customer-type-badge badge-new"><i class="fas fa-star"></i> Mới</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['setlock'] == 1): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Bị khóa</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">
                        <?php if (!empty($searchKeyword)): ?>
                            Không tìm thấy khách hàng nào với từ khóa "<?php echo $searchKeyword; ?>"
                        <?php elseif ($filterType !== 'all'): ?>
                            Không có khách hàng nào trong danh mục này
                        <?php else: ?>
                            Chưa có khách hàng nào
                        <?php endif; ?>
                    </h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Chi tiết khách hàng -->
<div class="modal fade customer-detail-modal" id="customerDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user"></i> Chi tiết khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status"></div>
                    <p>Đang tải...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterCustomers(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('type', type);
    url.searchParams.delete('search');
    window.location.href = url.toString();
}

async function showCustomerDetail(username) {
    const modal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
    modal.show();
    
    const content = document.getElementById('customerDetailContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p>Đang tải...</p></div>';
    
    try {
        const response = await fetch(`../api/customer_detail.php?username=${encodeURIComponent(username)}`, {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (result.success) {
            renderCustomerDetail(result.data);
        } else {
            content.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">Không thể tải thông tin khách hàng</div>';
    }
}

function renderCustomerDetail(data) {
    const customer = data.customer;
    const orders = data.orders;
    const stats = data.stats;
    
    // Kiểm tra đơn hàng gần nhất trong 60 ngày
    const sixtyDaysAgo = Date.now() - 60*24*60*60*1000;
    const lastOrderDate = orders.length > 0 ? new Date(orders[0].ngay_dat).getTime() : 0;
    const isPotential = stats.approved_orders >= 1 && stats.approved_orders <= 2 
                        && stats.total_spent >= 500000 
                        && lastOrderDate >= sixtyDaysAgo;
    
    let customerType = '';
    if (stats.approved_orders >= 3) {
        customerType = '<span class="customer-type-badge badge-frequent"><i class="fas fa-crown"></i> Khách hàng thường xuyên</span>';
    } else if (isPotential) {
        customerType = '<span class="customer-type-badge badge-potential"><i class="fas fa-gem"></i> Khách hàng tiềm năng</span>';
    } else if (new Date(customer.ngaytao) >= new Date(Date.now() - 30*24*60*60*1000)) {
        customerType = '<span class="customer-type-badge badge-new"><i class="fas fa-star"></i> Khách hàng mới</span>';
    }
    
    let html = `
        <div class="customer-stats-mini">
            <div class="mini-stat">
                <h4>${stats.order_count}</h4>
                <p>Đơn hàng</p>
            </div>
            <div class="mini-stat" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h4>${formatCurrency(stats.total_spent)}</h4>
                <p>Tổng chi tiêu</p>
            </div>
            <div class="mini-stat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4>${stats.approved_orders}</h4>
                <p>Đơn thành công</p>
            </div>
        </div>
        
        <h6><i class="fas fa-info-circle"></i> Thông tin cá nhân ${customerType}</h6>
        <div class="customer-info-grid">
            <div class="info-item">
                <label>Username</label>
                <span>${escapeHtml(customer.username)}</span>
            </div>
            <div class="info-item">
                <label>Họ tên</label>
                <span>${escapeHtml(customer.hoten || '-')}</span>
            </div>
            <div class="info-item">
                <label>Điện thoại</label>
                <span>${escapeHtml(customer.dienthoai || '-')}</span>
            </div>
            <div class="info-item">
                <label>Email</label>
                <span>${escapeHtml(customer.email || '-')}</span>
            </div>
            <div class="info-item">
                <label>Địa chỉ</label>
                <span>${escapeHtml(customer.diachi || '-')}</span>
            </div>
            <div class="info-item">
                <label>Ngày đăng ký</label>
                <span>${formatDate(customer.ngaytao)}</span>
            </div>
        </div>
        
        <h6 class="mt-4"><i class="fas fa-shopping-cart"></i> Lịch sử mua hàng (${orders.length} đơn gần nhất)</h6>
    `;
    
    if (orders.length > 0) {
        html += `
            <div class="table-responsive">
                <table class="table table-sm order-history-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        orders.forEach(order => {
            const statusClass = getStatusClass(order.trang_thai);
            const statusText = getStatusText(order.trang_thai);
            const paymentText = getPaymentText(order.trang_thai_thanh_toan);
            
            html += `
                <tr>
                    <td><strong>#${order.ma_don_hang_text || order.id}</strong></td>
                    <td>${formatDate(order.ngay_dat)}</td>
                    <td><strong class="text-success">${formatCurrency(order.tong_tien)}</strong></td>
                    <td><span class="order-status ${statusClass}">${statusText}</span></td>
                    <td>${paymentText}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        html += '<p class="text-muted text-center py-3">Chưa có đơn hàng nào</p>';
    }
    
    document.getElementById('customerDetailContent').innerHTML = html;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount || 0) + 'đ';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusClass(status) {
    const classes = {
        'pending': 'status-pending',
        'approved': 'status-approved',
        'cancelled': 'status-cancelled',
        'shipping': 'status-shipping'
    };
    return classes[status] || 'status-pending';
}

function getStatusText(status) {
    const texts = {
        'pending': 'Chờ duyệt',
        'approved': 'Đã duyệt',
        'cancelled': 'Đã hủy',
        'shipping': 'Đang giao'
    };
    return texts[status] || status;
}

function getPaymentText(status) {
    const texts = {
        'pending': '<span class="text-warning">Chưa TT</span>',
        'paid': '<span class="text-success">Đã TT</span>',
        'refunded': '<span class="text-danger">Hoàn tiền</span>'
    };
    return texts[status] || status;
}
</script>
