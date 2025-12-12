<?php

/**
 * MoMo Payment Handler cho Giỏ hàng
 * Tích hợp với MoMo Payment system mới
 */

// Start session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include MoMo payment system mới
require_once __DIR__ . '/../../../payment/MoMoPayment.php';
require_once __DIR__ . '/../mPDO.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Clear any previous output to ensure clean JSON response
if (ob_get_level()) {
    ob_clean();
}

try {
    // Kiểm tra method POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['USER'])) {
        throw new Exception('Vui lòng đăng nhập để thanh toán');
    }

    // Lấy dữ liệu từ POST
    $paymentMethod = $_POST['payment_method'] ?? '';
    $orderCode = $_POST['order_code'] ?? '';
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    
    // Lấy thông tin phí vận chuyển, thuế và coupon từ POST
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $vatAmount = floatval($_POST['vat_amount'] ?? 0);
    $shippingFee = floatval($_POST['shipping_fee'] ?? 0);
    $shippingMethod = $_POST['shipping_method'] ?? 'standard';
    $couponCode = $_POST['coupon_code'] ?? $_SESSION['applied_coupon'] ?? null;
    $couponDiscount = floatval($_POST['coupon_discount'] ?? $_SESSION['coupon_discount'] ?? 0);
    
    // Log để debug
    error_log("MoMo Payment Debug - Subtotal: $subtotal, VAT: $vatAmount, Shipping: $shippingFee, Coupon: $couponDiscount");
    
    // TÍNH TỔNG TIỀN ĐÚNG CÁCH: Subtotal + VAT + Shipping - Coupon
    // Không sử dụng amount từ POST vì có thể bị sai
    $amount = intval($subtotal + $vatAmount + $shippingFee - $couponDiscount);
    
    error_log("MoMo Payment Debug - Calculated amount: $amount");
    
    // Nếu subtotal = 0, fallback về session
    if ($subtotal == 0 && isset($_SESSION['subtotal'])) {
        $subtotal = $_SESSION['subtotal'];
        $vatAmount = $_SESSION['vat_amount'] ?? 0;
        $couponDiscount = $_SESSION['coupon_discount'] ?? 0;
        $amount = intval($subtotal + $vatAmount + $shippingFee - $couponDiscount);
        error_log("MoMo Payment Debug - Using session values, amount: $amount");
    }
    
    // Lưu thông tin vào session để sử dụng sau
    $_SESSION['vat_amount'] = $vatAmount;
    $_SESSION['shipping_fee'] = $shippingFee;
    $_SESSION['shipping_method'] = $shippingMethod;
    $_SESSION['coupon_code'] = $couponCode;
    $_SESSION['coupon_discount'] = $couponDiscount;

    // Validate dữ liệu
    if ($paymentMethod !== 'momo') {
        throw new Exception('Phương thức thanh toán không hợp lệ');
    }

    if (empty($orderCode)) {
        throw new Exception('Mã đơn hàng không hợp lệ');
    }

    if (empty($shippingAddress)) {
        throw new Exception('Địa chỉ giao hàng không được để trống');
    }

    if ($amount < 1000) {
        throw new Exception('Số tiền quá nhỏ. MoMo yêu cầu tối thiểu 1,000 VND');
    }
    
    if ($amount > 50000000) {
        $formattedAmount = number_format($amount, 0, ',', '.');
        throw new Exception("Số tiền {$formattedAmount} VND vượt quá giới hạn MoMo (tối đa 50,000,000 VND). Vui lòng chọn phương thức thanh toán khác như Chuyển khoản ngân hàng hoặc COD.");
    }

    // Lấy thông tin user
    $userId = is_object($_SESSION['USER']) ? $_SESSION['USER']->iduser : $_SESSION['USER'];

    // Kiểm tra giỏ hàng có sản phẩm không
    $pdo = new mPDO();

    // Kiểm tra xem bảng tbl_giohang có tồn tại không
    try {
        $cartQuery = "SELECT COUNT(*) as count FROM tbl_giohang WHERE user_id = ?";
        $cartResult = $pdo->executeS($cartQuery, [$userId], false);
    } catch (Exception $e) {
        // Nếu bảng không tồn tại, tạo giỏ hàng giả cho test
        error_log("Cart table not found, creating fake cart for testing: " . $e->getMessage());
        $cartResult = [['count' => 1]]; // Giả lập có 1 sản phẩm trong giỏ
    }

    if (!$cartResult || $cartResult['count'] == 0) {
        throw new Exception('Giỏ hàng trống');
    }

    // Tạo thông tin đơn hàng cho MoMo
    $orderInfo = "Thanh toan don hang #" . $orderCode;
    $extraData = json_encode([
        'order_code' => $orderCode,
        'user_id' => $userId,
        'shipping_address' => $shippingAddress,
        'source' => 'cart_checkout'
    ]);

    // Lưu thông tin đơn hàng vào session để hiển thị hóa đơn sau khi thanh toán
    $_SESSION['pending_order'] = [
        'order_code' => $orderCode,
        'user_id' => $userId,
        'shipping_address' => $shippingAddress,
        'amount' => $amount,
        'cart_items' => $cartResult // Lưu cart items trước khi xóa
    ];

    // Tạo MoMo payment
    $momoPayment = new MoMoPayment();
    
    // Log thông tin trước khi gọi MoMo API
    error_log('MoMo API Call - Amount: ' . $amount);
    error_log('MoMo API Call - Order Info: ' . $orderInfo);
    error_log('MoMo API Call - Extra Data: ' . $extraData);
    
    $response = $momoPayment->createPayment($amount, $orderInfo, $extraData);
    
    // Log full response từ MoMo
    error_log('MoMo API Response: ' . json_encode($response));

    // Kiểm tra response từ MoMo
    if (isset($response['resultCode']) && $response['resultCode'] == 0) {
        // Lưu đơn hàng vào database ngay lập tức
        require_once '../mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();

        try {
            // Kiểm tra và bắt đầu transaction nếu chưa có
            if (!$conn->inTransaction()) {
                $conn->beginTransaction();
            }

            // Kiểm tra và thêm cột nếu chưa tồn tại (tương tự payment_confirm.php)
            $notificationColumns = [
                'pending_read' => "SHOW COLUMNS FROM don_hang LIKE 'pending_read'",
                'approved_read' => "SHOW COLUMNS FROM don_hang LIKE 'approved_read'",
                'cancelled_read' => "SHOW COLUMNS FROM don_hang LIKE 'cancelled_read'",
                'thue' => "SHOW COLUMNS FROM don_hang LIKE 'thue'",
                'phi_van_chuyen' => "SHOW COLUMNS FROM don_hang LIKE 'phi_van_chuyen'"
            ];

            $missingColumns = [];
            foreach ($notificationColumns as $column => $sql) {
                $checkStmt = $conn->prepare($sql);
                $checkStmt->execute();
                if ($checkStmt->rowCount() == 0) {
                    $missingColumns[] = $column;
                }
            }

            if (!empty($missingColumns)) {
                foreach ($missingColumns as $column) {
                    if ($column == 'thue' || $column == 'phi_van_chuyen') {
                        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN $column DECIMAL(15,2) DEFAULT 0";
                    } else {
                        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN $column TINYINT(1) NOT NULL DEFAULT 0";
                    }
                    $conn->exec($addColumnSql);
                }
            }

            // Lấy thông tin thuế và phí vận chuyển từ session
            $vatAmount = $_SESSION['vat_amount'] ?? 0;
            $shippingFee = $_SESSION['shipping_fee'] ?? 0;
            
            // Lấy thông tin shipping method
            $shippingMethodCode = $shippingMethod;
            $shippingMethodName = '';
            $estimatedDelivery = '';
            
            // Lấy tên phương thức vận chuyển
            try {
                require_once '../mod/ShippingMethodCls.php';
                $shippingMethodObj = new ShippingMethod();
                $shippingMethodInfo = $shippingMethodObj->getMethodByCode($shippingMethodCode);
                if ($shippingMethodInfo) {
                    $shippingMethodName = $shippingMethodInfo['name'] ?? 'Giao hàng tiêu chuẩn';
                    $minDays = $shippingMethodInfo['estimated_days_min'];
                    $maxDays = $shippingMethodInfo['estimated_days_max'];
                    if ($minDays == $maxDays) {
                        $estimatedDelivery = $minDays == 0 ? 'Nhận ngay' : date('d/m/Y', strtotime("+{$minDays} weekdays"));
                    } else {
                        $estimatedDelivery = date('d/m/Y', strtotime("+{$minDays} weekdays")) . ' - ' . date('d/m/Y', strtotime("+{$maxDays} weekdays"));
                    }
                }
            } catch (Exception $e) {
                error_log("MoMo - Error getting shipping method info: " . $e->getMessage());
            }

            // Kiểm tra và thêm cột coupon nếu chưa có
            $couponColumns = [
                'coupon_code' => "SHOW COLUMNS FROM don_hang LIKE 'coupon_code'",
                'coupon_discount' => "SHOW COLUMNS FROM don_hang LIKE 'coupon_discount'",
                'shipping_method' => "SHOW COLUMNS FROM don_hang LIKE 'shipping_method'",
                'shipping_method_name' => "SHOW COLUMNS FROM don_hang LIKE 'shipping_method_name'",
                'estimated_delivery' => "SHOW COLUMNS FROM don_hang LIKE 'estimated_delivery'"
            ];
            
            foreach ($couponColumns as $column => $sql) {
                $checkStmt = $conn->prepare($sql);
                $checkStmt->execute();
                if ($checkStmt->rowCount() == 0) {
                    if ($column == 'coupon_discount') {
                        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN $column DECIMAL(15,2) DEFAULT 0";
                    } else {
                        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN $column VARCHAR(100) DEFAULT NULL";
                    }
                    $conn->exec($addColumnSql);
                    error_log("MoMo - Added column $column to don_hang table");
                }
            }

            // Lưu đơn hàng với trạng thái pending - BAO GỒM COUPON
            $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, dia_chi_giao_hang,
                              tong_tien, thue, phi_van_chuyen, shipping_method, shipping_method_name, estimated_delivery,
                              coupon_code, coupon_discount, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan,
                              pending_read, ngay_tao, ngay_cap_nhat)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'momo', 'pending', 0, NOW(), NOW())";

            $stmt = $conn->prepare($insertOrderSql);
            $stmt->execute([
                $response['orderId'], 
                $userId, 
                $shippingAddress, 
                $amount, 
                $vatAmount, 
                $shippingFee,
                $shippingMethodCode,
                $shippingMethodName,
                $estimatedDelivery,
                $couponCode,
                $couponDiscount
            ]);
            $orderId = $conn->lastInsertId();

            // QUAN TRỌNG: Chỉ lấy các sản phẩm đã chọn từ checkout, KHÔNG lấy toàn bộ giỏ hàng
            $selectedProducts = isset($_POST['selected_products']) ? json_decode($_POST['selected_products'], true) : null;
            
            if ($selectedProducts && is_array($selectedProducts) && count($selectedProducts) > 0) {
                // Sử dụng danh sách sản phẩm đã chọn từ checkout
                error_log("MoMo - Using selected products from checkout: " . json_encode($selectedProducts));
                $cartItems = [];
                foreach ($selectedProducts as $product) {
                    $productId = $product['id'] ?? $product['product_id'] ?? null;
                    $quantity = $product['quantity'] ?? 1;
                    $price = $product['price'] ?? 0;
                    $name = $product['name'] ?? '';
                    
                    if ($productId) {
                        $cartItems[] = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'giathamkhao' => $price,
                            'tenhanghoa' => $name
                        ];
                    }
                }
                error_log("MoMo - Cart items to process: " . count($cartItems));
            } else {
                // Fallback: Lấy từ session order_details nếu có
                if (isset($_SESSION['order_details']) && is_array($_SESSION['order_details'])) {
                    error_log("MoMo - Using order_details from session");
                    $cartItems = [];
                    foreach ($_SESSION['order_details'] as $product) {
                        $cartItems[] = [
                            'product_id' => $product['id'],
                            'quantity' => $product['quantity'],
                            'giathamkhao' => $product['price'],
                            'tenhanghoa' => $product['name']
                        ];
                    }
                } else {
                    // Cuối cùng mới lấy từ giỏ hàng (không khuyến khích)
                    error_log("MoMo - WARNING: Falling back to entire cart - this should not happen!");
                    $cartQuery = "SELECT gh.*, hh.tenhanghoa, hh.giathamkhao
                                 FROM tbl_giohang gh
                                 JOIN hanghoa hh ON gh.product_id = hh.idhanghoa
                                 WHERE gh.user_id = ?";
                    $cartStmt = $conn->prepare($cartQuery);
                    $cartStmt->execute([$userId]);
                    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            // Log danh sách sản phẩm sẽ được xử lý
            error_log("MoMo - Products to be processed: " . json_encode(array_column($cartItems, 'product_id')));

            // Khởi tạo đối tượng tồn kho
            require_once '../mod/mtonkhoCls.php';
            $tonkho = new MTonKho();

            foreach ($cartItems as $item) {
                // Lưu chi tiết đơn hàng
                $insertItemSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia, ngay_tao)
                                 VALUES (?, ?, ?, ?, NOW())";
                $itemStmt = $conn->prepare($insertItemSql);
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['giathamkhao']]);

                // TRỪ TỒN KHO NGAY KHI TẠO ĐƠN HÀNG MOMO
                $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['product_id']);
                if ($tonkhoInfo) {
                    error_log("MoMo - Tồn kho hiện tại của sản phẩm ID " . $item['product_id'] . ": " . $tonkhoInfo->soLuong);
                    
                    // Sử dụng hàm updateSoLuong với isIncrement = false để giảm số lượng
                    // useExternalTransaction = true vì đang trong transaction
                    $updateResult = $tonkho->updateSoLuong($item['product_id'], $item['quantity'], false, true);
                    
                    if ($updateResult) {
                        error_log("MoMo - Đã trừ tồn kho thành công cho sản phẩm ID: " . $item['product_id'] . ", giảm: " . $item['quantity']);
                        
                        // Kiểm tra lại tồn kho sau khi cập nhật
                        $updatedTonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['product_id']);
                        if ($updatedTonkhoInfo) {
                            error_log("MoMo - Tồn kho sau khi trừ của sản phẩm ID " . $item['product_id'] . ": " . $updatedTonkhoInfo->soLuong);
                        }
                    } else {
                        error_log("MoMo - Cập nhật tồn kho thất bại cho sản phẩm ID: " . $item['product_id']);
                        throw new Exception("Không thể cập nhật tồn kho cho sản phẩm ID: " . $item['product_id']);
                    }
                } else {
                    error_log("MoMo - Không tìm thấy thông tin tồn kho cho sản phẩm ID: " . $item['product_id']);
                    throw new Exception("Sản phẩm ID " . $item['product_id'] . " không có trong kho");
                }
            }

            // Kiểm tra transaction trước khi commit
            if ($conn->inTransaction()) {
                $conn->commit();
            }
            
            // LƯU THÔNG TIN COUPON VÀO SESSION để ghi nhận sau khi thanh toán thành công
            // Việc ghi nhận coupon sẽ được thực hiện trong momo_return.php khi thanh toán thành công
            // Điều này đảm bảo coupon chỉ bị trừ khi user thực sự thanh toán xong
            if ($couponCode && $couponDiscount > 0) {
                $_SESSION['pending_coupon'] = [
                    'code' => $couponCode,
                    'discount' => $couponDiscount,
                    'order_id' => $orderId,
                    'user_id' => $userId
                ];
                error_log("MoMo - Saved pending coupon to session: $couponCode, discount: $couponDiscount");
            }

            // Lưu thông tin vào session để sử dụng sau
            // QUAN TRỌNG: Lưu danh sách product_id đã thanh toán để xóa đúng sản phẩm khỏi giỏ hàng
            $purchasedProductIds = array_column($cartItems, 'product_id');
            $_SESSION['pending_order'] = [
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'amount' => $amount,
                'shipping_address' => $shippingAddress,
                'momo_order_id' => $response['orderId'],
                'momo_request_id' => $response['requestId'],
                'created_at' => date('Y-m-d H:i:s'),
                'purchased_product_ids' => $purchasedProductIds // Danh sách sản phẩm đã thanh toán
            ];
            error_log("MoMo - Saved purchased product IDs to session: " . json_encode($purchasedProductIds));

            // Log thông tin để debug
            error_log('MoMo Cart Payment Created: ' . json_encode([
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'user_id' => $userId,
                'amount' => $amount,
                'momo_order_id' => $response['orderId']
            ]));

            // Trả về URL thanh toán
            echo json_encode([
                'success' => true,
                'payUrl' => $response['payUrl'],
                'orderId' => $response['orderId'],
                'database_order_id' => $orderId,
                'message' => 'Tạo thanh toán thành công'
            ]);
        } catch (Exception $e) {
            // Kiểm tra transaction trước khi rollback
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Lỗi lưu đơn hàng MoMo: ' . $e->getMessage());

            // Vẫn trả về URL thanh toán nhưng ghi log lỗi
            echo json_encode([
                'success' => true,
                'payUrl' => $response['payUrl'],
                'orderId' => $response['orderId'],
                'message' => 'Tạo thanh toán thành công',
                'warning' => 'Có lỗi khi lưu đơn hàng: ' . $e->getMessage()
            ]);
        }
    } else {
        // Lỗi từ MoMo
        $errorMsg = $response['message'] ?? 'Lỗi không xác định từ MoMo';
        error_log('MoMo Cart Payment Error: ' . json_encode($response));

        echo json_encode([
            'success' => false,
            'message' => 'Lỗi từ MoMo: ' . $errorMsg,
            'error_code' => $response['resultCode'] ?? 'unknown'
        ]);
    }
} catch (Exception $e) {
    error_log('MoMo Cart Payment Exception: ' . $e->getMessage());
    error_log('MoMo Cart Payment Stack Trace: ' . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
