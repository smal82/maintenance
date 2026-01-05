// assets/js/notifications.js

const Notifications = {
    loadNotifications: function() {
        $.ajax({
            url: APP_CONFIG.baseUrl + '/api/notifications.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.renderNotifications(response.notifications);
                    this.updateCount(response.unread_count);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error loading notifications:', error);
            }
        });
    },
    
    renderNotifications: function(notifications) {
        const container = $('#notificationsList');
        container.empty();
        
        if (!notifications || notifications.length === 0) {
            container.html(`
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>Nessuna notifica</p>
                </div>
            `);
            return;
        }
        
        notifications.forEach(notification => {
            const item = $(`
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                    <div class="notification-icon">
                        <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <h5>${notification.title}</h5>
                        <p>${notification.message}</p>
                        <span class="notification-time">${Utils.formatDate(notification.created_at, 'dd/mm/yyyy HH:MM')}</span>
                    </div>
                    ${!notification.is_read ? '<button class="notification-mark-read" title="Segna come letta"><i class="fas fa-check"></i></button>' : ''}
                </div>
            `);
            
            if (notification.link) {
                item.css('cursor', 'pointer').on('click', function() {
                    window.location.href = notification.link;
                });
            }
            
            container.append(item);
        });
        
        // Add styles for notifications
        if (!$('#notification-styles').length) {
            this.addNotificationStyles();
        }
    },
    
    updateCount: function(count) {
        const badge = $('#notificationCount');
        if (count > 0) {
            badge.text(count).show();
        } else {
            badge.hide();
        }
    },
    
    getNotificationIcon: function(type) {
        const icons = {
            maintenance: 'wrench',
            asset: 'box',
            user: 'user',
            system: 'cog',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'bell';
    },
    
    markAsRead: function(notificationId) {
        $.ajax({
            url: APP_CONFIG.baseUrl + '/api/notifications.php',
            method: 'POST',
            data: {
                action: 'mark_read',
                notification_id: notificationId,
                csrf_token: APP_CONFIG.csrfToken
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.loadNotifications();
                }
            }
        });
    },
    
    markAllAsRead: function() {
        $.ajax({
            url: APP_CONFIG.baseUrl + '/api/notifications.php',
            method: 'POST',
            data: {
                action: 'mark_all_read',
                csrf_token: APP_CONFIG.csrfToken
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.loadNotifications();
                    Utils.showToast('Tutte le notifiche sono state lette', 'success');
                }
            }
        });
    },
    
    addNotificationStyles: function() {
        $('head').append(`
            <style id="notification-styles">
                .notifications-list {
                    max-height: 400px;
                    overflow-y: auto;
                }
                .notification-item {
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                    padding: 12px 16px;
                    border-bottom: 1px solid var(--color-border);
                    transition: background-color 0.2s;
                }
                .notification-item:hover {
                    background-color: var(--color-bg);
                }
                .notification-item.unread {
                    background-color: var(--color-primary-lighter);
                }
                .notification-icon {
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    background-color: var(--color-primary);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .notification-content {
                    flex: 1;
                }
                .notification-content h5 {
                    font-size: 0.875rem;
                    font-weight: 600;
                    margin-bottom: 4px;
                }
                .notification-content p {
                    font-size: 0.8125rem;
                    color: var(--color-text-light);
                    margin-bottom: 4px;
                }
                .notification-time {
                    font-size: 0.75rem;
                    color: var(--color-text-lighter);
                }
                .notification-mark-read {
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--color-text-light);
                    transition: all 0.2s;
                }
                .notification-mark-read:hover {
                    background-color: var(--color-success);
                    color: white;
                }
            </style>
        `);
    }
};

$(document).ready(function() {
    const notificationsBtn = $('#notificationsBtn');
    const notificationsMenu = $('#notificationsMenu');
    const userMenuBtn = $('#userMenuBtn');
    const userMenu = $('#userMenu');
    
    // Toggle notifications dropdown
    notificationsBtn.on('click', function(e) {
        e.stopPropagation();
        notificationsMenu.toggleClass('show');
        userMenu.removeClass('show');
        
        if (notificationsMenu.hasClass('show')) {
            Notifications.loadNotifications();
        }
    });
    
    // Toggle user menu dropdown
    userMenuBtn.on('click', function(e) {
        e.stopPropagation();
        userMenu.toggleClass('show');
        notificationsMenu.removeClass('show');
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.notifications-dropdown, .user-dropdown').length) {
            notificationsMenu.removeClass('show');
            userMenu.removeClass('show');
        }
    });
    
    // Mark notification as read
    $(document).on('click', '.notification-mark-read', function(e) {
        e.stopPropagation();
        const notificationId = $(this).closest('.notification-item').data('id');
        Notifications.markAsRead(notificationId);
    });
    
    // Load notifications on page load
    Notifications.loadNotifications();
    
    // Reload notifications every 30 seconds
    setInterval(() => {
        Notifications.loadNotifications();
    }, 30000);
});