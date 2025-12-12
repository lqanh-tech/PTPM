/**
 * Order Export Handler
 * Xử lý xuất đơn hàng (PDF, Excel, Print)
 */

class OrderExportHandler {
    constructor() {
        this.selectedOrders = new Set();
        this.init();
    }

    init() {
        this.attachEventListeners();
    }

    /**
     * Gắn event listeners
     */
    attachEventListeners() {
        // Checkbox chọn tất cả
        const selectAllCheckbox = document.getElementById('select-all-orders');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.selectAllOrders(e.target.checked);
            });
        }

        // Checkbox từng đơn
        document.querySelectorAll('.order-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.toggleOrderSelection(e.target.value, e.target.checked);
            });
        });

        // Nút export
        document.getElementById('btn-export-pdf')?.addEventListener('click', () => this.exportPDF());
        document.getElementById('btn-export-excel')?.addEventListener('click', () => this.exportExcel());
        document.getElementById('btn-export-summary-pdf')?.addEventListener('click', () => this.exportSummaryPDF());
        document.getElementById('btn-export-summary-excel')?.addEventListener('click', () => this.exportSummaryExcel());
    }

    /**
     * Chọn/bỏ chọn tất cả đơn hàng
     */
    selectAllOrders(checked) {
        document.querySelectorAll('.order-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
            this.toggleOrderSelection(checkbox.value, checked);
        });
        this.updateExportButtons();
    }

    /**
     * Toggle chọn đơn hàng
     */
    toggleOrderSelection(orderId, checked) {
        if (checked) {
            this.selectedOrders.add(orderId);
        } else {
            this.selectedOrders.delete(orderId);
        }
        this.updateExportButtons();
    }

    /**
     * Cập nhật trạng thái nút export
     */
    updateExportButtons() {
        const count = this.selectedOrders.size;
        const btnExportPDF = document.getElementById('btn-export-pdf');
        const btnExportExcel = document.getElementById('btn-export-excel');
        
        if (btnExportPDF) {
            btnExportPDF.disabled = count === 0;
            btnExportPDF.innerHTML = count > 0 
                ? `<i class="fas fa-file-pdf"></i> Xuất PDF (${count})` 
                : '<i class="fas fa-file-pdf"></i> Xuất PDF';
        }
        
        if (btnExportExcel) {
            btnExportExcel.disabled = count === 0;
            btnExportExcel.innerHTML = count > 0 
                ? `<i class="fas fa-file-excel"></i> Xuất Excel (${count})` 
                : '<i class="fas fa-file-excel"></i> Xuất Excel';
        }
    }

    /**
     * In hóa đơn đơn lẻ
     */
    printInvoice(orderId) {
        const url = `./elements_LQA/mgiohang/export/print_invoice.php?order_id=${orderId}`;
        window.open(url, '_blank', 'width=900,height=700');
    }

    /**
     * Xuất PDF chi tiết các đơn đã chọn
     */
    exportPDF() {
        if (this.selectedOrders.size === 0) {
            alert('Vui lòng chọn ít nhất 1 đơn hàng');
            return;
        }

        const orderIds = Array.from(this.selectedOrders).join(',');
        const type = this.selectedOrders.size === 1 ? 'single' : 'multiple';
        
        // Nếu là single, dùng order_id, nếu multiple dùng order_ids
        const param = type === 'single' ? `order_id=${orderIds}` : `order_ids=${orderIds}`;
        const url = `./elements_LQA/mgiohang/export/export_pdf.php?type=${type}&${param}`;
        
        window.open(url, '_blank');
    }

    /**
     * Xuất Excel chi tiết các đơn đã chọn
     */
    exportExcel() {
        if (this.selectedOrders.size === 0) {
            alert('Vui lòng chọn ít nhất 1 đơn hàng');
            return;
        }

        const orderIds = Array.from(this.selectedOrders).join(',');
        const url = `./elements_LQA/mgiohang/export/export_excel.php?type=detailed&order_ids=${orderIds}`;
        
        window.open(url, '_blank');
    }

    /**
     * Xuất PDF tổng hợp (theo bộ lọc hiện tại)
     */
    exportSummaryPDF() {
        const filters = this.getCurrentFilters();
        const queryString = new URLSearchParams(filters).toString();
        const url = `./elements_LQA/mgiohang/export/export_pdf.php?type=summary&${queryString}`;
        
        window.open(url, '_blank');
    }

    /**
     * Xuất Excel tổng hợp (theo bộ lọc hiện tại)
     */
    exportSummaryExcel() {
        const filters = this.getCurrentFilters();
        const queryString = new URLSearchParams(filters).toString();
        const url = `./elements_LQA/mgiohang/export/export_excel.php?type=summary&${queryString}`;
        
        window.open(url, '_blank');
    }

    /**
     * Lấy bộ lọc hiện tại từ form
     */
    getCurrentFilters() {
        const filters = {};
        
        const statusFilter = document.getElementById('filter-status');
        if (statusFilter && statusFilter.value) {
            filters.status = statusFilter.value;
        }
        
        const paymentFilter = document.getElementById('filter-payment');
        if (paymentFilter && paymentFilter.value) {
            filters.payment_method = paymentFilter.value;
        }
        
        const dateFrom = document.getElementById('filter-date-from');
        if (dateFrom && dateFrom.value) {
            filters.date_from = dateFrom.value;
        }
        
        const dateTo = document.getElementById('filter-date-to');
        if (dateTo && dateTo.value) {
            filters.date_to = dateTo.value;
        }
        
        const search = document.getElementById('search-input');
        if (search && search.value) {
            filters.search = search.value;
        }
        
        return filters;
    }

    /**
     * Hiển thị thông báo toast
     */
    showToast(message, type = 'info') {
        // Sử dụng toast notification có sẵn
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * Export menu dropdown
     */
    showExportMenu(orderId) {
        const menu = `
            <div class="export-dropdown-menu" id="export-menu-${orderId}">
                <button onclick="orderExporter.printInvoice(${orderId})" class="export-menu-item">
                    <i class="fas fa-print"></i> In hóa đơn
                </button>
                <button onclick="orderExporter.exportSinglePDF(${orderId})" class="export-menu-item">
                    <i class="fas fa-file-pdf"></i> Xuất PDF
                </button>
                <button onclick="orderExporter.exportSingleExcel(${orderId})" class="export-menu-item">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        `;
        
        // Hiển thị menu (cần implement UI)
        console.log('Export menu for order:', orderId);
    }

    /**
     * Xuất PDF đơn lẻ
     */
    exportSinglePDF(orderId) {
        const url = `./elements_LQA/mgiohang/export/export_pdf.php?type=single&order_id=${orderId}`;
        window.open(url, '_blank');
    }

    /**
     * Xuất Excel đơn lẻ
     */
    exportSingleExcel(orderId) {
        const url = `./elements_LQA/mgiohang/export/export_excel.php?type=detailed&order_ids=${orderId}`;
        window.open(url, '_blank');
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.orderExporter = new OrderExportHandler();
});

// Export cho sử dụng global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OrderExportHandler;
}
