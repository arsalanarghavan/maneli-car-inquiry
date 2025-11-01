<?php
/**
 * Notifications Page Template
 * Complete notifications page that syncs with header notifications
 * Displays all new inquiries and notifications
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user data - works for all users (customer, expert, manager, admin)
$handler = Maneli_Dashboard_Handler::instance();
$user_data = $handler->get_current_user_for_template();
$current_user = $user_data['wp_user'];
$user_id = $user_data['user_id'] ?? 0;

// If no WordPress user, try to get from session
if (!$current_user && is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
}

// For session-based users
if (!$current_user) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    if (isset($_SESSION['maneli']['user_id'])) {
        $user_id = $_SESSION['maneli']['user_id'];
    } elseif (isset($_SESSION['maneli_user_name'])) {
        // Fallback for old session format
        $user_id = 0; // Session-based users don't have WP user ID
    }
}

$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', $current_user ? $current_user->roles : [], true);

// Check permission - must be logged in (either WordPress or session)
if (!is_user_logged_in() && empty($user_id)) {
    wp_redirect(home_url('/login'));
    exit;
}

require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';

// Get notification statistics - works for all users
// For session-based users without WP user ID, try to find user by phone
$notification_user_id = $user_id;
if ($user_id == 0 && isset($_SESSION['maneli_user_phone'])) {
    $phone_user = get_user_by('login', $_SESSION['maneli_user_phone']);
    if ($phone_user) {
        $notification_user_id = $phone_user->ID;
    }
}

$total_notifications = 0;
$unread_count = 0;
$read_count = 0;

if ($notification_user_id > 0) {
    $total_notifications = count(Maneli_Notification_Handler::get_notifications([
        'user_id' => $notification_user_id,
        'limit' => 1000,
        'offset' => 0,
    ]));
    $unread_count = Maneli_Notification_Handler::get_unread_count($notification_user_id);
    $read_count = $total_notifications - $unread_count;
}

// Get all new inquiries (for admin and experts)
$new_inquiries = [];
if ($is_admin || $is_expert) {
    // Get new cash inquiries
    $cash_inquiries = get_posts([
        'post_type' => 'cash_inquiry',
        'post_status' => 'publish',
        'posts_per_page' => 50,
        'meta_query' => [
            [
                'key' => 'cash_inquiry_status',
                'value' => 'new',
                'compare' => '='
            ]
        ],
        'date_query' => [
            [
                'after' => date('Y-m-d', strtotime('-30 days')),
                'inclusive' => true,
            ]
        ],
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    // Get new installment inquiries
    $installment_inquiries = get_posts([
        'post_type' => 'inquiry',
        'post_status' => 'publish',
        'posts_per_page' => 50,
        'meta_query' => [
            [
                'key' => 'inquiry_status',
                'value' => 'pending',
                'compare' => '='
            ]
        ],
        'date_query' => [
            [
                'after' => date('Y-m-d', strtotime('-30 days')),
                'inclusive' => true,
            ]
        ],
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
}

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('maneli_notifications_nonce');
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Notifications', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Notifications and Alerts', 'maneli-car-inquiry'); ?></h1>
            </div>
            <div class="btn-list">
                <button type="button" class="btn btn-primary btn-wave" id="mark-all-read-btn">
                    <i class="la la-check-double me-1"></i>
                    <?php esc_html_e('Mark All as Read', 'maneli-car-inquiry'); ?>
                </button>
                <button type="button" class="btn btn-light btn-wave" id="delete-read-btn">
                    <i class="la la-trash me-1"></i>
                    <?php esc_html_e('Delete Read', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-bell fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">کل اعلان‌ها</span>
                                </div>
                                <h4 class="fw-semibold mb-0" id="total-count"><?php echo maneli_number_format_persian($total_notifications); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-danger-transparent">
                                    <i class="la la-bell-slash fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">خوانده نشده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger" id="unread-count"><?php echo maneli_number_format_persian($unread_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-check-circle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">خوانده شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success" id="read-count"><?php echo maneli_number_format_persian($read_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="card custom-card mb-4">
            <div class="card-body">
                <ul class="nav nav-pills mb-0" id="notification-filter-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-filter="all" type="button" role="tab">
                            <i class="la la-list me-1"></i>
                            همه اعلان‌ها
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="unread-tab" data-filter="unread" type="button" role="tab">
                            <i class="la la-bell-slash me-1"></i>
                            خوانده نشده
                            <span class="badge bg-danger ms-1" id="unread-badge"><?php echo maneli_number_format_persian($unread_count); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="read-tab" data-filter="read" type="button" role="tab">
                            <i class="la la-check-circle me-1"></i>
                            خوانده شده
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card custom-card">
            <div class="card-body">
                <div id="notifications-container">
                    <!-- Notifications will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="text-muted mt-3">در حال بارگذاری اعلان‌ها...</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- End::main-content -->

<script type="text/javascript">
(function() {
    'use strict';
    
    // Wait for jQuery
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined' || typeof window.jQuery !== 'undefined') {
            var $ = typeof jQuery !== 'undefined' ? jQuery : window.jQuery;
            callback($);
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 100);
        }
    }
    
    waitForJQuery(function($) {
        // Wait for DOM to be ready
        $(document).ready(function() {
        
        var currentFilter = 'all';
        var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
        var nonce = '<?php echo esc_js($nonce); ?>';
        
        // Localized strings
        const notifTranslations = {
            noNotifications: <?php echo wp_json_encode(esc_html__('No notifications', 'maneli-car-inquiry')); ?>,
            noNotificationsDesc: <?php echo wp_json_encode(esc_html__('When you receive new notifications, they will appear here.', 'maneli-car-inquiry')); ?>,
            markAsRead: <?php echo wp_json_encode(esc_html__('Mark as Read', 'maneli-car-inquiry')); ?>,
            delete: <?php echo wp_json_encode(esc_html__('Delete', 'maneli-car-inquiry')); ?>,
            processing: <?php echo wp_json_encode(esc_html__('Processing...', 'maneli-car-inquiry')); ?>,
            markAllRead: <?php echo wp_json_encode(esc_html__('Mark All as Read', 'maneli-car-inquiry')); ?>,
            loadingError: <?php echo wp_json_encode(esc_html__('Error loading notifications', 'maneli-car-inquiry')); ?>,
            serverError: <?php echo wp_json_encode(esc_html__('Server connection error', 'maneli-car-inquiry')); ?>,
            operationFailed: <?php echo wp_json_encode(esc_html__('Operation failed', 'maneli-car-inquiry')); ?>,
            deleteAllReadConfirm: <?php echo wp_json_encode(esc_html__('Are you sure you want to delete all read notifications?', 'maneli-car-inquiry')); ?>,
            deleting: <?php echo wp_json_encode(esc_html__('Deleting...', 'maneli-car-inquiry')); ?>,
            deleteRead: <?php echo wp_json_encode(esc_html__('Delete Read', 'maneli-car-inquiry')); ?>,
            deleteConfirm: <?php echo wp_json_encode(esc_html__('Are you sure you want to delete this notification?', 'maneli-car-inquiry')); ?>
        };
        
        // Load notifications
    function loadNotifications(filter) {
        var data = {
            action: 'maneli_get_notifications',
            nonce: nonce,
            limit: 100,
            offset: 0
        };
        
        if (filter === 'unread') {
            data.is_read = 0;
        } else if (filter === 'read') {
            data.is_read = 1;
        }
        
        $('#notifications-container').html(
            '<div class="text-center py-3">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
            '<p class="text-muted mt-2 mb-0">در حال بارگذاری...</p>' +
            '</div>'
        );
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    renderNotifications(response.data.notifications);
                    updateCounts();
                } else {
                    showError(notifTranslations.loadingError);
                }
            },
            error: function() {
                showError(notifTranslations.serverError);
            }
        });
    }
    
    // Render notifications
    function renderNotifications(notifications) {
        var container = $('#notifications-container');
        
        if (notifications.length === 0) {
            container.html(
                '<div class="text-center py-5">' +
                '<i class="la la-inbox fs-1 text-muted"></i>' +
                '<h5 class="text-muted mt-3">' + notifTranslations.noNotifications + '</h5>' +
                '<p class="text-muted">' + notifTranslations.noNotificationsDesc + '</p>' +
                '</div>'
            );
            return;
        }
        
        var html = '<ul class="list-unstyled mb-0">';
        
        $.each(notifications, function(index, notification) {
            var iconClass = getIconForType(notification.type);
            var bgClass = notification.is_read ? 'bg-primary-transparent' : 'bg-danger-transparent';
            var unreadClass = notification.is_read ? '' : 'unread-notification';
            var link = notification.link || 'javascript:void(0);';
            
            html += '<li class="notification-item ' + unreadClass + '" data-id="' + notification.id + '">';
            html += '<div class="d-flex align-items-start gap-3 p-3 border-bottom">';
            
            // Icon
            html += '<div class="notification-icon ' + bgClass + ' rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 maneli-notification-icon">';
            html += '<i class="' + iconClass + ' fs-5"></i>';
            html += '</div>';
            
            // Content
            html += '<div class="flex-grow-1">';
            if (link && link !== 'javascript:void(0);') {
                html += '<h6 class="mb-1"><a href="' + link + '" class="text-default">' + escapeHtml(notification.title) + '</a></h6>';
            } else {
                html += '<h6 class="mb-1">' + escapeHtml(notification.title) + '</h6>';
            }
            html += '<p class="text-muted mb-2 fs-13">' + escapeHtml(notification.message) + '</p>';
            html += '<div class="d-flex justify-content-between align-items-center">';
            html += '<span class="text-muted fs-12"><i class="la la-clock me-1"></i>' + notification.created_at + '</span>';
            html += '<div class="btn-group btn-group-sm">';
            if (!notification.is_read) {
                html += '<button type="button" class="btn btn-soft-primary btn-sm mark-read-btn" data-id="' + notification.id + '" title="' + notifTranslations.markAsRead + '">';
                html += '<i class="la la-check me-1"></i>' + notifTranslations.markAsRead;
                html += '</button>';
            }
            html += '<button type="button" class="btn btn-soft-danger btn-sm delete-btn" data-id="' + notification.id + '" title="' + notifTranslations.delete + '">';
            html += '<i class="la la-trash"></i>';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</li>';
        });
        
        html += '</ul>';
        container.html(html);
    }
    
    // Get icon for notification type
    function getIconForType(type) {
        var icons = {
            'inquiry_new': 'la la-file-plus',
            'inquiry_assigned': 'la la-user-check',
            'inquiry_status_changed': 'la la-exchange-alt',
            'payment_received': 'la la-money-bill-wave',
            'meeting_scheduled': 'la la-calendar-check',
            'inquiry_updated': 'la la-sync',
            'message_received': 'la la-envelope',
        };
        return icons[type] || 'la la-bell';
    }
    
    // Escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Update counts
    function updateCounts() {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_get_unread_count',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var unread = response.data.count || 0;
                    var total = parseInt($('#notifications-container .notification-item').length) || 0;
                    var read = total - parseInt($('#notifications-container .notification-item.unread-notification').length) || 0;
                    
                    $('#unread-count').text(formatPersianNumber(unread));
                    $('#unread-badge').text(formatPersianNumber(unread));
                    $('#read-count').text(formatPersianNumber(read));
                    
                    if (unread === 0) {
                        $('#unread-badge').hide();
                    } else {
                        $('#unread-badge').show();
                    }
                    
                    // Update header badge if exists
                    var $headerBadge = $('.header-icon-pulse');
                    if ($headerBadge.length) {
                        if (unread > 0) {
                            $headerBadge.text(formatPersianNumber(unread)).show();
                        } else {
                            $headerBadge.hide();
                        }
                    }
                    
                    // Update header notification count badge if exists
                    var $headerCountBadge = $('#notifiation-data');
                    if ($headerCountBadge.length && unread > 0) {
                        $headerCountBadge.text(formatPersianNumber(unread) + ' خوانده نشده');
                    } else if ($headerCountBadge.length) {
                        $headerCountBadge.text('0 خوانده نشده');
                    }
                }
            }
        });
    }
    
    // Format Persian number
    function formatPersianNumber(num) {
        var persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/\d/g, function(w) {
            return persianDigits[parseInt(w)];
        });
    }
    
    // Show error message
    function showError(message) {
        $('#notifications-container').html(
            '<div class="alert alert-danger alert-dismissible fade show">' +
            '<i class="la la-exclamation-triangle me-2"></i>' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
    }
    
    // Filter tabs click
    $('#notification-filter-tabs button').on('click', function() {
        $(this).addClass('active').siblings().removeClass('active');
        currentFilter = $(this).data('filter');
        loadNotifications(currentFilter);
    });
    
    // Mark all as read
    $('#mark-all-read-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="la la-spinner la-spin me-1"></i>' + notifTranslations.processing);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_mark_all_notifications_read',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(currentFilter);
                    updateCounts();
                    // Reload header notifications if function exists
                    if (typeof maneliNotifications !== 'undefined') {
                        if (maneliNotifications.loadHeaderNotifications) {
                            maneliNotifications.loadHeaderNotifications();
                        }
                        if (maneliNotifications.loadUnreadCount) {
                            maneliNotifications.loadUnreadCount();
                        }
                    }
                } else {
                    alert('خطا: ' + (response.data.message || notifTranslations.operationFailed));
                }
            },
            error: function() {
                alert(notifTranslations.serverError);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="la la-check-double me-1"></i>' + notifTranslations.markAllRead);
            }
        });
    });
    
    // Delete all read
    $('#delete-read-btn').on('click', function() {
        if (!confirm(notifTranslations.deleteAllReadConfirm)) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="la la-spinner la-spin me-1"></i>' + notifTranslations.deleting);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_delete_all_read_notifications',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(currentFilter);
                    updateCounts();
                } else {
                    alert('خطا: ' + (response.data.message || notifTranslations.operationFailed));
                }
            },
            error: function() {
                alert(notifTranslations.serverError);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="la la-trash me-1"></i>' + notifTranslations.deleteRead);
            }
        });
    });
    
    // Mark as read (delegate)
    $(document).on('click', '.mark-read-btn', function() {
        var notificationId = $(this).data('id');
        var $btn = $(this);
        var $item = $(this).closest('.notification-item');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_mark_notification_read',
                nonce: nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    $item.removeClass('unread-notification');
                    $btn.closest('.btn-group').html(
                        '<button type="button" class="btn btn-soft-danger btn-sm delete-btn" data-id="' + notificationId + '" title="' + notifTranslations.delete + '">' +
                        '<i class="la la-trash"></i>' +
                        '</button>'
                    );
                    updateCounts();
                    // Reload header notifications if function exists
                    if (typeof maneliNotifications !== 'undefined') {
                        if (maneliNotifications.loadHeaderNotifications) {
                            maneliNotifications.loadHeaderNotifications();
                        }
                        if (maneliNotifications.loadUnreadCount) {
                            maneliNotifications.loadUnreadCount();
                        }
                    }
                }
            },
            error: function() {
                alert(notifTranslations.serverError);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Delete notification (delegate)
    $(document).on('click', '.delete-btn', function() {
        var notificationId = $(this).data('id');
        var $item = $(this).closest('.notification-item');
        
        if (!confirm(notifTranslations.deleteConfirm)) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'maneli_delete_notification',
                nonce: nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#notifications-container ul li').length === 0) {
                            loadNotifications(currentFilter);
                        }
                        updateCounts();
                    // Reload header notifications if function exists
                    if (typeof maneliNotifications !== 'undefined') {
                        if (maneliNotifications.loadHeaderNotifications) {
                            maneliNotifications.loadHeaderNotifications();
                        }
                        if (maneliNotifications.loadUnreadCount) {
                            maneliNotifications.loadUnreadCount();
                        }
                    }
                    });
                }
            },
            error: function() {
                alert(notifTranslations.serverError);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Initial load
    loadNotifications(currentFilter);
    
    // Auto refresh every 30 seconds
    setInterval(function() {
        loadNotifications(currentFilter);
        updateCounts();
    }, 30000);
    
    // Sync with header on page visibility change
    $(document).on('visibilitychange', function() {
        if (!document.hidden) {
            loadNotifications(currentFilter);
            updateCounts();
        }
    });
    
        }); // End of $(document).ready
    }); // End of waitForJQuery
})(); // End of IIFE
</script>

<style>
.notification-item {
    transition: all 0.3s ease;
    border-radius: 8px;
    margin-bottom: 4px;
}

.notification-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.notification-item.unread-notification {
    background-color: rgba(var(--danger-rgb), 0.05);
    border-right: 3px solid var(--danger-color);
}

.notification-icon {
    flex-shrink: 0;
}

.notification-item .btn-group {
    opacity: 0;
    transition: opacity 0.2s;
}

.notification-item:hover .btn-group {
    opacity: 1;
}

#notifications-container ul li:last-child {
    border-bottom: none !important;
}

.spinner-border-sm {
    width: 1.5rem;
    height: 1.5rem;
}
</style>

