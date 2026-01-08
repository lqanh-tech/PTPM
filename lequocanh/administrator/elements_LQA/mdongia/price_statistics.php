<?php

$dongiaPaths = [
    '../mod/dongiaCls.php',
    './elements_LQA/mod/dongiaCls.php',
    './administrator/elements_LQA/mod/dongiaCls.php',
    __DIR__ . '/../mod/dongiaCls.php'
];

$foundDongia = false;
foreach ($dongiaPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundDongia = true;
        break;
    }
}

if (!$foundDongia) {
    error_log("Không thể tìm thấy file dongiaCls.php");
    throw new Exception("Không thể tải file dongiaCls.php");
}

$hanghoaPaths = [
    '../mod/hanghoaCls.php',
    './elements_LQA/mod/hanghoaCls.php',
    './administrator/elements_LQA/mod/hanghoaCls.php',
    __DIR__ . '/../mod/hanghoaCls.php'
];

$foundHanghoa = false;
foreach ($hanghoaPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundHanghoa = true;
        break;
    }
}

if (!$foundHanghoa) {
    error_log("Không thể tìm thấy file hanghoaCls.php");
    throw new Exception("Không thể tải file hanghoaCls.php");
}

try {
    $dongiaObj = new Dongia();
    $hanghoaObj = new hanghoa();

    $allPrices = $dongiaObj->DongiaGetAll();

    $allProducts = $hanghoaObj->HanghoaGetAll();

    $totalPrices = count($allPrices);
    $activePrices = 0;
    $expiredPrices = 0;
    $productsWithPrice = 0;
    $productsWithoutPrice = 0;
    $totalValue = 0;

    foreach ($allPrices as $price) {
        if ($price->apDung) {
            $activePrices++;
            $totalValue += $price->giaBan;
        }

        if (strtotime($price->ngayKetThuc) < time()) {
            $expiredPrices++;
        }
    }

    foreach ($allProducts as $product) {
        $hasActivePrice = false;
        foreach ($allPrices as $price) {
            if ($price->idHangHoa == $product->idhanghoa && $price->apDung) {
                $hasActivePrice = true;
                break;
            }
        }

        if ($hasActivePrice) {
            $productsWithPrice++;
        } else {
            $productsWithoutPrice++;
        }
    }

    $averagePrice = $activePrices > 0 ? $totalValue / $activePrices : 0;
} catch (Exception $e) {
    error_log("Error in price statistics: " . $e->getMessage());
    $totalPrices = $activePrices = $expiredPrices = 0;
    $productsWithPrice = $productsWithoutPrice = 0;
    $averagePrice = $totalValue = 0;
}
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-tags"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tổng đơn giá</span>
                <span class="info-box-number"><?php echo number_format($totalPrices); ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Tất cả đơn giá trong hệ thống
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đang áp dụng</span>
                <span class="info-box-number"><?php echo number_format($activePrices); ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo $totalPrices > 0 ? ($activePrices / $totalPrices * 100) : 0; ?>%"></div>
                </div>
                <span class="progress-description">
                    <?php echo $totalPrices > 0 ? round($activePrices / $totalPrices * 100, 1) : 0; ?>% tổng đơn giá
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đã hết hạn</span>
                <span class="info-box-number"><?php echo number_format($expiredPrices); ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo $totalPrices > 0 ? ($expiredPrices / $totalPrices * 100) : 0; ?>%"></div>
                </div>
                <span class="progress-description">
                    <?php echo $totalPrices > 0 ? round($expiredPrices / $totalPrices * 100, 1) : 0; ?>% tổng đơn giá
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Giá trung bình</span>
                <span class="info-box-number"><?php echo number_format($averagePrice, 0, ',', '.'); ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: 85%"></div>
                </div>
                <span class="progress-description">
                    VNĐ / sản phẩm
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Phân bố sản phẩm
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center">
                        <div class="progress-group">
                            <span class="progress-text">Có đơn giá</span>
                            <span class="float-right"><b><?php echo $productsWithPrice; ?></b>/<?php echo $productsWithPrice + $productsWithoutPrice; ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: <?php echo ($productsWithPrice + $productsWithoutPrice) > 0 ? ($productsWithPrice / ($productsWithPrice + $productsWithoutPrice) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 text-center">
                        <div class="progress-group">
                            <span class="progress-text">Chưa có giá</span>
                            <span class="float-right"><b><?php echo $productsWithoutPrice; ?></b>/<?php echo $productsWithPrice + $productsWithoutPrice; ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-danger" style="width: <?php echo ($productsWithPrice + $productsWithoutPrice) > 0 ? ($productsWithoutPrice / ($productsWithPrice + $productsWithoutPrice) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($productsWithoutPrice > 0): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Có <?php echo $productsWithoutPrice; ?> sản phẩm chưa có đơn giá.
                        <a href="index.php?req=hanghoa" class="alert-link">Xem danh sách sản phẩm</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-money-bill-wave"></i> Thống kê giá trị
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="description-block border-right">
                            <h5 class="description-header text-success">
                                <?php echo number_format($totalValue, 0, ',', '.'); ?> VNĐ
                            </h5>
                            <span class="description-text">Tổng giá trị hàng hóa</span>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-6">
                        <div class="description-block">
                            <span class="description-percentage text-success">
                                <i class="fas fa-caret-up"></i>
                                <?php echo $activePrices; ?>
                            </span>
                            <h5 class="description-header">Sản phẩm có giá</h5>
                            <span class="description-text">Đang bán</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="description-block">
                            <span class="description-percentage text-warning">
                                <i class="fas fa-caret-down"></i>
                                <?php echo $productsWithoutPrice; ?>
                            </span>
                            <h5 class="description-header">Chưa có giá</h5>
                            <span class="description-text">Cần thiết lập</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0, 0, 0, 0.2);
    }

    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }

    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }

    .progress {
        background: rgba(0, 0, 0, 0.2);
        margin: 5px 0;
        height: 2px;
    }

    .progress-description {
        margin: 0;
        font-size: 12px;
    }
</style>