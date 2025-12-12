-- Create tables for banners, news, and promotional offers

-- Table for banners (slides on homepage)
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) NOT NULL,
    link_url VARCHAR(500),
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for news/articles
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    summary TEXT,
    content TEXT NOT NULL,
    featured_image VARCHAR(500),
    author VARCHAR(100) DEFAULT 'Admin',
    is_published BOOLEAN DEFAULT FALSE,
    published_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for promotional offers (for displaying discounted products)
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_percent DECIMAL(5,2),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data for banners
INSERT INTO banners (title, description, image_url, link_url, position, is_active) VALUES
('Khuyến mãi lớn mùa hè', 'Giảm giá lên đến 50% cho các sản phẩm điện thoại', '/administrator/uploads/banner_summer_sale.jpg', '#', 1, TRUE),
('Mua 1 tặng 1', 'Chương trình khuyến mãi đặc biệt trong tuần này', '/administrator/uploads/banner_buy_one_get_one.jpg', '#', 2, TRUE),
('Sản phẩm mới', 'Các sản phẩm mới nhất với công nghệ tiên tiến', '/administrator/uploads/banner_new_products.jpg', '#', 3, TRUE);

-- Insert sample data for news
INSERT INTO news (title, slug, summary, content, featured_image, author, is_published, published_date) VALUES
('Công nghệ mới trong điện thoại thông minh 2024', 'cong-nghe-moi-trong-dien-thoai-thong-minh-2024', 'Các xu hướng công nghệ mới nhất trong ngành điện thoại di động năm 2024...', 'Các xu hướng công nghệ mới nhất trong ngành điện thoại di động năm 2024...', '/administrator/uploads/news_tech_2024.jpg', 'Admin', TRUE, NOW()),
('Chương trình khuyến mãi đặc biệt', 'chuong-trinh-khuyen-mai-dac-biet', 'Chúng tôi xin thông báo về chương trình khuyến mãi lớn trong tháng này...', 'Chúng tôi xin thông báo về chương trình khuyến mãi lớn trong tháng này...', NULL, 'Admin', TRUE, NOW()),
('Tuyển dụng nhân sự', 'tuyen-dung-nhan-su', 'Công ty chúng tôi đang có nhu cầu tuyển dụng các vị trí nhân sự...', 'Công ty chúng tôi đang có nhu cầu tuyển dụng các vị trí nhân sự...', NULL, 'Admin', FALSE, NULL);

-- Insert sample data for promotions
INSERT INTO promotions (title, description, discount_percent, start_date, end_date, is_active) VALUES
('Giảm giá mùa hè', 'Giảm 20% cho tất cả các sản phẩm', 20.00, '2024-06-01', '2024-08-31', TRUE),
('Mua sắm cuối năm', 'Giảm giá lớn cho các sản phẩm cuối năm', 25.00, '2024-11-01', '2024-12-31', FALSE),
('Khuyến mãi đặc biệt', 'Ưu đãi đặc biệt cho khách hàng thân thiết', 15.00, '2024-01-01', '2024-01-31', FALSE);