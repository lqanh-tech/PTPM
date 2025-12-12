-- Product Reviews System Database Schema
-- Phase 1: MVP Implementation

-- Create product_reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idhanghoa INT NOT NULL,
    iduser INT NOT NULL,
    idhoadon INT NULL COMMENT 'Null if not verified purchase',
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(200) NOT NULL,
    review_text TEXT NOT NULL,
    is_verified_purchase TINYINT(1) DEFAULT 0 COMMENT '1 if user actually purchased this product',
    is_approved TINYINT(1) DEFAULT 1 COMMENT 'Auto-approve in Phase 1',
    helpful_count INT DEFAULT 0 COMMENT 'Number of users who found this helpful',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE,
    FOREIGN KEY (iduser) REFERENCES user(iduser) ON DELETE CASCADE,
    FOREIGN KEY (idhoadon) REFERENCES hoadon(idhoadon) ON DELETE SET NULL,
    UNIQUE KEY unique_user_product (iduser, idhanghoa, idhoadon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX idx_product_reviews_product ON product_reviews(idhanghoa);
CREATE INDEX idx_product_reviews_user ON product_reviews(iduser);
CREATE INDEX idx_product_reviews_approved ON product_reviews(is_approved);
CREATE INDEX idx_product_reviews_rating ON product_reviews(rating);
CREATE INDEX idx_product_reviews_created ON product_reviews(created_at);

-- Create review_helpful table to track who marked reviews as helpful
CREATE TABLE IF NOT EXISTS review_helpful (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    iduser INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (iduser) REFERENCES user(iduser) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (review_id, iduser)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add order status column to hoadon table (if not exists)
-- This allows tracking when order is delivered
ALTER TABLE hoadon 
ADD COLUMN IF NOT EXISTS trangthai ENUM(
    'Đang xử lý',
    'Đã xác nhận', 
    'Đang giao',
    'Đã giao',
    'Hoàn thành',
    'Hủy'
) DEFAULT 'Đang xử lý' COMMENT 'Order status for review eligibility';

-- Add index for order status queries
CREATE INDEX IF NOT EXISTS idx_hoadon_status ON hoadon(trangthai);

-- Sample data for testing (optional - comment out if not needed)
-- INSERT INTO product_reviews (idhanghoa, iduser, idhoadon, rating, review_title, review_text, is_verified_purchase)
-- VALUES 
-- (1, 1, 1, 5, 'Sản phẩm tuyệt vời!', 'Điện thoại chất lượng cao, giao hàng nhanh, rất hài lòng!', 1),
-- (1, 2, 2, 4, 'Tốt nhưng hơi đắt', 'Sản phẩm ok, nhưng giá có vẻ cao so với thị trường', 1),
-- (2, 1, 3, 5, 'Đáng đồng tiền', 'Pin trâu, màn hình đẹp, recommend!', 1);
