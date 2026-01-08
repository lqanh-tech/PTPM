<?php
header('Content-Type: application/json');

try {

    $autoUpdatePrice = isset($_POST['auto_update_price']) ? true : false;
    $overrideExisting = isset($_POST['override_existing']) ? true : false;
    $createPriceFromImport = isset($_POST['create_price_from_import']) ? true : false;
    $autoApplyProfit = isset($_POST['auto_apply_profit']) ? true : false;
    $profitMargin = isset($_POST['profit_margin']) ? floatval($_POST['profit_margin']) : 20;
    
    if ($profitMargin < 0 || $profitMargin > 1000) {
        throw new Exception('Tỷ lệ lợi nhuận phải từ 0% đến 1000%');
    }
    
    $configFile = '../config/price_logic_config.php';
    if (!file_exists($configFile)) {
        throw new Exception('File cấu hình không tồn tại');
    }
    
    $configContent = file_get_contents($configFile);
    
    $configContent = preg_replace(
        '/const AUTO_UPDATE_PRICE_ON_IMPORT = (true|false);/',
        'const AUTO_UPDATE_PRICE_ON_IMPORT = ' . ($autoUpdatePrice ? 'true' : 'false') . ';',
        $configContent
    );
    
    $configContent = preg_replace(
        '/const OVERRIDE_EXISTING_PRICE = (true|false);/',
        'const OVERRIDE_EXISTING_PRICE = ' . ($overrideExisting ? 'true' : 'false') . ';',
        $configContent
    );
    
    $configContent = preg_replace(
        '/const CREATE_PRICE_FROM_IMPORT = (true|false);/',
        'const CREATE_PRICE_FROM_IMPORT = ' . ($createPriceFromImport ? 'true' : 'false') . ';',
        $configContent
    );
    
    $configContent = preg_replace(
        '/const AUTO_APPLY_PROFIT_MARGIN = (true|false);/',
        'const AUTO_APPLY_PROFIT_MARGIN = ' . ($autoApplyProfit ? 'true' : 'false') . ';',
        $configContent
    );
    
    $configContent = preg_replace(
        '/const DEFAULT_PROFIT_MARGIN = [\d.]+;/',
        'const DEFAULT_PROFIT_MARGIN = ' . $profitMargin . ';',
        $configContent
    );
    
    if (file_put_contents($configFile, $configContent) === false) {
        throw new Exception('Không thể ghi file cấu hình');
    }
    
    $logMessage = date('Y-m-d H:i:s') . " - Cấu hình giá đã được cập nhật:\n";
    $logMessage .= "- Tự động cập nhật giá: " . ($autoUpdatePrice ? 'Bật' : 'Tắt') . "\n";
    $logMessage .= "- Ghi đè giá đã có: " . ($overrideExisting ? 'Bật' : 'Tắt') . "\n";
    $logMessage .= "- Tạo giá từ phiếu nhập: " . ($createPriceFromImport ? 'Bật' : 'Tắt') . "\n";
    $logMessage .= "- Tự động áp dụng lợi nhuận: " . ($autoApplyProfit ? 'Bật' : 'Tắt') . "\n";
    $logMessage .= "- Tỷ lệ lợi nhuận: " . $profitMargin . "%\n\n";
    
    $logFile = dirname(__FILE__) . '/price_config_changes.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cấu hình đã được lưu thành công!',
        'config' => [
            'auto_update_price_on_import' => $autoUpdatePrice,
            'override_existing_price' => $overrideExisting,
            'create_price_from_import' => $createPriceFromImport,
            'auto_apply_profit_margin' => $autoApplyProfit,
            'default_profit_margin' => $profitMargin
        ]
    ]);
    
} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
