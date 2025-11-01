/**
 * Real-time Notification Handler
 * 
 * Updates notification icon badge and dropdown in the dashboard header
 * 
 * @package Maneli_Car_Inquiry
 */

(function($) {
    'use strict';
    
    var maneliNotifications = {
        ajaxUrl: '',
        nonce: '',
        updateInterval: 30000, // 30 seconds
        
        init: function() {
            this.ajaxUrl = typeof maneli_ajax !== 'undefined' ? maneli_ajax.url : '';
            this.nonce = typeof maneli_ajax !== 'undefined' ? maneli_ajax.notifications_nonce : '';
            
            if (!this.ajaxUrl || !this.nonce) {
                console.warn('Maneli Notifications: AJAX configuration not found');
                return;
            }
            
            this.loadUnreadCount();
            this.loadHeaderNotifications();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                maneliNotifications.loadUnreadCount();
                maneliNotifications.loadHeaderNotifications();
            }, this.updateInterval);
        },
        
        loadUnreadCount: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_unread_count',
                    nonce: this.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count !== undefined) {
                        var count = response.data.count;
                        var $badge = $('.header-icon-pulse');
                        
                        if (count > 0) {
                            $badge.text(count).show();
                        } else {
                            $badge.hide();
                        }
                    }
                },
                error: function() {
                    console.warn('Maneli Notifications: Failed to load unread count');
                }
            });
        },
        
        loadHeaderNotifications: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_notifications',
                    nonce: this.nonce,
                    limit: 5,
                    offset: 0
                },
                success: function(response) {
                    if (response.success && response.data.notifications) {
                        maneliNotifications.renderHeaderNotifications(response.data.notifications);
                    }
                },
                error: function() {
                    console.warn('Maneli Notifications: Failed to load header notifications');
                }
            });
        },
        
        renderHeaderNotifications: function(notifications) {
            var $container = $('#header-notification-scroll');
            if (!$container.length) return;
            
            if (notifications.length === 0) {
                $container.html('<li class="dropdown-item text-center py-4"><p class="text-muted mb-0">اعلانی وجود ندارد</p></li>');
                $('.empty-item1').removeClass('d-none');
                return;
            }
            
            var html = '';
            $.each(notifications, function(index, notification) {
                var iconClass = maneliNotifications.getIconForType(notification.type);
                var avatarClass = notification.is_read ? 'bg-primary' : 'bg-primary2';
                
                html += '<li class="dropdown-item">';
                html += '<div class="d-flex align-items-center">';
                html += '<div class="pe-2 lh-1">';
                html += '<span class="avatar avatar-md avatar-rounded ' + avatarClass + '">';
                html += '<i class="' + iconClass + ' lh-1 fs-16"></i>';
                html += '</span>';
                html += '</div>';
                html += '<div class="flex-grow-1 d-flex align-items-center justify-content-between">';
                html += '<div>';
                html += '<p class="mb-0 fw-medium">' + notification.title + '</p>';
                html += '<div class="text-muted fw-normal fs-12 header-notification-text text-truncate">' + notification.message + '</div>';
                html += '<div class="fw-normal fs-10 text-muted op-8">' + notification.created_at + '</div>';
                html += '</div>';
                html += '<div>';
                html += '<a href="javascript:void(0);" class="min-w-fit-content header-notification-remove" data-id="' + notification.id + '">';
                html += '<i class="ri-close-line"></i>';
                html += '</a>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</li>';
            });
            
            $container.html(html);
            maneliNotifications.bindNotificationEvents();
        },
        
        getIconForType: function(type) {
            var icons = {
                'inquiry_new': 'ri-file-add-line',
                'inquiry_assigned': 'ri-user-line',
                'inquiry_status_changed': 'ri-shield-check-line',
                'payment_received': 'ri-money-dollar-circle-line',
                'meeting_scheduled': 'ri-calendar-todo-line',
            };
            return icons[type] || 'ri-notification-line';
        },
        
        bindNotificationEvents: function() {
            var self = this;
            
            // Handle notification click
            $('.header-notification-remove').on('click', function(e) {
                e.preventDefault();
                var notificationId = $(this).data('id');
                var $item = $(this).closest('.dropdown-item');
                
                $.ajax({
                    url: self.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'maneli_mark_notification_read',
                        nonce: self.nonce,
                        notification_id: notificationId
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                                self.loadUnreadCount();
                            });
                        }
                    }
                });
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        maneliNotifications.init();
    });
    
})(jQuery);

