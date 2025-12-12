<?php
/**
 * Trang Hỗ Trợ Khách Hàng
 * User có thể tạo ticket và chat với admin
 */

require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: ../index.php?req=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỗ Trợ Khách Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
        }
        
        .support-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .tickets-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 20px;
            height: calc(100vh - 300px);
        }
        
        .tickets-sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .tickets-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .ticket-card {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .ticket-card:hover {
            background: #f8f9fa;
        }
        
        .ticket-card.active {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .ticket-number {
            font-weight: 700;
            color: #2196f3;
            margin-bottom: 5px;
        }
        
        .ticket-subject {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .ticket-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .ticket-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-open { background: #e3f2fd; color: #1976d2; }
        .status-in_progress { background: #fff3e0; color: #f57c00; }
        .status-waiting_user { background: #f3e5f5; color: #7b1fa2; }
        .status-resolved { background: #e8f5e9; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #616161; }
        
        .chat-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }
        
        .message.admin {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .message.user .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .message.admin .message-avatar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .message-content {
            flex: 1;
            max-width: 70%;
        }
        
        .message.admin .message-content {
            text-align: right;
        }
        
        .message-bubble {
            background: #f5f5f5;
            padding: 12px 16px;
            border-radius: 12px;
            display: inline-block;
            text-align: left;
        }
        
        .message.admin .message-bubble {
            background: #2196f3;
            color: white;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 5px;
        }
        
        .chat-input {
            padding: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .chat-input textarea {
            width: 100%;
            min-height: 60px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="support-container">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-headset"></i> Hỗ Trợ Khách Hàng</h1>
                    <p class="text-muted mb-0">Gửi yêu cầu hỗ trợ và chat với đội ngũ hỗ trợ</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fas fa-plus"></i> Tạo yêu cầu mới
                    </button>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                </div>
            </div>
        </div>
        
        <div class="tickets-grid">
            <!-- Tickets Sidebar -->
            <div class="tickets-sidebar">
                <div class="sidebar-header">
                    <h5 class="mb-0">Yêu cầu của bạn</h5>
                </div>
                <div class="tickets-list" id="ticketsList">
                    <div class="text-center p-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Đang tải...</p>
                    </div>
                </div>
            </div>
            
            <!-- Chat Container -->
            <div class="chat-container" id="chatContainer">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>Chọn một yêu cầu để xem chi tiết</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Ticket Modal -->
    <div class="modal fade" id="newTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo yêu cầu hỗ trợ mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" id="ticketSubject" class="form-control" placeholder="Mô tả ngắn gọn vấn đề...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Danh mục</label>
                        <select id="ticketCategory" class="form-select">
                            <option value="review_report">Báo cáo bình luận</option>
                            <option value="order_issue">Vấn đề đơn hàng</option>
                            <option value="product_question">Câu hỏi sản phẩm</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea id="ticketMessage" class="form-control" rows="5" placeholder="Mô tả chi tiết vấn đề của bạn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="createTicket()">Gửi yêu cầu</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="support.js?v=20241205_fix3"></script>
</body>
</html>
