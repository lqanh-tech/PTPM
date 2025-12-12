<?php
/**
 * Trang Quản Lý Hỗ Trợ Khách Hàng (Support Tickets)
 * Admin có thể xem và trả lời tickets
 */
?>

<style>
.support-tickets {
    padding: 20px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-box h3 {
    font-size: 2rem;
    margin: 10px 0;
}

.tickets-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 20px;
    height: calc(100vh - 300px);
}

.tickets-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-y: auto;
}

.ticket-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
}

.ticket-item:hover {
    background: #f8f9fa;
}

.ticket-item.active {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.ticket-number {
    font-weight: 700;
    color: #333;
}

.ticket-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-open { background: #e3f2fd; color: #1976d2; }
.status-in_progress { background: #fff3e0; color: #f57c00; }
.status-waiting_user { background: #f3e5f5; color: #7b1fa2; }
.status-resolved { background: #e8f5e9; color: #388e3c; }
.status-closed { background: #f5f5f5; color: #616161; }

.ticket-subject {
    font-weight: 600;
    margin-bottom: 5px;
}

.ticket-meta {
    font-size: 0.85rem;
    color: #666;
}

.ticket-detail {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ticket-detail-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.ticket-detail-header h3 {
    margin: 0 0 10px 0;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.messages-container {
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

.reply-form {
    padding: 20px;
    border-top: 1px solid #f0f0f0;
}

.reply-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
}

.reply-form button {
    margin-top: 10px;
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

<div class="support-tickets">
    <h1><i class="fas fa-headset"></i> Hỗ Trợ Khách Hàng</h1>
    
    <!-- Stats -->
    <div class="stats-row" id="statsRow">
        <div class="stat-box">
            <p>Tổng tickets</p>
            <h3 id="totalTickets">-</h3>
        </div>
        <div class="stat-box">
            <p>Mới</p>
            <h3 id="openTickets">-</h3>
        </div>
        <div class="stat-box">
            <p>Đang xử lý</p>
            <h3 id="inProgressTickets">-</h3>
        </div>
        <div class="stat-box">
            <p>Chờ khách</p>
            <h3 id="waitingUserTickets">-</h3>
        </div>
        <div class="stat-box">
            <p>Đã giải quyết</p>
            <h3 id="resolvedTickets">-</h3>
        </div>
    </div>
    
    <!-- Tickets Container -->
    <div class="tickets-container">
        <!-- Tickets List -->
        <div class="tickets-list" id="ticketsList">
            <div class="loading text-center p-4">
                <div class="spinner-border" role="status"></div>
                <p>Đang tải...</p>
            </div>
        </div>
        
        <!-- Ticket Detail -->
        <div class="ticket-detail" id="ticketDetail">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Chọn một ticket để xem chi tiết</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentTicketId = null;
let refreshInterval = null;

async function loadTickets() {
    try {
        const response = await fetch('../api/support_tickets.php?action=admin_list', {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        renderStats(result.data.stats);
        renderTicketsList(result.data.tickets);
        
    } catch (error) {
        console.error('Load tickets error:', error);
    }
}

function renderStats(stats) {
    document.getElementById('totalTickets').textContent = stats.total || 0;
    document.getElementById('openTickets').textContent = stats.open_count || 0;
    document.getElementById('inProgressTickets').textContent = stats.in_progress_count || 0;
    document.getElementById('waitingUserTickets').textContent = stats.waiting_user_count || 0;
    document.getElementById('resolvedTickets').textContent = stats.resolved_count || 0;
}

function renderTicketsList(tickets) {
    const container = document.getElementById('ticketsList');
    
    if (tickets.length === 0) {
        container.innerHTML = '<p class="text-center p-4">Không có ticket nào</p>';
        return;
    }
    
    container.innerHTML = tickets.map(ticket => `
        <div class="ticket-item ${ticket.id == currentTicketId ? 'active' : ''}" data-ticket-id="${ticket.id}" onclick="loadTicketDetail(${ticket.id})">
            <div class="ticket-header">
                <span class="ticket-number">#${ticket.ticket_number}</span>
                <span class="ticket-status status-${ticket.status}">${getStatusText(ticket.status)}</span>
            </div>
            <div class="ticket-subject">${escapeHtml(ticket.subject)}</div>
            <div class="ticket-meta">
                <i class="fas fa-user"></i> ${escapeHtml(ticket.user_name)}
                ${ticket.unread_count > 0 ? `<span class="badge bg-danger ms-2">${ticket.unread_count} mới</span>` : ''}
            </div>
            <div class="ticket-meta">
                <i class="fas fa-clock"></i> ${formatDate(ticket.updated_at)}
            </div>
        </div>
    `).join('');
}

async function loadTicketDetail(ticketId) {
    try {
        currentTicketId = ticketId;
        
        const response = await fetch(`../api/support_tickets.php?action=details&ticket_id=${ticketId}`, {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        renderTicketDetail(result.data.ticket, result.data.messages);
        
        // Update active state in list using data-ticket-id
        document.querySelectorAll('.ticket-item').forEach(item => {
            const itemTicketId = item.getAttribute('data-ticket-id');
            if (itemTicketId == ticketId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
    } catch (error) {
        console.error('Load ticket detail error:', error);
    }
}

function renderTicketDetail(ticket, messages) {
    const container = document.getElementById('ticketDetail');
    
    container.innerHTML = `
        <div class="ticket-detail-header">
            <h3>${escapeHtml(ticket.subject)}</h3>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="ticket-status status-${ticket.status}">${getStatusText(ticket.status)}</span>
                    <span class="badge bg-secondary ms-2">${ticket.category}</span>
                </div>
                <div class="ticket-actions">
                    <select onchange="updateTicketStatus(${ticket.id}, this.value)" class="form-select form-select-sm">
                        <option value="">Đổi trạng thái...</option>
                        <option value="open">Mới</option>
                        <option value="in_progress">Đang xử lý</option>
                        <option value="waiting_user">Chờ khách hàng</option>
                        <option value="resolved">Đã giải quyết</option>
                        <option value="closed">Đóng</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="messages-container" id="messagesContainer">
            ${messages.map(msg => `
                <div class="message ${msg.sender_type}">
                    <div class="message-avatar">${getInitials(msg.sender_id)}</div>
                    <div class="message-content">
                        <div class="message-bubble">${escapeHtml(msg.message)}</div>
                        <div class="message-time">${formatDate(msg.created_at)}</div>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <div class="reply-form">
            <textarea id="replyMessage" placeholder="Nhập tin nhắn trả lời..." class="form-control"></textarea>
            <button onclick="sendReply(${ticket.id})" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Gửi
            </button>
        </div>
    `;
    
    // Scroll to bottom
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

async function sendReply(ticketId) {
    try {
        const message = document.getElementById('replyMessage').value.trim();
        
        if (!message) {
            alert('Vui lòng nhập tin nhắn');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('ticket_id', ticketId);
        formData.append('message', message);
        
        const response = await fetch('../api/support_tickets.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('replyMessage').value = '';
            loadTicketDetail(ticketId);
            loadTickets();
        } else {
            console.error('Send reply failed:', result.error);
        }
    } catch (error) {
        console.error('Send reply error:', error);
    }
}

async function updateTicketStatus(ticketId, status) {
    if (!status) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('ticket_id', ticketId);
        formData.append('status', status);
        
        const response = await fetch('../api/support_tickets.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadTicketDetail(ticketId);
            loadTickets();
        } else {
            console.error('Update status failed:', result.error);
        }
    } catch (error) {
        console.error('Update status error:', error);
    }
}

function getStatusText(status) {
    const statusMap = {
        'open': 'Mới',
        'in_progress': 'Đang xử lý',
        'waiting_user': 'Chờ khách hàng',
        'resolved': 'Đã giải quyết',
        'closed': 'Đã đóng'
    };
    return statusMap[status] || status;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load on page load
loadTickets();

// Auto refresh every 10 seconds
refreshInterval = setInterval(loadTickets, 10000);

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
