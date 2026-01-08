<?php
require_once '../config/price_logic_config.php';

$currentConfig = PriceLogicConfig::getCurrentConfig();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i> Cấu Hình Logic Giá Sản Phẩm
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Thông tin!</h5>
                        Trang này cho phép bạn cấu hình cách hệ thống xử lý giá sản phẩm khi có phiếu nhập.
                    </div>

                    <form id="priceConfigForm" method="post" action="./elements_LQA/mprice_config/price_config_act.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Cấu Hình Cập Nhật Giá</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="auto_update_price" name="auto_update_price" value="1"
                                                       <?php echo $currentConfig['auto_update_price_on_import'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_update_price">
                                                    <strong>Tự động cập nhật giá khi duyệt phiếu nhập</strong>
                                                </label>
                                                <small class="form-text text-muted">
                                                    Nếu bật, hệ thống sẽ tự động cập nhật giá tham khảo khi duyệt phiếu nhập
                                                </small>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="override_existing" name="override_existing" value="1"
                                                       <?php echo $currentConfig['override_existing_price'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="override_existing">
                                                    <strong>Ghi đè giá đã có</strong>
                                                </label>
                                                <small class="form-text text-muted">
                                                    Nếu bật, sẽ ghi đè giá ngay cả khi sản phẩm đã có đơn giá đang áp dụng
                                                </small>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="create_price_from_import" name="create_price_from_import" value="1"
                                                       <?php echo $currentConfig['create_price_from_import'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="create_price_from_import">
                                                    <strong>Tạo đơn giá mới từ phiếu nhập</strong>
                                                </label>
                                                <small class="form-text text-muted">
                                                    Nếu bật, sẽ tự động tạo đơn giá mới cho sản phẩm chưa có giá
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Cấu Hình Lợi Nhuận</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="auto_apply_profit" name="auto_apply_profit" value="1"
                                                       <?php echo $currentConfig['auto_apply_profit_margin'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_apply_profit">
                                                    <strong>Tự động áp dụng tỷ lệ lợi nhuận</strong>
                                                </label>
                                                <small class="form-text text-muted">
                                                    Nếu bật, giá bán sẽ được tính từ giá nhập + lợi nhuận
                                                </small>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="profit_margin">Tỷ lệ lợi nhuận mặc định (%)</label>
                                            <input type="number" class="form-control" id="profit_margin" 
                                                   name="profit_margin" min="0" max="1000" step="0.1"
                                                   value="<?php echo $currentConfig['default_profit_margin']; ?>">
                                            <small class="form-text text-muted">
                                                Tỷ lệ lợi nhuận sẽ được áp dụng khi tính giá bán từ giá nhập
                                            </small>
                                        </div>

                                        <div class="alert alert-warning">
                                            <h6><i class="icon fas fa-exclamation-triangle"></i> Lưu ý:</h6>
                                            <ul class="mb-0">
                                                <li>Giá nhập: Giá mua từ nhà cung cấp</li>
                                                <li>Giá bán: Giá nhập + lợi nhuận</li>
                                                <li>Ví dụ: Giá nhập 100,000 VNĐ + 20% = Giá bán 120,000 VNĐ</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Tình Huống Thực Tế</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-box bg-info">
                                                    <span class="info-box-icon"><i class="fas fa-plus"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Sản phẩm mới</span>
                                                        <span class="info-box-number">Chưa có giá</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: 100%"></div>
                                                        </div>
                                                        <span class="progress-description">
                                                            Sẽ tạo đơn giá mới từ phiếu nhập
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-warning">
                                                    <span class="info-box-icon"><i class="fas fa-edit"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Sản phẩm có giá</span>
                                                        <span class="info-box-number">Đã có đơn giá</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: 70%"></div>
                                                        </div>
                                                        <span class="progress-description">
                                                            Tùy thuộc cấu hình "Ghi đè giá đã có"
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-success">
                                                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Giá được bảo vệ</span>
                                                        <span class="info-box-number">An toàn</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: 100%"></div>
                                                        </div>
                                                        <span class="progress-description">
                                                            Đơn giá thủ công không bị ghi đè
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Lưu Cấu Hình
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="location.reload()">
                                    <i class="fas fa-undo"></i> Hủy Bỏ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    $('#priceConfigForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi lưu cấu hình'
                });
            }
        });
    });
});
</script>
