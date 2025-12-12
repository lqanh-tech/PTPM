<head>
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../public_files/mycss.css">
    <!-- Add Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* CSS cho form thêm người dùng */
        #formMessages {
            margin-bottom: 15px;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* CSS cho nút trong bảng */
        .btn-action {
            display: inline-block;
            margin-right: 5px;
            color: #333;
            text-decoration: none;
            font-size: 16px;
        }

        .btn-edit {
            color: #007bff;
        }

        .btn-lock {
            color: #ffc107;
        }

        .btn-delete {
            color: #dc3545;
        }

        .btn-action:hover {
            opacity: 0.8;
        }
    </style>
</head>

<body>

    <div class="admin-title">Quản lý người dùng</div>
    <hr>

    <?php
    require './elements_LQA/mod/userCls.php';
    $userObj = new user();
    $list_user = $userObj->UserGetAll();
    $l = count($list_user);

    // Thống kê
    $totalUsers = count($list_user);
    $activeUsers = 0;
    $last30DaysLogins = 0;
    $newUsersThisMonth = 0;

    foreach ($list_user as $u) {
        if ($u->setlock == 1) $activeUsers++;

        // Đếm người dùng đăng nhập trong 30 ngày
        if (isset($_COOKIE[$u->username])) {
            $lastLogin = strtotime($_COOKIE[$u->username]);
            if ((time() - $lastLogin) <= (30 * 24 * 60 * 60)) {
                $last30DaysLogins++;
            }
        }

        // Đếm người dùng mới trong tháng này
        if (isset($u->ngaydangki)) {
            $registerDate = strtotime($u->ngaydangki);
            if (date('Y-m', $registerDate) === date('Y-m')) {
                $newUsersThisMonth++;
            }
        }
    }
    ?>

    <!-- Dashboard Cards -->
    <div class="admin-dashboard">
        <div class="dashboard-cards">
            <div class="dashboard-card primary">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Tổng số người dùng</h4>
                        <h2><?php echo $totalUsers; ?></h2>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card success">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Người dùng hoạt động</h4>
                        <h2><?php echo $activeUsers; ?></h2>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card info">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Đăng nhập 30 ngày qua</h4>
                        <h2><?php echo $last30DaysLogins; ?></h2>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                </div>
            </div>

            <div class="dashboard-card warning">
                <div class="card-content">
                    <div class="card-info">
                        <h4>Người dùng mới tháng này</h4>
                        <h2><?php echo $newUsersThisMonth; ?></h2>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr />
    <div class="admin-form">
        <h3>Thêm người dùng mới</h3>
        <div id="formMessages"></div>
        <form name="newuser" id="formreg" method="post" action='./elements_LQA/mUser/userAct.php?reqact=addnew'>
            <table class="form-table">
                <tr>
                    <td>Tên đăng nhập:</td>
                    <td><input type="text" name="username" required /></td>
                </tr>
                <tr>
                    <td>Mật khẩu:</td>
                    <td><input type="password" name="password" required /></td>
                </tr>
                <tr>
                    <td>Họ tên:</td>
                    <td><input type="text" name="hoten" required /></td>
                </tr>
                <tr>
                    <td>Giới tính:</td>
                    <td>
                        Nam<input type="radio" name="gioitinh" value="1" checked="true" />
                        Nữ<input type="radio" name="gioitinh" value="0" />
                    </td>
                </tr>
                <tr>
                    <td>Ngày sinh:</td>
                    <td><input type="date" name="ngaysinh" required /></td>
                </tr>
                <tr>
                    <td>Địa chỉ:</td>
                    <td><input type="text" name="diachi" required /></td>
                </tr>
                <tr>
                    <td>Điện thoại:</td>
                    <td><input type="tel" name="dienthoai" pattern="[0-9]{10}" required /></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><input type="email" name="email" placeholder="Email (không bắt buộc)" /></td>
                </tr>
                <tr>
                    <td colspan="2" class="form-actions">
                        <button type="submit" class="btn btn-primary">Tạo mới</button>
                        <button type="reset" class="btn btn-secondary">Làm lại</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            // Hàm làm mới danh sách người dùng
            window.refreshUserList = function() {
                $.ajax({
                    url: "./elements_LQA/mUser/userAjax.php?action=getUsers",
                    type: "GET",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    success: function(data) {
                        // Cập nhật bảng người dùng
                        $(".content_user table tbody").html(data);

                        // Cập nhật số lượng người dùng
                        $.ajax({
                            url: "./elements_LQA/mUser/userAjax.php?action=getUserCount",
                            type: "GET",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            },
                            success: function(count) {
                                $(".admin-info b").text(count);
                            }
                        });
                    },
                    error: function() {
                        console.error("Không thể làm mới danh sách người dùng");
                    }
                });
            };

            // Xử lý form thêm người dùng
            $("#formreg").submit(function(e) {
                e.preventDefault(); // Ngăn form submit theo cách thông thường

                // Hiển thị thông báo đang xử lý
                $("#formMessages").html('<div class="alert alert-info">Đang xử lý...</div>');

                // Kiểm tra các trường dữ liệu
                var username = $("input[name='username']").val();
                var password = $("input[name='password']").val();
                var hoten = $("input[name='hoten']").val();
                var ngaysinh = $("input[name='ngaysinh']").val();
                var diachi = $("input[name='diachi']").val();
                var dienthoai = $("input[name='dienthoai']").val();

                // Kiểm tra dữ liệu
                if (!username || !password || !hoten || !ngaysinh || !diachi || !dienthoai) {
                    $("#formMessages").html('<div class="alert alert-danger">Vui lòng điền đầy đủ thông tin!</div>');
                    return false;
                }

                // Kiểm tra số điện thoại
                if (!/^[0-9]{10}$/.test(dienthoai)) {
                    $("#formMessages").html('<div class="alert alert-danger">Số điện thoại phải có 10 chữ số!</div>');
                    return false;
                }

                // Gửi dữ liệu bằng AJAX
                $.ajax({
                    url: "./elements_LQA/mUser/userAct.php?reqact=addnew",
                    type: "POST",
                    data: $(this).serialize(),
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            $("#formMessages").html('<div class="alert alert-success">' + response.message + '</div>');

                            // Xóa dữ liệu trong form
                            $("#formreg")[0].reset();

                            // Thay vì tải lại trang, chỉ hiển thị thông báo thành công
                            // và làm mới danh sách người dùng bằng AJAX
                            setTimeout(function() {
                                // Thêm người dùng mới vào bảng mà không cần tải lại trang
                                refreshUserList();
                            }, 1000);
                        } else {
                            // Hiển thị thông báo lỗi
                            $("#formMessages").html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        // Hiển thị thông báo lỗi
                        $("#formMessages").html('<div class="alert alert-danger">Có lỗi xảy ra, vui lòng thử lại!</div>');
                    }
                });
            });
        });
    </script>

    <hr />
    <div class="content_user">
        <div class="admin-info">
            Tổng số người dùng: <b><?php echo $l; ?></b>
        </div>

        <div class="table-scroll-container">
            <table class="content-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Mật khẩu</th>
                        <th>Họ tên</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>Địa chỉ</th>
                        <th>Điện thoại</th>
                        <th>Email</th>
                        <th>Trạng thái</th>
                        <th>Chức năng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($l > 0) {
                        foreach ($list_user as $u) {
                            $isAdmin = ($u->username === 'admin');
                    ?>
                            <tr>
                                <td><?php echo $u->iduser; ?></td>
                                <td><?php echo $u->username; ?></td>
                                <td>
                                    <div class="password-field">
                                        <span class="password-dots">••••••••</span>
                                        <span class="password-text" style="display: none;">
                                            <?php echo htmlspecialchars($u->password); ?>
                                        </span>
                                        <i class="fas fa-eye toggle-password" style="cursor: pointer; margin-left: 5px;"></i>
                                    </div>
                                </td>
                                <td><?php echo $u->hoten; ?></td>
                                <td><?php echo $u->gioitinh; ?></td>
                                <td><?php echo $u->ngaysinh; ?></td>
                                <td><?php echo $u->diachi; ?></td>
                                <td><?php echo $u->dienthoai; ?></td>
                                <td><?php echo !empty($u->email) ? htmlspecialchars($u->email) : '<span style="color: #999;">Chưa có</span>'; ?></td>
                                <td align="center">
                                    <?php if (isset($_SESSION['ADMIN'])) { ?>
                                        <a href='./elements_LQA/mUser/userAct.php?reqact=setlock&iduser=<?php echo $u->iduser; ?>&setlock=<?php echo $u->setlock; ?>'
                                            class="status-icon">
                                            <img src="<?php echo $u->setlock == 1 ? './elements_LQA/img_LQA/Unlock.png' : './elements_LQA/img_LQA/Lock.png'; ?>"
                                                class="iconimg" alt="Trạng thái">
                                        </a>
                                    <?php } else { ?>
                                        <img src="<?php echo $u->setlock == 1 ? './elements_LQA/img_LQA/Unlock.png' : './elements_LQA/img_LQA/Lock.png'; ?>"
                                            class="iconimg" alt="Trạng thái">
                                    <?php } ?>
                                </td>
                                <td class="action-buttons">
                                    <?php if (isset($_SESSION['ADMIN'])) { ?>
                                        <a href='./elements_LQA/mUser/userAct.php?reqact=deleteuser&iduser=<?php echo $u->iduser; ?>'
                                            class="admin-action" data-username="<?php echo $u->username; ?>"
                                            onclick="return confirmDelete('<?php echo $u->username; ?>');">
                                            <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                        </a>
                                    <?php } else { ?>
                                        <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                    <?php } ?>

                                    <?php if (isset($_SESSION['ADMIN']) || (isset($_SESSION['USER']) && $_SESSION['USER'] == $u->username)) { ?>
                                        <a href='javascript:void(0);' class="update-user"
                                            data-username="<?php echo htmlspecialchars($u->username); ?>"
                                            data-userid="<?php echo $u->iduser; ?>">
                                            <img src="./elements_LQA/img_LQA/Update.png" class="iconimg" alt="Update">
                                        </a>
                                    <?php } else { ?>
                                        <img src="./elements_LQA/img_LQA/Update.png" class="iconimg disabled" alt="Update">
                                    <?php } ?>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div><!-- End table-scroll-container -->
    </div>

    <style>
        /* Scrollable Table Container */
        .table-scroll-container {
            max-height: 60vh;
            min-height: 300px;
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 15px;
            background: #fff;
        }
        
        .table-scroll-container::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 5px;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }
        
        .table-scroll-container .content-table {
            margin-bottom: 0;
            width: 100%;
        }
        
        .table-scroll-container .content-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-scroll-container .content-table thead th {
            background: #343a40;
            color: #fff;
            padding: 12px 10px;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
        }
    </style>

    <?php if (isset($_GET['result'])): ?>
        <?php
        $message = '';
        $alertClass = 'alert-danger';

        switch ($_GET['result']) {
            case 'ok':
                $message = 'Thao tác thành công!';
                $alertClass = 'alert-success';
                break;
            case 'notok':
                $message = 'Có lỗi xảy ra!';
                break;
            case 'not_authorized':
                $message = 'Bạn không có quyền thực hiện thao tác này!';
                break;
            case 'invalid_verify_pass':
                $message = 'Mật khẩu xác thực không chính xác!';
                break;
            case 'username_exists':
                $message = 'Tên đăng nhập đã tồn tại!';
                break;
            case 'missing_data':
                $message = 'Vui lòng điền đầy đủ thông tin!';
                break;
        }
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</body>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
    integrity="sha384-oBqDVmMz4fnFO9gyb6g5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5c5" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
    integrity="sha384-1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1n1" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        // Xử lý hiển thị/ẩn mật khẩu khi click vào biểu tượng con mắt
        $('.toggle-password').on('click', function() {
            var passwordDots = $(this).siblings('.password-dots');
            var passwordText = $(this).siblings('.password-text');

            if (passwordDots.is(':visible')) {
                passwordDots.hide();
                passwordText.show();
                $(this).removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordDots.show();
                passwordText.hide();
                $(this).removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>
<script src="../../js_LQA/jscript.js"></script>