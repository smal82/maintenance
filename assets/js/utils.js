// assets/js/utils.js

const Utils = {
    // Format date
    formatDate: function(dateString, format = 'dd/mm/yyyy') {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year)
            .replace('HH', hours)
            .replace('MM', minutes);
    },
    
    // Format currency
    formatCurrency: function(amount, currency = '€') {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Show toast notification
    showToast: function(message, type = 'info', duration = 3000) {
        const toast = $('<div>')
            .addClass(`toast toast-${type}`)
            .html(`
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            `)
            .appendTo('body');
        
        setTimeout(() => {
            toast.addClass('show');
        }, 10);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    getToastIcon: function(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || icons.info;
    },
    
    // Confirm dialog
    confirm: function(message, title = 'Conferma') {
        return new Promise((resolve) => {
            const modal = $(`
                <div class="modal-overlay" id="confirmModal">
                    <div class="modal">
                        <div class="modal-header">
                            <h3>${title}</h3>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline" id="cancelBtn">Annulla</button>
                            <button class="btn btn-danger" id="confirmBtn">Conferma</button>
                        </div>
                    </div>
                </div>
            `).appendTo('body');
            
            setTimeout(() => modal.addClass('show'), 10);
            
            modal.find('#confirmBtn').on('click', function() {
                modal.removeClass('show');
                setTimeout(() => {
                    modal.remove();
                    resolve(true);
                }, 300);
            });
            
            modal.find('#cancelBtn, .modal-overlay').on('click', function(e) {
                if (e.target === e.currentTarget) {
                    modal.removeClass('show');
                    setTimeout(() => {
                        modal.remove();
                        resolve(false);
                    }, 300);
                }
            });
        });
    },
    
    // Validate form
    validateForm: function(formId) {
        let isValid = true;
        const form = $(`#${formId}`);
        
        form.find('[required]').each(function() {
            const input = $(this);
            const value = input.val().trim();
            
            if (!value) {
                isValid = false;
                input.addClass('error');
                
                let errorMsg = input.next('.form-error');
                if (!errorMsg.length) {
                    errorMsg = $('<span class="form-error">Campo obbligatorio</span>');
                    input.after(errorMsg);
                }
            } else {
                input.removeClass('error');
                input.next('.form-error').remove();
            }
        });
        
        return isValid;
    },
    
    // Clear form
    clearForm: function(formId) {
        const form = $(`#${formId}`);
        form[0].reset();
        form.find('.error').removeClass('error');
        form.find('.form-error').remove();
    },
    
    // Generate QR Code URL
    generateQRCode: function(data, size = 300) {
        return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(data)}`;
    },
    
    // Copy to clipboard
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Copiato negli appunti', 'success');
            });
        } else {
            const textarea = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            textarea.remove();
            this.showToast('Copiato negli appunti', 'success');
        }
    },
    
    // Format file size
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },
    
    // Get priority color
    getPriorityColor: function(priority) {
        const colors = {
            low: '#95a5a6',
            medium: '#f39c12',
            high: '#e74c3c',
            critical: '#c0392b'
        };
        return colors[priority] || colors.medium;
    },
    
    // Get status color
    getStatusColor: function(status) {
        const colors = {
            scheduled: '#3498db',
            in_progress: '#f39c12',
            completed: '#2ecc71',
            cancelled: '#95a5a6',
            operational: '#2ecc71',
            maintenance: '#f39c12',
            broken: '#e74c3c',
            retired: '#95a5a6'
        };
        return colors[status] || '#3498db';
    },
    
    // Parse query string
    parseQueryString: function(query) {
        const params = {};
        const pairs = (query || window.location.search.slice(1)).split('&');
        
        pairs.forEach(pair => {
            const [key, value] = pair.split('=');
            if (key) {
                params[decodeURIComponent(key)] = decodeURIComponent(value || '');
            }
        });
        
        return params;
    },
    
    // Build query string
    buildQueryString: function(params) {
        return Object.keys(params)
            .filter(key => params[key] !== null && params[key] !== undefined && params[key] !== '')
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
    },
    
    // Truncate text
    truncate: function(text, length = 50) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    },
    
    // Scroll to top
    scrollToTop: function(smooth = true) {
        if (smooth) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.scrollTo(0, 0);
        }
    }
};

// Add toast styles dynamically
$(document).ready(function() {
    if (!$('#toast-styles').length) {
        $('head').append(`
            <style id="toast-styles">
                .toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 16px 24px;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    z-index: 9999;
                    opacity: 0;
                    transform: translateY(20px);
                    transition: all 0.3s ease;
                    color: white;
                    font-weight: 500;
                }
                .toast.show {
                    opacity: 1;
                    transform: translateY(0);
                }
                .toast-success { background-color: #2ecc71; }
                .toast-error { background-color: #e74c3c; }
                .toast-warning { background-color: #f39c12; }
                .toast-info { background-color: #3498db; }
                .toast i { font-size: 1.25rem; }
            </style>
        `);
    }
});