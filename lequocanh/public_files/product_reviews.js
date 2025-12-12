/**
 * Product Reviews JavaScript
 * Handles review submission, display, and interactions
 */

class ProductReviews {
    constructor(idhanghoa) {
        this.idhanghoa = idhanghoa;
        this.currentRating = 0;
        this.currentFilter = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadReviews();
    }

    setupEventListeners() {
        // Star rating input
        const stars = document.querySelectorAll('.star-rating-input .star');
        stars.forEach((star, index) => {
            star.addEventListener('click', () => this.setRating(index + 1));
            star.addEventListener('mouseenter', () => this.highlightStars(index + 1));
        });

        const ratingInput = document.querySelector('.star-rating-input');
        if (ratingInput) {
            ratingInput.addEventListener('mouseleave', () => this.highlightStars(this.currentRating));
        }

        // Review form submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitReview();
            });
        }

        // Write review button
        const writeBtn = document.getElementById('btnWriteReview');
        if (writeBtn) {
            writeBtn.addEventListener('click', () => this.showReviewForm());
        }

        // Cancel review
        const cancelBtn = document.getElementById('btnCancelReview');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hideReviewForm());
        }

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.filterReviews(e.target.dataset.rating));
        });
    }

    setRating(rating) {
        this.currentRating = rating;
        this.highlightStars(rating);
        document.getElementById('ratingValue').value = rating;
    }

    highlightStars(count) {
        const stars = document.querySelectorAll('.star-rating-input .star');
        stars.forEach((star, index) => {
            if (index < count) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    showReviewForm() {
        const form = document.getElementById('reviewFormContainer');
        if (form) {
            form.style.display = 'block';
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }

    hideReviewForm() {
        const form = document.getElementById('reviewFormContainer');
        if (form) {
            form.style.display = 'none';
            document.getElementById('reviewForm').reset();
            this.currentRating = 0;
            this.highlightStars(0);
        }
    }

    async submitReview() {
        const title = document.getElementById('reviewTitle').value;
        const text = document.getElementById('reviewText').value;
        const rating = this.currentRating;

        if (rating === 0) {
            this.showMessage('Vui lòng chọn số sao đánh giá', 'error');
            return;
        }

        if (!title.trim()) {
            this.showMessage('Vui lòng nhập tiêu đề đánh giá', 'error');
            return;
        }

        if (!text.trim()) {
            this.showMessage('Vui lòng nhập nội dung đánh giá', 'error');
            return;
        }

        try {
            const response = await fetch('api/submit_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    idhanghoa: this.idhanghoa,
                    rating: rating,
                    title: title,
                    text: text
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(data.message, 'success');
                this.hideReviewForm();
                this.loadReviews();
                
                // Hide write review button
                const writeBtn = document.getElementById('btnWriteReview');
                if (writeBtn) {
                    writeBtn.style.display = 'none';
                }
            } else {
                this.showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            this.showMessage('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        }
    }

    async loadReviews(ratingFilter = null) {
        const container = document.getElementById('reviewsList');
        if (!container) return;

        container.innerHTML = '<div class="reviews-loading"><div class="spinner"></div></div>';

        try {
            const params = new URLSearchParams({
                idhanghoa: this.idhanghoa
            });

            if (ratingFilter) {
                params.append('rating', ratingFilter);
            }

            const response = await fetch(`api/get_product_reviews.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.displayReviews(data.reviews);
                this.updateRatingStats(data.stats);
            } else {
                container.innerHTML = '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> Không thể tải đánh giá</div>';
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
            container.innerHTML = '<div class="alert-error"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối</div>';
        }
    }

    displayReviews(reviews) {
        const container = document.getElementById('reviewsList');
        
        if (reviews.length === 0) {
            container.innerHTML = `
                <div class="reviews-empty">
                    <i class="fas fa-comments"></i>
                    <h4>Chưa có đánh giá nào</h4>
                    <p>Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = reviews.map(review => this.createReviewCard(review)).join('');

        // Add helpful button event listeners
        document.querySelectorAll('.btn-helpful').forEach(btn => {
            btn.addEventListener('click', () => this.markHelpful(btn.dataset.reviewId));
        });
    }

    createReviewCard(review) {
        const stars = this.generateStars(review.rating);
        const initials = review.hoten.split(' ').map(n => n[0]).join('').substring(0, 2);
        const date = new Date(review.created_at).toLocaleDateString('vi-VN');
        const verifiedBadge = review.is_verified_purchase == 1 
            ? '<span class="verified-badge"><i class="fas fa-check-circle"></i> Đã mua hàng</span>' 
            : '';

        return `
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">${initials}</div>
                        <div class="reviewer-details">
                            <h5>${this.escapeHtml(review.hoten)}</h5>
                            <div class="review-date">${date}</div>
                        </div>
                    </div>
                    <div class="review-rating">
                        <div class="stars">${stars}</div>
                        ${verifiedBadge}
                    </div>
                </div>
                <div class="review-content">
                    <div class="review-title">${this.escapeHtml(review.review_title)}</div>
                    <div class="review-text">${this.escapeHtml(review.review_text)}</div>
                </div>
                <div class="review-footer">
                    <button class="btn-helpful" data-review-id="${review.id}">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Hữu ích</span>
                        <span class="helpful-count">(${review.helpful_count})</span>
                    </button>
                </div>
            </div>
        `;
    }

    async markHelpful(reviewId) {
        try {
            const response = await fetch('api/mark_helpful.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ review_id: reviewId })
            });

            const data = await response.json();

            if (data.success) {
                this.loadReviews(this.currentFilter);
            } else {
                this.showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error marking helpful:', error);
        }
    }

    filterReviews(rating) {
        this.currentFilter = rating;
        
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        if (rating) {
            event.target.classList.add('active');
            this.loadReviews(rating);
        } else {
            document.querySelector('.filter-btn[data-rating=""]').classList.add('active');
            this.loadReviews();
        }
    }

    updateRatingStats(stats) {
        if (!stats) return;

        // Update average rating
        const avgElement = document.getElementById('avgRating');
        if (avgElement) {
            avgElement.textContent = stats.avg_rating || 0;
        }

        // Update total reviews
        const totalElement = document.getElementById('totalReviews');
        if (totalElement) {
            totalElement.textContent = `${stats.total_reviews || 0} đánh giá`;
        }

        // Update rating distribution bars
        this.updateRatingBar(5, stats.five_star_percent || 0, stats.five_star || 0);
        this.updateRatingBar(4, stats.four_star_percent || 0, stats.four_star || 0);
        this.updateRatingBar(3, stats.three_star_percent || 0, stats.three_star || 0);
        this.updateRatingBar(2, stats.two_star_percent || 0, stats.two_star || 0);
        this.updateRatingBar(1, stats.one_star_percent || 0, stats.one_star || 0);
    }

    updateRatingBar(stars, percent, count) {
        const fill = document.getElementById(`bar${stars}StarFill`);
        const countElement = document.getElementById(`count${stars}Star`);
        
        if (fill) fill.style.width = `${percent}%`;
        if (countElement) countElement.textContent = count;
    }

    generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= rating ? '★' : '☆';
        }
        return stars;
    }

    showMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const alert = document.createElement('div');
        alert.className = alertClass;
        alert.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
        
        const container = document.getElementById('reviewFormContainer') || document.querySelector('.product-reviews-section');
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize on product pages
document.addEventListener('DOMContentLoaded', () => {
    const reviewsSection = document.querySelector('[data-product-id]');
    if (reviewsSection) {
        const productId = reviewsSection.dataset.productId;
        window.productReviews = new ProductReviews(productId);
    }
});
