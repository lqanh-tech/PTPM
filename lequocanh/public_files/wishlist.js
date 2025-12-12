/**
 * Wishlist JavaScript
 * Quản lý danh sách yêu thích
 */

const Wishlist = {
    apiUrl: 'api/wishlist.php',
    cartApiUrl: 'api/cart.php',
    dropdownOpen: false,
    
    /**
     * Khởi tạo
     */
    init: function() {
        this.loadWishlistCount();
        this.loadWishlistSection();
        this.initProductButtons();
        this.initDropdownClose();
    },
    
    /**
     * Khởi tạo sự kiện khi dropdown mở
     */
    initDropdownClose: function() {
        // Load content khi Bootstrap dropdown mở
        const dropdownBtn = document.getElementById('wishlistDropdownBtn');
        if (dropdownBtn) {
            // Sử dụng click event thay vì shown.bs.dropdown
            dropdownBtn.addEventListener('click', () => {
                // Delay nhỏ để dropdown mở trước
                setTimeout(() => {
                    this.loadDropdownContent();
                }, 100);
            });
        }
    },
    
    /**
     * Load nội dung dropdown
     */
    loadDropdownContent: async function() {
        const content = document.getElementById('wishlistDropdownContent');
        if (!content) return;
        
        content.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
        
        try {
            const response = await fetch(`${this.apiUrl}?action=list`, {
                credentials: 'include'
            });
            const result = await response.json();
            
            if (result.success) {
                this.renderDropdownContent(content, result.data.items);
            } else {
                content.innerHTML = '<div class="wishlist-dropdown-empty"><i class="far fa-heart"></i><p>Không thể tải dữ liệu</p></div>';
            }
        } catch (error) {
            console.error('Error loading wishlist dropdown:', error);
            content.innerHTML = '<div class="wishlist-dropdown-empty"><i class="fas fa-exclamation-circle"></i><p>Có lỗi xảy ra</p></div>';
        }
    },
    
    /**
     * Render nội dung dropdown
     */
    renderDropdownContent: function(container, items) {
        if (items.length === 0) {
            container.innerHTML = `
                <div class="wishlist-dropdown-empty">
                    <i class="far fa-heart"></i>
                    <p>Chưa có sản phẩm yêu thích</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        items.slice(0, 5).forEach(item => {
            const imageUrl = item.hinhanh_url || 'https://via.placeholder.com/60x60/f8f9fa/999?text=No';
            const price = this.formatCurrency(item.display_price);
            
            html += `
                <div class="wishlist-dropdown-item" data-product-id="${item.product_id}">
                    <button class="wishlist-dropdown-item-remove" onclick="Wishlist.removeFromDropdown(${item.product_id})" title="Xóa">
                        <i class="fas fa-times"></i>
                    </button>
                    <img src="${imageUrl}" alt="${this.escapeHtml(item.tenhanghoa)}">
                    <div class="wishlist-dropdown-item-info">
                        <div class="wishlist-dropdown-item-name" title="${this.escapeHtml(item.tenhanghoa)}">${this.escapeHtml(item.tenhanghoa)}</div>
                        <div class="wishlist-dropdown-item-price">${price}</div>
                        <div class="wishlist-dropdown-item-actions">
                            <a href="index.php?reqHanghoa=${item.product_id}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="event.stopPropagation(); Wishlist.addToCart(${item.product_id});">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        if (items.length > 5) {
            html += `<div class="text-center text-muted py-2" style="font-size: 0.85rem;">Và ${items.length - 5} sản phẩm khác...</div>`;
        }
        
        container.innerHTML = html;
    },
    
    /**
     * Xóa từ dropdown
     */
    removeFromDropdown: async function(productId) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update badge
                this.updateBadge(result.data.count);
                
                // Remove from dropdown
                const item = document.querySelector(`.wishlist-dropdown-item[data-product-id="${productId}"]`);
                if (item) {
                    item.style.animation = 'fadeOut 0.2s ease';
                    setTimeout(() => {
                        item.remove();
                        // Check if empty
                        const content = document.getElementById('wishlistDropdownContent');
                        if (content && content.querySelectorAll('.wishlist-dropdown-item').length === 0) {
                            content.innerHTML = '<div class="wishlist-dropdown-empty"><i class="far fa-heart"></i><p>Chưa có sản phẩm yêu thích</p></div>';
                        }
                    }, 200);
                }
                
                // Update product button
                const btn = document.querySelector(`.product-wishlist-btn[data-product-id="${productId}"]`);
                if (btn) {
                    btn.classList.remove('active');
                    btn.innerHTML = '<i class="far fa-heart"></i>';
                }
                
                // Reload section
                this.loadWishlistSection();
                
                this.showToast(result.data.message, 'success');
            }
        } catch (error) {
            console.error('Error removing from wishlist:', error);
        }
    },
    
    /**
     * Load số lượng wishlist
     */
    loadWishlistCount: async function() {
        try {
            const response = await fetch(`${this.apiUrl}?action=count`, {
                credentials: 'include'
            });
            const result = await response.json();
            
            if (result.success) {
                this.updateBadge(result.data.count);
            }
        } catch (error) {
            console.error('Error loading wishlist count:', error);
        }
    },
    
    /**
     * Load wishlist section trên trang chủ
     */
    loadWishlistSection: async function() {
        const container = document.getElementById('wishlistSection');
        if (!container) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=list`, {
                credentials: 'include'
            });
            const result = await response.json();
            
            if (result.success && result.data.items.length > 0) {
                container.style.display = 'block';
                this.renderWishlistSection(container, result.data.items);
            } else {
                container.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading wishlist:', error);
            container.style.display = 'none';
        }
    },
    
    /**
     * Render wishlist section
     */
    renderWishlistSection: function(container, items) {
        let html = `
            <div class="wishlist-header">
                <h4><i class="fas fa-heart"></i> Sản phẩm yêu thích của bạn</h4>
                <span class="wishlist-count">${items.length} sản phẩm</span>
            </div>
            <div class="wishlist-items">
        `;
        
        items.forEach(item => {
            const imageUrl = item.hinhanh_url || 'https://via.placeholder.com/200x140/f8f9fa/999?text=No+Image';
            const currentPrice = this.formatCurrency(item.display_price);
            const originalPrice = item.has_discount ? this.formatCurrency(item.gia) : '';
            const discountPercent = item.has_discount ? Math.round((1 - item.display_price / item.gia) * 100) : 0;
            
            // Xử lý trạng thái sản phẩm từ API
            let stockStatus = '';
            let cartBtnDisabled = '';
            let cartBtnClass = 'btn-success';
            
            if (item.status_code === 'discontinued') {
                // Ngưng bán
                stockStatus = `<span class="wishlist-stock discontinued"><i class="fas fa-ban"></i> Ngưng bán</span>`;
                cartBtnDisabled = 'disabled';
                cartBtnClass = 'btn-secondary';
            } else if (item.status_code === 'out_of_stock') {
                // Hết hàng
                stockStatus = `<span class="wishlist-stock out-of-stock"><i class="fas fa-times"></i> Hết hàng</span>`;
                cartBtnDisabled = 'disabled';
                cartBtnClass = 'btn-secondary';
            } else {
                // Còn hàng
                stockStatus = `<span class="wishlist-stock in-stock"><i class="fas fa-check"></i> Còn hàng</span>`;
            }
            
            html += `
                <div class="wishlist-item" data-product-id="${item.product_id}">
                    ${discountPercent > 0 ? `<span class="wishlist-discount-badge">-${discountPercent}%</span>` : ''}
                    <button class="wishlist-remove-btn" onclick="Wishlist.remove(${item.product_id})" title="Xóa khỏi yêu thích">
                        <i class="fas fa-times"></i>
                    </button>
                    <img src="${imageUrl}" alt="${this.escapeHtml(item.tenhanghoa)}" class="wishlist-item-image">
                    <div class="wishlist-item-info">
                        <div class="wishlist-item-name" title="${this.escapeHtml(item.tenhanghoa)}">${this.escapeHtml(item.tenhanghoa)}</div>
                        <div class="wishlist-item-price">
                            <span class="current-price">${currentPrice}</span>
                            ${originalPrice ? `<span class="original-price">${originalPrice}</span>` : ''}
                        </div>
                        ${stockStatus}
                    </div>
                    <div class="wishlist-item-actions">
                        <a href="index.php?reqHanghoa=${item.product_id}" class="btn btn-primary btn-sm wishlist-view-btn" title="Xem chi tiết sản phẩm">
                            <i class="fas fa-eye"></i> Xem
                        </a>
                        <button type="button" class="btn ${cartBtnClass} btn-sm wishlist-cart-btn" data-product-id="${item.product_id}" title="${item.can_buy ? 'Thêm vào giỏ hàng' : item.status_text}" ${cartBtnDisabled}>
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Bind event cho nút thêm giỏ hàng
        container.querySelectorAll('.wishlist-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const productId = btn.dataset.productId;
                console.log('Cart button clicked for product:', productId);
                this.addToCart(productId);
            });
        });
        
        // Đảm bảo link "Xem" không bị can thiệp
        container.querySelectorAll('.wishlist-view-btn').forEach(link => {
            link.addEventListener('click', (e) => {
                // Cho phép link hoạt động bình thường
                console.log('View button clicked, navigating to:', link.href);
            });
        });
    },
    
    /**
     * Toggle wishlist (thêm/xóa)
     */
    toggle: async function(productId, button) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('product_id', productId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update button state
                if (button) {
                    if (result.data.in_wishlist) {
                        button.classList.add('active');
                        button.innerHTML = '<i class="fas fa-heart"></i>';
                    } else {
                        button.classList.remove('active');
                        button.innerHTML = '<i class="far fa-heart"></i>';
                    }
                }
                
                // Update badge
                this.updateBadge(result.data.count);
                
                // Show toast
                this.showToast(result.data.message, 'success');
                
                // Reload wishlist section
                this.loadWishlistSection();
            } else {
                this.showToast(result.error, 'error');
            }
        } catch (error) {
            console.error('Error toggling wishlist:', error);
            this.showToast('Có lỗi xảy ra', 'error');
        }
    },
    
    /**
     * Xóa khỏi wishlist
     */
    remove: async function(productId) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update badge
                this.updateBadge(result.data.count);
                
                // Remove item from DOM
                const item = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
                if (item) {
                    item.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        item.remove();
                        // Check if empty
                        const container = document.getElementById('wishlistSection');
                        const items = container.querySelectorAll('.wishlist-item');
                        if (items.length === 0) {
                            container.style.display = 'none';
                        } else {
                            // Update count
                            const countEl = container.querySelector('.wishlist-count');
                            if (countEl) {
                                countEl.textContent = `${items.length} sản phẩm`;
                            }
                        }
                    }, 300);
                }
                
                // Update product button if exists
                const btn = document.querySelector(`.product-wishlist-btn[data-product-id="${productId}"]`);
                if (btn) {
                    btn.classList.remove('active');
                    btn.innerHTML = '<i class="far fa-heart"></i>';
                }
                
                this.showToast(result.data.message, 'success');
            } else {
                this.showToast(result.error, 'error');
            }
        } catch (error) {
            console.error('Error removing from wishlist:', error);
            this.showToast('Có lỗi xảy ra', 'error');
        }
    },
    
    /**
     * Thêm vào giỏ hàng
     */
    addToCart: async function(productId) {
        this.showToast('Đang thêm vào giỏ hàng...', 'success');
        
        try {
            const formData = new FormData();
            formData.append('idhanghoa', productId);
            formData.append('soluong', 1);
            
            const response = await fetch(`${this.cartApiUrl}?action=add`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const result = await response.json();
            console.log('Add to cart result:', result);
            
            if (result.success) {
                this.showToast('Đã thêm vào giỏ hàng!', 'success');
                // Update cart badge
                document.querySelectorAll('.fa-shopping-cart').forEach(icon => {
                    const badge = icon.closest('a')?.querySelector('.badge');
                    if (badge && result.data && result.data.cart_count) {
                        badge.textContent = result.data.cart_count;
                    }
                });
                // Reload page to update cart count after 1 second
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(result.error || 'Không thể thêm vào giỏ hàng', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showToast('Có lỗi xảy ra, đang chuyển hướng...', 'error');
            // Fallback: redirect to product page
            setTimeout(() => {
                window.location.href = `index.php?reqHanghoa=${productId}`;
            }, 1000);
        }
    },
    
    /**
     * Khởi tạo nút wishlist trên product cards
     */
    initProductButtons: async function() {
        const buttons = document.querySelectorAll('.product-wishlist-btn');
        if (buttons.length === 0) return;
        
        // Get all product IDs
        const productIds = Array.from(buttons).map(btn => btn.dataset.productId);
        
        // Check which ones are in wishlist
        for (const btn of buttons) {
            const productId = btn.dataset.productId;
            try {
                const response = await fetch(`${this.apiUrl}?action=check&product_id=${productId}`, {
                    credentials: 'include'
                });
                const result = await response.json();
                
                if (result.success && result.data.in_wishlist) {
                    btn.classList.add('active');
                    btn.innerHTML = '<i class="fas fa-heart"></i>';
                }
            } catch (error) {
                console.error('Error checking wishlist:', error);
            }
        }
    },
    
    /**
     * Update badge count
     */
    updateBadge: function(count) {
        const badges = document.querySelectorAll('.wishlist-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },
    
    /**
     * Show toast notification
     */
    showToast: function(message, type = 'success') {
        // Remove existing toast
        const existing = document.querySelector('.wishlist-toast');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = `wishlist-toast ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },
    
    /**
     * Format currency
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
    },
    
    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.8); }
    }
`;
document.head.appendChild(style);

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    Wishlist.init();
});

// Global functions for HTML onclick
function scrollToWishlistSection() {
    // Đóng dropdown
    const dropdownBtn = document.getElementById('wishlistDropdownBtn');
    if (dropdownBtn) {
        const dropdown = bootstrap.Dropdown.getInstance(dropdownBtn);
        if (dropdown) dropdown.hide();
    }
    
    const section = document.getElementById('wishlistSection');
    if (section && section.style.display !== 'none') {
        section.scrollIntoView({ behavior: 'smooth' });
    } else {
        Wishlist.showToast('Danh sách yêu thích đang trống', 'error');
    }
}
