<?php

?>

<style>
.review-management {
    padding: 20px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    font-size: 2rem;
    margin: 10px 0;
    color: #333;
}

.stat-card p {
    color: #666;
    margin: 0;
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.reviews-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.review-row {
    border-bottom: 1px solid #f0f0f0;
    padding: 20px;
    display: grid;
    grid-template-columns: 60px 1fr 150px 150px;
    gap: 20px;
    align-items: start;
}

.review-row:hover {
    background: #f8f9fa;
}

.review-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
}

.review-content h4 {
    margin: 0 0 8px 0;
    font-size: 1rem;
}

.review-meta {
    display: flex;
    gap: 15px;
    margin: 8px 0;
    font-size: 0.85rem;
    color: #666;
}

.review-text {
    margin: 10px 0;
    color: #555;
}

.review-status {
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-visible {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-hidden {
    background: #fff3e0;
    color: #e65100;
}

.status-deleted {
    background: #ffebee;
    color: #c62828;
}

.review-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.btn-hide {
    background: #ff9800;
    color: white;
}

.btn-show {
    background: #4caf50;
    color: white;
}

.btn-delete {
    background: #f44336;
    color: white;
}

.btn-view {
    background: #2196f3;
    color: white;
}

.action-btn:hover {
    opacity: 0.8;
    transform: translateY(-2px);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 20px;
}

.pagination button {
    padding: 8px 16px;
    border: 1px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
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

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
}

.modal-content h3 {
    margin-top: 0;
}

.modal-content textarea {
    width: 100%;
    min-height: 100px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin: 15px 0;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.loading {
    text-align: center;
    padding: 40px;
}
</style>

<div class="review-management">
    <h1><i class="fas fa-comments"></i> Quản Lý Bình Luận</h1>
    
    <!-- Stats Cards -->
    <div class="stats-cards" id="statsCards">
        <div class="stat-card">
            <p>Tổng bình luận</p>
            <h3 id="totalReviews">-</h3>
        </div>
        <div class="stat-card">
            <p>Đang hiển thị</p>
            <h3 id="visibleReviews">-</h3>
        </div>
        <div class="stat-card">
            <p>Đã ẩn</p>
            <h3 id="hiddenReviews">-</h3>
        </div>
        <div class="stat-card">
            <p>Đã xóa</p>
            <h3 id="deletedReviews">-</h3>
        </div>
        <div class="stat-card">
            <p>Chờ duyệt</p>
            <h3 id="pendingReviews">-</h3>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters">
        <select id="statusFilter" class="form-select" style="width: 200px;">
            <option value="all">Tất cả trạng thái</option>
            <option value="visible">Đang hiển thị</option>
            <option value="hidden">Đã ẩn</option>
            <option value="deleted">Đã xóa</option>
        </select>
        
        <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm..." style="width: 300px;">
        
        <button onclick="loadReviews(1)" class="btn btn-primary">
            <i class="fas fa-search"></i> Tìm kiếm
        </button>
        
        <button onclick="loadReviews(1)" class="btn btn-secondary">
            <i class="fas fa-sync"></i> Làm mới
        </button>
    </div>
    
    <!-- Reviews Table -->
    <div class="reviews-table">
        <div id="reviewsList" class="loading">
            <div class="spinner-border" role="status"></div>
            <p>Đang tải...</p>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="pagination" id="pagination"></div>
</div>

<!-- Modal for actions -->
<div id="actionModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Xác nhận</h3>
        <p id="modalMessage"></p>
        <textarea id="modalNote" placeholder="Ghi chú (tùy chọn)"></textarea>
        <div class="modal-actions">
            <button onclick="closeModal()" class="btn btn-secondary">Hủy</button>
            <button onclick="confirmAction()" class="btn btn-primary" id="confirmBtn">Xác nhận</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentAction = null;
let currentReviewId = null;

async function loadReviews(page = 1) {
    try {
        const status = document.getElementById('statusFilter').value;
        const search = document.getElementById('searchInput').value;
        
        const url = `../api/review_management.php?action=list&page=${page}&status=${status}&search=${encodeURIComponent(search)}`;
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        renderStats(result.data.stats);
        renderReviews(result.data.reviews);
        renderPagination(result.data.pagination);
        
        currentPage = page;
    } catch (error) {
        console.error('Load reviews error:', error);
        document.getElementById('reviewsList').innerHTML = 
            '<div class="alert alert-danger m-3">Không thể tải dữ liệu</div>';
    }
}

function renderStats(stats) {
    document.getElementById('totalReviews').textContent = stats.total_reviews || 0;
    document.getElementById('visibleReviews').textContent = stats.visible_reviews || 0;
    document.getElementById('hiddenReviews').textContent = stats.hidden_reviews || 0;
    document.getElementById('deletedReviews').textContent = stats.deleted_reviews || 0;
    document.getElementById('pendingReviews').textContent = stats.pending_approval || 0;
}

function renderReviews(reviews) {
    const container = document.getElementById('reviewsList');
    
    if (reviews.length === 0) {
        container.innerHTML = '<p class="text-center p-4">Không có bình luận nào</p>';
        return;
    }
    
    container.innerHTML = reviews.map(review => `
        <div class="review-row">
            <div class="review-avatar">${getInitials(review.user_name)}</div>
            
            <div class="review-content">
                <h4>${escapeHtml(review.product_name || 'Sản phẩm')}</h4>
                <div class="review-meta">
                    <span><i class="fas fa-user"></i> ${escapeHtml(review.user_name)}</span>
                    <span><i class="fas fa-star text-warning"></i> ${review.rating}/5</span>
                    <span><i class="fas fa-clock"></i> ${formatDate(review.ngay_tao)}</span>
                    ${review.report_count > 0 ? `<span class="text-danger"><i class="fas fa-flag"></i> ${review.report_count} khiếu nại</span>` : ''}
                </div>
                <div class="review-text">${escapeHtml(review.comment || 'Không có nội dung')}</div>
                ${review.admin_note ? `<div class="text-muted small"><i class="fas fa-info-circle"></i> ${escapeHtml(review.admin_note)}</div>` : ''}
            </div>
            
            <div class="review-status">
                <span class="status-badge status-${review.status}">${getStatusText(review.status)}</span>
            </div>
            
            <div class="review-actions">
                ${review.status === 'visible' ? 
                    `<button onclick="showActionModal('hide', ${review.id})" class="action-btn btn-hide">
                        <i class="fas fa-eye-slash"></i> Ẩn
                    </button>` : ''}
                ${review.status === 'hidden' ? 
                    `<button onclick="showActionModal('show', ${review.id})" class="action-btn btn-show">
                        <i class="fas fa-eye"></i> Hiện
                    </button>` : ''}
                ${review.status !== 'deleted' ? 
                    `<button onclick="showActionModal('delete', ${review.id})" class="action-btn btn-delete">
                        <i class="fas fa-trash"></i> Xóa
                    </button>` : ''}
            </div>
        </div>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    html += `<button ${pagination.page === 1 ? 'disabled' : ''} onclick="loadReviews(${pagination.page - 1})">
        <i class="fas fa-chevron-left"></i>
    </button>`;
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            html += `<button class="${i === pagination.page ? 'active' : ''}" onclick="loadReviews(${i})">${i}</button>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += '<span>...</span>';
        }
    }
    
    html += `<button ${pagination.page === pagination.total_pages ? 'disabled' : ''} onclick="loadReviews(${pagination.page + 1})">
        <i class="fas fa-chevron-right"></i>
    </button>`;
    
    container.innerHTML = html;
}

function showActionModal(action, reviewId) {
    currentAction = action;
    currentReviewId = reviewId;
    
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('modalTitle');
    const message = document.getElementById('modalMessage');
    
    if (action === 'hide') {
        title.textContent = 'Ẩn bình luận';
        message.textContent = 'Bạn có chắc muốn ẩn bình luận này?';
    } else if (action === 'show') {
        title.textContent = 'Hiện bình luận';
        message.textContent = 'Bạn có chắc muốn hiện bình luận này?';
    } else if (action === 'delete') {
        title.textContent = 'Xóa bình luận';
        message.textContent = 'Bạn có chắc muốn xóa bình luận này? Hành động này không thể hoàn tác.';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('actionModal').classList.remove('active');
    document.getElementById('modalNote').value = '';
    currentAction = null;
    currentReviewId = null;
}

async function confirmAction() {
    if (!currentAction || !currentReviewId) return;
    
    try {
        const note = document.getElementById('modalNote').value;
        const formData = new FormData();
        formData.append('review_id', currentReviewId);
        formData.append('note', note);
        
        if (currentAction === 'delete') {
            formData.append('action', 'delete');
        } else {
            formData.append('action', 'toggle_visibility');
            formData.append('action_type', currentAction);
        }
        
        const response = await fetch('../api/review_management.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadReviews(currentPage);
            alert(result.data.message);
        } else {
            alert(result.error);
        }
    } catch (error) {
        console.error('Action error:', error);
        alert('Có lỗi xảy ra');
    }
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function getStatusText(status) {
    const statusMap = {
        'visible': 'Đang hiển thị',
        'hidden': 'Đã ẩn',
        'deleted': 'Đã xóa'
    };
    return statusMap[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadReviews(1);

setInterval(() => loadReviews(currentPage), 30000);
</script>
