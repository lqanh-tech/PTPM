<?php

require_once __DIR__ . '/config/local_config.php';

require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/config/logger_config.php';

SessionManager::start();

require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/hanghoaCls.php';

$productIds = [];
if (isset($_GET['products'])) {
    $productIds = explode(',', $_GET['products']);

    $productIds = array_map('intval', $productIds);
}

$products = [];
if (!empty($productIds)) {
    $hanghoa = new hanghoa();
    foreach ($productIds as $id) {
        $product = $hanghoa->HanghoaGetbyId($id);
        if ($product) {
            $products[] = $product;
        }
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
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- Local CSS files - Critical first -->
    <link rel="stylesheet" href="public_files/critical.css">
    <link rel="stylesheet" href="public_files/mycss.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="public_files/mycss.css">
    </noscript>

    <title>So sánh sản phẩm</title>

    <!-- Minimal inline critical CSS -->
    <style>

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

        .comparison-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .comparison-table td,
        .comparison-table th {
            text-align: center;
            vertical-align: middle;
        }

        .product-image {
            max-width: 150px;
            height: 150px;
            object-fit: contain;
        }

        .product-name {
            font-weight: bold;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="./index.php">
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
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">So sánh sản phẩm</h1>

                <?php if (empty($products)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không tìm thấy sản phẩm để so sánh. Vui lòng chọn sản phẩm từ trang danh sách.
                    </div>
                    <a href="./index.php" class="btn btn-primary">Quay lại trang chủ</a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered comparison-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 20%;">Thuộc tính</th>
                                    <?php foreach ($products as $product): ?>
                                        <th scope="col">
                                            <div class="product-name"><?php echo htmlspecialchars($product->tenhanghoa); ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">Hình ảnh</th>
                                    <?php foreach ($products as $product): ?>
                                        <td>
                                            <?php
                                            $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
                                            if ($hinhanh && !empty($hinhanh->duong_dan)):
                                            ?>
                                                <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $product->hinhanh; ?>"
                                                    class="product-image" alt="<?php echo htmlspecialchars($product->tenhanghoa); ?>">
                                            <?php else: ?>
                                                <img src="./administrator/elements_LQA/img_LQA/no-image.png"
                                                    class="product-image" alt="Không có hình ảnh">
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th scope="row">Giá</th>
                                    <?php foreach ($products as $product): ?>
                                        <td>
                                            <div class="price">
                                                <?php echo number_format($product->giathamkhao, 0, ',', '.') . ' VNĐ'; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th scope="row">Mô tả</th>
                                    <?php foreach ($products as $product): ?>
                                        <td><?php echo htmlspecialchars($product->mota ?? 'Không có mô tả'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th scope="row">Hành động</th>
                                    <?php foreach ($products as $product): ?>
                                        <td>
                                            <a href="./index.php?reqHanghoa=<?php echo $product->idhanghoa; ?>"
                                                class="btn btn-outline-primary mb-2">
                                                <i class="fas fa-eye me-1"></i> Xem chi tiết
                                            </a>
                                            <br>
                                            <a href="./administrator/elements_LQA/mgiohang/giohangAct.php?reqact=addtocart&idhanghoa=<?php echo $product->idhanghoa; ?>"
                                                class="btn btn-success">
                                                <i class="fas fa-shopping-cart me-1"></i> Thêm vào giỏ
                                            </a>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="./index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-5 bg-dark text-white">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-3">Về chúng tôi</h5>
                    <p class="small text-muted">
                        Cửa hàng điện thoại uy tín hàng đầu Việt Nam. Chuyên cung cấp các sản phẩm chính hãng với chất
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
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Hướng dẫn mua hàng</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách bảo hành</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách đổi trả</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách vận chuyển</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Điều khoản dịch vụ</a></li>
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
                    <img src="path/to/verified-badge.png" alt="Chứng nhận" class="img-fluid" style="max-height: 40px;">
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts - Optimized loading -->
    <!-- Load jQuery first (required by other scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>

    <!-- Load Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Load local scripts -->
    <script src="administrator/js_LQA/jscript.js" defer></script>
    <script src="public_files/search.js" defer></script>
</body>

</html>