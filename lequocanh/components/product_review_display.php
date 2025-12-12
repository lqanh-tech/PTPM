<?php
/**
 * Component hiển thị đánh giá sản phẩm
 * Sử dụng trong trang chi tiết sản phẩm
 */

$productId = $productId ?? $_GET['id'] ?? null;

if (!$productId) {
    return;
}
?>

<style>
.reviews-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    margin: 30px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.reviews-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.reviews-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 15px;
}

.rating-summary {
    display: flex;
    gap: 40px;
    align-items: center;
    flex-wrap: wrap;
}

.rating-overview {
    text-align: center;
}

.rating-number {
    font-size: 3rem;
    font-weight: 700;
    color: #333;
    line-height: 1;
}

.rating-stars {
    color: #ffc107;
    font-size: 1.5rem;
    margin: 10px 0;
}

.rating-count {
    color: #666;
    font-size: 0.9rem;
}

.rating-breakdown {
    flex: 1;
    min-width: 300px;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.rating-bar-label {
    min-width: 60px;
    font-size: 0.9rem;
    color: #666;
}

.rating-bar-fill {
    flex: 1;
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.rating-bar-fill-inner {
    height: 100%;
    background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
    transition: width 0.3s ease;
}

.rating-bar-count {
    min-width: 40px;
    text-align: right;
    font-size: 0.9rem;
    color: #666;
}

.reviews-list {
    margin-top: 30px;
}

.review-item {
    border-bottom: 1px solid #f0f0f0;
    padding: 25px 0;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
}

.review-user-info {
    flex: 1;
}

.review-user-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.review-date {
    font-size: 0.85rem;
    color: #999;
}

.review-rating {
    color: #ffc107;
    font-size: 1.1rem;
}

.review-content {
    margin: 15px 0;
    color: #555;
    line-height: 1.6;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 8px;
}

.review-actions {
    display: flex;
    gap: 15px;
    margin-top: 12px;
}

.review-action-btn {
    background: none;
    border: 1px solid #e0e0e0;
    padding: 6px 14px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.85rem;
    color: #666;
    transition: all 0.2s;
}

.review-action-btn:hover {
    background: #f5f5f5;
    border-color: #ccc;
}

.review-action-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
}

.pagination button {
    padding: 8px 16px;
    border: 1px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.pagination button:hover:not(:disabled) {
    background: #f5f5f5;
}

.pagination button.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-reviews {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-reviews i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}
</style>

<div class="reviews-section" id="reviewsSection">
    <div class="reviews-header">
        <h3><i class="fas fa-star text-warning"></i> Đánh giá sản phẩm</h3>
        
        <div class="rating-summary" id="ratingSummary">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="reviews-list" id="reviewsList">
        <!-- Reviews will be loaded here -->
    </div>
    
    <div class="pagination" id="reviewsPagination"></div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Báo cáo bình luận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Lý do báo cáo</label>
                    <select id="reportReason" class="form-select">
                        <option value="">Chọn lý do...</option>
                        <option value="spam">Spam</option>
                        <option value="offensive">Ngôn từ xúc phạm</option>
                        <option value="fake">Đánh giá giả mạo</option>
                        <option value="inappropriate">Nội dung không phù hợp</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả chi tiết (tùy chọn)</label>
                    <textarea id="reportDescription" class="form-control" rows="3" placeholder="Mô tả vấn đề..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="submitReport()">Gửi báo cáo</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const productId = <?php echo json_encode($productId); ?>;
    let currentPage = 1;
    
    async function loadReviews(page = 1) {
        try {
            const response = await fetch(`/lequocanh/api/product_reviews.php?action=list&product_id=${productId}&page=${page}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            renderSummary(result.data.stats);
            renderReviews(result.data.reviews);
            renderPagination(result.data.pagination);
            
            currentPage = page;
        } catch (error) {
            console.error('Load reviews error:', error);
            document.getElementById('reviewsList').innerHTML = 
                '<div class="alert alert-danger">Không thể tải đánh giá</div>';
        }
    }
    
    function renderSummary(stats) {
        const container = document.getElementById('ratingSummary');
        
        if (stats.total_reviews === 0) {
            container.innerHTML = `
                <div class="no-reviews">
                    <i class="fas fa-star"></i>
                    <p>Chưa có đánh giá nào</p>
                    <p class="text-muted">Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                </div>
            `;
            document.getElementById('reviewsList').innerHTML = '';
            document.getElementById('reviewsPagination').innerHTML = '';
            return;
        }
        
        const avgRating = parseFloat(stats.average_rating).toFixed(1);
        const totalReviews = parseInt(stats.total_reviews);
        
        container.innerHTML = `
            <div class="rating-overview">
                <div class="rating-number">${avgRating}</div>
                <div class="rating-stars">${renderStars(avgRating)}</div>
                <div class="rating-count">${totalReviews} đánh giá</div>
            </div>
            
            <div class="rating-breakdown">
                ${[5,4,3,2,1].map(star => {
                    const count = parseInt(stats[`${['', 'one', 'two', 'three', 'four', 'five'][star]}_star`] || 0);
                    const percentage = totalReviews > 0 ? (count / totalReviews * 100) : 0;
                    return `
                        <div class="rating-bar">
                            <div class="rating-bar-label">${star} <i class="fas fa-star text-warning"></i></div>
                            <div class="rating-bar-fill">
                                <div class="rating-bar-fill-inner" style="width: ${percentage}%"></div>
                            </div>
                            <div class="rating-bar-count">${count}</div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }
    
    function renderReviews(reviews) {
        const container = document.getElementById('reviewsList');
        
        if (reviews.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">Không có đánh giá nào</p>';
            return;
        }
        
        container.innerHTML = reviews.map(review => `
            <div class="review-item">
                <div class="review-header">
                    <div class="review-user">
                        <div class="review-avatar">${getInitials(review.user_name)}</div>
                        <div class="review-user-info">
                            <div class="review-user-name">
                                ${escapeHtml(review.user_name || 'Khách hàng')}
                                ${review.is_verified_purchase ? '<span class="verified-badge"><i class="fas fa-check-circle"></i> Đã mua hàng</span>' : ''}
                            </div>
                            <div class="review-date">${formatDate(review.ngay_tao)}</div>
                        </div>
                    </div>
                    <div class="review-rating">${renderStars(review.rating)}</div>
                </div>
                
                ${review.comment ? `<div class="review-content">${escapeHtml(review.comment)}</div>` : ''}
                
                <div class="review-actions">
                    <button class="review-action-btn" onclick="markHelpful(${review.id})">
                        <i class="fas fa-thumbs-up"></i> Hữu ích (${review.helpful_count || 0})
                    </button>
                    <button class="review-action-btn" onclick="reportReview(${review.id})">
                        <i class="fas fa-flag"></i> Báo cáo
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    function renderPagination(pagination) {
        const container = document.getElementById('reviewsPagination');
        
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `<button ${pagination.page === 1 ? 'disabled' : ''} onclick="loadReviews(${pagination.page - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;
        
        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                html += `<button class="${i === pagination.page ? 'active' : ''}" onclick="loadReviews(${i})">${i}</button>`;
            } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                html += '<span>...</span>';
            }
        }
        
        // Next button
        html += `<button ${pagination.page === pagination.total_pages ? 'disabled' : ''} onclick="loadReviews(${pagination.page + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;
        
        container.innerHTML = html;
    }
    
    function renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        return '★'.repeat(fullStars) + 
               (hasHalfStar ? '☆' : '') + 
               '☆'.repeat(emptyStars);
    }
    
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0][0].toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Hôm nay';
        if (diffDays === 1) return 'Hôm qua';
        if (diffDays < 7) return `${diffDays} ngày trước`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} tuần trước`;
        if (diffDays < 365) return `${Math.floor(diffDays / 30)} tháng trước`;
        return `${Math.floor(diffDays / 365)} năm trước`;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    window.markHelpful = async function(reviewId) {
        try {
            const formData = new FormData();
            formData.append('action', 'helpful');
            formData.append('review_id', reviewId);
            
            const response = await fetch('/lequocanh/api/product_reviews.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadReviews(currentPage);
            } else {
                alert(result.error);
            }
        } catch (error) {
            console.error('Mark helpful error:', error);
            alert('Có lỗi xảy ra');
        }
    };
    
    let currentReportReviewId = null;
    
    window.reportReview = function(reviewId) {
        currentReportReviewId = reviewId;
        const modal = new bootstrap.Modal(document.getElementById('reportModal'));
        modal.show();
    };
    
    window.submitReport = async function() {
        try {
            const reason = document.getElementById('reportReason').value;
            const description = document.getElementById('reportDescription').value;
            
            if (!reason) {
                alert('Vui lòng chọn lý do báo cáo');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'submit');
            formData.append('review_id', currentReportReviewId);
            formData.append('reason', reason);
            formData.append('description', description);
            
            const response = await fetch('/lequocanh/api/report_review.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
                modal.hide();
                document.getElementById('reportReason').value = '';
                document.getElementById('reportDescription').value = '';
            } else {
                alert(result.error);
            }
        } catch (error) {
            console.error('Report error:', error);
            alert('Có lỗi xảy ra');
        }
    };
    
    // Load reviews on page load
    loadReviews(1);
})();
</script>
