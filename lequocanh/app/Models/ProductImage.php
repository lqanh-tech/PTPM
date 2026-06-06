<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

/**
 * ProductImage model - handles all image CRUD for products.
 * Migrated from hanghoaCls.php image methods.
 */
class ProductImage
{
    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    // ── Column detection cache ──
    private static $hasCheckedFileHashColumn = false;
    private static $fileHashColumnExists = false;

    // ═══════════════════════════════════════════
    //  IMAGE CRUD
    // ═══════════════════════════════════════════

    /**
     * Get image by ID.
     * Replaces hanghoaCls::GetHinhAnhById()
     */
    public static function getById(int $id): ?object
    {
        if (!$id) {
            return null;
        }

        try {
            $db = self::db();

            try {
                $checkTable = $db->query("SHOW TABLES LIKE 'hinhanh'");
                if ($checkTable->rowCount() == 0) {
                    return null;
                }
            } catch (\PDOException $e) {
                Logger::error('ProductImage::getById', ['error' => 'table check', 'detail' => $e->getMessage()]);
            }

            $stmt = $db->prepare('SELECT id, ten_file, loai_file, duong_dan, du_lieu, trang_thai, ngay_tao, file_hash FROM hinhanh WHERE id = ?');
            $stmt->execute([$id]);
            $hinhanh = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$hinhanh) {
                return null;
            }

            // Fix path prefix for uploads
            if (empty($hinhanh->du_lieu) && !empty($hinhanh->duong_dan)) {
                $hinhanh->duong_dan = str_replace('\\', '/', $hinhanh->duong_dan);
                if (
                    strpos($hinhanh->duong_dan, 'administrator/') !== 0 &&
                    strpos($hinhanh->duong_dan, 'uploads/') === 0
                ) {
                    $hinhanh->duong_dan = 'administrator/' . $hinhanh->duong_dan;
                }
            }

            return $hinhanh;
        } catch (\PDOException $e) {
            Logger::error('ProductImage::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all images with usage count.
     * Replaces hanghoaCls::GetAllHinhAnh()
     */
    public static function getAll(): array
    {
        try {
            $db = self::db();
            $sql = 'SELECT h.id, h.ten_file, h.loai_file, h.duong_dan, h.du_lieu, h.trang_thai, h.ngay_tao, h.file_hash, LENGTH(h.du_lieu) as file_size,
                    (SELECT COUNT(*) FROM hanghoa WHERE hinhanh = h.id) as usage_count
                    FROM hinhanh h
                    ORDER BY h.ngay_tao DESC';
            $stmt = $db->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            Logger::error('ProductImage::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Insert new image record.
     * Replaces hanghoaCls::ThemHinhAnh()
     */
    public static function create(string $ten_file, string $loai_file, string $duong_dan, ?string $file_hash = null, ?string $binary_data = null): bool
    {
        try {
            $db = self::db();

            if ($binary_data !== null) {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, du_lieu, trang_thai, ngay_tao, file_hash)
                        VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP, ?)";
                $stmt = $db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan, $binary_data, $file_hash]);
            } elseif ($file_hash) {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao, file_hash)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, ?)";
                $stmt = $db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan, $file_hash]);
            } else {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)";
                $stmt = $db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan]);
            }
        } catch (\PDOException $e) {
            Logger::error('ProductImage::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Delete image by ID.
     * Replaces hanghoaCls::XoaHinhAnh()
     */
    public static function delete(int $id): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("DELETE FROM hinhanh WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::delete', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get image path by ID.
     * Replaces hanghoaCls::GetImagePath()
     */
    public static function getPath(int $id): ?string
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT duong_dan FROM hinhanh WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->duong_dan : null;
    }

    /**
     * Update image status to active.
     * Replaces hanghoaCls::UpdateImageStatus()
     */
    public static function updateStatus(int $id): bool
    {
        $db = self::db();
        $stmt = $db->prepare('UPDATE hinhanh SET trang_thai = 1 WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Get last insert ID.
     * Replaces hanghoaCls::GetLastInsertId()
     */
    public static function getLastInsertId(): string
    {
        return self::db()->lastInsertId();
    }

    // ═══════════════════════════════════════════
    //  IMAGE ↔ PRODUCT RELATIONS
    // ═══════════════════════════════════════════

    /**
     * Ensure hanghoa_hinhanh table exists.
     * Replaces hanghoaCls::CreateHanghoaHinhanhTable()
     */
    public static function ensureRelationTable(): bool
    {
        try {
            $db = self::db();
            $sql = "CREATE TABLE IF NOT EXISTS hanghoa_hinhanh (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idhanghoa INT NOT NULL,
                idhinhanh INT NOT NULL,
                UNIQUE KEY (idhanghoa, idhinhanh)
            )";
            $stmt = $db->prepare($sql);
            return $stmt->execute();
        } catch (\PDOException $e) {
            Logger::error('ProductImage::ensureRelationTable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply image to product (set as primary + create relation).
     * Replaces hanghoaCls::ApplyImageToProduct()
     */
    public static function applyToProduct(int $idhanghoa, int $id_hinhanh): bool
    {
        try {
            $db = self::db();

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            self::ensureRelationTable();

            // Set as primary image
            $stmt = $db->prepare('UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?');
            $result = $stmt->execute([$id_hinhanh, $idhanghoa]);

            if (!$result) {
                throw new \Exception("Không thể cập nhật hình ảnh chính");
            }

            // Create relation if not exists
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?');
            $checkStmt->execute([$idhanghoa, $id_hinhanh]);
            $exists = $checkStmt->fetchColumn() > 0;

            if (!$exists) {
                $insertStmt = $db->prepare('INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)');
                $insertResult = $insertStmt->execute([$idhanghoa, $id_hinhanh]);

                if (!$insertResult) {
                    throw new \Exception("Không thể thêm quan hệ hình ảnh");
                }
            }

            self::updateStatus($id_hinhanh);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            try {
                self::db()->rollBack();
            } catch (\PDOException $rollbackException) {
                Logger::error('ProductImage::applyToProduct', ['error' => 'rollback', 'detail' => $rollbackException->getMessage()]);
            }
            Logger::error('ProductImage::applyToProduct', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Remove image from product (clear primary image).
     * Replaces hanghoaCls::RemoveImageFromProduct()
     */
    public static function removeFromProduct(int $idhanghoa): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET hinhanh = 0 WHERE idhanghoa = ?");
            return $stmt->execute([$idhanghoa]);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::removeFromProduct', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get products using a specific image.
     * Replaces hanghoaCls::GetProductsByImageId()
     */
    public static function getProductsByImageId(int $imageId): array
    {
        $db = self::db();
        $stmt = $db->prepare("SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE hinhanh = ?");
        $stmt->execute([$imageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update all products using old image to new image.
     * Replaces hanghoaCls::UpdateProductImages()
     */
    public static function updateProductImages(int $oldImageId, int $newImageId): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET hinhanh = ? WHERE hinhanh = ?");
            return $stmt->execute([$newImageId, $oldImageId]);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::updateProductImages', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update product's primary image.
     * Replaces hanghoaCls::CapNhatHinhAnhSanPham()
     */
    public static function updateProductImage(int $idhanghoa, int $id_hinhanh_moi): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?");
            return $stmt->execute([$id_hinhanh_moi, $idhanghoa]);
        } catch (\Exception $e) {
            Logger::error('ProductImage::updateProductImage', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get all images for a product.
     * Replaces hanghoaCls::GetAllImagesForProduct()
     */
    public static function getAllForProduct(int $idhanghoa): array
    {
        try {
            $db = self::db();
            $sql = "SELECT h.id, h.ten_file, h.loai_file, h.duong_dan, h.du_lieu, h.trang_thai, h.ngay_tao, h.file_hash FROM hinhanh h
                    INNER JOIN hanghoa_hinhanh hh ON h.id = hh.idhinhanh
                    WHERE hh.idhanghoa = :idhanghoa";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(":idhanghoa", $idhanghoa);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::getAllForProduct', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Count images for a product.
     * Replaces hanghoaCls::CountImagesForProduct()
     */
    public static function countForProduct(int $idhanghoa): int
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = :idhanghoa");
            $stmt->bindValue(":idhanghoa", $idhanghoa);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            Logger::error('ProductImage::countForProduct', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    // ═══════════════════════════════════════════
    //  IMAGE LOOKUP & MATCHING
    // ═══════════════════════════════════════════

    /**
     * Check if image exists by file name.
     * Replaces hanghoaCls::CheckImageExists()
     */
    public static function existsByFileName(string $fileName): bool
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT COUNT(*) FROM hinhanh WHERE ten_file = :fileName");
            $stmt->bindValue(":fileName", $fileName);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            Logger::error('ProductImage::existsByFileName', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if image exists by file hash (with auto-migration).
     * Replaces hanghoaCls::CheckImageExistsByHash()
     */
    public static function existsByHash(string $fileHash)
    {
        try {
            $db = self::db();

            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnStmt = $db->prepare("SHOW COLUMNS FROM hinhanh LIKE 'file_hash'");
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                if (!self::$fileHashColumnExists) {
                    $db->exec("ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL");
                    self::$fileHashColumnExists = true;
                    return false;
                }
            } elseif (!self::$fileHashColumnExists) {
                return false;
            }

            $stmt = $db->prepare("SELECT id FROM hinhanh WHERE file_hash = ?");
            $stmt->execute([$fileHash]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ? $result->id : false;
        } catch (\PDOException $e) {
            Logger::error('ProductImage::existsByHash', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Find products by exact name match.
     * Replaces hanghoaCls::FindProductsByExactName()
     */
    public static function findProductsByExactName(string $productName): array
    {
        try {
            $db = self::db();
            $stmt = $db->prepare("SELECT idhanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, idloaihang, trang_thai FROM hanghoa WHERE tenhanghoa = :productName");
            $stmt->bindValue(":productName", $productName);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::findProductsByExactName', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Find products by name (LIKE match).
     * Replaces hanghoaCls::FindProductsByName()
     */
    public static function findProductsByName(string $name): array
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT idhanghoa, tenhanghoa, mota, giathamkhao, giakhuyenmai, hinhanh, idloaihang, trang_thai FROM hanghoa WHERE tenhanghoa LIKE ?');
        $stmt->execute(["%" . $name . "%"]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Check if exact image name matches product name.
     * Replaces hanghoaCls::IsExactImageNameMatch()
     */
    public static function isExactImageNameMatch(string $tenhanghoa, string $ten_file): bool
    {
        $productNameLower = mb_strtolower($tenhanghoa, 'UTF-8');
        $fileNameLower = mb_strtolower($ten_file, 'UTF-8');

        return strpos($fileNameLower, $productNameLower) !== false ||
               strpos($fileNameLower, str_replace(' ', '', $productNameLower)) !== false;
    }

    // ═══════════════════════════════════════════
    //  DIAGNOSTICS
    // ═══════════════════════════════════════════

    /**
     * Get products with mismatched images.
     * Replaces hanghoaCls::GetMismatchedProductImages()
     */
    public static function getMismatchedProductImages(): array
    {
        try {
            $db = self::db();
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, ha.id, ha.ten_file
                   FROM hanghoa h
                   JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE ha.ten_file NOT LIKE CONCAT('%', h.tenhanghoa, '%')
                   AND ha.ten_file NOT LIKE CONCAT('%', REPLACE(h.tenhanghoa, ' ', ''), '%')";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::getMismatchedProductImages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Find products with missing image references.
     * Replaces hanghoaCls::FindMissingImages()
     */
    public static function findMissingImages(): array
    {
        try {
            $db = self::db();
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh
                   FROM hanghoa h
                   LEFT JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE h.hinhanh > 0 AND ha.id IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            Logger::error('ProductImage::findMissingImages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Find exact match image for a product.
     * Replaces hanghoaCls::FindExactMatchImage()
     */
    public static function findExactMatchImage(int $idhanghoa): ?object
    {
        try {
            $db = self::db();

            $stmt = $db->prepare("SELECT tenhanghoa FROM hanghoa WHERE idhanghoa = ?");
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return null;
            }

            $stmt = $db->prepare("SELECT id, ten_file, loai_file, duong_dan, trang_thai FROM hinhanh WHERE ten_file LIKE ?");
            $productName = '%' . $product->tenhanghoa . '%';
            $stmt->execute([$productName]);
            return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
        } catch (\PDOException $e) {
            Logger::error('ProductImage::findExactMatchImage', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Remove all mismatched product images.
     * Replaces hanghoaCls::RemoveAllMismatchedImages()
     */
    public static function removeAllMismatchedImages(): int
    {
        try {
            $db = self::db();
            $mismatched = self::getMismatchedProductImages();
            $count = 0;

            foreach ($mismatched as $item) {
                $stmt = $db->prepare("UPDATE hanghoa SET hinhanh = 0 WHERE idhanghoa = ?");
                if ($stmt->execute([$item->idhanghoa])) {
                    $count++;
                }
            }

            return $count;
        } catch (\PDOException $e) {
            Logger::error('ProductImage::removeAllMismatchedImages', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
