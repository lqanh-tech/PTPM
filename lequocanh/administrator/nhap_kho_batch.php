<?php
require_once 'elements_LQA/mod/database.php';

echo "<h2>Nhập kho hàng loạt - Thêm 10 đơn vị cho mỗi sản phẩm</h2>";

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    echo "<h3>1. Lấy danh sách hàng hóa...</h3>";
    $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa ORDER BY idhanghoa";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tìm thấy " . count($products) . " sản phẩm cần nhập kho.</p>";
    
    if (count($products) == 0) {
        echo "<p style='color: orange;'>Không có sản phẩm nào để nhập kho.</p>";
        exit;
    }

    echo "<h3>2. Tạo phiếu nhập...</h3>";
    $maPhieuNhap = "PN_" . date("YmdHis");
    $ngayNhap = date("Y-m-d H:i:s");
    $idNhanVien = 3;
    $idNCC = 1;
    $ghiChu = "Nhập kho hàng loạt - Thêm 10 đơn vị cho mỗi sản phẩm";
    
    $sqlPhieuNhap = "INSERT INTO phieunhap (maPhieuNhap, ngayNhap, idNhanVien, idNCC, ghiChu, trangThai) 
                     VALUES (?, ?, ?, ?, ?, 1)";
    $stmtPhieuNhap = $db->prepare($sqlPhieuNhap);
    $stmtPhieuNhap->execute([$maPhieuNhap, $ngayNhap, $idNhanVien, $idNCC, $ghiChu]);
    
    $idPhieuNhap = $db->lastInsertId();
    echo "<p style='color: green;'>✅ Tạo phiếu nhập thành công. Mã phiếu: <strong>$maPhieuNhap</strong>, ID: $idPhieuNhap</p>";

    echo "<h3>3. Xử lý từng sản phẩm...</h3>";
    $tongTien = 0;
    $soLuongNhap = 10;
    $processedCount = 0;
    $errorCount = 0;
    
    foreach ($products as $product) {
        try {
            $idhanghoa = $product['idhanghoa'];
            $tenhanghoa = $product['tenhanghoa'];
            $giathamkhao = $product['giathamkhao'];
            
            $giaNhap = $giathamkhao * 0.8;
            $thanhTien = $giaNhap * $soLuongNhap;
            
            $sqlCTPN = "INSERT INTO chitietphieunhap (idPhieuNhap, idhanghoa, soLuong, donGia, giaNhap, thanhTien) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmtCTPN = $db->prepare($sqlCTPN);
            $stmtCTPN->execute([
                $idPhieuNhap, 
                $idhanghoa, 
                $soLuongNhap, 
                $giathamkhao,
                $giaNhap,
                $thanhTien
            ]);
            
            $sqlCheckTonKho = "SELECT idTonKho, soLuong FROM tonkho WHERE idhanghoa = ?";
            $stmtCheck = $db->prepare($sqlCheckTonKho);
            $stmtCheck->execute([$idhanghoa]);
            $existingStock = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existingStock) {

                $soLuongMoi = $existingStock['soLuong'] + $soLuongNhap;
                $sqlUpdateTonKho = "UPDATE tonkho SET soLuong = ?, ngayCapNhat = CURRENT_TIMESTAMP WHERE idhanghoa = ?";
                $stmtUpdate = $db->prepare($sqlUpdateTonKho);
                $stmtUpdate->execute([$soLuongMoi, $idhanghoa]);
                
                echo "<small>✅ ID $idhanghoa - '$tenhanghoa': Tồn kho {$existingStock['soLuong']} → $soLuongMoi (+$soLuongNhap)</small><br>";
            } else {

                $sqlInsertTonKho = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, ngayCapNhat) VALUES (?, ?, 10, CURRENT_TIMESTAMP)";
                $stmtInsert = $db->prepare($sqlInsertTonKho);
                $stmtInsert->execute([$idhanghoa, $soLuongNhap]);
                
                echo "<small>✅ ID $idhanghoa - '$tenhanghoa': Tạo mới tồn kho với số lượng $soLuongNhap</small><br>";
            }
            
            try {
                $sqlLichSu = "INSERT INTO tonkho_lichsu (idhanghoa, soLuongTruoc, soLuongSau, soLuongThayDoi, loaiThayDoi, lyDoThayDoi, ngayThayDoi, idPhieuNhap) 
                             VALUES (?, ?, ?, ?, 'NHAP', 'Nhập kho hàng loạt', CURRENT_TIMESTAMP, ?)";
                $stmtLichSu = $db->prepare($sqlLichSu);
                $soLuongTruoc = $existingStock ? $existingStock['soLuong'] : 0;
                $soLuongSau = $soLuongTruoc + $soLuongNhap;
                $stmtLichSu->execute([$idhanghoa, $soLuongTruoc, $soLuongSau, $soLuongNhap, $idPhieuNhap]);
            } catch (Exception $e) {

                echo "<small style='color: orange;'>⚠️ Không thể ghi lịch sử cho sản phẩm ID $idhanghoa</small><br>";
            }
            
            $tongTien += $thanhTien;
            $processedCount++;
            
        } catch (Exception $e) {
            $errorCount++;
            echo "<small style='color: red;'>❌ Lỗi xử lý sản phẩm ID {$product['idhanghoa']}: " . $e->getMessage() . "</small><br>";
        }
    }
    
    echo "<h3>4. Hoàn tất phiếu nhập...</h3>";
    $sqlUpdateTongTien = "UPDATE phieunhap SET tongTien = ? WHERE idPhieuNhap = ?";
    $stmtUpdateTongTien = $db->prepare($sqlUpdateTongTien);
    $stmtUpdateTongTien->execute([$tongTien, $idPhieuNhap]);
    
    $db->commit();
    
    echo "<div style='border: 2px solid green; padding: 15px; background-color: #f0fff0; margin: 20px 0;'>";
    echo "<h3 style='color: green; margin-top: 0;'>🎉 NHẬP KHO THÀNH CÔNG!</h3>";
    echo "<p><strong>Phiếu nhập:</strong> $maPhieuNhap (ID: $idPhieuNhap)</p>";
    echo "<p><strong>Số sản phẩm xử lý:</strong> $processedCount/$" . count($products) . "</p>";
    echo "<p><strong>Số sản phẩm lỗi:</strong> $errorCount</p>";
    echo "<p><strong>Tổng tiền nhập:</strong> " . number_format($tongTien, 0, ',', '.') . " VNĐ</p>";
    echo "<p><strong>Số lượng nhập mỗi sản phẩm:</strong> $soLuongNhap đơn vị</p>";
    echo "<p><strong>Ngày nhập:</strong> $ngayNhap</p>";
    echo "</div>";
    
    echo "<h3>5. Thống kê tồn kho sau khi nhập:</h3>";
    $sqlThongKe = "SELECT COUNT(*) as total_products, SUM(soLuong) as total_stock FROM tonkho WHERE soLuong > 0";
    $stmtThongKe = $db->prepare($sqlThongKe);
    $stmtThongKe->execute();
    $thongKe = $stmtThongKe->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Tổng số sản phẩm có tồn kho:</strong> " . $thongKe['total_products'] . "</p>";
    echo "<p><strong>Tổng số lượng tồn kho:</strong> " . number_format($thongKe['total_stock']) . " đơn vị</p>";

} catch (Exception $e) {

    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo "<div style='border: 2px solid red; padding: 15px; background-color: #fff0f0; margin: 20px 0;'>";
    echo "<h3 style='color: red; margin-top: 0;'>❌ LỖI NHẬP KHO!</h3>";
    echo "<p><strong>Chi tiết lỗi:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Toàn bộ thao tác đã được hủy bỏ để đảm bảo tính nhất quán dữ liệu.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='../../index.php'>← Quay lại trang chủ</a> | <a href='index.php?req=tonkhoview'>Xem tồn kho</a></p>";
?>
