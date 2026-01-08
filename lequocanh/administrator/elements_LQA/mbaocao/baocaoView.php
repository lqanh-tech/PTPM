<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('baocaoview', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-chart-line"></i> Báo cáo & Thống kê</h2>
    </div>

    <div class="report-dashboard">
        <div class="dashboard-cards">
            <div class="dashboard-card primary">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Báo cáo doanh thu</h4>
                        <p>Xem doanh thu theo ngày, tháng, năm</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="card-action">
                    <a href="index.php?req=doanhThuView" class="btn btn-primary">Xem báo cáo</a>
                </div>
            </div>

            <div class="dashboard-card success">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Thống kê sản phẩm bán chạy</h4>
                        <p>Xem các sản phẩm bán chạy nhất</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
                <div class="card-action">
                    <a href="index.php?req=sanPhamBanChayView" class="btn btn-success">Xem thống kê</a>
                </div>
            </div>

            <div class="dashboard-card info">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Báo cáo lợi nhuận</h4>
                        <p>Xem lợi nhuận theo thời gian và sản phẩm</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
                <div class="card-action">
                    <a href="index.php?req=loiNhuanView" class="btn btn-info">Xem báo cáo</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-description">
        <h3>Giới thiệu về hệ thống báo cáo</h3>
        <p>Hệ thống báo cáo và thống kê giúp bạn theo dõi hiệu quả kinh doanh của cửa hàng. Các báo cáo được cập nhật theo thời gian thực và cung cấp thông tin chi tiết về:</p>

        <div class="report-features">
            <div class="feature">
                <i class="fas fa-money-bill-wave"></i>
                <h4>Báo cáo doanh thu</h4>
                <p>Xem doanh thu theo ngày, tháng, năm. Phân tích xu hướng doanh thu và so sánh giữa các khoảng thời gian.</p>
            </div>

            <div class="feature">
                <i class="fas fa-chart-bar"></i>
                <h4>Thống kê sản phẩm bán chạy</h4>
                <p>Xác định các sản phẩm bán chạy nhất để tối ưu hóa kho hàng và chiến lược kinh doanh.</p>
            </div>

            <div class="feature">
                <i class="fas fa-chart-pie"></i>
                <h4>Báo cáo lợi nhuận</h4>
                <p>Phân tích lợi nhuận theo sản phẩm và thời gian để đưa ra quyết định kinh doanh hiệu quả.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .report-dashboard {
        margin-bottom: 30px;
    }

    .dashboard-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-card {
        flex: 1;
        min-width: 300px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .dashboard-card.primary {
        border-top: 4px solid #007bff;
    }

    .dashboard-card.success {
        border-top: 4px solid #28a745;
    }

    .dashboard-card.info {
        border-top: 4px solid #17a2b8;
    }

    .card-content {
        display: flex;
        padding: 20px;
    }

    .card-info {
        flex: 1;
    }

    .card-info h4 {
        margin: 0 0 10px 0;
        font-size: 18px;
    }

    .card-info p {
        margin: 0;
        color: #666;
    }

    .card-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        font-size: 24px;
        color: #555;
    }

    .card-action {
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
        text-align: right;
    }

    .report-description {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .report-features {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 20px;
    }

    .feature {
        flex: 1;
        min-width: 250px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        text-align: center;
    }

    .feature i {
        font-size: 36px;
        margin-bottom: 15px;
        color: #007bff;
    }

    .feature h4 {
        margin: 0 0 10px 0;
    }

    .feature p {
        margin: 0;
        color: #666;
    }

    .btn {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                this.style.transition = 'all 0.3s ease';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            });
        });
    });
</script>