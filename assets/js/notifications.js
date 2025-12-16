/**
 * Real-time Notification Handler
 * 
 * Updates notification icon badge and dropdown in the dashboard header
 * 
 * @package Autopuzzle_Car_Inquiry
 */

(function($) {
    'use strict';
    
    var maneliNotifications = {
        ajaxUrl: '',
        nonce: '',
        updateInterval: 30000, // 30 seconds
        
        init: function() {
            this.ajaxUrl = typeof autopuzzle_ajax !== 'undefined' ? autopuzzle_ajax.url : '';
            this.nonce = typeof autopuzzle_ajax !== 'undefined' ? autopuzzle_ajax.notifications_nonce : '';
            
            if (!this.ajaxUrl || !this.nonce) {
                console.warn('AutoPuzzle Notifications: AJAX configuration not found');
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
                    action: 'autopuzzle_get_unread_count',
                    nonce: this.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count !== undefined) {
                        var count = response.data.count;
                        var $badge = $('.header-icon-pulse');
                        var $badgeLabel = $('#header-unread-label');
                        
                        if (count > 0) {
                            $badge
                                .text('')
                                .removeAttr('data-count')
                                .removeClass('autopuzzle-initially-hidden')
                                .show();
                            if ($badgeLabel.length) {
                                var template = $badgeLabel.data('template') || '%s unread';
                                $badgeLabel.text(template.replace('%s', count));
                            }
                        } else {
                            $badge
                                .text('')
                                .removeAttr('data-count')
                                .addClass('autopuzzle-initially-hidden')
                                .hide();
                            if ($badgeLabel.length) {
                                var emptyText = $badgeLabel.data('empty') || '';
                                $badgeLabel.text(emptyText);
                            }
                        }
                    }
                },
                error: function() {
                    console.warn('AutoPuzzle Notifications: Failed to load unread count');
                }
            });
        },
        
        loadHeaderNotifications: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'autopuzzle_get_notifications',
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
                    console.warn('AutoPuzzle Notifications: Failed to load header notifications');
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
                'cash_status_changed': 'ri-shield-check-line',
                'installment_status_changed': 'ri-shield-check-line',
                'installment_tracking_changed': 'ri-shield-check-line',
                'expert_assigned': 'ri-user-line',
                'document_uploaded': 'ri-file-upload-line',
                'document_approved': 'ri-checkbox-circle-line',
                'document_rejected': 'ri-close-circle-line',
                'more_docs_requested': 'ri-file-list-3-line',
                'followup_scheduled': 'ri-calendar-todo-line',
                'followup_today': 'ri-calendar-todo-line',
                'followup_overdue': 'ri-alarm-warning-line',
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
                        action: 'autopuzzle_mark_notification_read',
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

