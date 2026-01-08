<?php

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

$stmt = $db->query("
    SELECT * FROM v_shipping_methods_with_fees 
    WHERE is_active = 1 
    ORDER BY sort_order DESC
");

$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processedMethods = [];
foreach ($shippingMethods as $method) {

    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $method['calculated_fee'] = $result['fee'] ?? 0;
    
    $stmt = $db->prepare("
        SELECT base_fee, fee_per_kg, min_order_free_ship
        FROM shipping_fees
        WHERE shipping_method_id = ? AND is_active = 1
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$method['id']]);
    $feeDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $method['base_fee'] = $feeDetail['base_fee'] ?? 0;
    $method['fee_per_kg'] = $feeDetail['fee_per_kg'] ?? 0;
    $method['min_free_ship'] = $feeDetail['min_order_free_ship'] ?? 0;
    $method['is_free'] = ($method['calculated_fee'] == 0);
    
    $processedMethods[] = $method;
}
$shippingMethods = $processedMethods;
?>

<!-- Phương thức vận chuyển -->
<div class="card mb-4" id="shipping-method-section">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h5>
    </div>
    <div class="card-body">
        <!-- DEBUG: Total = <?php echo count($shippingMethods); ?> -->
        <div class="table-responsive">
            <table class="shipping-methods-table">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <th style="text-align: center; width: 40px; padding: 12px 8px;"></th>
                        <th style="padding: 12px 8px;">Tên</th>
                        <th style="padding: 12px 8px;">Mô tả</th>
                        <th style="padding: 12px 8px;">Thời gian giao</th>
                        <th style="text-align: right; padding: 12px 8px;">Phí hiện tại</th>
                        <th style="text-align: center; padding: 12px 8px;">Miễn phí từ</th>
                        <th style="text-align: center; padding: 12px 8px; width: 60px;">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($shippingMethods as $index => $method): 
                        $isSelected = $index === 0;
                    ?>
                    <tr class="shipping-method-row <?php echo $isSelected ? 'selected' : ''; ?>" 
                        data-method="<?php echo htmlspecialchars($method['code']); ?>"
                        data-fee="<?php echo $method['calculated_fee']; ?>"
                        data-method-id="<?php echo $method['id']; ?>"
                        style="cursor: pointer; border-bottom: 1px solid #dee2e6;">
                        
                        <td style="text-align: center; padding: 12px 8px;">
                            <input type="radio" 
                                   name="shipping_method" 
                                   id="shipping_<?php echo $method['code']; ?>" 
                                   value="<?php echo htmlspecialchars($method['code']); ?>"
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   style="cursor: pointer; width: 18px; height: 18px;">
                        </td>
                        
                        <td style="padding: 12px 8px; font-weight: 600; color: #2c3e50;">
                            <?php echo htmlspecialchars($method['name']); ?>
                        </td>
                        
                        <td style="padding: 12px 8px; color: #6c757d; font-size: 13px;">
                            <?php echo htmlspecialchars($method['description'] ?? ''); ?>
                        </td>
                        
                        <td style="padding: 12px 8px; color: #6c757d; font-size: 13px;">
                            <i class="far fa-clock" style="margin-right: 5px;"></i>
                            <?php 
                            $deliveryTime = $method['delivery_time'] ?? '';
                            if ($deliveryTime && !stripos($deliveryTime, 'ngày làm việc')) {
                                echo htmlspecialchars($deliveryTime) . ' <small>(ngày làm việc)</small>';
                            } else {
                                echo htmlspecialchars($deliveryTime);
                            }
                            ?>
                        </td>
                        
                        <td style="padding: 12px 8px; text-align: right;">
                            <?php if ($method['is_free']): ?>
                                <strong style="color: #28a745; font-size: 15px;">Miễn phí</strong>
                            <?php else: ?>
                                <strong style="color: #2c3e50; font-size: 15px;">
                                    <?php echo number_format($method['calculated_fee'], 0, ',', '.'); ?>₫
                                </strong>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px 8px; text-align: center; color: #6c757d; font-size: 12px;">
                            <?php if ($method['min_free_ship'] > 0): ?>
                                <span style="color: #28a745;">
                                    <i class="fas fa-gift"></i> ≥ <?php echo number_format($method['min_free_ship'], 0, ',', '.'); ?>₫
                                </span>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px 8px; text-align: center;">
                            <button type="button" class="btn-shipping-detail" 
                                    onclick="showShippingDetail(event, '<?php echo htmlspecialchars($method['code']); ?>', '<?php echo htmlspecialchars($method['name']); ?>', '<?php echo $method['base_fee']; ?>', '<?php echo $method['fee_per_kg']; ?>', '<?php echo $cartWeight; ?>', '<?php echo $method['calculated_fee']; ?>', '<?php echo $method['is_free'] ? 1 : 0; ?>')"
                                    title="Xem chi tiết tính phí"
                                    style="background: none; border: none; color: #667eea; cursor: pointer; font-size: 13px; padding: 4px 6px;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Hidden inputs -->
        <input type="hidden" id="selected_shipping_method" name="selected_shipping_method" value="<?php echo $shippingMethods[0]['code'] ?? 'standard'; ?>">
        <input type="hidden" id="selected_shipping_fee" name="selected_shipping_fee" value="<?php echo $shippingMethods[0]['calculated_fee'] ?? 0; ?>">
    </div>
</div>

<style>
.shipping-methods-table {
    width: 100%;
    border-collapse: collapse;
}

.shipping-method-row {
    transition: all 0.2s ease;
}

.shipping-method-row:hover {
    background: #f8f9fa;
    border-left: 3px solid #667eea;
}

.shipping-method-row.selected {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    border-left: 3px solid #667eea;
}

.btn-shipping-detail:hover {
    color: #5568d3;
    text-decoration: underline;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.shipping-method-row');
    
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-shipping-detail')) return;
            if (e.target.tagName === 'INPUT' && e.target.type === 'radio') {
                selectShippingMethod(this);
                return;
            }
            selectShippingMethod(this);
        });
    });
    
    document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const row = this.closest('.shipping-method-row');
            if (row) selectShippingMethod(row);
        });
    });
});

function selectShippingMethod(row) {
    document.querySelectorAll('.shipping-method-row').forEach(r => {
        r.classList.remove('selected');
        r.querySelector('input[type="radio"]').checked = false;
    });
    
    row.classList.add('selected');
    row.querySelector('input[type="radio"]').checked = true;
    
    const methodCode = row.dataset.method;
    const methodFee = parseFloat(row.dataset.fee) || 0;
    
    document.getElementById('selected_shipping_method').value = methodCode;
    document.getElementById('selected_shipping_fee').value = methodFee;
    
    updateTotalWithShipping(methodFee);
    
    document.dispatchEvent(new CustomEvent('shippingMethodChanged', {
        detail: { method: methodCode, fee: methodFee }
    }));
}

function updateTotalWithShipping(shippingFee) {

    const shippingFeeElement = document.getElementById('shipping-fee-value');
    if (shippingFeeElement) {
        if (shippingFee === 0) {
            shippingFeeElement.innerHTML = '<span style="color: #28a745; font-weight: 600;">Miễn phí</span>';
        } else {
            shippingFeeElement.textContent = formatMoney(shippingFee) + ' ₫';
        }
    }
    
    if (typeof window.currentShippingFee !== 'undefined') {
        window.currentShippingFee = shippingFee;
    }
    
    if (typeof window.updateFinalTotal === 'function') {
        window.updateFinalTotal();
    } else {

        const subtotalElement = document.getElementById('subtotal-display');
        const vatElement = document.getElementById('vat-display');
        const totalElement = document.getElementById('final-total-display');
        
        if (subtotalElement && vatElement && totalElement) {
            const subtotal = parseFloat(subtotalElement.textContent.replace(/[^\d]/g, '')) || 0;
            const vat = parseFloat(vatElement.textContent.replace(/[^\d]/g, '')) || 0;
            const total = subtotal + vat + shippingFee;
            
            totalElement.textContent = formatMoney(total) + ' ₫';
        }
    }
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(amount));
}
</script>
