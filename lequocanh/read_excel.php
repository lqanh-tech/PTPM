<?php
/**
 * Script đọc file Excel (.xlsx)
 * Sử dụng PhpSpreadsheet library
 */

// Cài đặt PhpSpreadsheet nếu chưa có:
// composer require phpoffice/phpspreadsheet

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function readExcelFile($filePath) {
    try {
        // Load file Excel
        $spreadsheet = IOFactory::load($filePath);
        
        // Lấy sheet đầu tiên
        $sheet = $spreadsheet->getActiveSheet();
        
        // Lấy tất cả dữ liệu
        $data = $sheet->toArray();
        
        return [
            'success' => true,
            'data' => $data,
            'rows' => count($data),
            'columns' => count($data[0] ?? [])
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Sử dụng
if (isset($_GET['file'])) {
    $filePath = $_GET['file'];
    $result = readExcelFile($filePath);
    
    if ($result['success']) {
        echo "<h2>Đọc file thành công!</h2>";
        echo "<p>Số dòng: " . $result['rows'] . "</p>";
        echo "<p>Số cột: " . $result['columns'] . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($result['data'] as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td style='padding: 5px;'>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Lỗi: " . $result['error'] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đọc File Excel</title>
</head>
<body>
    <h1>Upload File Excel</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_file" accept=".xlsx,.xls">
        <button type="submit">Đọc File</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        $uploadedFile = $_FILES['excel_file']['tmp_name'];
        $result = readExcelFile($uploadedFile);
        
        if ($result['success']) {
            echo "<h2>Đọc file thành công!</h2>";
            echo "<p>Số dòng: " . $result['rows'] . "</p>";
            echo "<p>Số cột: " . $result['columns'] . "</p>";
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($result['data'] as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td style='padding: 5px;'>" . htmlspecialchars($cell ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>Lỗi: " . $result['error'] . "</p>";
        }
    }
    ?>
</body>
</html>
