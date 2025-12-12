<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use SessionManager for safe session handling
require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/elements_LQA/config/logger_config.php';

// Start session safely
SessionManager::start();

// Check if user is logged in
if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('location:userLogin.php');
    exit(); // Add exit to prevent further execution
}

// Process marketing content forms BEFORE HTML output
require_once __DIR__ . '/elements_LQA/madmin/marketing_content_handler.php';

// Tích hợp hệ thống ghi nhật ký truy cập menu
require_once './elements_LQA/mnhatkyhoatdong/menuAccessLogger.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin page</title>
    <link href="./stylecss_LQA/mycss.css" rel="stylesheet" type="text/css">
    <link href="./layoutcss/fixed.css" rel="stylesheet" type="text/css">

    <!-- Bootstrap CSS (sử dụng Bootstrap 4 vì modal sử dụng cú pháp này) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Thêm jQuery từ CDN (trước Bootstrap và các script khác) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Inject BASE_URL from PHP to JavaScript -->
    <script>
        window.BASE_URL = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : 'http://localhost:20080', '/'); ?>';
    </script>

    <!-- Thêm style trực tiếp để sửa vấn đề hình ảnh -->
    <style>
        img.iconimg {
            width: 24px !important;
            height: 24px !important;
            object-fit: contain !important;
            cursor: pointer;
        }

        img[src*="Delete.png"],
        img[src*="Update.png"],
        img[src*="Lock.png"],
        img[src*="Unlock.png"],
        img[src*="Success.png"],
        img[src*="Fail.png"],
        img[src*="Wait.png"] {
            width: 24px !important;
            height: 24px !important;
        }

        .btn-sm {
            padding: 0;
            background: transparent;
            border: none;
        }

        /* Modal styles */
        .modal-dialog {
            max-width: 600px;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-body {
            padding: 20px;
        }
    </style>
</head>

<body>
    <div id="top_div">
        <?php
        //top page processing
        require "./elements_LQA/top.php";
        ?>
    </div>
    <div id="left_div">
        <?php
        //left page processing
        require "./elements_LQA/left.php";
        ?>
    </div>
    <div id="center_div">
        <?php
        //center page processing
        require "./elements_LQA/center.php";
        ?>
    </div>

    <div id="right_div"></div>
    <div id="bottom_div"></div>
    <div id="signoutbutton">
        <a href="./elements_LQA/mUser/userAct.php?reqact=userlogout">
            <img src="./elements_LQA/img_LQA/Logout.png" class="iconimg">
        </a>
    </div>

    <!-- Bootstrap Modal for Updates -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Cập nhật</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="updateModalContent">
                    <!-- Form content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- jQuery must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Bootstrap JS và Popper -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <!-- Script riêng -->
    <script src="./js_LQA/modal-handler.js"></script>
    <script src="./js_LQA/jscript.js"></script>
</body>

</html>