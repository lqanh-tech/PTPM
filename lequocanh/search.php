<?php
require_once './administrator/elements_LQA/mod/hanghoaCls.php';
$hanghoa = new hanghoa();

$query = isset($_GET['query']) ? $_GET['query'] : '';
$list_hanghoa = $hanghoa->searchHanghoa($query);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public_files/mycss.css">
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>Kết quả tìm kiếm</title>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <h2 class="text-center mb-4">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($query); ?>"</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if (count($list_hanghoa) > 0): ?>
                <?php foreach ($list_hanghoa as $v): ?>
                    <?php

                    $hinhanh = $hanghoa->GetHinhAnhById($v->hinhanh);

                    if ($v->hinhanh > 0) {
                        $imagePath = "./administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $v->hinhanh;
                    } else {
                        $imagePath = "./administrator/elements_LQA/img_LQA/no-image.png";
                    }
                    ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="updating-image-container">
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo $v->tenhanghoa; ?>" class="card-img-top">

                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $v->tenhanghoa; ?></h5>
                                <p class="card-text text-muted">
                                    Giá bán:
                                    <span class="text-danger fw-bold">
                                        <?php echo number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ'; ?>
                                    </span>
                                </p>
                                <a href="./index.php?reqHanghoa=<?php echo $v->idhanghoa; ?>" class="btn btn-outline-primary">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning text-center" role="alert">
                    Không tìm thấy sản phẩm nào.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <link rel="stylesheet" href="public_files/mycss.css">
    <script src="administrator/elements_LQA/js_LQA/jscript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>