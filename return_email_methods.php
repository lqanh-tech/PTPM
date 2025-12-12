// Add these methods to EmailService.php after buildPaymentConfirmedEmailHTML method (around line 796)

    /**
     * Build HTML email cho yêu cầu đổi/trả hàng
     */
    private function buildReturnRequestEmailHTML($order, $orderItems, $user, $reason)
    {
        $html = $this->getEmailHeader();
        
        $html .= '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px 30px; text-align: center;">';
        $html .= '<h1 style="color: white; margin: 0; font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">🔄 Yêu Cầu Đổi/Trả Hàng</h1>';
        $html .= '<p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">Chúng tôi đã tiếp nhận yêu cầu của bạn</p>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 30px;">';
        $html .= '<p style="font-size: 16px; margin-bottom: 10px;">Xin chào <strong style="color: #f5576c;">' . htmlspecialchars($user['hoten']) . '</strong>,</p>';
        $html .= '<p style="color: #555; line-height: 1.6;">Yêu cầu đổi/trả hàng cho đơn hàng <strong>#' . htmlspecialchars($order['ma_don_hang_text']) . '</strong> của bạn đã được tiếp nhận.</p>';
        
        // Lý do đổi trả
        if (!empty($reason)) {
            $html .= '<div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #ffc107;">';
            $html .= '<h3 style="margin-top: 0; color: #856404; font-size: 16px;">📝 Lý do đổi/trả</h3>';
            $html .= '<p style="color: #856404; margin: 0;">' . htmlspecialchars($reason) . '</p>';
            $html .= '</div>';
        }
        
        // Chi tiết sản phẩm
        $html .= $this->buildOrderItemsTable($orderItems);
        
        // Thông tin xử lý
        $html .= '<div style="background: #e3f2fd; padding: 25px; border-radius: 10px; margin: 25px 0; border-left: 4px solid #2196f3;">';
        $html .= '<h3 style="margin-top: 0; color: #1976d2; font-size: 18px;">⏰ Quy trình xử lý</h3>';
        $html .= '<ul style="margin: 10px 0; padding-left: 20px; color: #555; line-height: 1.8;">';
        $html .= '<li>Yêu cầu của bạn sẽ được xem xét trong <strong>24-48h</strong></li>';
        $html .= '<li>Chúng tôi sẽ thông báo kết quả qua email</li>';
        $html .= '<li>Nếu được chấp nhận, bạn sẽ nhận hướng dẫn gửi hàng trả</li>';
        $html .= '<li>Thời gian hoàn tiền/đổi hàng: <strong>7-10 ngày làm việc</strong></li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<p style="color: #999; font-size: 13px; margin-top: 30px; text-align: center; font-style: italic;">Cảm ơn bạn đã liên hệ. Chúng tôi sẽ hỗ trợ bạn sớm nhất có thể.</p>';
        $html .= '</div>';
        
        $html .= $this->getEmailFooter();
        
        return $html;
    }
    
    /**
     * Build HTML email cho đổi/trả hàng được duyệt
     */
    private function buildReturnApprovedEmailHTML($order, $orderItems, $user)
    {
        $html = $this->getEmailHeader();
        
        $html .= '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px 30px; text-align: center;">';
        $html .= '<h1 style="color: white; margin: 0; font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">✅ Yêu Cầu Đã Được Chấp Nhận!</h1>';
        $html .= '<p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">Đổi/trả hàng được duyệt</p>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 30px;">';
        $html .= '<p style="font-size: 16px; margin-bottom: 10px;">Xin chào <strong style="color: #11998e;">' . htmlspecialchars($user['hoten']) . '</strong>,</p>';
        $html .= '<p style="color: #555; line-height: 1.6;">Yêu cầu đổi/trả hàng cho đơn hàng <strong>#' . htmlspecialchars($order['ma_don_hang_text']) . '</strong> của bạn đã được chấp nhận!</p>';
        
        // Chi tiết sản phẩm
        $html .= $this->buildOrderItemsTable($orderItems);
        
        // Hướng dẫn gửi hàng
        $html .= '<div style="background: linear-gradient(to right, #f8f9fa, #e9ecef); padding: 25px; border-radius: 10px; margin: 25px 0; border-left: 4px solid #11998e;">';
        $html .= '<h3 style="margin-top: 0; color: #333; font-size: 18px;">📦 Hướng Dẫn Gửi Hàng Trả</h3>';
        $html .= '<ol style="margin: 10px 0; padding-left: 20px; color: #555; line-height: 1.8;">';
        $html .= '<li><strong>Chuẩn bị sản phẩm:</strong> Đóng gói cẩn thận, kèm hóa đơn (nếu có)</li>';
        $html .= '<li><strong>Gửi hàng:</strong> Mang đến bưu điện hoặc gọi shipper đến lấy</li>';
        $html .= '<li><strong>Địa chỉ nhận hàng:</strong><br><em>LQA Shop - 19 Nguyễn Hữu Thọ, Tân Phong, Q7, TP.HCM</em></li>';
        $html .= '<li><strong>Ghi rõ:</strong> Mã đơn hàng ' . htmlspecialchars($order['ma_don_hang_text']) . '</li>';
        $html .= '</ol>';
        $html .= '</div>';
        
        // Thông tin hoàn tiền
        $html .= '<div style="background: #e8f5e9; padding: 25px; border-radius: 10px; margin: 25px 0; border: 2px solid #4caf50;">';
        $html .= '<h3 style="margin-top: 0; color: #2e7d32; font-size: 18px;">💰 Thông Tin Hoàn Tiền</h3>';
        $html .= '<table style="width: 100%;">';
        $html .= '<tr><td style="padding: 8px 0; color: #555;"><strong>Số tiền:</strong></td><td style="text-align: right; font-size: 20px; color: #4caf50; font-weight: bold;">' . number_format($order['tong_tien'], 0, ',', '.') . ' đ</td></tr>';
        $html .= '<tr><td style="padding: 8px 0; color: #555;"><strong>Thời gian:</strong></td><td style="text-align: right;">7-10 ngày làm việc sau khi nhận hàng</td></tr>';
        $html .= '<tr><td style="padding: 8px 0; color: #555;"><strong>Phương thức:</strong></td><td style="text-align: right;">Chuyển khoản ngân hàng</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Lưu ý
        $html .= '<div style="background: #f3e5f5; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #9c27b0;">';
        $html .= '<h4 style="margin-top: 0; color: #7b1fa2;">📝 Lưu ý quan trọng</h4>';
        $html .= '<ul style="margin: 10px 0; padding-left: 20px; color: #555; line-height: 1.8;">';
        $html .= '<li>Vui lòng gửi hàng trong vòng <strong>3 ngày</strong></li>';
        $html .= '<li>Sản phẩm phải còn nguyên tem, chưa qua sử dụng</li>';
        $html .= '<li>Đóng gói cẩn thận để tránh hư hỏng trong quá trình vận chuyển</li>';
        $html .= '<li>Liên hệ hotline <strong style="color: #7b1fa2;">0956789012</strong> nếu cần hỗ trợ</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<p style="color: #999; font-size: 13px; margin-top: 30px; text-align: center; font-style: italic;">Cảm ơn bạn đã tin tưởng LQA Shop!</p>';
        $html .= '</div>';
        
        $html .= $this->getEmailFooter();
        
        return $html;
    }
