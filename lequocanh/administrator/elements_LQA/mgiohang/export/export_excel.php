<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/OrderExporter.php';
require_once __DIR__ . '/../../../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    http_response_code(403);
    die('Unauthorized - Please login first');
}

try {
    $exporter = new OrderExporter();

    $type = $_GET['type'] ?? 'summary';
    $orderIds = [];

    if ($type === 'detailed') {
        if (!isset($_GET['order_ids'])) {
            die('Missing order_ids parameter');
        }
        $orderIds = array_map('intval', explode(',', $_GET['order_ids']));
    }

    $spreadsheet = new Spreadsheet();

    if ($type === 'summary') {

        exportSummarySheet($spreadsheet, $exporter, $_GET);
    } else {

        exportDetailedSheets($spreadsheet, $exporter, $orderIds);
    }

    $filename = 'don_hang_' . date('YmdHis') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    error_log('Export Excel Error: ' . $e->getMessage());
    die('Error creating Excel: ' . $e->getMessage());
}

function exportSummarySheet($spreadsheet, $exporter, $filters) {
    $orders = $exporter->getOrdersList($filters);
    
    $statsSheet = $spreadsheet->getActiveSheet();
    $statsSheet->setTitle('Thống kê');
    createStatisticsSheet($statsSheet, $orders, $filters);
    
    $detailSheet = $spreadsheet->createSheet(1);
    $detailSheet->setTitle('Danh sách đơn hàng');
    createDetailSheet($detailSheet, $orders);
    
    $dateSheet = $spreadsheet->createSheet(2);
    $dateSheet->setTitle('Phân tích theo ngày');
    createDateAnalysisSheet($dateSheet, $orders);
}

function createStatisticsSheet($sheet, $orders, $filters) {

    $stats = calculateStatistics($orders);
    
    $row = 1;
    
    $sheet->setCellValue('A' . $row, 'BÁO CÁO THỐNG KÊ ĐƠN HÀNG');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('2C3E50');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECF0F1');
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    $row = 3;
    $sheet->setCellValue('A' . $row, 'Ngày xuất: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:F3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    if (!empty($filters)) {
        $row = 4;
        $filterText = 'Bộ lọc: ';
        if (!empty($filters['status'])) $filterText .= 'Trạng thái=' . $filters['status'] . ' | ';
        if (!empty($filters['date_from'])) $filterText .= 'Từ ' . $filters['date_from'] . ' | ';
        if (!empty($filters['date_to'])) $filterText .= 'Đến ' . $filters['date_to'];
        $sheet->setCellValue('A' . $row, $filterText);
        $sheet->mergeCells('A4:F4');
        $sheet->getStyle('A4')->getFont()->setItalic(true);
    }
    
    $row = 6;
    
    $sheet->setCellValue('A' . $row, 'TỔNG QUAN');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3498DB');
    $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Tổng số đơn hàng:');
    $sheet->setCellValue('B' . $row, $stats['total_orders']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('B' . $row)->getFont()->setSize(14)->getColor()->setRGB('2980B9');
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Tổng doanh thu:');
    $sheet->setCellValue('B' . $row, $stats['total_revenue']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('B' . $row)->getFont()->setSize(14)->getColor()->setRGB('E74C3C');
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Giá trị đơn trung bình:');
    $sheet->setCellValue('B' . $row, $stats['avg_order_value']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    $row += 2;
    
    $sheet->setCellValue('A' . $row, 'THỐNG KÊ THEO TRẠNG THÁI');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('95A5A6');
    $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $headers = ['Trạng thái', 'Số đơn', 'Doanh thu', 'Tỷ lệ'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECF0F1');
        $col++;
    }
    
    $row++;
    foreach ($stats['by_status'] as $status => $data) {
        $percentage = ($data['count'] / $stats['total_orders']) * 100;
        $sheet->setCellValue('A' . $row, $status);
        $sheet->setCellValue('B' . $row, $data['count']);
        $sheet->setCellValue('C' . $row, $data['revenue']);
        $sheet->setCellValue('D' . $row, $percentage / 100);
        
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.0%');
        
        $row++;
    }
    
    $row += 2;
    
    $sheet->setCellValue('A' . $row, 'THỐNG KÊ THEO PHƯƠNG THỨC THANH TOÁN');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('16A085');
    $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECF0F1');
        $col++;
    }
    
    $row++;
    foreach ($stats['by_payment'] as $payment => $data) {
        $percentage = ($data['count'] / $stats['total_orders']) * 100;
        $sheet->setCellValue('A' . $row, strtoupper($payment));
        $sheet->setCellValue('B' . $row, $data['count']);
        $sheet->setCellValue('C' . $row, $data['revenue']);
        $sheet->setCellValue('D' . $row, $percentage / 100);
        
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.0%');
        
        $row++;
    }
    
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function createDetailSheet($sheet, $orders) {
    $row = 1;
    
    $sheet->setCellValue('A' . $row, 'DANH SÁCH CHI TIẾT ĐƠN HÀNG');
    $sheet->mergeCells('A1:L1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3498DB');
    $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
    $sheet->getRowDimension(1)->setRowHeight(25);
    
    $row = 3;
    $sheet->setCellValue('A' . $row, 'Ngày xuất: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:L3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 5;
    
    $headers = ['STT', 'Mã đơn hàng', 'Khách hàng', 'Điện thoại', 'Email', 'Địa chỉ', 'Ngày đặt', 'Tổng tiền', 'Trạng thái', 'PT Thanh toán', 'TT Thanh toán', 'Ghi chú'];
    $col = 'A';
    
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('34495E');
        $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }
    
    $row = 6;
    $stt = 1;
    $totalRevenue = 0;
    
    foreach ($orders as $order) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $order['ma_don_hang_text']);
        $sheet->setCellValue('C' . $row, $order['ten_khach_hang']);
        $sheet->setCellValue('D' . $row, $order['dien_thoai'] ?? '');
        $sheet->setCellValue('E' . $row, $order['email'] ?? '');
        $sheet->setCellValue('F' . $row, $order['dia_chi_giao_hang'] ?? '');
        $sheet->setCellValue('G' . $row, date('d/m/Y H:i', strtotime($order['ngay_tao'])));
        $sheet->setCellValue('H' . $row, $order['tong_tien']);
        $sheet->setCellValue('I' . $row, $order['trang_thai']);
        $sheet->setCellValue('J' . $row, strtoupper($order['phuong_thuc_thanh_toan']));
        $sheet->setCellValue('K' . $row, $order['trang_thai_thanh_toan'] ?? '');
        $sheet->setCellValue('L' . $row, $order['ghi_chu'] ?? '');
        
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        
        $statusColor = getStatusColor($order['trang_thai']);
        $sheet->getStyle('I' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($statusColor);
        
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':L' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8F9FA');
        }
        
        $totalRevenue += $order['tong_tien'];
        $row++;
    }
    
    $row++;
    $sheet->setCellValue('G' . $row, 'TỔNG DOANH THU:');
    $sheet->getStyle('G' . $row)->getFont()->setBold(true)->setSize(12);
    $sheet->setCellValue('H' . $row, $totalRevenue);
    $sheet->getStyle('H' . $row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('E74C3C');
    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    foreach (range('A', 'L') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $sheet->getStyle('A5:L' . ($row - 1))->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);
}

function createDateAnalysisSheet($sheet, $orders) {

    $byDate = [];
    foreach ($orders as $order) {
        $date = date('Y-m-d', strtotime($order['ngay_tao']));
        if (!isset($byDate[$date])) {
            $byDate[$date] = ['count' => 0, 'revenue' => 0, 'orders' => []];
        }
        $byDate[$date]['count']++;
        $byDate[$date]['revenue'] += $order['tong_tien'];
        $byDate[$date]['orders'][] = $order;
    }
    
    ksort($byDate);
    
    $row = 1;
    
    $sheet->setCellValue('A' . $row, 'PHÂN TÍCH DOANH THU THEO NGÀY');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('27AE60');
    $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
    
    $row = 3;
    
    $headers = ['Ngày', 'Số đơn', 'Doanh thu', 'Đơn TB', 'Tăng trưởng'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2ECC71');
        $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $col++;
    }
    
    $row = 4;
    $prevRevenue = 0;
    
    foreach ($byDate as $date => $data) {
        $avgOrder = $data['count'] > 0 ? $data['revenue'] / $data['count'] : 0;
        $growth = $prevRevenue > 0 ? (($data['revenue'] - $prevRevenue) / $prevRevenue) * 100 : 0;
        
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($date)));
        $sheet->setCellValue('B' . $row, $data['count']);
        $sheet->setCellValue('C' . $row, $data['revenue']);
        $sheet->setCellValue('D' . $row, $avgOrder);
        $sheet->setCellValue('E' . $row, $growth / 100);
        
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.0%');
        
        if ($growth > 0) {
            $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('27AE60');
        } elseif ($growth < 0) {
            $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('E74C3C');
        }
        
        $prevRevenue = $data['revenue'];
        $row++;
    }
    
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function calculateStatistics($orders) {
    $stats = [
        'total_orders' => count($orders),
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'by_status' => [],
        'by_payment' => []
    ];
    
    foreach ($orders as $order) {
        $stats['total_revenue'] += $order['tong_tien'];
        
        $status = $order['trang_thai'];
        if (!isset($stats['by_status'][$status])) {
            $stats['by_status'][$status] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_status'][$status]['count']++;
        $stats['by_status'][$status]['revenue'] += $order['tong_tien'];
        
        $payment = $order['phuong_thuc_thanh_toan'];
        if (!isset($stats['by_payment'][$payment])) {
            $stats['by_payment'][$payment] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_payment'][$payment]['count']++;
        $stats['by_payment'][$payment]['revenue'] += $order['tong_tien'];
    }
    
    $stats['avg_order_value'] = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;
    
    return $stats;
}

function exportDetailedSheets($spreadsheet, $exporter, $orderIds) {
    $orders = $exporter->getMultipleOrdersDetails($orderIds);
    
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Tổng quan');
    
    $row = 1;
    $sheet->setCellValue('A' . $row, 'DANH SÁCH ĐƠN HÀNG CHI TIẾT');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 3;
    $headers = ['Mã đơn hàng', 'Khách hàng', 'Ngày đặt', 'Tổng tiền', 'Trạng thái', 'Sheet'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E8F4F8');
        $col++;
    }
    
    $row = 4;
    $sheetIndex = 1;
    
    foreach ($orders as $order) {
        $sheet->setCellValue('A' . $row, $order['ma_don_hang_text']);
        $sheet->setCellValue('B' . $row, $order['ten_khach_hang']);
        $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($order['ngay_tao'])));
        $sheet->setCellValue('D' . $row, $order['tong_tien']);
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->setCellValue('E' . $row, $order['trang_thai']);
        $sheet->setCellValue('F' . $row, 'Sheet ' . ($sheetIndex + 1));
        
        createOrderDetailSheet($spreadsheet, $order, $sheetIndex);
        
        $row++;
        $sheetIndex++;
    }
    
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function createOrderDetailSheet($spreadsheet, $order, $index) {
    $sheet = $spreadsheet->createSheet($index);
    $sheet->setTitle(substr($order['ma_don_hang_text'], 0, 20));
    
    $row = 1;
    
    $sheet->setCellValue('A' . $row, 'CHI TIẾT ĐƠN HÀNG');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 3;
    $sheet->setCellValue('A' . $row, 'Mã đơn hàng:');
    $sheet->setCellValue('B' . $row, $order['ma_don_hang_text']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Ngày đặt:');
    $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($order['ngay_tao'])));
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Khách hàng:');
    $sheet->setCellValue('B' . $row, $order['ten_khach_hang']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Điện thoại:');
    $sheet->setCellValue('B' . $row, $order['dien_thoai']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Email:');
    $sheet->setCellValue('B' . $row, $order['email']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Địa chỉ:');
    $sheet->setCellValue('B' . $row, $order['dia_chi_giao_hang']);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->mergeCells('B' . $row . ':E' . $row);
    
    $row += 2;
    $sheet->setCellValue('A' . $row, 'DANH SÁCH SẢN PHẨM');
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    
    $row++;
    $headers = ['STT', 'Tên sản phẩm', 'Đơn giá', 'Số lượng', 'Thành tiền'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E8F4F8');
        $col++;
    }
    
    $row++;
    $stt = 1;
    foreach ($order['items'] as $item) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $item['tenhanghoa']);
        $sheet->setCellValue('C' . $row, $item['gia']);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $sheet->setCellValue('D' . $row, $item['so_luong']);
        $sheet->setCellValue('E' . $row, $item['gia'] * $item['so_luong']);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
        $row++;
    }
    
    $row++;
    $sheet->setCellValue('D' . $row, 'Tạm tính:');
    $sheet->getStyle('D' . $row)->getFont()->setBold(true);
    $sheet->setCellValue('E' . $row, $order['tong_tien'] - ($order['phi_van_chuyen'] ?? 0));
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    $row++;
    $sheet->setCellValue('D' . $row, 'Phí vận chuyển:');
    $sheet->getStyle('D' . $row)->getFont()->setBold(true);
    $sheet->setCellValue('E' . $row, $order['phi_van_chuyen'] ?? 0);
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    $row++;
    $sheet->setCellValue('D' . $row, 'TỔNG CỘNG:');
    $sheet->getStyle('D' . $row)->getFont()->setBold(true);
    $sheet->setCellValue('E' . $row, $order['tong_tien']);
    $sheet->getStyle('E' . $row)->getFont()->setBold(true)->getColor()->setRGB('DC3545');
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0 "đ"');
    
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'FFF3CD',
        'approved' => 'D1ECF1',
        'delivered' => 'CCE5FF',
        'completed' => 'D4EDDA',
        'cancelled' => 'F8D7DA',
        'processing' => 'D1ECF1',
        'Chờ xác nhận' => 'FFF3CD',
        'Đang giao' => 'D1ECF1',
        'Đã giao' => 'CCE5FF',
        'Hoàn tất' => 'D4EDDA',
        'Đã duyệt' => 'D1ECF1',
        'Đã hủy' => 'F8D7DA'
    ];
    
    return $colors[$status] ?? 'FFFFFF';
}
