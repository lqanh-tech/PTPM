<?php

require_once __DIR__ . '/database.php';

class AutoFeatured {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function autoMarkBestSellers($limit = 20) {

        $this->db->exec("UPDATE hanghoa SET is_featured = 0");
        
        $sql = "UPDATE hanghoa h
                INNER JOIN (
                    SELECT ct.ma_san_pham, SUM(ct.so_luong) as total_sold
                    FROM chi_tiet_don_hang ct
                    INNER JOIN don_hang dh ON ct.ma_don_hang = dh.id
                    WHERE dh.trang_thai_thanh_toan IN ('paid', 'completed')
                    GROUP BY ct.ma_san_pham
                    ORDER BY total_sold DESC
                    LIMIT " . intval($limit) . "
                ) as top_sellers ON h.idhanghoa = top_sellers.ma_san_pham
                SET h.is_featured = 1";
        
        return $this->db->exec($sql);
    }
    
    public function autoMarkMostViewed($limit = 20) {
        $this->db->exec("UPDATE hanghoa SET is_featured = 0");
        
        $sql = "UPDATE hanghoa h
                INNER JOIN (
                    SELECT idhanghoa
                    FROM hanghoa
                    WHERE view_count > 0
                    ORDER BY view_count DESC
                    LIMIT " . intval($limit) . "
                ) as top_viewed ON h.idhanghoa = top_viewed.idhanghoa
                SET h.is_featured = 1";
        
        return $this->db->exec($sql);
    }
    
    public function autoMarkByScore($limit = 20) {
        $this->db->exec("UPDATE hanghoa SET is_featured = 0");
        
        $sql = "UPDATE hanghoa h
                INNER JOIN (
                    SELECT 
                        h.idhanghoa,
                        -- Điểm doanh số (40%)
                        COALESCE(SUM(ct.so_luong), 0) * 0.4 as sales_score,
                        -- Điểm lượt xem (30%)
                        (h.view_count * 0.3) as view_score,
                        -- Điểm mới (20%) - sản phẩm trong 30 ngày
                        CASE 
                            WHEN h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                            THEN 100 * 0.2 
                            ELSE 0 
                        END as new_score,
                        -- Điểm khuyến mãi (10%) - đang giảm giá
                        CASE 
                            WHEN h.is_sale = 1 
                            THEN 50 * 0.1 
                            ELSE 0 
                        END as sale_score,
                        -- Tổng điểm
                        (
                            COALESCE(SUM(ct.so_luong), 0) * 0.4 +
                            (h.view_count * 0.3) +
                            CASE WHEN h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 100 * 0.2 ELSE 0 END +
                            CASE WHEN h.is_sale = 1 THEN 50 * 0.1 ELSE 0 END
                        ) as total_score
                    FROM hanghoa h
                    LEFT JOIN chi_tiet_don_hang ct ON h.idhanghoa = ct.ma_san_pham
                    LEFT JOIN don_hang dh ON ct.ma_don_hang = dh.id 
                        AND (dh.trang_thai_thanh_toan IN ('paid', 'completed'))
                    GROUP BY h.idhanghoa
                    HAVING total_score > 0
                    ORDER BY total_score DESC
                    LIMIT " . intval($limit) . "
                ) as scored ON h.idhanghoa = scored.idhanghoa
                SET h.is_featured = 1";
        
        return $this->db->exec($sql);
    }
    
    public function autoMarkTrending($limit = 20) {
        $this->db->exec("UPDATE hanghoa SET is_featured = 0");
        
        $sql = "UPDATE hanghoa h
                INNER JOIN (
                    SELECT 
                        h.idhanghoa,
                        -- Doanh số 7 ngày gần đây
                        COALESCE(SUM(CASE 
                            WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                            THEN ct.so_luong 
                            ELSE 0 
                        END), 0) as recent_sales,
                        -- Doanh số 7 ngày trước đó
                        COALESCE(SUM(CASE 
                            WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
                            AND dh.ngay_tao < DATE_SUB(NOW(), INTERVAL 7 DAY)
                            THEN ct.so_luong 
                            ELSE 0 
                        END), 0) as previous_sales,
                        -- Tỷ lệ tăng trưởng
                        CASE 
                            WHEN SUM(CASE 
                                WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
                                AND dh.ngay_tao < DATE_SUB(NOW(), INTERVAL 7 DAY)
                                THEN ct.so_luong 
                                ELSE 0 
                            END) > 0
                            THEN (
                                (SUM(CASE WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN ct.so_luong ELSE 0 END) - 
                                 SUM(CASE WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND dh.ngay_tao < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN ct.so_luong ELSE 0 END)) /
                                SUM(CASE WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND dh.ngay_tao < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN ct.so_luong ELSE 0 END)
                            ) * 100
                            ELSE 0
                        END as growth_rate
                    FROM hanghoa h
                    LEFT JOIN chi_tiet_don_hang ct ON h.idhanghoa = ct.ma_san_pham
                    LEFT JOIN don_hang dh ON ct.ma_don_hang = dh.id 
                        AND (dh.trang_thai_thanh_toan IN ('paid', 'completed'))
                    GROUP BY h.idhanghoa
                    HAVING recent_sales > 0 AND growth_rate > 50
                    ORDER BY growth_rate DESC
                    LIMIT " . intval($limit) . "
                ) as trending ON h.idhanghoa = trending.idhanghoa
                SET h.is_featured = 1";
        
        return $this->db->exec($sql);
    }
    
    public function autoMarkHighMargin($limit = 20, $min_margin_percent = 30) {
        $this->db->exec("UPDATE hanghoa SET is_featured = 0");
        
        $sql = "UPDATE hanghoa h
                INNER JOIN (
                    SELECT 
                        h.idhanghoa,
                        h.giathamkhao,
                        COALESCE(AVG(pn.gianhap), 0) as avg_cost,
                        CASE 
                            WHEN AVG(pn.gianhap) > 0 
                            THEN ((h.giathamkhao - AVG(pn.gianhap)) / h.giathamkhao) * 100
                            ELSE 0
                        END as margin_percent
                    FROM hanghoa h
                    LEFT JOIN chitietphieunhap pn ON h.idhanghoa = pn.idhanghoa
                    GROUP BY h.idhanghoa
                    HAVING margin_percent >= " . intval($min_margin_percent) . "
                    ORDER BY margin_percent DESC
                    LIMIT " . intval($limit) . "
                ) as high_margin ON h.idhanghoa = high_margin.idhanghoa
                SET h.is_featured = 1";
        
        return $this->db->exec($sql);
    }
    
    public function getProductAnalytics($idhanghoa) {
        $sql = "SELECT 
                h.*,
                -- Doanh số
                COALESCE(SUM(ct.so_luong), 0) as total_sold,
                COALESCE(SUM(ct.so_luong * ct.gia), 0) as total_revenue,
                -- Lượt xem
                h.view_count,
                -- Tỷ lệ chuyển đổi
                CASE 
                    WHEN h.view_count > 0 
                    THEN (COALESCE(SUM(ct.so_luong), 0) / h.view_count) * 100
                    ELSE 0
                END as conversion_rate,
                -- Doanh số 7 ngày
                COALESCE(SUM(CASE 
                    WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                    THEN ct.so_luong 
                    ELSE 0 
                END), 0) as sales_7days,
                -- Doanh số 30 ngày
                COALESCE(SUM(CASE 
                    WHEN dh.ngay_tao >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    THEN ct.so_luong 
                    ELSE 0 
                END), 0) as sales_30days,
                -- Giá nhập trung bình
                COALESCE(AVG(pn.gianhap), 0) as avg_cost,
                -- Margin
                CASE 
                    WHEN AVG(pn.gianhap) > 0 
                    THEN ((h.giathamkhao - AVG(pn.gianhap)) / h.giathamkhao) * 100
                    ELSE 0
                END as margin_percent
                FROM hanghoa h
                LEFT JOIN chi_tiet_don_hang ct ON h.idhanghoa = ct.ma_san_pham
                LEFT JOIN don_hang dh ON ct.ma_don_hang = dh.id 
                    AND (dh.trang_thai_thanh_toan IN ('paid', 'completed'))
                LEFT JOIN chitietphieunhap pn ON h.idhanghoa = pn.idhanghoa
                WHERE h.idhanghoa = ?
                GROUP BY h.idhanghoa";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    public function getTopProducts($criteria = 'sales', $limit = 20) {

        $allowedCriteria = ['sales', 'revenue', 'views', 'conversion', 'margin'];
        if (!in_array($criteria, $allowedCriteria)) {
            $criteria = 'sales';
        }
        
        switch($criteria) {
            case 'sales':
                $orderBy = 'total_sold DESC';
                break;
            case 'revenue':
                $orderBy = 'total_revenue DESC';
                break;
            case 'views':
                $orderBy = 'view_count DESC';
                break;
            case 'conversion':
                $orderBy = 'conversion_rate DESC';
                break;
            case 'margin':
                $orderBy = 'margin_percent DESC';
                break;
            default:
                $orderBy = 'total_sold DESC';
                break;
        }
        
        $sql = "SELECT 
                h.*,
                t.tenTH as ten_thuonghieu,
                COALESCE(SUM(ct.so_luong), 0) as total_sold,
                COALESCE(SUM(ct.so_luong * ct.gia), 0) as total_revenue,
                COALESCE(h.view_count, 0) as view_count,
                CASE 
                    WHEN COALESCE(h.view_count, 0) > 0 
                    THEN (COALESCE(SUM(ct.so_luong), 0) / h.view_count) * 100
                    ELSE 0
                END as conversion_rate,
                CASE 
                    WHEN AVG(pn.gianhap) > 0 
                    THEN ((h.giathamkhao - AVG(pn.gianhap)) / h.giathamkhao) * 100
                    ELSE 0
                END as margin_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN chi_tiet_don_hang ct ON h.idhanghoa = ct.ma_san_pham
                LEFT JOIN don_hang dh ON ct.ma_don_hang = dh.id 
                    AND dh.trang_thai_thanh_toan IN ('paid', 'completed')
                LEFT JOIN chitietphieunhap pn ON h.idhanghoa = pn.idhanghoa
                GROUP BY h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.view_count, t.tenTH
                ORDER BY " . $orderBy . "
                LIMIT " . intval($limit);
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
