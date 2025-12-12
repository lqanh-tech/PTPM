/**
 * Support Page JavaScript
 * Handles ticket creation and chat functionality
 */

let currentTicketId = null;
let refreshInterval = null;

// Load user's tickets
async function loadTickets() {
    try {
        const response = await fetch('../api/support_tickets.php?action=user_list', {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        renderTicketsList(result.data.tickets);
        
        // Don't auto-reload ticket detail to avoid duplicate calls
    } catch (error) {
        console.error('Load tickets error:', error);
        // Silent fail - don't show any error
    }
}

// Render tickets list
function renderTicketsList(tickets) {
    const container = document.getElementById('ticketsList');
    
    if (tickets.length === 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">Bạn chưa có yêu cầu hỗ trợ nào</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    Tạo yêu cầu mới
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = tickets.map(ticket => `
        <div class="ticket-card ${ticket.id === currentTicketId ? 'active' : ''}" data-ticket-id="${ticket.id}" onclick="loadTicketDetail(${ticket.id})">
            <div class="ticket-number">#${ticket.ticket_number}</div>
            <div class="ticket-subject">${escapeHtml(ticket.subject)}</div>
            <div class="ticket-meta">
                <i class="fas fa-clock"></i> ${formatDate(ticket.updated_at)}
                ${ticket.unread_count > 0 ? `<span class="badge bg-danger ms-2">${ticket.unread_count} mới</span>` : ''}
            </div>
            <span class="ticket-status status-${ticket.status}">${getStatusText(ticket.status)}</span>
        </div>
    `).join('');
}

// Load ticket detail
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
        
        // Update active state in list using data-ticket-id attribute
        document.querySelectorAll('.ticket-card').forEach(card => {
            const cardTicketId = card.getAttribute('data-ticket-id');
            if (cardTicketId == ticketId) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
        
    } catch (error) {
        console.error('Load ticket detail error:', error);
        // Don't show alert, just log to console
    }
}

// Render ticket detail
function renderTicketDetail(ticket, messages) {
    const container = document.getElementById('chatContainer');
    
    container.innerHTML = `
        <div class="chat-header">
            <h5 class="mb-1">${escapeHtml(ticket.subject)}</h5>
            <div class="d-flex gap-2">
                <span class="ticket-status status-${ticket.status}">${getStatusText(ticket.status)}</span>
                <span class="badge bg-secondary">${getCategoryText(ticket.category)}</span>
            </div>
        </div>
        
        <div class="messages-area" id="messagesArea">
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
        
        ${ticket.status !== 'closed' ? `
            <div class="chat-input">
                <textarea id="messageInput" class="form-control mb-2" placeholder="Nhập tin nhắn..." rows="2"></textarea>
                <button onclick="sendMessage(${ticket.id})" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Gửi
                </button>
            </div>
        ` : '<div class="chat-input"><p class="text-muted mb-0">Yêu cầu này đã được đóng</p></div>'}
    `;
    
    // Scroll to bottom
    const messagesArea = document.getElementById('messagesArea');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
}

// Create new ticket
async function createTicket() {
    try {
        const subject = document.getElementById('ticketSubject').value.trim();
        const category = document.getElementById('ticketCategory').value;
        const message = document.getElementById('ticketMessage').value.trim();
        
        if (!subject || !message) {
            alert('Vui lòng nhập đầy đủ thông tin');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('subject', subject);
        formData.append('category', category);
        formData.append('message', message);
        
        const response = await fetch('../api/support_tickets.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Close modal
            const modalEl = document.getElementById('newTicketModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
            
            // Clear form
            document.getElementById('ticketSubject').value = '';
            document.getElementById('ticketMessage').value = '';
            
            // Reload tickets
            await loadTickets();
            
            // Open the new ticket
            if (result.data && result.data.ticket_id) {
                loadTicketDetail(result.data.ticket_id);
            }
            
            alert('Đã tạo yêu cầu hỗ trợ thành công!');
        } else {
            alert(result.error || 'Không thể tạo yêu cầu');
        }
    } catch (error) {
        console.error('Create ticket error:', error);
        alert('Không thể tạo yêu cầu. Vui lòng thử lại.');
    }
}

// Send message
async function sendMessage(ticketId) {
    try {
        const message = document.getElementById('messageInput').value.trim();
        
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
            document.getElementById('messageInput').value = '';
            loadTicketDetail(ticketId);
        } else {
            console.error('Send message failed:', result.error);
        }
    } catch (error) {
        console.error('Send message error:', error);
    }
}

// Helper functions
function getStatusText(status) {
    const statusMap = {
        'open': 'Mới',
        'in_progress': 'Đang xử lý',
        'waiting_user': 'Chờ phản hồi',
        'resolved': 'Đã giải quyết',
        'closed': 'Đã đóng'
    };
    return statusMap[status] || status;
}

function getCategoryText(category) {
    const categoryMap = {
        'review_report': 'Báo cáo bình luận',
        'order_issue': 'Vấn đề đơn hàng',
        'product_question': 'Câu hỏi sản phẩm',
        'other': 'Khác'
    };
    return categoryMap[category] || category;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) return 'Hôm nay ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    if (diffDays === 1) return 'Hôm qua ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    return date.toLocaleString('vi-VN');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
loadTickets();

// Auto refresh every 10 seconds
refreshInterval = setInterval(loadTickets, 10000);

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Handle Enter key in message input
document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && e.target.id === 'messageInput') {
        e.preventDefault();
        if (currentTicketId) {
            sendMessage(currentTicketId);
        }
    }
});
