// assets/js/sidebar.js

$(document).ready(function() {
    const sidebar = $('#sidebar');
    const sidebarToggle = $('#sidebarToggle');
    const mobileMenuToggle = $('#mobileMenuToggle');
    
    // Toggle sidebar collapsed state (desktop)
    sidebarToggle.on('click', function() {
        sidebar.toggleClass('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.hasClass('collapsed'));
    });
    
    // Toggle sidebar visibility (mobile)
    mobileMenuToggle.on('click', function() {
        sidebar.toggleClass('show');
    });
    
    // Close sidebar on mobile when clicking outside
    $(document).on('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!$(e.target).closest('#sidebar, #mobileMenuToggle').length) {
                sidebar.removeClass('show');
            }
        }
    });
    
    // Close sidebar on mobile when clicking a link
    sidebar.find('a').on('click', function() {
        if (window.innerWidth <= 768) {
            sidebar.removeClass('show');
        }
    });
    
    // Restore sidebar state from localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && window.innerWidth > 768) {
        sidebar.addClass('collapsed');
    }
    
    // Handle window resize
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.removeClass('show');
                const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                sidebar.toggleClass('collapsed', collapsed);
            } else {
                sidebar.removeClass('collapsed');
            }
        }, 250);
    });
});