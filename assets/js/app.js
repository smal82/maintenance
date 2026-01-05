// assets/js/app.js

$(document).ready(function() {
    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        const text = $(this).data('tooltip');
        const tooltip = $(`<div class="tooltip">${text}</div>`).appendTo('body');
        
        $(this).on('mouseenter', function() {
            const pos = $(this).offset();
            tooltip.css({
                top: pos.top - tooltip.outerHeight() - 8,
                left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
            }).addClass('show');
        }).on('mouseleave', function() {
            tooltip.removeClass('show');
        });
    });
    
    // Table sorting
    $('.table-sort').on('click', function() {
        const table = $(this).closest('table');
        const column = $(this).index();
        const isAsc = $(this).hasClass('asc');
        
        table.find('.table-sort').removeClass('asc desc');
        $(this).addClass(isAsc ? 'desc' : 'asc');
        
        const rows = table.find('tbody tr').get();
        rows.sort(function(a, b) {
            const A = $(a).find('td').eq(column).text().toUpperCase();
            const B = $(b).find('td').eq(column).text().toUpperCase();
            
            if (isAsc) {
                return A < B ? 1 : (A > B ? -1 : 0);
            } else {
                return A > B ? 1 : (A < B ? -1 : 0);
            }
        });
        
        $.each(rows, function(index, row) {
            table.find('tbody').append(row);
        });
    });
    
    // Table search
    $('.table-search input').on('input', Utils.debounce(function() {
        const searchText = $(this).val().toLowerCase();
        const table = $(this).closest('.table-container').find('table');
        
        table.find('tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchText));
        });
    }, 300));
    
    // Confirm delete actions
    $(document).on('click', '[data-confirm-delete]', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        const message = $(this).data('message') || 'Sei sicuro di voler eliminare questo elemento?';
        
        Utils.confirm(message, 'Conferma Eliminazione').then(confirmed => {
            if (confirmed) {
                if (url) {
                    window.location.href = url;
                } else {
                    $(this).closest('form').submit();
                }
            }
        });
    });
    
    // Auto-hide alerts
    $('.alert').each(function() {
        const autoHide = $(this).data('auto-hide');
        if (autoHide) {
            const duration = parseInt(autoHide) || 5000;
            const alert = $(this);
            setTimeout(() => {
                alert.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
        }
    });
    
    // Copy to clipboard buttons
    $(document).on('click', '[data-copy]', function() {
        const text = $(this).data('copy');
        Utils.copyToClipboard(text);
    });
    
    // Back to top button
    if ($('#backToTop').length === 0) {
        $('body').append('<button id="backToTop" class="btn-icon" style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000;"><i class="fas fa-arrow-up"></i></button>');
    }
    
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 300) {
            $('#backToTop').fadeIn();
        } else {
            $('#backToTop').fadeOut();
        }
    });
    
    $('#backToTop').on('click', function() {
        Utils.scrollToTop();
    });
    
    // Print buttons
    $(document).on('click', '[data-print]', function() {
        const target = $(this).data('print');
        if (target) {
            const content = $(target).html();
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Stampa</title>
                    <link rel="stylesheet" href="${APP_CONFIG.baseUrl}/assets/css/variables.css">
                    <link rel="stylesheet" href="${APP_CONFIG.baseUrl}/assets/css/reset.css">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        } else {
            window.print();
        }
    });
    
    // Export to CSV
    $(document).on('click', '[data-export-csv]', function() {
        const table = $($(this).data('export-csv'));
        const csv = [];
        
        // Headers
        const headers = [];
        table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        csv.push(headers.join(','));
        
        // Rows
        table.find('tbody tr').each(function() {
            const row = [];
            $(this).find('td').each(function() {
                row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv.push(row.join(','));
        });
        
        // Download
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'export_' + Date.now() + '.csv';
        link.click();
    });
    
    // Status badge update
    window.updateStatus = function(element, status) {
        const badges = {
            scheduled: 'badge-scheduled',
            in_progress: 'badge-in_progress',
            completed: 'badge-completed',
            cancelled: 'badge-cancelled'
        };
        
        $(element).removeClass('badge-scheduled badge-in_progress badge-completed badge-cancelled')
                  .addClass(badges[status])
                  .text(status.replace('_', ' ').toUpperCase());
    };
    
    // Priority badge update
    window.updatePriority = function(element, priority) {
        const badges = {
            low: 'badge-low',
            medium: 'badge-medium',
            high: 'badge-high',
            critical: 'badge-critical'
        };
        
        $(element).removeClass('badge-low badge-medium badge-high badge-critical')
                  .addClass(badges[priority])
                  .text(priority.toUpperCase());
    };
    
    // Initialize date inputs with today's date
    $('input[type="date"][data-today]').each(function() {
        if (!$(this).val()) {
            const today = new Date().toISOString().split('T')[0];
            $(this).val(today);
        }
    });
    
    // Handle ajax forms
    $('form[data-ajax]').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const formData = new FormData(this);
        
        Ajax.upload(url, formData, function(response) {
            if (response.success) {
                Utils.showToast(response.message || 'Operazione completata', 'success');
                if (response.redirect) {
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                }
            } else {
                Utils.showToast(response.message || 'Errore durante l\'operazione', 'error');
            }
        });
    });
    
    // Initialize page
    console.log('Maintenance System initialized');
});