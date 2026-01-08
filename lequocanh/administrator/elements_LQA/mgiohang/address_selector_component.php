<!-- Address Selector Component with GHN Integration -->
<!-- Include this in your checkout page -->

<style>
    .address-selector-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .address-selector-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .address-selector-item {
        position: relative;
    }
    
    .address-selector-item select {
        width: 100%;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .address-selector-item select:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .address-selector-item label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #495057;
    }
    
    .address-selector-item select:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    
    .loading-spinner {
        position: absolute;
        right: 30px;
        top: 38px;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #0d6efd;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .saved-addresses {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
    }
    
    .saved-address-item {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .saved-address-item:hover {
        background-color: #f8f9fa;
    }
    
    .saved-address-item:last-child {
        border-bottom: none;
    }
    
    .address-badge {
        display: inline-block;
        padding: 2px 8px;
        background-color: #0d6efd;
        color: white;
        border-radius: 4px;
        font-size: 11px;
        margin-left: 5px;
    }
</style>

<div class="address-selector-container">
    <h6 class="mb-3"><i class="fas fa-map-marker-alt"></i> Chọn địa chỉ giao hàng</h6>
    
    <div class="address-selector-row">
        <!-- Province Selector -->
        <div class="address-selector-item">
            <label for="province-select">Tỉnh/Thành phố <span class="text-danger">*</span></label>
            <select id="province-select" class="form-select" required>
                <option value="">-- Chọn Tỉnh/Thành phố --</option>
            </select>
            <div class="loading-spinner" id="province-loading" style="display: none;"></div>
        </div>
        
        <!-- District Selector -->
        <div class="address-selector-item">
            <label for="district-select">Quận/Huyện <span class="text-danger">*</span></label>
            <select id="district-select" class="form-select" disabled required>
                <option value="">-- Chọn Quận/Huyện --</option>
            </select>
            <div class="loading-spinner" id="district-loading" style="display: none;"></div>
        </div>
        
        <!-- Ward Selector -->
        <div class="address-selector-item">
            <label for="ward-select">Phường/Xã <span class="text-danger">*</span></label>
            <select id="ward-select" class="form-select" disabled required>
                <option value="">-- Chọn Phường/Xã --</option>
            </select>
            <div class="loading-spinner" id="ward-loading" style="display: none;"></div>
        </div>
    </div>
    
    <!-- Detail Address -->
    <div class="mb-3">
        <label for="address-detail">Địa chỉ cụ thể <span class="text-danger">*</span></label>
        <input type="text" 
               class="form-control" 
               id="address-detail" 
               placeholder="Số nhà, tên đường..."
               value="<?php echo htmlspecialchars($userAddress); ?>"
               required>
        <small class="form-text text-muted">
            Ví dụ: Số 123, Đường Lê Lợi, hoặc Tòa nhà ABC, Tầng 5
        </small>
    </div>
    
    <!-- Contact Info -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="receiver-name">Tên người nhận <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control" 
                   id="receiver-name" 
                   placeholder="Nhập tên người nhận"
                   required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="receiver-phone">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" 
                   class="form-control" 
                   id="receiver-phone" 
                   placeholder="Nhập số điện thoại"
                   pattern="[0-9]{10,11}"
                   required>
        </div>
    </div>
    
    <!-- Full Address Display -->
    <div class="alert alert-info" id="full-address-display" style="display: none;">
        <strong><i class="fas fa-map-marked-alt"></i> Địa chỉ đầy đủ:</strong>
        <p class="mb-0 mt-2" id="full-address-text"></p>
    </div>
    
    <!-- Shipping Info Display -->
    <div id="shipping-calculation-result" style="display: none;">
        <!-- Will be populated by JavaScript -->
    </div>
</div>

<script>

const AddressSelector = (function() {
    let selectedProvince = null;
    let selectedDistrict = null;
    let selectedWard = null;
    
    const elements = {
        provinceSelect: document.getElementById('province-select'),
        districtSelect: document.getElementById('district-select'),
        wardSelect: document.getElementById('ward-select'),
        addressDetail: document.getElementById('address-detail'),
        receiverName: document.getElementById('receiver-name'),
        receiverPhone: document.getElementById('receiver-phone'),
        fullAddressDisplay: document.getElementById('full-address-display'),
        fullAddressText: document.getElementById('full-address-text'),
        shippingResult: document.getElementById('shipping-calculation-result'),
    };
    
    const loaders = {
        province: document.getElementById('province-loading'),
        district: document.getElementById('district-loading'),
        ward: document.getElementById('ward-loading'),
    };
    
    function init() {
        loadProvinces();
        setupEventListeners();
    }
    
    function setupEventListeners() {
        elements.provinceSelect.addEventListener('change', onProvinceChange);
        elements.districtSelect.addEventListener('change', onDistrictChange);
        elements.wardSelect.addEventListener('change', onWardChange);
        elements.addressDetail.addEventListener('blur', updateFullAddress);
    }
    
    async function loadProvinces() {
        try {
            showLoader('province', true);
            const response = await fetch('get_address_data.php?type=provinces');
            const result = await response.json();
            
            if (result.success && result.data) {
                populateSelect(elements.provinceSelect, result.data, 'id', 'name');
            } else {
                console.error('Failed to load provinces:', result.message);
                alert('Không thể tải danh sách Tỉnh/Thành phố. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('Error loading provinces:', error);
            alert('Lỗi khi tải danh sách Tỉnh/Thành phố.');
        } finally {
            showLoader('province', false);
        }
    }
    
    async function onProvinceChange(e) {
        const provinceId = e.target.value;
        
        resetSelect(elements.districtSelect, '-- Chọn Quận/Huyện --');
        resetSelect(elements.wardSelect, '-- Chọn Phường/Xã --');
        elements.shippingResult.style.display = 'none';
        
        if (!provinceId) {
            elements.districtSelect.disabled = true;
            elements.wardSelect.disabled = true;
            return;
        }
        
        selectedProvince = {
            id: provinceId,
            name: e.target.options[e.target.selectedIndex].text
        };
        
        try {
            showLoader('district', true);
            elements.districtSelect.disabled = true;
            
            const response = await fetch(`get_address_data.php?type=districts&province_id=${provinceId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                populateSelect(elements.districtSelect, result.data, 'id', 'name');
                elements.districtSelect.disabled = false;
            }
        } catch (error) {
            console.error('Error loading districts:', error);
            alert('Lỗi khi tải danh sách Quận/Huyện.');
        } finally {
            showLoader('district', false);
        }
        
        updateFullAddress();
    }
    
    async function onDistrictChange(e) {
        const districtId = e.target.value;
        
        resetSelect(elements.wardSelect, '-- Chọn Phường/Xã --');
        elements.shippingResult.style.display = 'none';
        
        if (!districtId) {
            elements.wardSelect.disabled = true;
            return;
        }
        
        selectedDistrict = {
            id: districtId,
            name: e.target.options[e.target.selectedIndex].text
        };
        
        try {
            showLoader('ward', true);
            elements.wardSelect.disabled = true;
            
            const response = await fetch(`get_address_data.php?type=wards&district_id=${districtId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                populateSelect(elements.wardSelect, result.data, 'code', 'name');
                elements.wardSelect.disabled = false;
            }
        } catch (error) {
            console.error('Error loading wards:', error);
            alert('Lỗi khi tải danh sách Phường/Xã.');
        } finally {
            showLoader('ward', false);
        }
        
        updateFullAddress();
    }
    
    function onWardChange(e) {
        const wardCode = e.target.value;
        
        if (wardCode) {
            selectedWard = {
                code: wardCode,
                name: e.target.options[e.target.selectedIndex].text
            };
            
            updateFullAddress();
            calculateShipping();
        }
    }
    
    function updateFullAddress() {
        const detail = elements.addressDetail.value.trim();
        const parts = [];
        
        if (detail) parts.push(detail);
        if (selectedWard) parts.push(selectedWard.name);
        if (selectedDistrict) parts.push(selectedDistrict.name);
        if (selectedProvince) parts.push(selectedProvince.name);
        
        if (parts.length > 0) {
            elements.fullAddressText.textContent = parts.join(', ');
            elements.fullAddressDisplay.style.display = 'block';
        } else {
            elements.fullAddressDisplay.style.display = 'none';
        }
    }
    
    async function calculateShipping() {
        if (!selectedDistrict || !selectedWard) {
            return;
        }
        
        try {
            elements.shippingResult.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Đang tính phí vận chuyển...</div>';
            elements.shippingResult.style.display = 'block';
            
            const response = await fetch('calculate_shipping_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    to_province_id: selectedProvince.id,
                    to_province_name: selectedProvince.name,
                    to_district_id: selectedDistrict.id,
                    to_ward_code: selectedWard.code,
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                displayShippingResult(result);
            } else {
                elements.shippingResult.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ${result.message}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error calculating shipping:', error);
            elements.shippingResult.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Lỗi khi tính phí vận chuyển
                </div>
            `;
        }
    }
    
    function displayShippingResult(result) {
        const html = `
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-shipping-fast"></i> Phí vận chuyển:</strong>
                        <h5 class="mb-0 text-primary">${result.shipping_fee_formatted}</h5>
                        <small class="text-muted">Phương thức: ${result.method_name}</small>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-clock"></i> Thời gian dự kiến:</strong>
                        <h5 class="mb-0 text-info">${result.estimated_days} ngày</h5>
                        ${result.estimated_delivery_formatted ? `<small class="text-muted">Giao trước: ${result.estimated_delivery_formatted}</small>` : ''}
                    </div>
                </div>
                ${result.distance_formatted ? `<hr><small><i class="fas fa-route"></i> Khoảng cách: ${result.distance_formatted}</small>` : ''}
            </div>
            
            <div class="alert alert-light">
                <strong>Tổng cộng thanh toán:</strong>
                <h4 class="mb-0 text-danger">${result.total_amount_formatted}</h4>
                <small class="text-muted">
                    (Tiền hàng: ${result.breakdown.subtotal_formatted} + 
                    VAT: ${result.breakdown.vat_formatted} + 
                    Vận chuyển: ${result.breakdown.shipping_formatted})
                </small>
            </div>
        `;
        
        elements.shippingResult.innerHTML = html;
    }
    
    function populateSelect(selectElement, data, valueKey, textKey) {

        selectElement.innerHTML = '<option value="">' + selectElement.options[0].text + '</option>';
        
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            selectElement.appendChild(option);
        });
    }
    
    function resetSelect(selectElement, placeholder) {
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        selectElement.disabled = true;
    }
    
    function showLoader(type, show) {
        if (loaders[type]) {
            loaders[type].style.display = show ? 'block' : 'none';
        }
    }
    
    function getSelectedAddress() {
        return {
            province_id: selectedProvince?.id,
            province_name: selectedProvince?.name,
            district_id: selectedDistrict?.id,
            district_name: selectedDistrict?.name,
            ward_code: selectedWard?.code,
            ward_name: selectedWard?.name,
            address_detail: elements.addressDetail.value.trim(),
            receiver_name: elements.receiverName.value.trim(),
            receiver_phone: elements.receiverPhone.value.trim(),
            full_address: elements.fullAddressText.textContent,
        };
    }
    
    function validate() {
        const errors = [];
        
        if (!selectedProvince) errors.push('Vui lòng chọn Tỉnh/Thành phố');
        if (!selectedDistrict) errors.push('Vui lòng chọn Quận/Huyện');
        if (!selectedWard) errors.push('Vui lòng chọn Phường/Xã');
        if (!elements.addressDetail.value.trim()) errors.push('Vui lòng nhập địa chỉ cụ thể');
        if (!elements.receiverName.value.trim()) errors.push('Vui lòng nhập tên người nhận');
        if (!elements.receiverPhone.value.trim()) errors.push('Vui lòng nhập số điện thoại');
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    return {
        init,
        getSelectedAddress,
        validate,
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    AddressSelector.init();
    
    window.AddressSelector = AddressSelector;
});
</script>
