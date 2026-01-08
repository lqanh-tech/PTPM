<?php

require_once '../../../administrator/elements_LQA/mod/databaseOptimizer.php';
require_once '../../Response.php';
require_once '../../middleware/JwtAuthMiddleware.php';

class ProductsAPI {
    private $optimizer;
    
    public function __construct() {
        $this->optimizer = DatabaseOptimizer::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $pathParts = explode('/', trim($path, '/'));
        $productId = end($pathParts);
        
        switch ($method) {
            case 'GET':
                if (is_numeric($productId)) {
                    return $this->getById($productId);
                } else {
                    return $this->getAll();
                }
                break;
                
            case 'POST':
                return $this->create();
                break;
                
            case 'PUT':
                if (is_numeric($productId)) {
                    return $this->update($productId);
                }
                return Response::error('Product ID required for update', 400);
                break;
                
            case 'DELETE':
                if (is_numeric($productId)) {
                    return $this->delete($productId);
                }
                return Response::error('Product ID required for delete', 400);
                break;
                
            default:
                return Response::error('Method not allowed', 405);
        }
    }
    
    public function getAll() {
        try {

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            
            $sql = "SELECT h.*, l.tenloaihang, 
                           COALESCE(d.giaBan, h.giathamkhao) as current_price
                    FROM hanghoa h 
                    LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
                    LEFT JOIN dongia d ON h.idhanghoa = d.idHangHoa AND d.apDung = 1
                    WHERE h.setlock = 1";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (h.tenhanghoa LIKE ? OR h.mota LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($category)) {
                $sql .= " AND h.idloaihang = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY h.tenhanghoa LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $products = $this->optimizer->executeQuery($sql, $params, true);
            
            $countSql = "SELECT COUNT(*) as total FROM hanghoa h WHERE h.setlock = 1";
            $countParams = [];
            
            if (!empty($search)) {
                $countSql .= " AND (h.tenhanghoa LIKE ? OR h.mota LIKE ?)";
                $countParams[] = "%$search%";
                $countParams[] = "%$search%";
            }
            
            if (!empty($category)) {
                $countSql .= " AND h.idloaihang = ?";
                $countParams[] = $category;
            }
            
            $totalResult = $this->optimizer->executeQuery($countSql, $countParams, true);
            $total = $totalResult[0]['total'];
            
            return Response::success([
                'products' => $products,
                'pagination' => [
                    'total' => (int)$total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]);
            
        } catch (Exception $e) {
            return Response::error('Failed to fetch products: ' . $e->getMessage(), 500);
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT h.*, l.tenloaihang,
                           COALESCE(d.giaBan, h.giathamkhao) as current_price,
                           d.ngayApDung, d.ngayKetThuc
                    FROM hanghoa h 
                    LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
                    LEFT JOIN dongia d ON h.idhanghoa = d.idHangHoa AND d.apDung = 1
                    WHERE h.idhanghoa = ? AND h.setlock = 1";
            
            $product = $this->optimizer->executeQuery($sql, [$id], true);
            
            if (empty($product)) {
                return Response::error('Product not found', 404);
            }
            
            $imagesSql = "SELECT * FROM hinhanh WHERE idhanghoa = ?";
            $images = $this->optimizer->executeQuery($imagesSql, [$id], true);
            
            $product[0]['images'] = $images;
            
            return Response::success($product[0]);
            
        } catch (Exception $e) {
            return Response::error('Failed to fetch product: ' . $e->getMessage(), 500);
        }
    }
    
    public function create() {
        try {

            $auth = new JwtAuthMiddleware();
            $auth->handle();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['tenhanghoa', 'idloaihang', 'giathamkhao'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    return Response::error("Field '$field' is required", 400);
                }
            }
            
            $sql = "INSERT INTO hanghoa (tenhanghoa, idloaihang, giathamkhao, mota, setlock) 
                    VALUES (?, ?, ?, ?, 1)";
            
            $params = [
                $input['tenhanghoa'],
                $input['idloaihang'],
                $input['giathamkhao'],
                $input['mota'] ?? ''
            ];
            
            $this->optimizer->executeQuery($sql, $params, false);
            
            return Response::success(['message' => 'Product created successfully'], 201);
            
        } catch (Exception $e) {
            return Response::error('Failed to create product: ' . $e->getMessage(), 500);
        }
    }
    
    public function update($id) {
        try {

            $auth = new JwtAuthMiddleware();
            $auth->handle();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $existing = $this->optimizer->executeQuery(
                "SELECT idhanghoa FROM hanghoa WHERE idhanghoa = ? AND setlock = 1", 
                [$id], 
                true
            );
            
            if (empty($existing)) {
                return Response::error('Product not found', 404);
            }
            
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['tenhanghoa', 'idloaihang', 'giathamkhao', 'mota'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                return Response::error('No valid fields to update', 400);
            }
            
            $sql = "UPDATE hanghoa SET " . implode(', ', $updateFields) . " WHERE idhanghoa = ?";
            $params[] = $id;
            
            $this->optimizer->executeQuery($sql, $params, false);
            
            return Response::success(['message' => 'Product updated successfully']);
            
        } catch (Exception $e) {
            return Response::error('Failed to update product: ' . $e->getMessage(), 500);
        }
    }
    
    public function delete($id) {
        try {

            $auth = new JwtAuthMiddleware();
            $auth->handle();
            
            $sql = "UPDATE hanghoa SET setlock = 0 WHERE idhanghoa = ?";
            $result = $this->optimizer->executeQuery($sql, [$id], false);
            
            return Response::success(['message' => 'Product deleted successfully']);
            
        } catch (Exception $e) {
            return Response::error('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
}

$api = new ProductsAPI();
$api->handleRequest();