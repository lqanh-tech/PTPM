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
        MIN(sf.base_fee) as min_base_fee
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

error_log("DIRECT QUERY: Total methods: " . count($shippingMethods));
foreach ($shippingMethods as $idx => $m) {
    error_log("  [$idx] ID:" . $m['id'] . " Code:" . $m['code'] . " Name:" . $m['name'] . " Sort:" . $m['sort_order']);
}

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

<!-- Phương thức vận chuyển V2 -->
<div class="card mb-4" id="shipping-method-section">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h5>
    </div>
    <div class="card-body">
        <!-- DEBUG: Total methods to render: <?php echo count($shippingMethods); ?> -->
        <div class="shipping-methods-container">
            <?php foreach ($shippingMethods as $index => $method): ?>
            <!-- DEBUG: #<?php echo $index; ?> - <?php echo $method['code']; ?> - <?php echo $method['name']; ?> - Sort: <?php echo $method['sort_order']; ?> -->
            <div class="shipping-method-card <?php echo $index === 0 ? 'selected' : ''; ?>" 
                 data-method="<?php echo htmlspecialchars($method['code']); ?>"
                 data-fee="<?php echo $method['calculated_fee']; ?>"
                 data-method-id="<?php echo $method['id']; ?>">
                
                <!-- Radio button -->
                <div class="method-radio">
                    <input type="radio" 
                           name="shipping_method" 
                           id="shipping_<?php echo $method['code']; ?>" 
                           value="<?php echo htmlspecialchars($method['code']); ?>"
                           <?php echo $index === 0 ? 'checked' : ''; ?>>
                </div>
                
                <!-- Icon -->
                <div class="method-icon" style="background: <?php 
                    echo $method['code'] === 'express' ? 'linear-gradient(135deg, #fd7e14, #dc3545)' : 
                        ($method['code'] === 'pickup' ? 'linear-gradient(135deg, #28a745, #20c997)' : 
                        ($method['code'] === 'ghn' ? 'linear-gradient(135deg, #ff6600, #ff9900)' : 
                        'linear-gradient(135deg, #007bff, #0056b3)'));
                ?>;">
                    <i class="fas fa-truck"></i>
                </div>
                
                <!-- Info -->
                <div class="method-info">
                    <div class="method-name"><?php echo htmlspecialchars($method['name']); ?></div>
                    <div class="method-desc"><?php echo htmlspecialchars($method['description'] ?? ''); ?></div>
                    <div class="method-time">
                        <i class="far fa-clock"></i> 
                        <?php echo htmlspecialchars($method['delivery_time'] ?? ''); ?>
                        <?php if ($method['delivery_time'] && !stripos($method['delivery_time'], 'làm việc')): ?>
                        <small class="text-muted">(ngày làm việc)</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Fee -->
                <div class="method-fee">
                    <?php if ($method['is_free']): ?>
                        <div class="fee-amount free">Miễn phí</div>
                        <?php if ($method['min_free_ship'] > 0): ?>
                        <div class="fee-condition">
                            <i class="fas fa-gift"></i> Đơn ≥ <?php echo number_format($method['min_free_ship'], 0, ',', '.'); ?>₫
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="fee-amount"><?php echo number_format($method['calculated_fee'], 0, ',', '.'); ?>₫</div>
                        <?php if ($method['min_free_ship'] > 0): ?>
                        <div class="fee-condition">
                            Miễn phí từ <?php echo number_format($method['min_free_ship'], 0, ', '.'); ?>₫
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chi tiết tính phí (collapse) -->
                <div class="fee-details" style="display: none;">
                    <div class="fee-breakdown">
                        <div class="breakdown-title">
                            <i class="fas fa-receipt"></i> Chi tiết tính phí:
                        </div>
                        <div class="breakdown-row">
                            <span>Phí cơ bản:</span>
                            <strong><?php echo number_format($method['base_fee'], 0, ',', '.'); ?>₫</strong>
                        </div>
                        <div class="breakdown-row">
                            <span>Phí theo trọng lượng:</span>
                            <strong><?php echo number_format($cartWeight * $method['fee_per_kg'], 0, ',', '.'); ?>₫</strong>
                            <small>(<?php echo $cartWeight; ?>kg × <?php echo number_format($method['fee_per_kg'], 0, ',', '.'); ?>₫)</small>
                        </div>
                        <div class="breakdown-divider"></div>
                        <div class="breakdown-row">
                            <span>Tổng phí:</span>
                            <strong><?php echo number_format($method['base_fee'] + ($cartWeight * $method['fee_per_kg']), 0, ',', '.'); ?>₫</strong>
                        </div>
                        <?php if ($method['is_free'] && $method['min_free_ship'] > 0): ?>
                        <div class="breakdown-discount">
                            <i class="fas fa-gift"></i> Miễn phí vì đơn hàng <?php echo number_format($cartValue, 0, ',', '.'); ?>₫ ≥ <?php echo number_format($method['min_free_ship'], 0, ',', '.'); ?>₫
                        </div>
                        <?php endif; ?>
                        <div class="breakdown-final">
                            <span>Phí cuối cùng:</span>
                            <strong class="final-fee"><?php echo $method['is_free'] ? 'Miễn phí' : number_format($method['calculated_fee'], 0, ',', '.') . '₫'; ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Toggle chi tiết -->
                <button type="button" class="btn-toggle-details" onclick="toggleFeeDetails(this)">
                    <i class="fas fa-chevron-down"></i> Chi tiết
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Hidden inputs -->
        <input type="hidden" id="selected_shipping_method" name="selected_shipping_method" value="<?php echo $shippingMethods[0]['code'] ?? 'standard'; ?>">
        <input type="hidden" id="selected_shipping_fee" name="selected_shipping_fee" value="<?php echo $shippingMethods[0]['calculated_fee'] ?? 0; ?>">
    </div>
</div>

<!-- Keep all the existing CSS and JS from original file -->
<?php

$originalFile = file_get_contents(__FILE__);

?>

<style>
.shipping-methods-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.shipping-method-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.shipping-method-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.shipping-method-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.method-radio {
    margin-right: 15px;
}

.method-radio input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.method-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.method-icon i {
    font-size: 24px;
    color: white;
}

.method-info {
    flex: 1;
}

.method-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.method-desc {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 4px;
}

.method-time {
    font-size: 0.85rem;
    color: #999;
}

.method-fee {
    text-align: right;
    margin-left: 20px;
}

.fee-amount {
    font-size: 1.3rem;
    font-weight: 700;
    color: #667eea;
}

.fee-amount.free {
    color: #28a745;
}

.fee-condition {
    font-size: 0.8rem;
    color: #999;
    margin-top: 4px;
}

.btn-toggle-details {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 8px 12px;
    margin-left: auto;
}

.btn-toggle-details:hover {
    background: rgba(102, 126, 234, 0.1);
    border-radius: 6px;
}

.fee-details {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.fee-breakdown {
    font-size: 0.9rem;
}

.breakdown-title {
    font-weight: 600;
    margin-bottom: 10px;
    color: #667eea;
}

.breakdown-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
}

.breakdown-divider {
    border-top: 1px solid #dee2e6;
    margin: 10px 0;
}

.breakdown-discount {
    background: #d4edda;
    padding: 8px;
    border-radius: 4px;
    margin: 8px 0;
    color: #155724;
}

.breakdown-final {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: white;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 1.1rem;
}

.final-fee {
    color: #667eea;
}
</style>

<script>
function toggleFeeDetails(btn) {
    const card = btn.closest('.shipping-method-card');
    const details = card.querySelector('.fee-details');
    const icon = btn.querySelector('i');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        details.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.shipping-method-card');
    
    cards.forEach(card => {
        card.addEventListener('click', function() {

            cards.forEach(c => c.classList.remove('selected'));
            
            this.classList.add('selected');
            
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            document.getElementById('selected_shipping_method').value = this.dataset.method;
            document.getElementById('selected_shipping_fee').value = this.dataset.fee;
            
            const event = new CustomEvent('shippingMethodChanged', {
                detail: {
                    method: this.dataset.method,
                    fee: parseFloat(this.dataset.fee),
                    requiresAddress: this.dataset.method !== 'pickup'
                }
            });
            document.dispatchEvent(event);
        });
    });
});
</script>
