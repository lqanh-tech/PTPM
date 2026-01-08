<?php

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($shippingMethods as &$method) {
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
}
?>

<!-- Phương thức vận chuyển - Đồng bộ với quản lý -->
<div class="card mb-4" id="shipping-method-section">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h5>
    </div>
    <div class="card-body">
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
                    <?php foreach ($shippingMethods as $index => $method): 
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
                                   <?php echo $isSelected ? 'checked' : ''; ?>"
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

<!-- Modal chi tiết phí vận chuyển -->
<div id="shippingDetailModal" class="shipping-detail-modal">
    <div class="shipping-detail-content">
        <div class="shipping-detail-header">
            <h5 class="mb-0" id="detailMethodName"></h5>
            <button type="button" onclick="closeShippingDetail()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">
                &times;
            </button>
        </div>
        <div class="shipping-detail-body">
            <div class="detail-row">
                <span class="detail-label">Phí cơ bản:</span>
                <span class="detail-value" id="detailBaseFee"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phí theo trọng lượng:</span>
                <span class="detail-value" id="detailWeightFee"></span>
            </div>
            <div class="detail-row" id="detailWeightBreakdown" style="border-bottom: none; padding: 5px 0; color: #6c757d; font-size: 12px;">
            </div>
            <div style="border-top: 1px solid #e9ecef; margin: 10px 0;"></div>
            <div class="detail-row">
                <span class="detail-label">Tổng phí (trước ưu đãi):</span>
                <span class="detail-value" id="detailTotalBefore"></span>
            </div>
            <div class="detail-row" id="detailFreeShipNote" style="display: none; color: #28a745;">
                <span class="detail-label">Miễn phí vì điều kiện:</span>
                <span class="detail-value" id="detailFreeReason"></span>
            </div>
            <div style="border-top: 2px solid #667eea; margin: 10px 0;"></div>
            <div class="detail-row">
                <span style="font-weight: 700; color: #2c3e50;">Phí cuối cùng:</span>
                <span id="detailFinalFee" style="font-size: 18px; font-weight: 700; color: #667eea;"></span>
            </div>
        </div>
        <div class="detail-footer">
            <button type="button" onclick="closeShippingDetail()">Đóng</button>
        </div>
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
    border-bottom: 1px solid #dee2e6 !important;
}

.shipping-method-row.selected input[type="radio"] {
    accent-color: #667eea;
}

.btn-shipping-detail:hover {
    color: #5568d3;
    text-decoration: underline;
}

.shipping-detail-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.shipping-detail-modal.show {
    display: block;
}

.shipping-detail-content {
    background-color: white;
    margin: 15% auto;
    padding: 0;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    max-width: 500px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { 
        transform: translateY(-50px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

.shipping-detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.shipping-detail-body {
    padding: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6c757d;
    font-weight: 500;
}

.detail-value {
    color: #2c3e50;
    font-weight: 600;
    text-align: right;
}

.detail-footer {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 0 0 10px 10px;
    text-align: right;
}

.detail-footer button {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.detail-footer button:hover {
    background: #5568d3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.shipping-method-row');
    
    rows.forEach(row => {
        row.addEventListener('click', function(e) {

            if (e.target.closest('.btn-shipping-detail')) {
                return;
            }
            
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
            if (row) {
                selectShippingMethod(row);
            }
        });
    });
    
    const firstRow = document.querySelector('.shipping-method-row.selected');
    if (firstRow) {
        const initialFee = parseFloat(firstRow.dataset.fee) || 0;
        setTimeout(() => updateTotalAmount(initialFee), 100);
    }
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
    
    updateTotalAmount(methodFee);
    
    document.dispatchEvent(new CustomEvent('shippingMethodChanged', {
        detail: { method: methodCode, fee: methodFee }
    }));
}

function updateTotalAmount(shippingFee) {

    const shippingFeeDisplay = document.getElementById('shipping-fee-value');
    if (shippingFeeDisplay) {
        if (shippingFee === 0) {
            shippingFeeDisplay.innerHTML = '<span class="text-success">Miễn phí</span>';
        } else {
            shippingFeeDisplay.textContent = new Intl.NumberFormat('vi-VN').format(shippingFee) + ' ₫';
        }
    }
    
    const subtotal = parseFloat(document.getElementById('subtotal-value')?.textContent.replace(/[^\d]/g, '') || 0);
    const vat = parseFloat(document.getElementById('vat-value')?.textContent.replace(/[^\d]/g, '') || 0);
    const total = subtotal + vat + shippingFee;
    
    const finalTotalDisplay = document.getElementById('final-total-value');
    if (finalTotalDisplay) {
        finalTotalDisplay.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
    }
}

function showShippingDetail(event, code, name, baseFee, feePerKg, cartWeight, finalFee, isFree) {
    event.preventDefault();
    event.stopPropagation();
    
    const weightFee = parseFloat(cartWeight) * parseFloat(feePerKg);
    const totalBefore = parseFloat(baseFee) + weightFee;
    
    document.getElementById('detailMethodName').textContent = name;
    document.getElementById('detailBaseFee').textContent = new Intl.NumberFormat('vi-VN').format(baseFee) + ' ₫';
    document.getElementById('detailWeightFee').textContent = new Intl.NumberFormat('vi-VN').format(weightFee) + ' ₫';
    document.getElementById('detailWeightBreakdown').textContent = '(' + parseFloat(cartWeight) + 'kg × ' + new Intl.NumberFormat('vi-VN').format(feePerKg) + '₫/kg)';
    document.getElementById('detailTotalBefore').textContent = new Intl.NumberFormat('vi-VN').format(totalBefore) + ' ₫';
    
    const freeShipNote = document.getElementById('detailFreeShipNote');
    if (parseInt(isFree) === 1 && parseFloat(finalFee) === 0) {
        freeShipNote.style.display = 'flex';
        document.getElementById('detailFreeReason').textContent = 'Miễn phí vận chuyển';
    } else {
        freeShipNote.style.display = 'none';
    }
    
    const finalFeeDisplay = document.getElementById('detailFinalFee');
    if (parseInt(isFree) === 1) {
        finalFeeDisplay.textContent = 'Miễn phí';
        finalFeeDisplay.style.color = '#28a745';
    } else {
        finalFeeDisplay.textContent = new Intl.NumberFormat('vi-VN').format(finalFee) + ' ₫';
        finalFeeDisplay.style.color = '#667eea';
    }
    
    document.getElementById('shippingDetailModal').classList.add('show');
}

function closeShippingDetail() {
    document.getElementById('shippingDetailModal').classList.remove('show');
}

window.onclick = function(event) {
    const modal = document.getElementById('shippingDetailModal');
    if (event.target == modal) {
        closeShippingDetail();
    }
}
</script>
