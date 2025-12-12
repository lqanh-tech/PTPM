<?php
/**
 * Component nhập mã Coupon cho trang Checkout
 * Include vào checkout.php
 */

// Lấy thông tin coupon đã áp dụng từ session
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$couponData = $_SESSION['coupon_data'] ?? null;
?>

<!-- Mã giảm giá -->
<div class="card mb-4" id="coupon-section">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Mã giảm giá</h5>
    </div>
    <div class="card-body">
        <!-- Form nhập mã -->
        <div id="coupon-input-form" <?php echo $appliedCoupon ? 'style="display:none;"' : ''; ?>>
            <div class="input-group">
                <input type="text" class="form-control" id="coupon-code-input" 
                       placeholder="Nhập mã giảm giá" maxlength="50"
                       style="text-transform: uppercase;">
                <button type="button" class="btn btn-success" id="apply-coupon-btn">
                    <i class="fas fa-check me-1"></i>Áp dụng
                </button>
            </div>
            <div id="coupon-error" class="text-danger mt-2" style="display:none;"></div>
            
            <!-- Gợi ý mã giảm giá -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-lightbulb me-1"></i>Gợi ý: 
                    <span class="coupon-suggestion" data-code="SALE10">SALE10</span>,
                    <span class="coupon-suggestion" data-code="GIAM50K">GIAM50K</span>,
                    <span class="coupon-suggestion" data-code="NEWUSER20">NEWUSER20</span>
                </small>
            </div>
        </div>
        
        <!-- Hiển thị coupon đã áp dụng -->
        <div id="coupon-applied" <?php echo $appliedCoupon ? '' : 'style="display:none;"'; ?>>
            <div class="alert alert-success mb-0 d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-check-circle me-2"></i>
                    <strong id="applied-coupon-code"><?php echo htmlspecialchars($appliedCoupon ?? ''); ?></strong>
                    <span class="ms-2" id="applied-coupon-name">
                        <?php echo htmlspecialchars($couponData['name'] ?? ''); ?>
                    </span>
                    <br>
                    <small class="text-success">
                        Giảm: <strong id="applied-discount-amount"><?php echo number_format($couponDiscount); ?>đ</strong>
                    </small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" id="remove-coupon-btn">
                    <i class="fas fa-times"></i> Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden inputs để gửi khi submit form -->
<input type="hidden" name="coupon_code" id="coupon_code_hidden" value="<?php echo htmlspecialchars($appliedCoupon ?? ''); ?>">
<input type="hidden" name="coupon_discount" id="coupon_discount_hidden" value="<?php echo $couponDiscount; ?>">

<style>
.coupon-suggestion {
    cursor: pointer;
    color: #28a745;
    text-decoration: underline;
    transition: all 0.2s;
}
.coupon-suggestion:hover {
    color: #1e7e34;
    font-weight: bold;
}
#coupon-code-input {
    font-weight: bold;
    letter-spacing: 1px;
}
#coupon-section .card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const couponInput = document.getElementById('coupon-code-input');
    const applyBtn = document.getElementById('apply-coupon-btn');
    const removeBtn = document.getElementById('remove-coupon-btn');
    const couponError = document.getElementById('coupon-error');
    const couponInputForm = document.getElementById('coupon-input-form');
    const couponApplied = document.getElementById('coupon-applied');
    const couponCodeHidden = document.getElementById('coupon_code_hidden');
    const couponDiscountHidden = document.getElementById('coupon_discount_hidden');
    
    // Biến lưu trữ coupon discount
    window.currentCouponDiscount = <?php echo $couponDiscount; ?>;
    
    // Áp dụng mã coupon
    applyBtn.addEventListener('click', function() {
        applyCoupon();
    });
    
    // Enter để áp dụng
    couponInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoupon();
        }
    });
    
    // Click vào gợi ý
    document.querySelectorAll('.coupon-suggestion').forEach(function(el) {
        el.addEventListener('click', function() {
            couponInput.value = this.dataset.code;
            applyCoupon();
        });
    });
    
    // Xóa coupon
    removeBtn.addEventListener('click', function() {
        removeCoupon();
    });
    
    function applyCoupon() {
        const code = couponInput.value.trim().toUpperCase();
        if (!code) {
            showError('Vui lòng nhập mã giảm giá');
            return;
        }
        
        // Lấy tổng tiền hàng (subtotal)
        const subtotal = window.currentSubtotal || <?php echo $totalAmount ?? 0; ?>;
        
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
        
        fetch('./coupon_api.php?action=apply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                order_total: subtotal
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật UI
                document.getElementById('applied-coupon-code').textContent = code;
                document.getElementById('applied-discount-amount').textContent = data.data.discount_formatted;
                
                // Cập nhật hidden inputs
                couponCodeHidden.value = code;
                couponDiscountHidden.value = data.data.discount_amount;
                
                // Cập nhật biến global
                window.currentCouponDiscount = data.data.discount_amount;
                
                // Hiển thị coupon đã áp dụng
                couponInputForm.style.display = 'none';
                couponApplied.style.display = 'block';
                
                // Cập nhật tổng tiền
                updateFinalTotalWithCoupon();
                
                hideError();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Có lỗi xảy ra, vui lòng thử lại');
        })
        .finally(() => {
            applyBtn.disabled = false;
            applyBtn.innerHTML = '<i class="fas fa-check me-1"></i>Áp dụng';
        });
    }
    
    function removeCoupon() {
        fetch('./coupon_api.php?action=remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Reset UI
            couponInput.value = '';
            couponCodeHidden.value = '';
            couponDiscountHidden.value = '0';
            
            window.currentCouponDiscount = 0;
            
            couponInputForm.style.display = 'block';
            couponApplied.style.display = 'none';
            
            // Cập nhật tổng tiền
            updateFinalTotalWithCoupon();
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    function showError(message) {
        couponError.textContent = message;
        couponError.style.display = 'block';
    }
    
    function hideError() {
        couponError.style.display = 'none';
    }
    
    // Hàm cập nhật tổng tiền (gọi từ checkout.php)
    window.updateFinalTotalWithCoupon = function() {
        const subtotal = window.currentSubtotal || 0;
        const vatAmount = window.currentVatAmount || 0;
        const shippingFee = window.currentShippingFee || 0;
        const couponDiscount = window.currentCouponDiscount || 0;
        
        const finalTotal = subtotal + vatAmount + shippingFee - couponDiscount;
        
        // Cập nhật hiển thị
        const finalTotalDisplay = document.getElementById('final-total-display');
        if (finalTotalDisplay) {
            finalTotalDisplay.textContent = new Intl.NumberFormat('vi-VN').format(finalTotal) + ' ₫';
        }
        
        // Cập nhật dòng giảm giá coupon
        const couponDiscountRow = document.getElementById('coupon-discount-row');
        const couponDiscountDisplay = document.getElementById('coupon-discount-display');
        
        if (couponDiscount > 0) {
            if (couponDiscountRow) couponDiscountRow.style.display = 'table-row';
            if (couponDiscountDisplay) couponDiscountDisplay.textContent = '-' + new Intl.NumberFormat('vi-VN').format(couponDiscount) + ' ₫';
        } else {
            if (couponDiscountRow) couponDiscountRow.style.display = 'none';
        }
    };
});
</script>
