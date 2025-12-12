<?php
/**
 * Shipping Method Selector Component
 * Include this file in checkout.php to display shipping method options
 */

require_once __DIR__ . '/../mod/ShippingMethodCls.php';

$shippingMethodObj = new ShippingMethod();
$shippingMethods = $shippingMethodObj->getActiveMethods();
$pickupStoreInfo = $shippingMethodObj->getPickupStoreInfo();
?>

<!-- Phương thức vận chuyển -->
<div class="card mb-4" id="shipping-method-section">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h5>
    </div>
    <div class="card-body">
        <div class="shipping-methods-container">
            <?php foreach ($shippingMethods as $index => $method): ?>
            <div class="shipping-method-option <?php echo $index === 0 ? 'selected' : ''; ?>" 
                 data-method="<?php echo htmlspecialchars($method['code']); ?>"
                 data-fee="<?php echo $method['base_fee']; ?>"
                 data-requires-address="<?php echo $method['requires_address'] ?? 1; ?>">
                <div class="d-flex align-items-center">
                    <div class="shipping-method-radio">
                        <input type="radio" 
                               name="shipping_method" 
                               id="shipping_<?php echo $method['code']; ?>" 
                               value="<?php echo htmlspecialchars($method['code']); ?>"
                               <?php echo $index === 0 ? 'checked' : ''; ?>>
                    </div>
                    <div class="shipping-method-icon">
                        <i class="fas <?php echo htmlspecialchars($method['icon'] ?? 'fa-truck'); ?>"></i>
                    </div>
                    <div class="shipping-method-info flex-grow-1">
                        <div class="shipping-method-name">
                            <?php echo htmlspecialchars($method['name']); ?>
                        </div>
                        <div class="shipping-method-desc text-muted">
                            <?php echo htmlspecialchars($method['description']); ?>
                        </div>
                        <div class="shipping-method-time">
                            <small>
                                <i class="far fa-clock me-1"></i>
                                <?php 
                                if ($method['estimated_days_min'] == $method['estimated_days_max']) {
                                    if ($method['estimated_days_min'] == 0) {
                                        echo 'Nhận ngay';
                                    } else {
                                        echo $method['estimated_days_min'] . ' ngày';
                                    }
                                } else {
                                    echo $method['estimated_days_min'] . '-' . $method['estimated_days_max'] . ' ngày';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                    <div class="shipping-method-fee">
                        <?php if ($method['base_fee'] == 0): ?>
                            <span class="badge bg-success">Miễn phí</span>
                        <?php else: ?>
                            <span class="text-primary fw-bold">
                                <?php echo number_format($method['base_fee'], 0, ',', '.'); ?> ₫
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($method['code'] === 'pickup'): ?>
                <!-- Thông tin cửa hàng cho pickup -->
                <div class="pickup-store-info mt-3" style="display: none;">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fas fa-store me-2"></i><?php echo htmlspecialchars($pickupStoreInfo['name']); ?></h6>
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($pickupStoreInfo['address']); ?></p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($pickupStoreInfo['phone']); ?></p>
                        <p class="mb-0"><i class="far fa-clock me-2"></i><?php echo htmlspecialchars($pickupStoreInfo['working_hours']); ?></p>
                        <?php if (!empty($pickupStoreInfo['map_url'])): ?>
                        <a href="<?php echo htmlspecialchars($pickupStoreInfo['map_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-map me-1"></i>Xem bản đồ
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Hidden input để lưu phương thức đã chọn -->
        <input type="hidden" id="selected_shipping_method" name="selected_shipping_method" value="<?php echo $shippingMethods[0]['code'] ?? 'standard'; ?>">
        <input type="hidden" id="selected_shipping_fee" name="selected_shipping_fee" value="<?php echo $shippingMethods[0]['base_fee'] ?? 0; ?>">
    </div>
</div>

<style>
.shipping-methods-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.shipping-method-option {
    border: 2px solid #dee2e6;
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.shipping-method-option:hover {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.02);
}

.shipping-method-option.selected {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}

.shipping-method-radio {
    margin-right: 15px;
}

.shipping-method-radio input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.shipping-method-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.shipping-method-icon i {
    font-size: 1.5rem;
    color: white;
}

.shipping-method-option[data-method="express"] .shipping-method-icon {
    background: linear-gradient(135deg, #fd7e14, #dc3545);
}

.shipping-method-option[data-method="pickup"] .shipping-method-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.shipping-method-option[data-method="ghn"] .shipping-method-icon {
    background: linear-gradient(135deg, #ff6600, #ff9900);
}

.shipping-method-name {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}

.shipping-method-desc {
    font-size: 0.85rem;
}

.shipping-method-time {
    color: #666;
}

.shipping-method-fee {
    min-width: 100px;
    text-align: right;
}

.pickup-store-info {
    margin-left: 85px;
}

@media (max-width: 576px) {
    .shipping-method-option .d-flex {
        flex-wrap: wrap;
    }
    
    .shipping-method-fee {
        width: 100%;
        text-align: left;
        margin-top: 10px;
        margin-left: 85px;
    }
    
    .pickup-store-info {
        margin-left: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shippingOptions = document.querySelectorAll('.shipping-method-option');
    const selectedMethodInput = document.getElementById('selected_shipping_method');
    const selectedFeeInput = document.getElementById('selected_shipping_fee');
    
    shippingOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all
            shippingOptions.forEach(opt => {
                opt.classList.remove('selected');
                opt.querySelector('input[type="radio"]').checked = false;
                
                // Hide pickup info if exists
                const pickupInfo = opt.querySelector('.pickup-store-info');
                if (pickupInfo) pickupInfo.style.display = 'none';
            });
            
            // Add selected class to clicked option
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
            
            // Update hidden inputs
            const methodCode = this.dataset.method;
            const methodFee = parseFloat(this.dataset.fee) || 0;
            const requiresAddress = this.dataset.requiresAddress === '1';
            
            selectedMethodInput.value = methodCode;
            selectedFeeInput.value = methodFee;
            
            // Show/hide address section based on method
            const addressSection = document.getElementById('shipping-address');
            if (addressSection) {
                if (methodCode === 'pickup') {
                    addressSection.closest('.mb-3').style.display = 'none';
                } else {
                    addressSection.closest('.mb-3').style.display = 'block';
                }
            }
            
            // Show pickup store info if pickup selected
            if (methodCode === 'pickup') {
                const pickupInfo = this.querySelector('.pickup-store-info');
                if (pickupInfo) pickupInfo.style.display = 'block';
            }
            
            // Update shipping fee display
            updateShippingFeeDisplay(methodFee);
            
            // Trigger custom event for other scripts
            document.dispatchEvent(new CustomEvent('shippingMethodChanged', {
                detail: {
                    method: methodCode,
                    fee: methodFee,
                    requiresAddress: requiresAddress
                }
            }));
        });
    });
    
    // Function to update shipping fee display
    function updateShippingFeeDisplay(fee) {
        const shippingFeeValue = document.getElementById('shipping-fee-value');
        const shippingStatus = document.getElementById('shipping-status');
        
        if (shippingFeeValue) {
            if (fee === 0) {
                shippingFeeValue.innerHTML = '<span class="text-success">Miễn phí</span>';
            } else {
                shippingFeeValue.textContent = new Intl.NumberFormat('vi-VN').format(fee) + ' ₫';
            }
        }
        
        if (shippingStatus) {
            shippingStatus.textContent = 'Đã chọn';
        }
        
        // Update global shipping fee variable - sử dụng window object
        if (typeof window.currentShippingFee !== 'undefined') {
            window.currentShippingFee = fee;
        }
        
        // Update final total if function exists
        if (typeof window.updateFinalTotal === 'function') {
            window.updateFinalTotal();
        }
        
        // Cập nhật tổng tiền trực tiếp nếu các element tồn tại
        const finalTotalDisplay = document.getElementById('final-total-display');
        if (finalTotalDisplay) {
            const subtotal = parseFloat(document.getElementById('subtotal-display')?.textContent.replace(/[^\d]/g, '') || 0);
            const vat = parseFloat(document.getElementById('vat-display')?.textContent.replace(/[^\d]/g, '') || 0);
            const total = subtotal + vat + fee;
            finalTotalDisplay.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
        }
    }
    
    // Initialize with first option
    const firstOption = document.querySelector('.shipping-method-option.selected');
    if (firstOption) {
        const initialFee = parseFloat(firstOption.dataset.fee) || 0;
        // Delay để đảm bảo checkout.php đã load xong
        setTimeout(() => updateShippingFeeDisplay(initialFee), 100);
    }
});
</script>
