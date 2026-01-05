// assets/js/ajax.js

const Ajax = {
    // Default settings
    defaults: {
        dataType: 'json',
        beforeSend: function() {
            $('.loading-overlay').show();
        },
        complete: function() {
            $('.loading-overlay').hide();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Utils.showToast('Errore durante la richiesta', 'error');
        }
    },
    
    // GET request
    get: function(url, data = {}, success, error) {
        return $.ajax({
            url: url,
            method: 'GET',
            data: data,
            ...this.defaults,
            success: success || this.defaults.success,
            error: error || this.defaults.error
        });
    },
    
    // POST request
    post: function(url, data = {}, success, error) {
        data.csrf_token = APP_CONFIG.csrfToken;
        
        return $.ajax({
            url: url,
            method: 'POST',
            data: data,
            ...this.defaults,
            success: success || this.defaults.success,
            error: error || this.defaults.error
        });
    },
    
    // PUT request
    put: function(url, data = {}, success, error) {
        data.csrf_token = APP_CONFIG.csrfToken;
        data._method = 'PUT';
        
        return $.ajax({
            url: url,
            method: 'POST',
            data: data,
            ...this.defaults,
            success: success || this.defaults.success,
            error: error || this.defaults.error
        });
    },
    
    // DELETE request
    delete: function(url, data = {}, success, error) {
        data.csrf_token = APP_CONFIG.csrfToken;
        data._method = 'DELETE';
        
        return $.ajax({
            url: url,
            method: 'POST',
            data: data,
            ...this.defaults,
            success: success || this.defaults.success,
            error: error || this.defaults.error
        });
    },
    
    // Upload file
    upload: function(url, formData, success, error) {
        formData.append('csrf_token', APP_CONFIG.csrfToken);
        
        return $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            ...this.defaults,
            success: success || this.defaults.success,
            error: error || this.defaults.error
        });
    },
    
    // Load content into element
    loadContent: function(url, targetSelector, data = {}) {
        return this.get(url, data, function(response) {
            if (response.success && response.html) {
                $(targetSelector).html(response.html);
            }
        });
    }
};

// Add loading overlay if not exists
$(document).ready(function() {
    if (!$('.loading-overlay').length) {
        $('body').append(`
            <div class="loading-overlay" style="display: none;">
                <div class="spinner"></div>
            </div>
        `);
    }
});