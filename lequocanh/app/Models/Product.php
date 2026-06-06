<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;
use Exception;

/**
 * @property int $idhanghoa
 * @property string $tenhanghoa
 * @property string $mota
 * @property float $giathamkhao
 * @property float $giakhuyenmai
 * @property int $hinhanh
 * @property int $idloaihang
 * @property int $idThuongHieu
 * @property int $idDonViTinh
 * @property int $idNhanVien
 * @property string $ghichu
 * @property int $trang_thai
 */
class Product extends BaseModel
{
    protected static $table = 'hanghoa';
    protected static $primaryKey = 'idhanghoa';
    protected static $timestamps = false;

    protected static $fillable = [
        'tenhanghoa',
        'mota',
        'giathamkhao',
        'giakhuyenmai',
        'hinhanh',
        'idloaihang',
        'idThuongHieu',
        'idDonViTinh',
        'idNhanVien',
        'ghichu',
        'trang_thai'
    ];

    protected static $hidden = [];

    // ── Status constants ──
    const STATUS_ACTIVE = 1;
    const STATUS_DISCONTINUED = 2;
    const STATUS_OUT_OF_STOCK = 3;

    // ── Legacy helper: get PDO connection ──
    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    // ── Status column detection (legacy compat) ──
    private static $statusColumnInfo = null;

    private static function getStatusColumnInfo(): array
    {
        if (self::$statusColumnInfo !== null) {
            return self::$statusColumnInfo;
        }

        self::$statusColumnInfo = ['column' => null, 'type' => null];

        try {
            $db = self::db();
            $checkNew = $db->query("SHOW COLUMNS FROM hanghoa LIKE 'trangthai'");
            if ($checkNew && $checkNew->rowCount() > 0) {
                self::$statusColumnInfo = ['column' => 'trangthai', 'type' => 'enum'];
                return self::$statusColumnInfo;
            }

            $checkLegacy = $db->query("SHOW COLUMNS FROM hanghoa LIKE 'trang_thai'");
            if ($checkLegacy && $checkLegacy->rowCount() > 0) {
                self::$statusColumnInfo = ['column' => 'trang_thai', 'type' => 'int'];
            }
        } catch (\PDOException $e) {
            error_log('Product::getStatusColumnInfo error: ' . $e->getMessage());
        }

        return self::$statusColumnInfo;
    }

    private static function buildStatusCondition(string $alias = ''): string
    {
        $info = self::getStatusColumnInfo();
        if (!$info['column']) {
            return '';
        }

        $prefix = $alias ? $alias . '.' : '';
        if ($info['column'] === 'trangthai') {
            return "({$prefix}{$info['column']} IS NULL OR {$prefix}{$info['column']} != 'ngung_ban')";
        }

        return "({$prefix}{$info['column']} IS NULL OR {$prefix}{$info['column']} != 2)";
    }

    // ═══════════════════════════════════════════
    //  GET METHODS (legacy compatible, return stdClass objects)
    // ═══════════════════════════════════════════

    /**
     * Get all products with joins (brand, unit, employee) + promotion pricing.
     * Replaces hanghoaCls::HanghoaGetAll()
     */
    public static function getAllWithPricing(): array
    {
        $db = self::db();
        $sql = 'SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                n.tenNV AS ten_nhanvien,
                CASE
                    WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != ""
                    THEN 0
                    ELSE 1
                END as image_priority,
                CASE
                    WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                    THEN h.giakhuyenmai
                    ELSE h.giathamkhao
                END as gia_hien_thi,
                CASE
                    WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                    THEN ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                LEFT JOIN nhanvien n ON h.idNhanVien = n.idNhanVien
                ORDER BY image_priority ASC, h.tenhanghoa ASC';
        $stmt = $db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all products with relations (admin view, returns Product models).
     * Used by ProductController.
     */
    public static function getAllWithRelations(): array
    {
        $sql = 'SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                n.tenNV AS ten_nhanvien,
                lh.tenloaihang AS ten_loaihang,
                CASE
                    WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != ""
                    THEN 0
                    ELSE 1
                END as image_priority
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                LEFT JOIN nhanvien n ON h.idNhanVien = n.idNhanVien
                LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                ORDER BY image_priority ASC, h.tenhanghoa ASC';

        $db = self::db();
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new static($row);
            $product->exists = true;
            $results[] = $product;
        }

        return $results;
    }

    /**
     * Get single product by ID (returns stdClass for backward compat).
     * Replaces hanghoaCls::HanghoaGetbyId()
     */
    public static function getById(int $idhanghoa): ?object
    {
        $db = self::db();
        $sql = 'SELECT idhanghoa, mahanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, ghichu, idloaihang, idNhanVien, idThuongHieu, idDonViTinh, is_featured, is_new, is_sale, sale_price, sale_percent, view_count, created_at, trang_thai FROM hanghoa WHERE idhanghoa = ?';
        $stmt = $db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get products by category with promotion pricing + status filter.
     * Replaces hanghoaCls::HanghoaGetbyIdloaihang()
     */
    public static function getByCategoryWithPricing(int $idloaihang): array
    {
        $db = self::db();
        $statusCondition = self::buildStatusCondition();

        $sql = 'SELECT idhanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, idloaihang, trang_thai, trangthai,
                CASE
                    WHEN hinhanh IS NOT NULL AND hinhanh != 0 AND hinhanh != ""
                    THEN 0 ELSE 1
                END as image_priority,
                CASE
                    WHEN giakhuyenmai IS NOT NULL AND giakhuyenmai > 0 AND giakhuyenmai < giathamkhao
                    THEN giakhuyenmai ELSE giathamkhao
                END as gia_hien_thi,
                CASE
                    WHEN giakhuyenmai IS NOT NULL AND giakhuyenmai > 0 AND giakhuyenmai < giathamkhao
                    THEN ROUND(((giathamkhao - giakhuyenmai) / giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa
                WHERE idloaihang = ?' . ($statusCondition ? " AND {$statusCondition}" : '') . '
                ORDER BY image_priority ASC, tenhanghoa ASC';


        $stmt = $db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idloaihang]);
        return $stmt->fetchAll();
    }

    /**
     * Get products by category (returns Product models).
     */
    public static function getByCategory(int $categoryId): array
    {
        return static::where('idloaihang', $categoryId);
    }

    public static function getByBrand(int $brandId): array
    {
        return static::where('idThuongHieu', $brandId);
    }

    // ═══════════════════════════════════════════
    //  SEARCH & FILTER
    // ═══════════════════════════════════════════

    /**
     * Search products by keyword (name, attributes, description).
     * Replaces hanghoaCls::searchHanghoa()
     */
    public static function searchProducts(string $keyword): array
    {
        try {
            $db = self::db();
            $searchTerm = '%' . $keyword . '%';

            $sql = "SELECT DISTINCT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                    CASE
                        WHEN LOWER(h.tenhanghoa) LIKE LOWER(:exact_keyword) THEN 1
                        WHEN LOWER(h.tenhanghoa) LIKE LOWER(:search_term) THEN 2
                        WHEN tt.tenThuocTinhHH IS NOT NULL AND LOWER(tt.tenThuocTinhHH) LIKE LOWER(:search_term) THEN 3
                        WHEN LOWER(h.mota) LIKE LOWER(:search_term) THEN 4
                        ELSE 5
                    END as search_priority,
                    CASE
                        WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != ''
                        THEN 0 ELSE 1
                    END as image_priority,
                    CASE
                        WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                        THEN h.giakhuyenmai ELSE h.giathamkhao
                    END as gia_hien_thi,
                    CASE
                        WHEN h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                        THEN ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100)
                        ELSE 0
                    END as discount_percent
                    FROM hanghoa h
                    LEFT JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
                    WHERE LOWER(h.tenhanghoa) LIKE LOWER(:search_term)
                       OR LOWER(h.mota) LIKE LOWER(:search_term)
                       OR (tt.tenThuocTinhHH IS NOT NULL AND LOWER(tt.tenThuocTinhHH) LIKE LOWER(:search_term))
                    ORDER BY search_priority ASC, image_priority ASC, h.tenhanghoa ASC
                    LIMIT 50";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':search_term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':exact_keyword', $keyword, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Product::searchProducts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Simple search (returns Product models, used by ProductController).
     */
    public static function search(string $keyword): array
    {
        $sql = "SELECT idhanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, idloaihang, trang_thai FROM " . static::getTable() . " WHERE tenhanghoa LIKE ? OR mota LIKE ?";
        $db = self::db();
        $stmt = $db->prepare($sql);
        $searchTerm = "%$keyword%";
        $stmt->execute([$searchTerm, $searchTerm]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new static($row);
            $product->exists = true;
            $results[] = $product;
        }

        return $results;
    }

    /**
     * Filter products with complex criteria (colors, sizes, price, rating).
     * Replaces hanghoaCls::filterProducts()
     */
    public static function filterProducts(array $filters): array
    {
        try {
            $db = self::db();

            // Build rating subquery
            $ratingSelect = '0 as average_rating';
            $reviewCountSelect = '0 as review_count';
            $productCol = null;

            try {
                $checkReviews = $db->query("SHOW TABLES LIKE 'product_reviews'");
                if ($checkReviews && $checkReviews->rowCount() > 0) {
                    $hasRatingCol = $db->query("SHOW COLUMNS FROM product_reviews LIKE 'rating'");
                    if ($hasRatingCol && $hasRatingCol->rowCount() > 0) {
                        $hasProductId = $db->query("SHOW COLUMNS FROM product_reviews LIKE 'product_id'");
                        $hasMaSanPham = $db->query("SHOW COLUMNS FROM product_reviews LIKE 'ma_san_pham'");

                        if ($hasProductId && $hasProductId->rowCount() > 0) {
                            $productCol = 'product_id';
                        } elseif ($hasMaSanPham && $hasMaSanPham->rowCount() > 0) {
                            $productCol = 'ma_san_pham';
                        }

                        if ($productCol) {
                            $ratingSelect = "(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = 'approved' OR pr.status IS NULL)) as average_rating";
                            $reviewCountSelect = "(SELECT COUNT(*) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = 'approved' OR pr.status IS NULL)) as review_count";
                        }
                    }
                }
            } catch (\PDOException $e) {
                $ratingSelect = '0 as average_rating';
                $reviewCountSelect = '0 as review_count';
            }

            $sql = "SELECT DISTINCT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,\n                    $ratingSelect,\n                    $reviewCountSelect\n                    FROM hanghoa h";

            $joins = [];
            $statusCondition = self::buildStatusCondition('h');
            $conditions = $statusCondition ? [$statusCondition] : [];
            $params = [];

            // Color/size filters
            if (!empty($filters['colors']) || !empty($filters['sizes'])) {
                $joins[] = 'INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa';
                $filterConditions = [];

                if (!empty($filters['colors'])) {
                    $colorAttrStmt = $db->query("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
                    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);

                    if ($colorAttr) {
                        $colorAttrId = $colorAttr['idThuocTinh'];
                        $colorMapping = [
                            'red' => 'Đỏ', 'blue' => 'Xanh dương', 'green' => 'Xanh lá',
                            'yellow' => 'Vàng', 'orange' => 'Cam', 'purple' => 'Tím',
                            'pink' => 'Hồng', 'black' => 'Đen', 'white' => 'Trắng',
                            'gray' => 'Xám', 'brown' => 'Nâu', 'silver' => 'Bạc'
                        ];

                        $colorOrConditions = [];
                        foreach ($filters['colors'] as $colorEn) {
                            $colorEn = trim($colorEn);
                            $colorVi = $colorMapping[$colorEn] ?? $colorEn;
                            $colorOrConditions[] = "LOWER(TRIM(tt.tenThuocTinhHH)) = LOWER(?)";
                            $params[] = $colorVi;
                        }

                        $filterConditions[] = "tt.idThuocTinh = $colorAttrId AND (" . implode(' OR ', $colorOrConditions) . ")";
                    }
                }

                if (!empty($filters['sizes'])) {
                    $sizeOrConditions = [];
                    foreach ($filters['sizes'] as $size) {
                        $sizeOrConditions[] = "CONCAT(',', tt.tenThuocTinhHH, ',') LIKE ?";
                        $params[] = '%,' . trim($size) . ',%';
                    }

                    $filterConditions[] = "tt.idThuocTinh IN (8, 9, 10) AND (" . implode(' OR ', $sizeOrConditions) . ")";
                }

                if (!empty($filterConditions)) {
                    $conditions[] = count($filterConditions) > 1
                        ? '(' . implode(' OR ', $filterConditions) . ')'
                        : $filterConditions[0];
                }
            }

            // Price filter
            if (isset($filters['min_price']) && isset($filters['max_price'])) {
                $conditions[] = '(CASE WHEN h.giakhuyenmai > 0 THEN h.giakhuyenmai ELSE h.giathamkhao END) BETWEEN ? AND ?';
                $params[] = $filters['min_price'];
                $params[] = $filters['max_price'];
            }

            // Category filter
            if (isset($filters['category']) && $filters['category'] > 0) {
                $conditions[] = 'h.idloaihang = ?';
                $params[] = $filters['category'];
            }

            // Rating filter
            if (isset($filters['min_rating']) && $filters['min_rating'] > 0 && $productCol) {
                $exactRating = (int)$filters['min_rating'];
                $ratingMin = $exactRating - 0.5;
                $ratingMax = $exactRating + 0.5;

                $conditions[] = "(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = 'approved' OR pr.status IS NULL)) >= ?";
                $params[] = $ratingMin;
                $conditions[] = "(SELECT COALESCE(AVG(pr.rating), 0) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = 'approved' OR pr.status IS NULL)) < ?";
                $params[] = $ratingMax;
                $conditions[] = "(SELECT COUNT(*) FROM product_reviews pr WHERE pr.$productCol = h.idhanghoa AND pr.is_approved = 1 AND (pr.status = 'approved' OR pr.status IS NULL)) > 0";
            }

            if (!empty($joins)) {
                $sql .= ' ' . implode(' ', $joins);
            }
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $sql .= ' ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != "" THEN 0 ELSE 1 END) ASC, h.tenhanghoa ASC';

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $stmt->setFetchMode(PDO::FETCH_OBJ);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Product::filterProducts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available filter options for a category.
     * Replaces hanghoaCls::getFilterOptions()
     */
    public static function getFilterOptions(?int $idloaihang = null): array
    {
        try {
            $db = self::db();
            $options = [
                'colors' => [],
                'sizes' => [],
                'price_range' => ['min' => 0, 'max' => 100000000]
            ];

            $sql = 'SELECT DISTINCT tt.tenThuocTinhHH
                    FROM thuoctinhhh tt
                    INNER JOIN hanghoa h ON tt.idhanghoa = h.idhanghoa';
            $params = [];

            if ($idloaihang) {
                $sql .= ' WHERE h.idloaihang = ?';
                $params[] = $idloaihang;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $attributes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $colorKeywords = ['màu', 'color'];
            $sizeKeywords = ['kích', 'size', 'cỡ'];

            foreach ($attributes as $attr) {
                $attrLower = mb_strtolower($attr, 'UTF-8');

                foreach ($colorKeywords as $keyword) {
                    if (mb_strpos($attrLower, $keyword) !== false) {
                        $options['colors'][] = $attr;
                        break;
                    }
                }

                foreach ($sizeKeywords as $keyword) {
                    if (mb_strpos($attrLower, $keyword) !== false) {
                        $options['sizes'][] = $attr;
                        break;
                    }
                }
            }

            return $options;
        } catch (\PDOException $e) {
            error_log("Product::getFilterOptions error: " . $e->getMessage());
            return ['colors' => [], 'sizes' => [], 'price_range' => ['min' => 0, 'max' => 100000000]];
        }
    }

    /**
     * Get related products by same category.
     * Replaces hanghoaCls::getRelatedProducts()
     */
    public static function getRelatedProducts(int $idhanghoa, int $limit = 6): array
    {
        try {
            $db = self::db();

            // Get current product
            $stmt = $db->prepare("SELECT idhanghoa, mahanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, ghichu, idloaihang, idNhanVien, idThuongHieu, idDonViTinh, is_featured, is_new, is_sale, sale_price, sale_percent, view_count, created_at, trang_thai FROM hanghoa WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $current = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$current) {
                return [];
            }

            if (!empty($current->idloaihang)) {
                $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai FROM hanghoa h
                        WHERE h.idhanghoa != ?
                        AND h.idloaihang = ?
                        AND (h.trang_thai IS NULL OR h.trang_thai != 2)
                        ORDER BY
                            CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                            h.idhanghoa DESC
                        LIMIT " . intval($limit);
                $params = [$idhanghoa, $current->idloaihang];
            } else {
                $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai FROM hanghoa h
                        WHERE h.idhanghoa != ?
                        AND h.trang_thai != 2
                        ORDER BY
                            CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END,
                            h.idhanghoa DESC
                        LIMIT " . intval($limit);
                $params = [$idhanghoa];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Product::getRelatedProducts error: " . $e->getMessage());
            return [];
        }
    }

    // ═══════════════════════════════════════════
    //  CRUD (legacy compatible)
    // ═══════════════════════════════════════════

    /**
     * Add product with legacy parameter order.
     * Replaces hanghoaCls::HanghoaAdd()
     */
    public static function addProduct(
        string $tenhanghoa,
        string $mota,
        $giathamkhao,
        $id_hinhanh,
        $idloaihang,
        $idThuongHieu,
        $idDonViTinh,
        $idNhanVien,
        string $ghichu = ''
    ) {
        try {
            $db = self::db();

            $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh;
            $idThuongHieu = ($idThuongHieu === '' || $idThuongHieu === 0 || $idThuongHieu === '0') ? null : $idThuongHieu;
            $idDonViTinh = ($idDonViTinh === '' || $idDonViTinh === 0 || $idDonViTinh === '0') ? null : $idDonViTinh;
            $idNhanVien = ($idNhanVien === '' || $idNhanVien === 0 || $idNhanVien === '0') ? null : $idNhanVien;

            if (empty($tenhanghoa) || empty($giathamkhao) || empty($idloaihang)) {
                return false;
            }

            $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu]);

            if ($result) {
                $lastId = $db->lastInsertId();

                // Create inventory record
                try {
                    $checkTonkhoTable = $db->query("SHOW TABLES LIKE 'tonkho'");
                    if ($checkTonkhoTable->rowCount() > 0) {
                        $stmtTonkho = $db->prepare("INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)");
                        $stmtTonkho->execute([$lastId]);
                    }
                } catch (\Exception $e) {
                    // non-critical
                }

                return $lastId;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Product::addProduct error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update product with legacy parameter order.
     * Replaces hanghoaCls::HanghoaUpdate()
     */
    public static function updateProduct(
        string $tenhanghoa,
        $id_hinhanh,
        string $mota,
        $giathamkhao,
        $idloaihang,
        $idThuongHieu,
        $idDonViTinh,
        $idNhanVien,
        int $idhanghoa,
        string $ghichu = ''
    ): int {
        $db = self::db();

        $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh;
        $idThuongHieu = $idThuongHieu === '' ? null : $idThuongHieu;
        $idDonViTinh = $idDonViTinh === '' ? null : $idDonViTinh;
        $idNhanVien = $idNhanVien === '' ? null : $idNhanVien;

        $sql = "UPDATE hanghoa SET tenhanghoa=?, hinhanh=?, mota=?, giathamkhao=?, idloaihang=?, idThuongHieu=?, idDonViTinh=?, idNhanVien=?, ghichu=? WHERE idhanghoa=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu, $idhanghoa]);
        return $stmt->rowCount();
    }

    /**
     * Update product price.
     * Replaces hanghoaCls::HanghoaUpdatePrice()
     */
    public static function updatePrice(int $idhanghoa, $giaban): int
    {
        $db = self::db();
        $stmt = $db->prepare("UPDATE hanghoa SET giathamkhao = ? WHERE idhanghoa = ?");
        $stmt->execute([$giaban, $idhanghoa]);
        return $stmt->rowCount();
    }

    /**
     * Delete product with related data check.
     * Replaces hanghoaCls::HanghoaDelete()
     */
    public static function deleteProduct(int $idhanghoa): array
    {
        try {
            $db = self::db();

            $relatedData = self::checkRelatedData($idhanghoa);

            if (!empty($relatedData)) {
                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan',
                    'related_tables' => $relatedData
                ];
            }

            $stmt = $db->prepare("DELETE FROM hanghoa WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $rowCount = $stmt->rowCount();

            return [
                'success' => true,
                'rows_affected' => $rowCount,
                'message' => $rowCount > 0 ? 'Xóa hàng hóa thành công' : 'Không tìm thấy hàng hóa để xóa'
            ];
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'foreign key constraint') !== false) {
                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan trong hệ thống'
                ];
            }
            error_log("Product::deleteProduct error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi khi xóa: ' . $e->getMessage()];
        }
    }

    /**
     * Check related data before deletion.
     */
    public static function checkRelatedData(int $idhanghoa): array
    {
        $related = [];
        $db = self::db();

        $tables = [
            'chitietgiohang' => 'orders',
            'chitietphieunhap' => 'imports',
            'hanghoa_hinhanh' => 'images',
            'product_reviews' => 'reviews',
        ];

        foreach ($tables as $table => $label) {
            try {
                $check = $db->query("SHOW TABLES LIKE '$table'");
                if ($check && $check->rowCount() > 0) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE idhanghoa = ?");
                    $stmt->execute([$idhanghoa]);
                    if ($stmt->fetchColumn() > 0) {
                        $related[$label] = "Product has $label data";
                    }
                }
            } catch (\PDOException $e) {
                // table may not exist
            }
        }

        return $related;
    }

    // ═══════════════════════════════════════════
    //  STATUS & STOCK
    // ═══════════════════════════════════════════

    /**
     * Get product status as display text.
     * Replaces hanghoaCls::getProductStatus()
     */
    public static function getProductStatus(int $idhanghoa): string
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT trang_thai FROM hanghoa WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return "Không xác định";
            }

            switch ((int)$product->trang_thai) {
                case self::STATUS_DISCONTINUED:
                    return "Ngừng bán";
                case self::STATUS_OUT_OF_STOCK:
                    return "Hết hàng";
                case self::STATUS_ACTIVE:
                default:
                    $quantity = self::getProductQuantity($idhanghoa);
                    if ($quantity == 0) {
                        return "Hết hàng";
                    }
                    return "Đang bán";
            }
        } catch (\PDOException $e) {
            error_log("Product::getProductStatus error: " . $e->getMessage());
            return "Không xác định";
        }
    }

    /**
     * Get raw status value.
     * Replaces hanghoaCls::getProductStatusValue()
     */
    public static function getProductStatusValue(int $idhanghoa): int
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT trang_thai FROM hanghoa WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ? (int)$result->trang_thai : self::STATUS_ACTIVE;
        } catch (\PDOException $e) {
            error_log("Product::getProductStatusValue error: " . $e->getMessage());
            return self::STATUS_ACTIVE;
        }
    }

    /**
     * Get product quantity from stock.
     * Replaces hanghoaCls::getProductQuantity()
     */
    public static function getProductQuantity(int $idhanghoa): int
    {
        try {
            $db = self::db();
            $checkTable = $db->query("SHOW TABLES LIKE 'tonkho'");
            if ($checkTable->rowCount() == 0) {
                return 0;
            }

            $stmt = $db->prepare("SELECT soLuong FROM tonkho WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ? (int)$result->soLuong : 0;
        } catch (\PDOException $e) {
            error_log("Product::getProductQuantity error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get tonkho record.
     * Replaces hanghoaCls::getTonKho()
     */
    public static function getTonKho(int $idhanghoa): ?object
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT soLuong FROM tonkho WHERE idhanghoa = ?");
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute([$idhanghoa]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            error_log("Product::getTonKho error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update product status.
     * Replaces hanghoaCls::updateProductStatus()
     */
    public static function updateProductStatus(int $idhanghoa, int $status): bool
    {
        try {
            if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_DISCONTINUED, self::STATUS_OUT_OF_STOCK])) {
                error_log("Product::updateProductStatus invalid status: " . $status);
                return false;
            }

            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET trang_thai = ? WHERE idhanghoa = ?");
            return $stmt->execute([$status, $idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::updateProductStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get products by status.
     * Replaces hanghoaCls::getProductsByStatus()
     */
    public static function getProductsByStatus(int $status): array
    {
        try {
            if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_DISCONTINUED, self::STATUS_OUT_OF_STOCK])) {
                return [];
            }

            $db = self::db();
            $stmt = $db->prepare("SELECT idhanghoa, mahanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, ghichu, idloaihang, idNhanVien, idThuongHieu, idDonViTinh, is_featured, is_new, is_sale, sale_price, sale_percent, view_count, created_at, trang_thai FROM hanghoa WHERE trang_thai = ? ORDER BY tenhanghoa ASC");
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Product::getProductsByStatus error: " . $e->getMessage());
            return [];
        }
    }

    public static function getDiscontinuedProducts(): array
    {
        return self::getProductsByStatus(self::STATUS_DISCONTINUED);
    }

    public static function getOutOfStockProducts(): array
    {
        return self::getProductsByStatus(self::STATUS_OUT_OF_STOCK);
    }

    // ═══════════════════════════════════════════
    //  REFERENCES (brands, units, employees)
    // ═══════════════════════════════════════════

    public static function getAllThuongHieu(): array
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT idThuongHieu, tenTH, mota FROM thuonghieu ORDER BY tenTH ASC");
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getAllDonViTinh(): array
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT idDonViTinh, tenDonViTinh, mota FROM donvitinh ORDER BY tenDonViTinh ASC");
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getAllNhanVien(): array
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT idNhanVien, tenNV, email, dienthoai FROM nhanvien ORDER BY tenNV ASC");
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getThuongHieuById(int $idThuongHieu): ?object
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT idThuongHieu, tenTH, mota FROM thuonghieu WHERE idThuongHieu = ?");
        $stmt->execute([$idThuongHieu]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    // ═══════════════════════════════════════════
    //  FEATURED / NEW / SALE (replaces FeaturedProducts class)
    // ═══════════════════════════════════════════

    public static function getFeaturedProducts(int $limit = 8): array
    {
        $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai,
                CASE
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_featured = 1
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         h.view_count DESC, h.created_at DESC
                LIMIT " . $limit;

        $db = self::db();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function getNewProducts(int $limit = 8): array
    {
        $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai,
                CASE
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_new = 1
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         h.created_at DESC
                LIMIT " . $limit;

        $db = self::db();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function getSaleProducts(int $limit = 8): array
    {
        $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                h.sale_price as gia_hien_tai,
                ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100) as discount_percent,
                h.sale_end_date
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_sale = 1
                AND h.sale_price IS NOT NULL
                AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         discount_percent DESC, h.created_at DESC
                LIMIT " . $limit;

        $db = self::db();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function getMostViewedProducts(int $limit = 8): array
    {
        $sql = "SELECT h.idhanghoa, h.mahanghoa, h.tenhanghoa, h.mota, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.ghichu, h.idloaihang, h.idNhanVien, h.idThuongHieu, h.idDonViTinh, h.is_featured, h.is_new, h.is_sale, h.sale_price, h.sale_percent, h.view_count, h.created_at, h.trang_thai,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.view_count > 0
                ORDER BY h.view_count DESC
                LIMIT " . $limit;

        $db = self::db();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ═══════════════════════════════════════════

    // ═══════════════════════════════════════════
    
    // ═══════════════════════════════════════════
    
    // ═══════════════════════════════════════════
    
    // ═══════════════════════════════════════════
    //  FEATURED/NEW/SALE MANAGEMENT (replaces FeaturedProducts class)
    // ═══════════════════════════════════════════

    public static function setFeatured(int $idhanghoa, int $is_featured = 1): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET is_featured = ? WHERE idhanghoa = ?");
            return $stmt->execute([$is_featured, $idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::setFeatured error: " . $e->getMessage());
            return false;
        }
    }

    public static function setNew(int $idhanghoa, int $is_new = 1): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET is_new = ? WHERE idhanghoa = ?");
            return $stmt->execute([$is_new, $idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::setNew error: " . $e->getMessage());
            return false;
        }
    }

    public static function setSale(int $idhanghoa, float $sale_price, ?string $sale_end_date = null): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET is_sale = 1, sale_price = ?, sale_start_date = NOW(), sale_end_date = ? WHERE idhanghoa = ?");
            return $stmt->execute([$sale_price, $sale_end_date, $idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::setSale error: " . $e->getMessage());
            return false;
        }
    }

    public static function removeSale(int $idhanghoa): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET is_sale = 0, sale_price = NULL, sale_start_date = NULL, sale_end_date = NULL WHERE idhanghoa = ?");
            return $stmt->execute([$idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::removeSale error: " . $e->getMessage());
            return false;
        }
    }

    public static function incrementViewCount(int $idhanghoa): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET view_count = view_count + 1 WHERE idhanghoa = ?");
            return $stmt->execute([$idhanghoa]);
        } catch (\PDOException $e) {
            error_log("Product::incrementViewCount error: " . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════
//  INSTANCE METHODS (for ProductController compatibility)
    // ═══════════════════════════════════════════

    public function getImageUrl(): string
    {
        if (empty($this->hinhanh)) {
            return '/lequocanh/administrator/elements_LQA/img_LQA/no-image.png';
        }

        return "/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $this->hinhanh;
    }

    public function getFormattedPrice(): string
    {
        return number_format((float)$this->giathamkhao, 0, ',', '.') . ' VNĐ';
    }

    public function hasImage(): bool
    {
        return !empty($this->hinhanh) && $this->hinhanh != 0;
    }

    public function getCategory(): ?object
    {
        if (empty($this->idloaihang)) {
            return null;
        }

        $db = self::db();
        $stmt = $db->prepare("SELECT idloaihang, tenloaihang, mota, hinhanh FROM loaihang WHERE idloaihang = ?");
        $stmt->execute([$this->idloaihang]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getBrand(): ?object
    {
        if (empty($this->idThuongHieu)) {
            return null;
        }

        return self::getThuongHieuById((int)$this->idThuongHieu);
    }

    public function getStock(): ?object
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT id, idhanghoa, soLuong, soLuongToiThieu, viTri FROM tonkho WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function isInStock(): bool
    {
        $stock = $this->getStock();
        return $stock && $stock->soLuong > 0;
    }

    public function save(): bool
    {
        $result = parent::save();

        if ($result && !$this->exists) {
            $this->createInitialInventory();
        }

        return $result;
    }

    private function createInitialInventory(): void
    {
        $db = self::db();
        $stmt = $db->prepare("INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)");
        $stmt->execute([$this->idhanghoa]);
    }

    public function delete(): bool
    {
        $relatedData = self::checkRelatedData((int)$this->idhanghoa);

        if (!empty($relatedData)) {
            throw new \Exception('Cannot delete product. Related data exists: ' . implode(', ', array_keys($relatedData)));
        }

        $this->deleteInventory();

        return parent::delete();
    }

    private function deleteInventory(): void
    {
        $db = self::db();
        $stmt = $db->prepare("DELETE FROM tonkho WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
    }

    public static function getValidationRules(): array
    {
        return [
            'tenhanghoa' => 'required|min:3|max:255',
            'giathamkhao' => 'required|numeric|min:0',
            'idloaihang' => 'required|numeric',
            'mota' => 'max:1000'
        ];
    }
}
