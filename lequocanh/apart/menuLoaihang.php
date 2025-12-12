<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/loaihangCls.php';
$current_id = isset($_GET['reqView']) ? $_GET['reqView'] : null;
?>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="./index.php">
            <img src="./administrator/elements_LQA/img_LQA/Home.png" alt="Home" width="24" height="24"
                class="d-inline-block align-text-top me-2">
            Home
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="nav nav-pills main-menu">
                <?php
                try {
                    $obj = new loaihang();
                    $list_lh = $obj->LoaihangGetAll();

                    // Hiển thị tất cả các mục
                    foreach ($list_lh as $v) {
                        $active_class = ($current_id == $v->idloaihang) ? 'active' : '';
                ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_class; ?> d-flex align-items-center"
                                href="./index.php?reqView=<?php echo $v->idloaihang; ?>">
                                <img src="data:image/png;base64,<?php echo $v->hinhanh; ?>"
                                    alt="<?php echo $v->tenloaihang; ?>"
                                    width="20" height="20" class="me-2">
                                <span><?php echo $v->tenloaihang; ?></span>
                            </a>
                        </li>
                <?php
                    }
                } catch (Exception $e) {
                    // Hiển thị thông báo lỗi thân thiện
                    echo '<li class="nav-item">';
                    echo '<span class="nav-link text-danger">';
                    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                    echo 'Lỗi kết nối cơ sở dữ liệu';
                    echo '</span>';
                    echo '</li>';
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link text-info" href="./kiem_tra_ket_noi.php" target="_blank">';
                    echo '<i class="fas fa-tools me-2"></i>';
                    echo 'Kiểm tra kết nối';
                    echo '</a>';
                    echo '</li>';

                    // Ghi log lỗi
                    error_log("Lỗi trong menuLoaihang.php: " . $e->getMessage());
                }
                ?>
            </ul>
        </div>
    </div>
</nav>