<?php
/**
 * Script chạy migration thêm cột trạng thái vào bảng hanghoa
 * Chạy file này để thực thi migration
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== BẮT ĐẦU MIGRATION: THÊM CỘT TRẠNG THÁI ===\n\n";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/add_product_status.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Tách các câu lệnh SQL (bỏ qua delimiter và trigger vì cần xử lý riêng)
    $statements = [];
    $lines = explode("\n", $sql);
    $currentStatement = '';
    $inDelimiter = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Bỏ qua comment và dòng trống
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Xử lý DELIMITER
        if (strpos($line, 'DELIMITER') === 0) {
            $inDelimiter = !$inDelimiter;
            continue;
        }
        
        $currentStatement .= $line . ' ';
        
        // Nếu đang trong delimiter block, tìm $$
        if ($inDelimiter) {
            if (strpos($line, '$$') !== false) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        } else {
            // Nếu không trong delimiter, tìm dấu ;
            if (substr($line, -1) === ';') {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        }
    }
    
    // Thực thi từng câu lệnh
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            // Bỏ dấu ; cuối nếu có
            $statement = rtrim($statement, ';');
            
            // Bỏ qua các câu lệnh SELECT thông báo
            if (stripos($statement, 'SELECT \'Migration completed') !== false) {
                continue;
            }
            
            echo "Đang thực thi: " . substr($statement, 0, 80) . "...\n";
            $db->exec($statement);
            $successCount++;
            echo "✓ Thành công\n\n";
            
        } catch (PDOException $e) {
            // Bỏ qua lỗi "Duplicate column" hoặc "already exists"
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Đã tồn tại, bỏ qua\n\n";
                continue;
            }
            
            $errorCount++;
            echo "✗ Lỗi: " . $e->getMessage() . "\n\n";
        }
    }
    
    // Kiểm tra kết quả
    echo "\n=== KIỂM TRA KẾT QUẢ ===\n";
    
    // Kiểm tra cột trangthai
    $checkColumn = $db->query("SHOW COLUMNS FROM hanghoa LIKE 'trangthai'");
    if ($checkColumn->rowCount() > 0) {
        $columnInfo = $checkColumn->fetch(PDO::FETCH_ASSOC);
        echo "✓ Cột 'trangthai' đã được thêm thành công\n";
        echo "  - Kiểu dữ liệu: " . $columnInfo['Type'] . "\n";
        echo "  - Giá trị mặc định: " . $columnInfo['Default'] . "\n";
    } else {
        echo "✗ Cột 'trangthai' chưa được thêm\n";
    }
    
    // Kiểm tra bảng history
    $checkTable = $db->query("SHOW TABLES LIKE 'hanghoa_trangthai_history'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Bảng 'hanghoa_trangthai_history' đã được tạo\n";
    } else {
        echo "⚠ Bảng 'hanghoa_trangthai_history' chưa được tạo\n";
    }
    
    // Kiểm tra trigger
    $checkTrigger = $db->query("SHOW TRIGGERS LIKE 'hanghoa'");
    $triggers = $checkTrigger->fetchAll(PDO::FETCH_ASSOC);
    $hasTrigger = false;
    foreach ($triggers as $trigger) {
        if ($trigger['Trigger'] === 'hanghoa_trangthai_log') {
            $hasTrigger = true;
            break;
        }
    }
    
    if ($hasTrigger) {
        echo "✓ Trigger 'hanghoa_trangthai_log' đã được tạo\n";
    } else {
        echo "⚠ Trigger 'hanghoa_trangthai_log' chưa được tạo (có thể cần quyền TRIGGER)\n";
    }
    
    // Đếm số sản phẩm theo trạng thái
    $countStatus = $db->query("
        SELECT trangthai, COUNT(*) as total 
        FROM hanghoa 
        GROUP BY trangthai
    ");
    
    echo "\n=== THỐNG KÊ SẢN PHẨM THEO TRẠNG THÁI ===\n";
    while ($row = $countStatus->fetch(PDO::FETCH_ASSOC)) {
        $statusLabel = [
            'dang_ban' => 'Đang bán',
            'ngung_ban' => 'Ngừng bán',
            'het_hang' => 'Hết hàng'
        ];
        echo "  - " . ($statusLabel[$row['trangthai']] ?? $row['trangthai']) . ": " . $row['total'] . " sản phẩm\n";
    }
    
    echo "\n=== MIGRATION HOÀN TẤT ===\n";
    echo "Thành công: $successCount câu lệnh\n";
    echo "Lỗi: $errorCount câu lệnh\n";
    
} catch (Exception $e) {
    echo "\n✗ LỖI NGHIÊM TRỌNG: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
