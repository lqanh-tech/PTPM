<?php
/**
 * TEST CUỐI CÙNG - Kiểm tra tất cả các sửa đổi
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

SessionManager::start();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Final Fixes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 0; min-height: 100vh; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .test-card { background: white; padding: 30px; margin-bottom: 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; background: #f8f9fa; border-radius: 8px; }
        .test-item.success { border-color: #28a745; background: #d4edda; }
        .test-item.error { border-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-color: #ffc107; background: #fff3cd; }
        .test-header { background: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .test-header h1 { color: #667eea; margin: 0; }
        .badge-custom { padding: 8px 16px; border-radius: 20px; font-weight: 600; }
        code { background: #2d2d2d; color: #f8f8f2; padding: 2px 6px; border-radius: 4px; }
        .btn-test { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .btn-test:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
    </style>
</head>
<body>
<div class="test-container">
    <div class="test-header">
        <h1><i class="fas fa-vial"></i> TEST CUỐI CÙNG</h1>
        <p class="text-muted mb-0">Kiểm tra tất cả các sửa đổi</p>
    </div>
    
    <?php
    $allPassed = true;
    $totalTests = 0;
    $passedTests = 0;
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // ============================================
        // TEST 1: Kiểm tra file notification widget
        // ============================================
        echo "<div class='test-card'>
            <h3><i class='fas fa-bell text-primary'></i> TEST 1: File Notification Widget</h3>
            <p class='text-muted'>Kiểm tra link footer và các sửa đổi</p>";
        
        $widgetFile = 'lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php';
        
        if (!file_exists($widgetFile)) {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> File không tồn tại
            </div>";
            $allPassed = false;
            $totalTests++;
        } else {
            $content = file_get_contents($widgetFile);
            
            $tests = [
                'Link footer đúng' => [
                    'check' => strpos($content, '/lequocanh/customer/order_history.php') !== false,
                    'expected' => '/lequocanh/customer/order_history.php',
                    'description' => 'Link "Xem lịch sử đơn hàng" phải đến trang customer'
                ],
                'Link hóa đơn đúng' => [
                    'check' => strpos($content, '/lequocanh/customer/order_invoice.php') !== false,
                    'expected' => '/lequocanh/customer/order_invoice.php',
                    'description' => 'Link thông báo phải đến trang hóa đơn customer'
                ],
                'Không có onclick viewNotificationDetail' => [
                    'check' => strpos($content, 'onclick="viewNotificationDetail') === false,
                    'expected' => 'Không có',
                    'description' => 'Đã bỏ onclick để không block navigation'
                ],
                'Có onclick markAsRead' => [
                    'check' => strpos($content, 'onclick="markAsRead') !== false,
                    'expected' => 'Có',
                    'description' => 'Thêm onclick markAsRead vào link'
                ],
                'URL tuyệt đối cho API' => [
                    'check' => strpos($content, '/lequocanh/administrator/elements_LQA/mthongbao/') !== false,
                    'expected' => 'URL tuyệt đối',
                    'description' => 'Tất cả API calls dùng absolute path'
                ],
                'markAsRead không reload' => [
                    'check' => !preg_match('/function markAsRead.*location\.reload/s', $content),
                    'expected' => 'Không reload',
                    'description' => 'Hàm markAsRead không reload trang'
                ]
            ];
            
            foreach ($tests as $name => $test) {
                $totalTests++;
                if ($test['check']) {
                    $passedTests++;
                    echo "<div class='test-item success'>
                        <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> $name
                        <br><small class='text-muted'>{$test['description']}</small>
                        <br><code>{$test['expected']}</code>
                    </div>";
                } else {
                    $allPassed = false;
                    echo "<div class='test-item error'>
                        <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> $name
                        <br><small class='text-muted'>{$test['description']}</small>
                        <br><span class='badge bg-danger'>Expected: {$test['expected']}</span>
                    </div>";
                }
            }
        }
        
        echo "</div>";
        
        // ============================================
        // TEST 2: Kiểm tra file orderDetailView.php
        // ============================================
        echo "<div class='test-card'>
            <h3><i class='fas fa-file-invoice text-success'></i> TEST 2: File Order Detail View</h3>
            <p class='text-muted'>Kiểm tra widget đánh giá đã được thêm</p>";
        
        $orderDetailFile = 'lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php';
        
        if (!file_exists($orderDetailFile)) {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> File không tồn tại
            </div>";
            $allPassed = false;
            $totalTests++;
        } else {
            $content = file_get_contents($orderDetailFile);
            
            $tests = [
                'Có include widget đánh giá' => [
                    'check' => strpos($content, 'product_review_widget.php') !== false,
                    'expected' => 'include product_review_widget.php',
                    'description' => 'Widget đánh giá đã được include'
                ],
                'Kiểm tra điều kiện USER' => [
                    'check' => strpos($content, "!isset(\$_SESSION['ADMIN'])") !== false,
                    'expected' => 'Chỉ hiển thị cho USER',
                    'description' => 'Widget chỉ hiển thị cho khách hàng, không phải admin'
                ],
                'Kiểm tra điều kiện approved' => [
                    'check' => strpos($content, "trang_thai' == 'approved'") !== false || strpos($content, "trang_thai_thanh_toan' == 'paid'") !== false,
                    'expected' => 'Chỉ hiển thị khi approved',
                    'description' => 'Widget chỉ hiển thị khi đơn hàng đã duyệt'
                ],
                'Có class no-print' => [
                    'check' => preg_match('/product_review_widget.*no-print/s', $content) || preg_match('/no-print.*product_review_widget/s', $content),
                    'expected' => 'Có class no-print',
                    'description' => 'Widget không in ra khi print hóa đơn'
                ]
            ];
            
            foreach ($tests as $name => $test) {
                $totalTests++;
                if ($test['check']) {
                    $passedTests++;
                    echo "<div class='test-item success'>
                        <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> $name
                        <br><small class='text-muted'>{$test['description']}</small>
                        <br><code>{$test['expected']}</code>
                    </div>";
                } else {
                    $allPassed = false;
                    echo "<div class='test-item error'>
                        <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> $name
                        <br><small class='text-muted'>{$test['description']}</small>
                        <br><span class='badge bg-danger'>Expected: {$test['expected']}</span>
                    </div>";
                }
            }
        }
        
        echo "</div>";
        
        // ============================================
        // TEST 3: Kiểm tra database
        // ============================================
        echo "<div class='test-card'>
            <h3><i class='fas fa-database text-info'></i> TEST 3: Database & Thông Báo</h3>
            <p class='text-muted'>Kiểm tra thông báo và đơn hàng trong database</p>";
        
        // Kiểm tra thông báo order_approved
        $sql = "SELECT COUNT(*) as count FROM customer_notifications WHERE type = 'order_approved'";
        $stmt = $conn->query($sql);
        $notifCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $totalTests++;
        if ($notifCount > 0) {
            $passedTests++;
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> Có thông báo order_approved
                <br><small class='text-muted'>Tìm thấy $notifCount thông báo</small>
            </div>";
            
            // Lấy thông báo mẫu
            $sql = "SELECT * FROM customer_notifications WHERE type = 'order_approved' ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->query($sql);
            $sampleNotif = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sampleNotif) {
                $hasLink = strpos($sampleNotif['message'], 'order_invoice.php') !== false;
                $totalTests++;
                if ($hasLink) {
                    $passedTests++;
                    echo "<div class='test-item success'>
                        <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> Thông báo có link hóa đơn
                        <br><small class='text-muted'>Message chứa link order_invoice.php</small>
                    </div>";
                } else {
                    $allPassed = false;
                    echo "<div class='test-item warning'>
                        <i class='fas fa-exclamation-triangle'></i> <strong>WARNING:</strong> Thông báo chưa có link hóa đơn
                        <br><small class='text-muted'>Thông báo cũ chưa có link, thông báo mới sẽ có</small>
                    </div>";
                }
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-info-circle'></i> <strong>INFO:</strong> Chưa có thông báo order_approved
                <br><small class='text-muted'>Tạo đơn hàng và admin duyệt để test</small>
            </div>";
        }
        
        // Kiểm tra đơn hàng đã duyệt
        $sql = "SELECT COUNT(*) as count FROM don_hang WHERE trang_thai = 'approved' OR trang_thai_thanh_toan = 'paid'";
        $stmt = $conn->query($sql);
        $approvedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $totalTests++;
        if ($approvedCount > 0) {
            $passedTests++;
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> Có đơn hàng đã duyệt
                <br><small class='text-muted'>Tìm thấy $approvedCount đơn hàng có thể đánh giá</small>
            </div>";
            
            // Lấy đơn hàng mẫu
            $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, trang_thai, trang_thai_thanh_toan 
                    FROM don_hang 
                    WHERE trang_thai = 'approved' OR trang_thai_thanh_toan = 'paid'
                    ORDER BY ngay_tao DESC LIMIT 1";
            $stmt = $conn->query($sql);
            $sampleOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sampleOrder) {
                echo "<div class='test-item'>
                    <strong>Đơn hàng mẫu để test:</strong>
                    <br>ID: <code>{$sampleOrder['id']}</code>
                    <br>Mã: <code>{$sampleOrder['ma_don_hang_text']}</code>
                    <br>User: <code>{$sampleOrder['ma_nguoi_dung']}</code>
                    <br>Trạng thái: <span class='badge bg-success'>{$sampleOrder['trang_thai']}</span>
                    <br>Thanh toán: <span class='badge bg-info'>{$sampleOrder['trang_thai_thanh_toan']}</span>
                    <br><br>
                    <a href='/lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id={$sampleOrder['id']}' 
                       class='btn btn-sm btn-primary' target='_blank'>
                        <i class='fas fa-eye'></i> Xem chi tiết đơn hàng
                    </a>
                    <a href='/lequocanh/customer/order_invoice.php?order_id={$sampleOrder['id']}' 
                       class='btn btn-sm btn-success' target='_blank'>
                        <i class='fas fa-file-invoice'></i> Xem hóa đơn
                    </a>
                </div>";
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-info-circle'></i> <strong>INFO:</strong> Chưa có đơn hàng đã duyệt
                <br><small class='text-muted'>Tạo đơn hàng và admin duyệt để test widget đánh giá</small>
            </div>";
        }
        
        echo "</div>";
        
        // ============================================
        // TEST 4: Kiểm tra component widget
        // ============================================
        echo "<div class='test-card'>
            <h3><i class='fas fa-puzzle-piece text-warning'></i> TEST 4: Component Widget</h3>
            <p class='text-muted'>Kiểm tra file component đánh giá</p>";
        
        $componentFile = 'lequocanh/components/product_review_widget.php';
        
        $totalTests++;
        if (file_exists($componentFile)) {
            $passedTests++;
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> Component widget tồn tại
                <br><code>$componentFile</code>
            </div>";
        } else {
            $allPassed = false;
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> Component widget không tồn tại
                <br><code>$componentFile</code>
            </div>";
        }
        
        $apiFile = 'lequocanh/api/product_reviews.php';
        
        $totalTests++;
        if (file_exists($apiFile)) {
            $passedTests++;
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> <strong>PASSED:</strong> API đánh giá tồn tại
                <br><code>$apiFile</code>
            </div>";
        } else {
            $allPassed = false;
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> <strong>FAILED:</strong> API đánh giá không tồn tại
                <br><code>$apiFile</code>
            </div>";
        }
        
        echo "</div>";
        
        // ============================================
        // TỔNG KẾT
        // ============================================
        $percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
        $statusClass = $percentage == 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');
        $statusIcon = $percentage == 100 ? 'check-circle' : ($percentage >= 80 ? 'exclamation-triangle' : 'times-circle');
        
        echo "<div class='test-card'>
            <div class='alert alert-$statusClass'>
                <h4><i class='fas fa-$statusIcon'></i> TỔNG KẾT</h4>
                <div class='row mt-3'>
                    <div class='col-md-4'>
                        <div class='text-center'>
                            <h2 class='mb-0'>$passedTests/$totalTests</h2>
                            <small class='text-muted'>Tests Passed</small>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <div class='text-center'>
                            <h2 class='mb-0'>$percentage%</h2>
                            <small class='text-muted'>Success Rate</small>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <div class='text-center'>
                            <h2 class='mb-0'>" . ($totalTests - $passedTests) . "</h2>
                            <small class='text-muted'>Failed</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class='mt-4'><i class='fas fa-clipboard-check'></i> Hướng dẫn test thủ công:</h5>
            <ol>
                <li><strong>Clear browser cache:</strong> Ctrl + Shift + Delete hoặc Ctrl + F5</li>
                <li><strong>Test link footer:</strong> Click chuông → Click 'Xem lịch sử đơn hàng' → Phải đến /customer/order_history.php</li>
                <li><strong>Test widget trong thông báo:</strong> Click 'Xem hóa đơn & Đánh giá' → Phải thấy widget ở cuối trang</li>
                <li><strong>Test widget trong chi tiết:</strong> Vào orderDetailView.php → Phải thấy widget ở cuối trang</li>
                <li><strong>Test đánh giá:</strong> Chọn sao → Viết nhận xét → Gửi → Phải thành công</li>
            </ol>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='test-card'>
            <div class='alert alert-danger'>
                <h5><i class='fas fa-exclamation-triangle'></i> Lỗi nghiêm trọng</h5>
                <p>{$e->getMessage()}</p>
                <pre>" . $e->getTraceAsString() . "</pre>
            </div>
        </div>";
    }
    ?>
    
    <div class="text-center mt-4">
        <a href="/clear_browser_cache.html" class="btn btn-test btn-lg">
            <i class="fas fa-sync-alt"></i> Clear Cache & Reload
        </a>
        <a href="/lequocanh/index.php" class="btn btn-primary btn-lg">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <a href="/lequocanh/customer/order_history.php" class="btn btn-success btn-lg">
            <i class="fas fa-history"></i> Lịch sử đơn hàng
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
