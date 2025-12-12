<?php
/**
 * Export PDF - Sử dụng TCPDF
 * Xuất hóa đơn đơn hàng ra PDF
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt display để không làm hỏng PDF
ini_set('log_errors', 1);

require_once __DIR__ . '/OrderExporter.php';
require_once __DIR__ . '/../../../../../vendor/autoload.php'; // Composer autoload

use TCPDF;

// Kiểm tra quyền admin
session_start();
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    http_response_code(403);
    die('Unauthorized - Please login first');
}

try {
    $exporter = new OrderExporter();

    // Xác định loại export
    $type = $_GET['type'] ?? 'single'; // single, multiple, summary
    $orderIds = [];

    if ($type === 'single') {
        if (!isset($_GET['order_id'])) {
            die('Missing order_id parameter');
        }
        $orderIds = [intval($_GET['order_id'])];
    } elseif ($type === 'multiple') {
        if (!isset($_GET['order_ids'])) {
            die('Missing order_ids parameter');
        }
        $orderIds = array_map('intval', explode(',', $_GET['order_ids']));
    }

    // Tạo PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('LeQuocAnh Shop');
    $pdf->SetAuthor('LeQuocAnh Shop');
    $pdf->SetTitle('Hóa đơn đơn hàng');

    // Font hỗ trợ tiếng Việt
    $pdf->SetFont('dejavusans', '', 10);

    // Xóa header/footer mặc định
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    if ($type === 'summary') {
        // Xuất tổng hợp
        exportSummary($pdf, $exporter, $_GET);
    } else {
        // Xuất chi tiết từng đơn
        $orders = $exporter->getMultipleOrdersDetails($orderIds);
        
        if (empty($orders)) {
            die('No orders found');
        }
        
        foreach ($orders as $index => $order) {
            if ($index > 0) {
                $pdf->AddPage();
            } else {
                $pdf->AddPage();
            }
            
            renderInvoice($pdf, $order);
        }
    }

    // Output PDF
    $filename = 'hoa_don_' . date('YmdHis') . '.pdf';
    $pdf->Output($filename, 'D'); // D = Download
    
} catch (Exception $e) {
    error_log('Export PDF Error: ' . $e->getMessage());
    die('Error creating PDF: ' . $e->getMessage());
}

/**
 * Render hóa đơn chi tiết
 */
function renderInvoice($pdf, $order) {
    $y = 15;
    
    // Logo và thông tin công ty
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 10, 'LEQUOCANH SHOP', 0, 1, 'C');
    
    $y += 10;
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->SetXY(15, $y);
    $pdf->MultiCell(0, 5, "Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM\nĐiện thoại: 0123456789 | Email: info@lequocanh.com", 0, 'C');
    
    $y += 15;
    
    // Tiêu đề hóa đơn
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 10, 'HÓA ĐƠN BÁN HÀNG', 0, 1, 'C');
    
    $y += 12;
    
    // Thông tin đơn hàng
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 6, 'Mã đơn hàng:', 0, 0);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, $order['ma_don_hang_text'], 0, 1);
    
    $y += 6;
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 6, 'Ngày đặt:', 0, 0);
    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($order['ngay_tao'])), 0, 1);
    
    $y += 10;
    
    // Thông tin khách hàng
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 6, 'THÔNG TIN KHÁCH HÀNG', 0, 1);
    
    $y += 7;
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 5, 'Họ tên:', 0, 0);
    $pdf->Cell(0, 5, $order['ten_khach_hang'] ?? 'N/A', 0, 1);
    
    $y += 5;
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 5, 'Điện thoại:', 0, 0);
    $pdf->Cell(0, 5, $order['dien_thoai'] ?? 'N/A', 0, 1);
    
    $y += 5;
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 5, 'Email:', 0, 0);
    $pdf->Cell(0, 5, $order['email'] ?? 'N/A', 0, 1);
    
    $y += 5;
    $pdf->SetXY(15, $y);
    $pdf->Cell(50, 5, 'Địa chỉ:', 0, 0);
    $pdf->MultiCell(0, 5, $order['dia_chi_giao_hang'] ?? 'N/A', 0, 'L');
    
    $y = $pdf->GetY() + 5;
    
    // Bảng sản phẩm
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetXY(15, $y);
    
    $pdf->Cell(10, 8, 'STT', 1, 0, 'C', true);
    $pdf->Cell(80, 8, 'Sản phẩm', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Đơn giá', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'SL', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Thành tiền', 1, 1, 'C', true);
    
    $y = $pdf->GetY();
    
    // Chi tiết sản phẩm
    $pdf->SetFont('dejavusans', '', 9);
    $stt = 1;
    foreach ($order['items'] as $item) {
        $pdf->SetXY(15, $y);
        $pdf->Cell(10, 7, $stt++, 1, 0, 'C');
        $pdf->Cell(80, 7, $item['tenhanghoa'], 1, 0, 'L');
        $pdf->Cell(25, 7, number_format($item['gia']) . 'đ', 1, 0, 'R');
        $pdf->Cell(20, 7, $item['so_luong'], 1, 0, 'C');
        $pdf->Cell(30, 7, number_format($item['gia'] * $item['so_luong']) . 'đ', 1, 1, 'R');
        $y = $pdf->GetY();
    }
    
    // Tổng tiền
    $y += 5;
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY(130, $y);
    $pdf->Cell(35, 6, 'Tạm tính:', 0, 0, 'L');
    $pdf->Cell(30, 6, number_format($order['tong_tien'] - ($order['phi_van_chuyen'] ?? 0)) . 'đ', 0, 1, 'R');
    
    $y += 6;
    $pdf->SetXY(130, $y);
    $pdf->Cell(35, 6, 'Phí vận chuyển:', 0, 0, 'L');
    $pdf->Cell(30, 6, number_format($order['phi_van_chuyen'] ?? 0) . 'đ', 0, 1, 'R');
    
    $y += 6;
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetXY(130, $y);
    $pdf->Cell(35, 8, 'TỔNG CỘNG:', 0, 0, 'L');
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(30, 8, number_format($order['tong_tien']) . 'đ', 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);
    
    // Phương thức thanh toán
    $y += 12;
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 6, 'Phương thức thanh toán: ' . strtoupper($order['phuong_thuc_thanh_toan']), 0, 1);
    
    // Footer
    $y = 260;
    $pdf->SetFont('dejavusans', 'I', 8);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 5, 'Cảm ơn quý khách đã mua hàng tại LeQuocAnh Shop!', 0, 1, 'C');
}

/**
 * Xuất báo cáo tổng hợp
 */
function exportSummary($pdf, $exporter, $filters) {
    $orders = $exporter->getOrdersList($filters);
    
    // Tính toán thống kê
    $stats = calculateStats($orders);
    
    // Page 1: Thống kê tổng quan
    $pdf->AddPage();
    renderStatisticsPage($pdf, $stats, $filters);
    
    // Page 2+: Danh sách chi tiết
    $pdf->AddPage('L'); // Landscape để có nhiều cột hơn
    renderDetailedList($pdf, $orders);
}

/**
 * Tính toán thống kê
 */
function calculateStats($orders) {
    $stats = [
        'total_orders' => count($orders),
        'total_revenue' => 0,
        'by_status' => [],
        'by_payment' => [],
        'by_payment_status' => [],
        'by_date' => []
    ];
    
    foreach ($orders as $order) {
        // Tổng doanh thu
        $stats['total_revenue'] += $order['tong_tien'];
        
        // Theo trạng thái
        $status = $order['trang_thai'];
        if (!isset($stats['by_status'][$status])) {
            $stats['by_status'][$status] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_status'][$status]['count']++;
        $stats['by_status'][$status]['revenue'] += $order['tong_tien'];
        
        // Theo phương thức thanh toán
        $payment = $order['phuong_thuc_thanh_toan'];
        if (!isset($stats['by_payment'][$payment])) {
            $stats['by_payment'][$payment] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_payment'][$payment]['count']++;
        $stats['by_payment'][$payment]['revenue'] += $order['tong_tien'];
        
        // Theo trạng thái thanh toán
        $paymentStatus = $order['trang_thai_thanh_toan'] ?? 'unknown';
        if (!isset($stats['by_payment_status'][$paymentStatus])) {
            $stats['by_payment_status'][$paymentStatus] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_payment_status'][$paymentStatus]['count']++;
        $stats['by_payment_status'][$paymentStatus]['revenue'] += $order['tong_tien'];
        
        // Theo ngày
        $date = date('Y-m-d', strtotime($order['ngay_tao']));
        if (!isset($stats['by_date'][$date])) {
            $stats['by_date'][$date] = ['count' => 0, 'revenue' => 0];
        }
        $stats['by_date'][$date]['count']++;
        $stats['by_date'][$date]['revenue'] += $order['tong_tien'];
    }
    
    return $stats;
}

/**
 * Render trang thống kê
 */
function renderStatisticsPage($pdf, $stats, $filters) {
    $y = 15;
    
    // Tiêu đề
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 12, 'BÁO CÁO TỔNG HỢP ĐƠN HÀNG', 0, 1, 'C');
    
    $y += 15;
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 6, 'Ngày xuất: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    // Bộ lọc đang áp dụng
    if (!empty($filters)) {
        $y += 8;
        $pdf->SetFont('dejavusans', 'I', 9);
        $pdf->SetXY(15, $y);
        $filterText = 'Bộ lọc: ';
        if (!empty($filters['status'])) $filterText .= 'Trạng thái=' . $filters['status'] . ' | ';
        if (!empty($filters['date_from'])) $filterText .= 'Từ ' . $filters['date_from'] . ' | ';
        if (!empty($filters['date_to'])) $filterText .= 'Đến ' . $filters['date_to'];
        $pdf->Cell(0, 5, $filterText, 0, 1, 'C');
    }
    
    $y += 15;
    
    // Box tổng quan
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Rect(15, $y, 180, 30, 'F');
    
    $pdf->SetXY(25, $y + 5);
    $pdf->Cell(80, 8, 'TỔNG SỐ ĐƠN HÀNG', 0, 0, 'L');
    $pdf->SetXY(115, $y + 5);
    $pdf->Cell(70, 8, 'TỔNG DOANH THU', 0, 1, 'L');
    
    $pdf->SetFont('dejavusans', 'B', 18);
    $pdf->SetXY(25, $y + 15);
    $pdf->Cell(80, 10, number_format($stats['total_orders']), 0, 0, 'L');
    $pdf->SetXY(115, $y + 15);
    $pdf->Cell(70, 10, number_format($stats['total_revenue']) . ' đ', 0, 1, 'L');
    
    $y += 40;
    
    // Thống kê theo trạng thái
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 8, 'THỐNG KÊ THEO TRẠNG THÁI ĐƠN HÀNG', 0, 1, 'L');
    
    $y += 10;
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->SetFillColor(236, 240, 241);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(15, $y);
    $pdf->Cell(60, 7, 'Trạng thái', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Số đơn', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Doanh thu', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Tỷ lệ', 1, 1, 'C', true);
    
    $y = $pdf->GetY();
    $pdf->SetFont('dejavusans', '', 9);
    
    foreach ($stats['by_status'] as $status => $data) {
        $percentage = ($data['count'] / $stats['total_orders']) * 100;
        $pdf->SetXY(15, $y);
        $pdf->Cell(60, 6, $status, 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['count']), 1, 0, 'C');
        $pdf->Cell(50, 6, number_format($data['revenue']) . ' đ', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($percentage, 1) . '%', 1, 1, 'C');
        $y = $pdf->GetY();
    }
    
    $y += 10;
    
    // Thống kê theo phương thức thanh toán
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 8, 'THỐNG KÊ THEO PHƯƠNG THỨC THANH TOÁN', 0, 1, 'L');
    
    $y += 10;
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->SetFillColor(236, 240, 241);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(15, $y);
    $pdf->Cell(60, 7, 'Phương thức', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Số đơn', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Doanh thu', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Tỷ lệ', 1, 1, 'C', true);
    
    $y = $pdf->GetY();
    $pdf->SetFont('dejavusans', '', 9);
    
    foreach ($stats['by_payment'] as $payment => $data) {
        $percentage = ($data['count'] / $stats['total_orders']) * 100;
        $pdf->SetXY(15, $y);
        $pdf->Cell(60, 6, strtoupper($payment), 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['count']), 1, 0, 'C');
        $pdf->Cell(50, 6, number_format($data['revenue']) . ' đ', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($percentage, 1) . '%', 1, 1, 'C');
        $y = $pdf->GetY();
    }
}

/**
 * Render danh sách chi tiết
 */
function renderDetailedList($pdf, $orders) {
    $y = 15;
    
    // Tiêu đề
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetXY(15, $y);
    $pdf->Cell(0, 10, 'DANH SÁCH CHI TIẾT ĐƠN HÀNG', 0, 1, 'C');
    
    $y += 12;
    
    // Header bảng
    $pdf->SetFont('dejavusans', 'B', 7);
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(15, $y);
    
    $pdf->Cell(8, 7, 'STT', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Mã đơn hàng', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Khách hàng', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Điện thoại', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Email', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Ngày đặt', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Tổng tiền', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'Trạng thái', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'PT TT', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'TT TT', 1, 1, 'C', true);
    
    $y = $pdf->GetY();
    
    // Dữ liệu
    $pdf->SetFont('dejavusans', '', 6);
    $pdf->SetTextColor(0, 0, 0);
    $stt = 1;
    
    foreach ($orders as $order) {
        if ($y > 180) {
            $pdf->AddPage('L');
            $y = 15;
            
            // Re-render header
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->SetFillColor(52, 152, 219);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(15, $y);
            
            $pdf->Cell(8, 7, 'STT', 1, 0, 'C', true);
            $pdf->Cell(35, 7, 'Mã đơn hàng', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Khách hàng', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Điện thoại', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'Email', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Ngày đặt', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Tổng tiền', 1, 0, 'C', true);
            $pdf->Cell(22, 7, 'Trạng thái', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'PT TT', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'TT TT', 1, 1, 'C', true);
            
            $y = $pdf->GetY();
            $pdf->SetFont('dejavusans', '', 6);
            $pdf->SetTextColor(0, 0, 0);
        }
        
        $pdf->SetXY(15, $y);
        $pdf->Cell(8, 5, $stt++, 1, 0, 'C');
        $pdf->Cell(35, 5, $order['ma_don_hang_text'], 1, 0, 'L');
        $pdf->Cell(30, 5, substr($order['ten_khach_hang'], 0, 20), 1, 0, 'L');
        $pdf->Cell(25, 5, $order['dien_thoai'] ?? '', 1, 0, 'C');
        $pdf->Cell(40, 5, substr($order['email'] ?? '', 0, 25), 1, 0, 'L');
        $pdf->Cell(25, 5, date('d/m/Y H:i', strtotime($order['ngay_tao'])), 1, 0, 'C');
        $pdf->Cell(25, 5, number_format($order['tong_tien']), 1, 0, 'R');
        $pdf->Cell(22, 5, $order['trang_thai'], 1, 0, 'C');
        $pdf->Cell(20, 5, strtoupper($order['phuong_thuc_thanh_toan']), 1, 0, 'C');
        $pdf->Cell(20, 5, $order['trang_thai_thanh_toan'] ?? '', 1, 1, 'C');
        
        $y = $pdf->GetY();
    }
}
