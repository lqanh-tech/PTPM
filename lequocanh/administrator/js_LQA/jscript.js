$(document).ready(function () {
  // Menu interaction
  $(".itemOrder").hide();
  $(".cateOrder").click(function () {
    $(this).next().slideDown();
  });
  $(".itemOrder").mouseleave(function () {
    $(this).slideUp();
  });

  // Form validation and submission
  $("#formreg").submit(function (event) {
    event.preventDefault(); // Ngăn form submit theo cách thông thường
    $("#noteForm").html("");
    var isValid = true;

    // Kiểm tra các trường dữ liệu
    var username = $("input[name='username']").val();
    var password = $("input[name='password']").val();
    var hoten = $("input[name='hoten']").val();
    var ngaysinh = $("input[name='ngaysinh']").val();
    var diachi = $("input[name='diachi']").val();
    var dienthoai = $("input[name='dienthoai']").val();

    // Kiểm tra dữ liệu
    if (
      !username ||
      !password ||
      !hoten ||
      !ngaysinh ||
      !diachi ||
      !dienthoai
    ) {
      if (typeof Toast !== 'undefined') {
        Toast.error("Vui lòng điền đầy đủ thông tin!");
      } else {
        alert("Vui lòng điền đầy đủ thông tin!");
      }
      return false;
    }

    // Kiểm tra số điện thoại
    if (!/^[0-9]{10}$/.test(dienthoai)) {
      if (typeof Toast !== 'undefined') {
        Toast.error("Số điện thoại phải có 10 chữ số!");
      } else {
        alert("Số điện thoại phải có 10 chữ số!");
      }
      return false;
    }

    // Hiển thị thông báo đang xử lý
    var loadingAlert = $('<div class="alert alert-info">Đang xử lý...</div>');
    $(".admin-form").prepend(loadingAlert);

    // Gửi dữ liệu bằng AJAX
    $.ajax({
      url: "./elements_LQA/mUser/userAct.php?reqact=addnew",
      type: "POST",
      data: $(this).serialize(),
      success: function (response) {
        // Xóa thông báo đang xử lý
        loadingAlert.remove();

        // Hiển thị thông báo thành công
        var successAlert = $(
          '<div class="alert alert-success">Thêm người dùng thành công!</div>'
        );
        $(".admin-form").prepend(successAlert);

        // Làm mới danh sách người dùng
        refreshUserList();

        // Xóa dữ liệu trong form
        $("#formreg")[0].reset();

        // Tự động ẩn thông báo sau 3 giây
        setTimeout(function () {
          successAlert.fadeOut(500, function () {
            $(this).remove();
          });
        }, 3000);
      },
      error: function (xhr, status, error) {
        // Xóa thông báo đang xử lý
        loadingAlert.remove();

        // Hiển thị thông báo lỗi
        var errorAlert = $(
          '<div class="alert alert-danger">Có lỗi xảy ra: ' + error + "</div>"
        );
        $(".admin-form").prepend(errorAlert);

        // Tự động ẩn thông báo sau 3 giây
        setTimeout(function () {
          errorAlert.fadeOut(500, function () {
            $(this).remove();
          });
        }, 3000);
      },
    });
  });

  // Hàm làm mới danh sách người dùng
  function refreshUserList() {
    $.ajax({
      url: "./elements_LQA/mUser/getUserList.php",
      type: "GET",
      success: function (data) {
        // Cập nhật bảng người dùng với dữ liệu mới
        $(".content_user .table-responsive").html(data);

        // Cập nhật số lượng người dùng
        var userCount = $(".content-table tbody tr").length;
        $(".admin-info b").text(userCount);

        // Cập nhật thống kê trên dashboard
        updateDashboardStats();
      },
    });
  }

  // Hàm cập nhật thống kê dashboard
  function updateDashboardStats() {
    $.ajax({
      url: "./elements_LQA/mUser/getDashboardStats.php",
      type: "GET",
      dataType: "json",
      success: function (data) {
        // Cập nhật các thẻ thống kê
        $(".dashboard-card.primary h2").text(data.totalUsers);
        $(".dashboard-card.success h2").text(data.activeUsers);
        $(".dashboard-card.info h2").text(data.last30DaysLogins);
        $(".dashboard-card.warning h2").text(data.newUsersThisMonth);
      },
    });
  }

  // Setup for loaihang update
  $("#w_update").hide();
  $(".w_update_btn_open").click(function (e) {
    e.preventDefault();
    $("#w_update").css("left", e.pageX + 5);
    $("#w_update").css("top", e.pageY + 5);

    var $idloaihang = $(this).attr("value");

    $("#w_update_form").load(
      "./elements_LQA/mloaihang/loaihangUpdate.php",
      { idloaihang: $idloaihang },
      function (response, status, request) {
        this;
      }
    );
    $("#w_update").show();
  });
  $("#w_close_btn").click(function (e) {
    e.preventDefault();
    $("#w_update").hide();
  });

  // Setup for hanghoa update
  $("#w_update_hh").hide();
  $(document).on(
    "click",
    '.w_update_btn_open_hh, .generic-update-btn[data-module="mhanghoa"]',
    function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Hiển thị ở giữa màn hình
      var windowHeight = $(window).height();
      var windowWidth = $(window).width();
      var popupHeight = 500; // Chiều cao ước tính của popup
      var popupWidth = 700; // Chiều rộng ước tính của popup

      // Đảm bảo popup hiển thị ở giữa màn hình
      $("#w_update_hh").css({
        top: "50%",
        left: "50%",
        transform: "translate(-50%, -50%)",
      });

      let iddata = $(this).attr("value") || $(this).data("id");
      console.log("Cập nhật hàng hóa ID:", iddata);

      // Clear any existing content
      $("#w_update_form_hh").empty();

      // Add a loading indicator
      $("#w_update_form_hh").html(
        "<div style='text-align:center;padding:20px;'>Đang tải...</div>"
      );

      // Hiển thị popup ngay lập tức
      $("#w_update_hh").show();

      $("#w_update_form_hh").load(
        "./elements_LQA/mhanghoa/hanghoaUpdate.php",
        { idhanghoa: iddata },
        function (response, status, xhr) {
          if (status == "error") {
            console.error(
              "Error loading popup: " + xhr.status + " " + xhr.statusText
            );
            $("#w_update_form_hh").html(
              "<div style='color:red;padding:20px;'>Lỗi khi tải form: " +
                xhr.status +
                " " +
                xhr.statusText +
                "</div>"
            );
            return;
          }

          // Gán sự kiện cho nút đóng trong form
          $("#w_update_form_hh #close-btn").on("click", function () {
            $("#w_update_hh").hide();
          });

          // Focus vào form sau khi hiển thị
          setTimeout(function () {
            $("#w_update_form_hh input:first").focus();
          }, 100);

          console.log("Form cập nhật hàng hóa đã được tải");
        }
      );
    }
  );

  // Đóng popup khi click vào overlay
  $(document).on("click", "#w_update_hh", function (e) {
    if ($(e.target).is("#w_update_hh")) {
      $(this).hide();
    }
  });

  // Xử lý nút đóng chính
  $("#w_close_btn_hh").click(function (e) {
    e.preventDefault();
    $("#w_update_hh").hide();
  });

  // Setup for thuonghieu update
  $("#w_update_th").hide();
  $(document).on("click", ".w_update_btn_open_th", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var id = $(this).attr("value");
    console.log("Opening brand update popup for ID: " + id);

    // Center the popup in the middle of the screen
    $("#w_update_th").css({
      display: "block",
      top: "50%",
      left: "50%",
      transform: "translate(-50%, -50%)",
      width: "auto",
      height: "auto",
    });

    // Clear any existing content
    $("#w_update_form_th").empty();

    // Show loading message
    $("#w_update_form_th").html(
      '<div style="text-align:center;padding:20px;">Đang tải...</div>'
    );

    // Load the update form using AJAX
    $.ajax({
      url: "./elements_LQA/mthuonghieu/thuonghieuUpdate.php",
      type: "GET",
      data: { idThuongHieu: id },
      success: function (data) {
        $("#w_update_form_th").html(data);

        // Bind close event to the close button in the loaded form
        $(document).on("click", "#close-btn", function () {
          $("#w_update_th").hide();
        });
      },
      error: function () {
        $("#w_update_form_th").html(
          '<div style="text-align:center;padding:20px;color:red;">Lỗi khi tải form. Vui lòng thử lại.</div>'
        );
      },
    });

    // Close popup when clicking on background
    $(document).on("click", "#w_update_th", function (e) {
      if ($(e.target).is("#w_update_th")) {
        $("#w_update_th").hide();
      }
    });
  });

  // Xử lý nút đóng chính
  $(document).on("click", "#w_close_btn_th", function () {
    $("#w_update_th").hide();
  });

  // Setup message listener for close event from iframe
  $(window).on("message", function (e) {
    var message = e.originalEvent.data;
    if (message === "closeUpdateForm") {
      // Đóng tất cả các cửa sổ popup có thể có
      $("#w_update_th").hide();
      $("#w_update_tt").hide();
      $("#w_update_dvt").hide();
      $("#w_update_hh").hide();
      $("#w_update_nv").hide();
      $("#w_update_tthh").hide();
    }
  });

  // Setup for nhanvien update
  $("#w_update_nv").hide();
  $(".w_update_btn_open_nv").click(function (e) {
    e.preventDefault();
    $("#w_update_nv").css("top", e.pageY + 5);
    $("#w_update_nv").css("left", e.pageX + 5);

    var $idNhanVien = $(this).attr("value");
    $("#w_update_form_nv").load(
      "./elements_LQA/mnhanvien/nhanvienUpdate.php",
      { idNhanVien: $idNhanVien },
      function (response, status, request) {
        this;
      }
    );
    $("#w_update_nv").show();
  });
  $("#w_close_btn_nv").click(function (e) {
    e.preventDefault();
    $("#w_update_nv").hide();
  });

  // Setup for donvitinh update
  $("#w_update_dvt").hide();
  $(document).on("click", ".w_update_btn_open_dvt", function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Center the popup in the middle of the screen
    $("#w_update_dvt").css({
      display: "block",
      top: "50%",
      left: "50%",
      transform: "translate(-50%, -50%)",
      width: "auto",
      height: "auto",
    });

    // Get ID from button value attribute
    var $idDonViTinh = $(this).attr("value");
    console.log("Cập nhật đơn vị tính ID:", $idDonViTinh);

    // Clear form before loading
    $("#w_update_form_dvt").empty();

    // Show loading message
    $("#w_update_form_dvt").html(
      '<div style="text-align:center;padding:20px;">Đang tải...</div>'
    );

    // Load update form using AJAX
    $.ajax({
      url: "./elements_LQA/mdonvitinh/donvitinhUpdate.php",
      type: "GET",
      data: { idDonViTinh: $idDonViTinh },
      success: function (data) {
        $("#w_update_form_dvt").html(data);

        // Bind close event to the close button in the loaded form
        $(document).on("click", "#close-btn", function () {
          $("#w_update_dvt").hide();
        });
      },
      error: function () {
        $("#w_update_form_dvt").html(
          '<div style="text-align:center;padding:20px;color:red;">Lỗi khi tải form. Vui lòng thử lại.</div>'
        );
      },
    });

    // Close popup when clicking on background
    $(document).on("click", "#w_update_dvt", function (e) {
      if ($(e.target).is("#w_update_dvt")) {
        $("#w_update_dvt").hide();
      }
    });
  });

  // Handle close button for unit of measurement using event delegation
  $(document).on("click", "#w_close_btn_dvt, #close-btn", function (e) {
    console.log("Close button clicked in jscript.js");
    if (e) e.preventDefault();
    $("#w_update_dvt").hide();
    console.log("Hiding w_update_dvt");
  });

  // Setup for thuoctinh update
  $("#w_update_tt").hide();
  $(document).on("click", ".w_update_btn_open_tt", function () {
    var id = $(this).data("id");
    console.log("Opening attribute update popup for ID: " + id);

    // Center the popup in the middle of the screen
    $("#w_update_tt").css({
      display: "block",
      top: "50%",
      left: "50%",
      transform: "translate(-50%, -50%)",
      width: "auto",
      height: "auto",
    });

    // Clear any existing content
    $("#w_update_form_tt").empty();

    // Show loading message
    $("#w_update_form_tt").html(
      '<div style="text-align:center;padding:20px;">Đang tải...</div>'
    );

    // Load the update form using AJAX into the form div, not the entire container
    $.ajax({
      url: "./elements_LQA/mthuoctinh/thuoctinhUpdate.php",
      type: "GET",
      data: { id: id },
      success: function (data) {
        $("#w_update_form_tt").html(data);

        // Bind close event to the close button in the loaded form
        $(document).on("click", "#close-btn-tt", function () {
          $("#w_update_tt").hide();
        });
      },
      error: function () {
        $("#w_update_form_tt").html(
          '<div style="text-align:center;padding:20px;color:red;">Lỗi khi tải form. Vui lòng thử lại.</div>'
        );
      },
    });

    // Close popup when clicking on background
    $(document).on("click", "#w_update_tt", function (e) {
      if ($(e.target).is("#w_update_tt")) {
        $("#w_update_tt").hide();
      }
    });
  });

  // Setup close button event for thuoctinh update
  $(document).on("click", "#w_close_btn_tt", function () {
    $("#w_update_tt").hide();
  });

  // Xử lý cập nhật người dùng
  $(document).on("click", ".update-user", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $idUser = $(this).data("userid");
    console.log("Cập nhật người dùng ID:", $idUser);

    // Chuyển đến trang cập nhật người dùng
    window.location.href = "index.php?req=userupdate&iduser=" + $idUser;
  });

  // Xử lý chung cho các nút update generic
  $(document).on("click", ".generic-update-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    let module = $(this).data("module");
    let id = $(this).data("id");
    let idParam = $(this).data("id-param") || "id";
    let updateUrl = $(this).data("update-url");
    let title = $(this).data("title") || "Cập nhật";

    console.log(
      "Cập nhật " + module + " với ID: " + id + ", dùng param: " + idParam
    );

    if (module === "mhanghoa") {
      // Đã xử lý riêng bên trên
      return;
    }

    // Xác định popup ID dựa vào module
    let popupId, formId, closeBtnId;
    switch (module) {
      case "mnhanvien":
        popupId = "#w_update_nv";
        formId = "#w_update_form_nv";
        closeBtnId = "#w_close_btn_nv";
        break;
      case "mdonvitinh":
        popupId = "#w_update_dvt";
        formId = "#w_update_form_dvt";
        closeBtnId = "#w_close_btn_dvt";
        break;
      case "mthuoctinh":
        popupId = "#w_update_tt";
        formId = "#w_update_form_tt";
        closeBtnId = "#w_close_btn_tt";
        break;
      // Thêm các module khác nếu cần
      default:
        console.error("Module không được hỗ trợ: " + module);
        return;
    }

    // Hiển thị ở giữa màn hình
    var windowHeight = $(window).height();
    var windowWidth = $(window).width();
    var popupHeight = 400; // Chiều cao ước tính của popup
    var popupWidth = 600; // Chiều rộng ước tính của popup

    $(popupId).css({
      top: "50%",
      left: "50%",
      transform: "translate(-50%, -50%)",
    });

    // Clear form trước khi load
    $(formId).empty();

    // Hiển thị thông báo đang tải
    $(formId).html(
      "<div style='text-align:center;padding:20px;'>Đang tải...</div>"
    );

    // Hiển thị popup ngay lập tức để người dùng thấy phản hồi
    $(popupId).show();

    // Chuẩn bị dữ liệu gửi đi
    var postData = {};
    postData[idParam] = id;

    // Load form cập nhật
    $(formId).load(updateUrl, postData, function (response, status, request) {
      if (status === "error") {
        $(formId).html(
          "<div style='color:red;padding:20px;'>Lỗi khi tải form: " +
            request.status +
            " " +
            request.statusText +
            "</div>"
        );
        return;
      }

      // Gán sự kiện cho nút đóng trong form
      $(formId + " #close-btn").on("click", function () {
        $(popupId).hide();
      });

      console.log("Form " + title + " đã được tải");
    });
  });
});
