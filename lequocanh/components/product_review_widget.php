<?php
/**
 * Widget hiển thị form đánh giá sản phẩm
 * Sử dụng trong trang order success
 */

// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = $orderId ?? $_GET['order_id'] ?? null;
$userId = $_SESSION['USER'] ?? null;

if (!$orderId || !$userId) {
    return;
}
?>

<style>
.review-widget {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.review-widget h4 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.3rem;
    font-weight: 600;
}

.product-review-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.product-review-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-review-item.reviewed {
    background: #f8f9fa;
    border-color: #28a745;
}

.product-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.product-info img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
}

.product-name {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.star-rating {
    display: flex;
    gap: 8px;
    margin: 10px 0;
}

.star-rating input[type="radio"] {
    display: none;
}

.star-rating label {
    font-size: 28px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.star-rating {
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.review-comment {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
    font-size: 14px;
}

.review-comment:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.submit-review-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.submit-review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

.submit-review-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.review-success {
    background: #d4edda;
    color: #155724;
    padding: 12px;
    border-radius: 8px;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.review-error {
    background: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 8px;
    margin-top: 10px;
}

.reviewed-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #28a745;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.char-counter {
    text-align: right;
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}
</style>

<div class="review-widget" id="reviewWidget">
    <h4><i class="fas fa-star text-warning"></i> Đánh giá sản phẩm</h4>
    <p class="text-muted mb-3">Chia sẻ trải nghiệm của bạn để giúp người khác mua hàng tốt hơn</p>
    
    <div id="reviewProductsList">
        <div class="text-center py-4">
            <div class="loading-spinner"></div>
            <p class="mt-2">Đang tải...</p>
        </div>
    </div>
</div>

<script>
(function() {
    const orderId = <?php echo json_encode($orderId); ?>;
    const userId = <?php echo json_encode($userId); ?>;
    
    if (!orderId || !userId) {
        document.getElementById('reviewWidget').style.display = 'none';
        return;
    }
    
    // Load danh sách sản phẩm cần đánh giá
    async function loadProducts() {
        try {
            const response = await fetch(`/lequocanh/api/product_reviews.php?action=check&order_id=${orderId}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            if (!result.data.can_review) {
                document.getElementById('reviewWidget').style.display = 'none';
                return;
            }
            
            renderProducts(result.data.products);
        } catch (error) {
            console.error('Load products error:', error);
            document.getElementById('reviewProductsList').innerHTML = 
                '<div class="alert alert-danger">Không thể tải danh sách sản phẩm</div>';
        }
    }
    
    function renderProducts(products) {
        const container = document.getElementById('reviewProductsList');
        
        if (products.length === 0) {
            container.innerHTML = '<p class="text-muted">Không có sản phẩm nào để đánh giá</p>';
            return;
        }
        
        container.innerHTML = products.map(product => {
            if (product.reviewed) {
                return `
                    <div class="product-review-item reviewed">
                        <div class="product-info">
                            <div class="product-name">${escapeHtml(product.product_name)}</div>
                        </div>
                        <div class="reviewed-badge">
                            <i class="fas fa-check-circle"></i>
                            Đã đánh giá
                        </div>
                    </div>
                `;
            }
            
            return `
                <div class="product-review-item" data-product-id="${product.product_id}">
                    <div class="product-info">
                        <div class="product-name">${escapeHtml(product.product_name)}</div>
                    </div>
                    
                    <div class="star-rating" data-product-id="${product.product_id}">
                        ${[5,4,3,2,1].map(star => `
                            <input type="radio" 
                                   id="star${star}_${product.product_id}" 
                                   name="rating_${product.product_id}" 
                                   value="${star}">
                            <label for="star${star}_${product.product_id}">★</label>
                        `).join('')}
                    </div>
                    
                    <textarea 
                        class="review-comment" 
                        placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."
                        maxlength="500"
                        data-product-id="${product.product_id}"></textarea>
                    <div class="char-counter">
                        <span class="current">0</span>/500 ký tự
                    </div>
                    
                    <button class="submit-review-btn mt-3" 
                            onclick="submitReview(${product.product_id})"
                            data-product-id="${product.product_id}">
                        <i class="fas fa-paper-plane"></i> Gửi đánh giá
                    </button>
                    
                    <div class="review-message" data-product-id="${product.product_id}"></div>
                </div>
            `;
        }).join('');
        
        // Add character counter
        document.querySelectorAll('.review-comment').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const counter = this.parentElement.querySelector('.char-counter .current');
                counter.textContent = this.value.length;
            });
        });
    }
    
    window.submitReview = async function(productId) {
        const button = document.querySelector(`button[data-product-id="${productId}"]`);
        const messageDiv = document.querySelector(`.review-message[data-product-id="${productId}"]`);
        const ratingInput = document.querySelector(`input[name="rating_${productId}"]:checked`);
        const commentTextarea = document.querySelector(`textarea[data-product-id="${productId}"]`);
        
        // Validate
        if (!ratingInput) {
            messageDiv.innerHTML = '<div class="review-error">Vui lòng chọn số sao đánh giá</div>';
            return;
        }
        
        const rating = ratingInput.value;
        const comment = commentTextarea.value.trim();
        
        // Disable button
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Đang gửi...';
        messageDiv.innerHTML = '';
        
        try {
            const formData = new FormData();
            formData.append('action', 'submit');
            formData.append('order_id', orderId);
            formData.append('product_id', productId);
            formData.append('rating', rating);
            formData.append('comment', comment);
            
            const response = await fetch('/lequocanh/api/product_reviews.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                messageDiv.innerHTML = `
                    <div class="review-success">
                        <i class="fas fa-check-circle"></i>
                        ${result.data.message}
                    </div>
                `;
                
                // Disable form
                setTimeout(() => {
                    const item = document.querySelector(`.product-review-item[data-product-id="${productId}"]`);
                    item.classList.add('reviewed');
                    item.innerHTML = `
                        <div class="product-info">
                            <div class="product-name">${commentTextarea.closest('.product-review-item').querySelector('.product-name').textContent}</div>
                        </div>
                        <div class="reviewed-badge">
                            <i class="fas fa-check-circle"></i>
                            Đã đánh giá
                        </div>
                    `;
                }, 2000);
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            messageDiv.innerHTML = `<div class="review-error">${error.message}</div>`;
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đánh giá';
        }
    };
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Load products on page load
    loadProducts();
})();
</script>
