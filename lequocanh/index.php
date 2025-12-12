<?php
// Load local development configuration for faster loading
require_once __DIR__ . '/config/local_config.php';

// Bootstrap is already loaded by the parent index.php - skip loading it again
// require_once __DIR__ . '/../bootstrap.php'; // REMOVED - prevents double loading

// Load essential classes that were provided by bootstrap
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/config/logger_config.php';

// Start session safely
SessionManager::start();

// Xóa session pending_order nếu user quay lại từ trang thanh toán thành công
if (isset($_GET['clear_session']) && $_GET['clear_session'] == '1') {
    unset($_SESSION['pending_order']);
    // Redirect để xóa parameter khỏi URL
    header('Location: index.php');
    exit();
}

// Kiểm tra nếu người dùng vừa quay về từ trang thanh toán
$showPaymentSuccess = false;
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1') {
    $showPaymentSuccess = true;
    // Log để debug
    error_log('User returned from successful payment - Session USER: ' . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'Not set'));
}

require_once __DIR__ . '/administrator/elements_LQA/mod/giohangCls.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';

$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();

// Kiểm tra xem người dùng có phải là nhân viên không
$isNhanVien = false;
if (isset($_SESSION['USER'])) {
    $username = $_SESSION['USER'];
    $db = Database::getInstance()->getConnection();

    // Kiểm tra user id
    $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if ($user) {
        // Kiểm tra xem có trong bảng nhân viên không
        $stmt = $db->prepare("SELECT COUNT(*) FROM nhanvien WHERE iduser = ?");
        $stmt->execute([$user->iduser]);
        $isNhanVien = $stmt->fetchColumn() > 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/lequocanh/">

    <!-- Preconnect to external domains for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://code.jquery.com">

    <!-- Optimize CSS loading -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- Local CSS files - Critical first -->
    <link rel="stylesheet" href="public_files/critical.css">
    <link rel="stylesheet" href="public_files/mycss.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="public_files/notification.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="public_files/product_filter.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="public_files/product_reviews.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="public_files/wishlist.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="public_files/mycss.css">
        <link rel="stylesheet" href="public_files/notification.css">
        <link rel="stylesheet" href="public_files/product_filter.css">
        <link rel="stylesheet" href="public_files/product_reviews.css">
        <link rel="stylesheet" href="public_files/wishlist.css">
    </noscript>

    <title>Cửa Hàng Điện Thoại</title>

    <!-- Minimal inline critical CSS -->
    <style>
        /* Critical above-the-fold styles */
        .navbar {
            z-index: 1030 !important;
        }

        .navbar.bg-dark {
            z-index: 1020 !important;
            position: relative;
        }

        .dropdown-menu {
            z-index: 1080 !important;
            position: absolute !important;
            background-color: white !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
        }

        .navbar-nav .dropdown .dropdown-menu {
            position: absolute !important;
            z-index: 1080 !important;
            top: 100% !important;
            left: auto !important;
            right: 0 !important;
            transform: none !important;
        }

        #userDropdown+.dropdown-menu {
            z-index: 1090 !important;
        }

        /* Loading indicator */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #28a745);
            z-index: 9999;
            transform: translateX(-100%);
            animation: loading 2s infinite;
        }

        @keyframes loading {
            0% {
                transform: translateX(-100%);
            }

            50% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Carousel custom styles */
        .news-carousel-caption {
            background: rgba(0, 0, 0, 0.7);
            border-radius: 8px;
            padding: 15px;
            margin: 20px;
        }

        .news-carousel-caption h5 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .news-carousel-caption p {
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .news-carousel-caption small {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        /* Support Button Animation */
        .pulse-animation {
            animation: pulse 2s infinite;
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            font-weight: 600;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }
        
        .pulse-animation:hover {
            animation: none;
            transform: scale(1.05);
            transition: transform 0.2s;
        }
        
        /* Footer link hover */
        .hover-white:hover {
            color: #fff !important;
            transition: color 0.2s;
        }
        
        /* Blog Section on Homepage */
        .blog-section {
            background: #f8f9fa;
            padding: 40px 0;
            margin-top: 30px;
        }
        .blog-section h3 {
            color: #333;
            margin-bottom: 25px;
        }
        .blog-card-home {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
        }
        .blog-card-home:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .blog-card-home img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .blog-card-home .card-body {
            padding: 15px;
        }
        .blog-card-home .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .blog-card-home .card-title a {
            color: #333;
            text-decoration: none;
        }
        .blog-card-home .card-title a:hover {
            color: #0d6efd;
        }
        .blog-card-home .card-text {
            font-size: 0.85rem;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

</head>

<body class="bg-light">
    <!-- Loading indicator -->
    <div class="page-loader" id="pageLoader"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-mobile-alt me-2"></i>
                Cửa Hàng Điện Thoại
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="search-container mx-auto">
                    <form class="d-flex" action="./search.php" method="GET" id="searchForm">
                        <input class="form-control me-2" type="search" placeholder="Tìm kiếm sản phẩm..."
                            aria-label="Search" name="query" id="searchInput">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <div id="searchResults"></div>
                </div>

                <div class="ms-auto d-flex align-items-center">
                    <?php if (isset($_SESSION['USER'])): ?>
                        <div class="dropdown me-2">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <?php
                                // Lấy tên người dùng từ database
                                $username = $_SESSION['USER'];
                                $db = Database::getInstance()->getConnection();
                                $stmt = $db->prepare("SELECT hoten FROM user WHERE username = ?");
                                $stmt->execute([$username]);
                                $user = $stmt->fetch(PDO::FETCH_OBJ);
                                echo $user ? $user->hoten : $username;
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="./administrator/elements_LQA/mUser/userProfile.php">
                                        <i class="fas fa-user-circle me-2"></i>Thông tin tài khoản
                                    </a></li>
                                <?php if ($isNhanVien): ?>
                                    <li><a class="dropdown-item" href="./administrator/index.php">
                                            <i class="fas fa-user-cog me-2"></i>Đến trang quản trị
                                        </a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item"
                                        href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </div>
                    <?php elseif (isset($_SESSION['ADMIN'])): ?>
                        <div class="dropdown me-2">
                            <button class="btn btn-light dropdown-toggle" type="button" id="adminDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-shield me-2"></i>
                                Quản trị viên
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="./administrator/index.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Bảng điều khiển
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item"
                                        href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="./administrator/userLogin.php" class="btn btn-light me-2">
                            <i class="fas fa-user me-2"></i>
                            Đăng nhập
                        </a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['USER'])): ?>
                        <!-- Nút Yêu thích với Dropdown -->
                        <div class="dropdown me-2">
                            <button class="btn btn-light position-relative" type="button" id="wishlistDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-heart text-danger"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-badge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="wishlistDropdownBtn" style="width: 320px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
                                <div style="background: linear-gradient(135deg, #fff5f5 0%, #fff 100%); padding: 12px 15px; border-bottom: 1px solid #eee;">
                                    <h6 class="mb-0" style="font-weight: 600; color: #333;"><i class="fas fa-heart text-danger me-2"></i>Sản phẩm yêu thích</h6>
                                </div>
                                <div id="wishlistDropdownContent" style="max-height: 280px; overflow-y: auto; padding: 10px; background: white;">
                                    <div class="text-center py-3 text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                    </div>
                                </div>
                                <div style="padding: 10px 15px; border-top: 1px solid #eee; text-align: center; background: #f8f9fa;">
                                    <a href="#wishlistSection" onclick="scrollToWishlistSection()" style="color: #e74c3c; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Xem tất cả</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Nút Hỗ Trợ/Khiếu Nại - NỔI BẬT -->
                        <a href="./customer/support.php" class="btn btn-warning me-2 pulse-animation" title="Liên hệ hỗ trợ">
                            <i class="fas fa-headset me-1"></i>
                            <span class="d-none d-lg-inline">Hỗ trợ</span>
                        </a>
                        
                        <!-- Icon thông báo đơn hàng với dropdown -->
                        <div class="position-relative me-2">
                            <button class="btn btn-light position-relative notification-btn" onclick="loadNotifications()">
                                <i class="fas fa-bell"></i>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                                    style="display: none;">
                                    0
                                </span>
                            </button>

                            <!-- Dropdown thông báo -->
                            <div class="notification-dropdown">
                                <div class="notification-header">
                                    <h6>Thông báo</h6>
                                    <div class="notification-actions-header">
                                        <button class="mark-all-read">Đánh dấu tất cả đã đọc</button>
                                        <button class="delete-read-notifications">Xóa thông báo đã đọc</button>
                                    </div>
                                    <ul class="notification-list">
                                        <!-- Nội dung thông báo sẽ được thêm bằng JavaScript -->
                                        <li class="notification-empty">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <p>Đang tải thông báo...</p>
                                        </li>
                                    </ul>
                                    <div class="notification-footer">
                                        <a href="./customer/order_history.php">Lịch sử mua hàng</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Icon giỏ hàng -->
                        <a href="./administrator/elements_LQA/mgiohang/giohangView.php"
                            class="btn btn-light position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartItemCount; ?>
                            </span>
                        </a>
                    <?php endif; ?>

                    <!-- Modal chi tiết đơn hàng -->
                    <div class="order-detail-modal">
                        <div class="order-detail-content">
                            <span class="order-detail-close">&times;</span>
                            <div class="order-detail-header">
                                <h3>Chi tiết đơn hàng #<span id="order-id"></span></h3>
                                <span class="order-status" id="order-status"></span>
                            </div>
                            <div class="order-detail-info">
                                <div class="order-detail-info-col">
                                    <div class="order-detail-info-item">
                                        <strong>Mã đơn hàng</strong>
                                        <div id="order-code"></div>
                                    </div>
                                    <div class="order-detail-info-item">
                                        <strong>Ngày đặt</strong>
                                        <div id="order-date"></div>
                                    </div>
                                    <div class="order-detail-info-item">
                                        <strong>Phương thức thanh toán</strong>
                                        <div id="order-payment-method"></div>
                                    </div>
                                </div>
                                <div class="order-detail-info-col">
                                    <div class="order-detail-info-item">
                                        <strong>Phương thức vận chuyển</strong>
                                        <div id="order-shipping-method"></div>
                                    </div>
                                    <div class="order-detail-info-item" id="estimated-delivery-row">
                                        <strong>Thời gian giao hàng dự kiến</strong>
                                        <div id="order-estimated-delivery"></div>
                                    </div>
                                    <div class="order-detail-info-item">
                                        <strong>Địa chỉ giao hàng</strong>
                                        <div id="order-address" class="order-detail-address"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-detail-items">
                                <h4>Sản phẩm</h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="70">Hình ảnh</th>
                                            <th>Sản phẩm</th>
                                            <th width="110">Đơn giá</th>
                                            <th width="80">Số lượng</th>
                                            <th width="120">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody id="order-items">
                                        <!-- Danh sách sản phẩm sẽ được thêm bằng JavaScript -->
                                    </tbody>
                                </table>
                                
                                <!-- Chi tiết thanh toán -->
                                <div class="order-payment-details">
                                    <div class="payment-row">
                                        <span>Tạm tính:</span>
                                        <span id="order-subtotal"></span>
                                    </div>
                                    <div class="payment-row">
                                        <span>Thuế VAT (10%):</span>
                                        <span id="order-tax"></span>
                                    </div>
                                    <div class="payment-row">
                                        <span>Phí vận chuyển:</span>
                                        <span id="order-shipping"></span>
                                    </div>
                                    <div class="payment-row payment-status-row">
                                        <span>Trạng thái thanh toán:</span>
                                        <span id="order-payment-status" class="payment-status-badge"></span>
                                    </div>
                                    <div class="order-detail-total">
                                        Tổng cộng: <span id="order-total"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Category Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <?php require __DIR__ . '/apart/menuLoaihang.php'; ?>
        </div>
    </nav>

    <!-- Promotional Offers Section (Discounted Products) -->
    <?php
    require_once __DIR__ . '/administrator/elements_LQA/mod/PromotionManager.php';
    $promotionManager = new PromotionManager();
    $discountedProducts = $promotionManager->getDiscountedProducts();
    if (!empty($discountedProducts)):
    ?>
        <div class="container mt-4">
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-3">Sản phẩm giảm giá</h3>
                    <div class="row">
                        <?php foreach ($discountedProducts as $product):
                            // Calculate discount percentage
                            $discountPercent = round((($product['giathamkhao'] - $product['giakhuyenmai']) / $product['giathamkhao']) * 100);
                        ?>
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card product-card h-100">
                                    <?php if ($product['hinhanh_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['hinhanh_url']); ?>" class="card-img-top"
                                            alt="<?php echo htmlspecialchars($product['tenhanghoa']); ?>"
                                            style="height: 200px; object-fit: contain; padding: 10px;">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/300x200/cccccc/666?text=No+Image" class="card-img-top"
                                            alt="No Image" style="height: 200px; object-fit: contain; padding: 10px;">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['tenhanghoa']); ?></h5>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span
                                                        class="text-danger fw-bold"><?php echo number_format($product['giakhuyenmai'], 0, ',', '.'); ?>
                                                        ₫</span>
                                                    <br>
                                                    <small
                                                        class="text-muted text-decoration-line-through"><?php echo number_format($product['giathamkhao'], 0, ',', '.'); ?>
                                                        ₫</small>
                                                </div>
                                                <span class="badge bg-danger"><?php echo $discountPercent; ?>% GIẢM</span>
                                            </div>
                                        </div>
                                    </div> <!-- Close card-body div -->
                                    <div class="card-footer">
                                        <a href="./administrator/elements_LQA/mgiohang/giohangView.php?reqHanghoa=<?php echo $product['idhanghoa']; ?>"
                                            class="btn btn-primary btn-sm w-100">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment Success Alert -->
        <?php if ($showPaymentSuccess): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Thanh toán thành công!</strong> Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đang được xử lý.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Wishlist Section (chỉ hiển thị khi đăng nhập) -->
        <?php if (isset($_SESSION['USER'])): ?>
            <div class="container mt-4">
                <div class="wishlist-section" id="wishlistSection" style="display: none;">
                    <!-- Nội dung sẽ được load bằng JavaScript -->
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="container py-4">
            <div class="row">
                <div class="col-12">
                    <?php
                    if (!isset($_GET['reqHanghoa'])) {
                        require __DIR__ . '/apart/viewListLoaihang.php';
                    } else {
                        require __DIR__ . '/apart/viewHangHoa.php';
                    }
                    ?>
                </div>
            </div>
        </main>

        <!-- Blog Section -->
        <?php
        require_once __DIR__ . '/administrator/elements_LQA/mod/PageManager.php';
        $pageManagerHome = new PageManager();
        $latestBlogs = $pageManagerHome->getAllBlogs(true);
        $latestBlogs = array_slice($latestBlogs, 0, 4); // Lấy 4 bài mới nhất
        
        if (!empty($latestBlogs)):
        ?>
        <section class="blog-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-blog me-2 text-primary"></i>Bài viết mới nhất</h3>
                    <a href="blog.php" class="btn btn-outline-primary btn-sm">Xem tất cả <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="row g-4">
                    <?php foreach ($latestBlogs as $blog): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="blog-card-home">
                            <?php if ($blog['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($blog['thumbnail']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                            <?php else: ?>
                            <div style="width:100%;height:150px;background:#e9ecef;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-image fa-2x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="page.php?slug=<?php echo htmlspecialchars($blog['slug']); ?>">
                                        <?php echo htmlspecialchars($blog['title']); ?>
                                    </a>
                                </h5>
                                <?php if ($blog['excerpt']): ?>
                                <p class="card-text"><?php echo htmlspecialchars($blog['excerpt']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="footer mt-auto py-5">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-3">Về chúng tôi</h5>
                        <p class="small text-muted">
                            Cửa hàng điện thoại uy tín hàng đầu Việt Nam. Chuyên cung cấp các sản phẩm chính hãng với
                            chất
                            lượng tốt nhất và dịch vụ chăm sóc khách hàng 24/7.
                        </p>
                        <div class="social-icons mt-4">
                            <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" title="Youtube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-3">Thông tin hữu ích</h5>
                        <ul class="list-unstyled text-muted">
                            <li class="mb-2"><a href="page.php?slug=gioi-thieu" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-info-circle me-1"></i>Giới thiệu</a>
                            </li>
                            <li class="mb-2"><a href="page.php?slug=huong-dan-mua-hang" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-shopping-bag me-1"></i>Hướng dẫn mua hàng</a>
                            </li>
                            <li class="mb-2"><a href="page.php?slug=chinh-sach-bao-hanh" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-shield-alt me-1"></i>Chính sách bảo hành</a>
                            </li>
                            <li class="mb-2"><a href="page.php?slug=chinh-sach-doi-tra" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-exchange-alt me-1"></i>Chính sách đổi trả</a>
                            </li>
                            <li class="mb-2"><a href="page.php?slug=chinh-sach-van-chuyen" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-truck me-1"></i>Chính sách vận chuyển</a>
                            </li>
                            <li class="mb-2"><a href="blog.php" class="text-muted text-decoration-none hover-white">
                                <i class="fas fa-blog me-1"></i>Blog tin tức</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-3">Liên hệ</h5>
                        <ul class="list-unstyled text-muted">
                            <li class="mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                123 Đường ABC, Phường XYZ, Quận 1, TP.HCM
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-phone me-2"></i>
                                Hotline: 1900 xxxx
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-envelope me-2"></i>
                                Email: support@example.com
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-clock me-2"></i>
                                Giờ làm việc: 8:00 - 22:00
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-3">Đăng ký nhận tin</h5>
                        <p class="small text-muted">Đăng ký để nhận thông tin về sản phẩm mới và khuyến mãi</p>
                        <form class="mb-3">
                            <div class="input-group">
                                <input class="form-control" type="email" placeholder="Email của bạn"
                                    style="border-radius: 20px 0 0 20px;">
                                <button class="btn btn-primary" type="submit" style="border-radius: 0 20px 20px 0;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        <div class="mt-4">
                            <img src="path/to/payment-methods.png" alt="Phương thức thanh toán" class="img-fluid"
                                style="max-height: 30px;">
                        </div>
                    </div>
                </div>

                <hr class="text-muted my-4">

                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="small text-muted mb-0">
                            &copy; <?php echo date('Y'); ?> Cửa Hàng Điện Thoại. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <img src="path/to/verified-badge.png" alt="Chứng nhận" class="img-fluid"
                            style="max-height: 40px;">
                    </div>
                </div>
            </div>
        </footer>

        <!-- Scripts - Optimized loading -->
        <script>
            // Remove loading indicator when page is loaded
            window.addEventListener('load', function() {
                document.getElementById('pageLoader').style.display = 'none';
            });

            // Fast CSS loading fallback
            ! function(e) {
                "use strict";
                var t = function(t, n, r) {
                    var o, i = e.document,
                        c = i.createElement("link");
                    if (n) o = n;
                    else {
                        var s = (i.body || i.getElementsByTagName("head")[0]).childNodes;
                        o = s[s.length - 1]
                    }
                    var u = i.styleSheets;
                    c.rel = "stylesheet", c.href = t, c.media = "only x",
                        function e(t) {
                            if (i.body) return t();
                            setTimeout(function() {
                                e(t)
                            })
                        }(function() {
                            o.parentNode.insertBefore(c, n ? o : o.nextSibling)
                        });
                    var f = function(e) {
                        for (var t = c.href, n = u.length; n--;)
                            if (u[n].href === t) return e();
                        setTimeout(function() {
                            f(e)
                        })
                    };
                    return f(function() {
                        c.media = r || "all"
                    }), c
                };
                "undefined" != typeof module && (module.exports = t)
            }(this);
        </script>

        <!-- Load jQuery first (required by other scripts) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>

        <!-- Load Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

        <!-- Load local scripts -->
        <script src="administrator/js_LQA/jscript.js" defer></script>
        <script src="public_files/search.js" defer></script>
        <script src="public_files/product_filter.js" defer></script>
        <script src="public_files/product_reviews.js" defer></script>
        <script src="public_files/performance.js" defer></script>

        <!-- Conditional notification script -->
        <?php if (isset($_SESSION['USER'])): ?>
            <script src="public_files/notification.js" defer></script>
            <script src="public_files/wishlist.js?v=<?php echo time(); ?>" defer></script>
        <?php endif; ?>

        <!-- Performance optimization script -->
        <script>
            // Performance variables
            let notificationsLoaded = false;
            let orderModalLoaded = false;

            // Lazy load notifications
            function loadNotifications() {
                if (!notificationsLoaded) {
                    // Create and load the full notification modal dynamically
                    const modalHTML = `
                    <div class="order-detail-modal" id="orderDetailModal">
                        <div class="order-detail-content">
                            <span class="order-detail-close" onclick="closeOrderModal()">&times;</span>
                            <div class="order-detail-header">
                                <h3>Chi tiết đơn hàng #<span id="order-id"></span></h3>
                                <span class="order-status" id="order-status"></span>
                            </div>
                            <div class="order-detail-info">
                                <div class="order-detail-info-col">
                                    <div class="order-detail-info-item">
                                        <strong>Mã đơn hàng</strong>
                                        <div id="order-code"></div>
                                    </div>
                                    <div class="order-detail-info-item">
                                        <strong>Ngày đặt</strong>
                                        <div id="order-date"></div>
                                    </div>
                                    <div class="order-detail-info-item">
                                        <strong>Phương thức thanh toán</strong>
                                        <div id="order-payment-method"></div>
                                    </div>
                                <div class="order-detail-info-col">
                                    <div class="order-detail-info-item">
                                        <strong>Địa chỉ giao hàng</strong>
                                        <div id="order-address" class="order-detail-address"></div>
                                    </div>
                                </div>
                            <div class="order-detail-items">
                                <h4>Sản phẩm</h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="60">Hình ảnh</th>
                                            <th>Sản phẩm</th>
                                            <th width="100">Đơn giá</th>
                                            <th width="80">Số lượng</th>
                                            <th width="120">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody id="order-items">
                                        <!-- Danh sách sản phẩm sẽ được thêm bằng JavaScript -->
                                    </tbody>
                                </table>
                                <div class="order-detail-total">
                                    Tổng tiền: <span id="order-total"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                    // Add to the notification container
                    const notificationContainer = document.querySelector('.position-relative.me-2');
                    if (notificationContainer) {
                        notificationContainer.insertAdjacentHTML('beforeend', modalHTML);
                    }

                    notificationsLoaded = true;
                }

                // Toggle notification dropdown
                const dropdown = document.getElementById('notificationDropdown');
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                }
            }

            function closeOrderModal() {
                const modal = document.getElementById('orderDetailModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }

            // Preload critical resources
            if ('serviceWorker' in navigator) {
                // Simple resource caching
                const resources = [
                    'public_files/mycss.css',
                    'public_files/notification.css',
                    'administrator/js_LQA/jscript.js',
                    'public_files/search.js'
                ];

                resources.forEach(resource => {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = resource;
                    document.head.appendChild(link);
                });
            }

            // Image lazy loading for better performance
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy-load');
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.addEventListener('DOMContentLoaded', () => {
                    const lazyImages = document.querySelectorAll('img[data-src]');
                    lazyImages.forEach(img => imageObserver.observe(img));
                });
            }
        </script>
</body>

</html>