/**
 * CSRF Protection Helper for JavaScript
 * Tự động thêm CSRF token vào các AJAX requests
 * 
 * Cách sử dụng:
 * 1. Include script này trong trang
 * 2. Đảm bảo có meta tag: <meta name="csrf-token" content="<?= csrf_token() ?>">
 * 3. Các fetch/XMLHttpRequest sẽ tự động có CSRF token
 */

(function() {
    'use strict';
    
    // Lấy CSRF token từ meta tag
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }
    
    // Expose globally
    window.getCSRFToken = getCSRFToken;
    
    // Override fetch để tự động thêm CSRF token
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Chỉ thêm cho POST, PUT, DELETE, PATCH
        const method = (options.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            options.headers = options.headers || {};
            
            // Nếu headers là Headers object
            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-CSRF-Token')) {
                    options.headers.set('X-CSRF-Token', getCSRFToken());
                }
            } else {
                // Nếu headers là plain object
                if (!options.headers['X-CSRF-Token']) {
                    options.headers['X-CSRF-Token'] = getCSRFToken();
                }
            }
        }
        
        return originalFetch.call(this, url, options);
    };
    
    // Override XMLHttpRequest để tự động thêm CSRF token
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url) {
        this._method = method;
        this._url = url;
        return originalXHROpen.apply(this, arguments);
    };
    
    XMLHttpRequest.prototype.send = function(data) {
        const method = (this._method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            this.setRequestHeader('X-CSRF-Token', getCSRFToken());
        }
        return originalXHRSend.apply(this, arguments);
    };
    
    // jQuery AJAX setup (nếu có jQuery)
    if (typeof jQuery !== 'undefined') {
        jQuery.ajaxSetup({
            beforeSend: function(xhr, settings) {
                const method = (settings.type || 'GET').toUpperCase();
                if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                    xhr.setRequestHeader('X-CSRF-Token', getCSRFToken());
                }
            }
        });
    }
    
    // Tự động thêm CSRF token vào forms
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[method="post"], form[method="POST"]');
        forms.forEach(function(form) {
            // Kiểm tra xem đã có csrf_token chưa
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = getCSRFToken();
                form.appendChild(input);
            }
        });
    });
    
    console.log('CSRF Helper loaded. Token:', getCSRFToken() ? 'Found' : 'Not found');
})();
