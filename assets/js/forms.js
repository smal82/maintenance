// assets/js/forms.js

$(document).ready(function() {
    // Auto-resize textareas
    $('textarea.form-control').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Clear error on input
    $('.form-control').on('input change', function() {
        $(this).removeClass('error');
        $(this).next('.form-error').remove();
    });
    
    // File upload preview
    $('input[type="file"]').on('change', function(e) {
        const files = e.target.files;
        const fileList = $(this).closest('.file-upload').next('.file-list');
        
        if (!fileList.length) {
            $(this).closest('.file-upload').after('<div class="file-list"></div>');
        }
        
        const container = $(this).closest('.file-upload').next('.file-list');
        container.empty();
        
        Array.from(files).forEach((file, index) => {
            const fileItem = $(`
                <div class="file-item" data-index="${index}">
                    <i class="fas fa-file file-item-icon"></i>
                    <span class="file-item-name">${file.name}</span>
                    <span class="file-item-size">${Utils.formatFileSize(file.size)}</span>
                    <button type="button" class="file-item-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            container.append(fileItem);
        });
    });
    
    // Remove file from list
    $(document).on('click', '.file-item-remove', function() {
        const fileItem = $(this).closest('.file-item');
        const fileInput = fileItem.closest('.form-group').find('input[type="file"]')[0];
        const index = fileItem.data('index');
        
        // Create new FileList without the removed file
        const dt = new DataTransfer();
        const files = fileInput.files;
        
        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }
        
        fileInput.files = dt.files;
        fileItem.remove();
    });
    
    // Character counter for textareas
    $('textarea[maxlength]').each(function() {
        const maxLength = $(this).attr('maxlength');
        const counter = $(`<div class="char-counter">0 / ${maxLength}</div>`);
        $(this).after(counter);
        
        $(this).on('input', function() {
            const length = $(this).val().length;
            counter.text(`${length} / ${maxLength}`);
        });
    });
    
    // Date input formatting
    $('input[type="date"]').on('change', function() {
        if (this.value) {
            $(this).addClass('has-value');
        } else {
            $(this).removeClass('has-value');
        }
    });
    
    // Number input validation
    $('input[type="number"]').on('input', function() {
        const min = parseFloat($(this).attr('min'));
        const max = parseFloat($(this).attr('max'));
        const value = parseFloat($(this).val());
        
        if (!isNaN(min) && value < min) {
            $(this).val(min);
        }
        if (!isNaN(max) && value > max) {
            $(this).val(max);
        }
    });
    
    // Auto-submit on select change
    $('select[data-auto-submit]').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Prevent double submission
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        
        setTimeout(() => {
            submitBtn.prop('disabled', false);
        }, 2000);
    });
    
    // Reset form button
    $(document).on('click', '[data-reset-form]', function() {
        const formId = $(this).data('reset-form');
        Utils.clearForm(formId);
    });
    
    // Form validation on submit
    $('form[data-validate]').on('submit', function(e) {
        const formId = $(this).attr('id');
        if (!Utils.validateForm(formId)) {
            e.preventDefault();
            Utils.showToast('Completa tutti i campi obbligatori', 'error');
        }
    });
});