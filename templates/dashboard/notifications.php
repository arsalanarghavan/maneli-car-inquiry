<?php
/**
 * Notifications Page Template
 * Complete notifications page that syncs with header notifications
 * Displays all new inquiries and notifications
 *
 * @package AutoPuzzle
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user data - works for all users (customer, expert, manager, admin)
$handler = Autopuzzle_Dashboard_Handler::instance();
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
    if (isset($_SESSION['autopuzzle']['user_id'])) {
        $user_id = $_SESSION['autopuzzle']['user_id'];
    } elseif (isset($_SESSION['autopuzzle_user_name'])) {
        // Fallback for old session format
        $user_id = 0; // Session-based users don't have WP user ID
    }
}

$is_admin = current_user_can('manage_autopuzzle_inquiries');
$is_expert = in_array('autopuzzle_expert', $current_user ? $current_user->roles : [], true);

// Check permission - must be logged in (either WordPress or session)
if (!is_user_logged_in() && empty($user_id)) {
    wp_redirect(home_url('/login'));
    exit;
}

require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-notification-handler.php';

// Get notification statistics - works for all users
// For session-based users without WP user ID, try to find user by phone
$notification_user_id = $user_id;
if ($user_id == 0 && isset($_SESSION['autopuzzle_user_phone'])) {
    $phone_user = get_user_by('login', $_SESSION['autopuzzle_user_phone']);
    if ($phone_user) {
        $notification_user_id = $phone_user->ID;
    }
}

$total_notifications = 0;
$unread_count = 0;
$read_count = 0;

if ($notification_user_id > 0) {
    // OPTIMIZED: Reduced limit from 1000 to 100 to save memory (load more via AJAX if needed)
    $total_notifications = count(Autopuzzle_Notification_Handler::get_notifications([
        'user_id' => $notification_user_id,
        'limit' => 100,  // Reduced from 1000 for memory efficiency
        'offset' => 0,
    ]));
    $unread_count = Autopuzzle_Notification_Handler::get_unread_count($notification_user_id);
    $read_count = $total_notifications - $unread_count;
}

$use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;

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
$nonce = wp_create_nonce('autopuzzle_notifications_nonce');
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between page-header-breadcrumb gap-3 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Notifications', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Notifications and Alerts', 'autopuzzle'); ?></h1>
            </div>
            <div class="notification-actions d-flex flex-wrap gap-2 w-100 justify-content-lg-end">
                <button type="button" class="btn btn-primary btn-wave" id="mark-all-read-btn">
                    <i class="la la-check-double me-1"></i>
                    <?php esc_html_e('Mark All as Read', 'autopuzzle'); ?>
                </button>
                <button type="button" class="btn btn-light btn-wave" id="delete-read-btn">
                    <i class="la la-trash me-1"></i>
                    <?php esc_html_e('Delete Read', 'autopuzzle'); ?>
                </button>
            </div>
        </div>
        <!-- End::page-header -->

<style>
/* Notification Statistics Cards - Inline Styles for Immediate Effect */
.card.custom-card.crm-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    position: relative !important;
    overflow: hidden !important;
    border-radius: 0.5rem !important;
    background: #fff !important;
}

.card.custom-card.crm-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
    pointer-events: none !important;
    transition: opacity 0.3s ease !important;
    opacity: 0 !important;
}

.card.custom-card.crm-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
}

.card.custom-card.crm-card:hover::before {
    opacity: 1 !important;
}

.card.custom-card.crm-card .card-body {
    position: relative !important;
    z-index: 1 !important;
    padding: 1.5rem !important;
}

.card.custom-card.crm-card:hover .p-2 {
    transform: scale(1.1) !important;
}

.card.custom-card.crm-card:hover .avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.card.custom-card.crm-card h4 {
    font-weight: 700 !important;
    letter-spacing: -0.5px !important;
    font-size: 1.75rem !important;
    color: #1f2937 !important;
    transition: color 0.3s ease !important;
}

.card.custom-card.crm-card:hover h4 {
    color: #5e72e4 !important;
}

.card.custom-card.crm-card .border-primary,
.card.custom-card.crm-card .bg-primary {
    background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important;
}

.card.custom-card.crm-card .border-success,
.card.custom-card.crm-card .bg-success {
    background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important;
}

.card.custom-card.crm-card .border-warning,
.card.custom-card.crm-card .bg-warning {
    background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important;
}

.card.custom-card.crm-card .border-secondary,
.card.custom-card.crm-card .bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

.card.custom-card.crm-card .border-danger,
.card.custom-card.crm-card .bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.notification-actions .btn {
    flex: 1 1 calc(50% - 0.5rem);
}

@media (min-width: 992px) {
    .notification-actions {
        gap: 0.5rem;
    }

    .notification-actions .btn {
        flex: 0 0 auto;
    }
}

.notification-tabs-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.notification-tabs-scroll .nav {
    flex-wrap: nowrap;
    gap: 0.5rem;
}

.notification-tabs-scroll .nav-link {
    white-space: nowrap;
}

</style>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-bell fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Notifications', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center" id="total-count"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($total_notifications) : number_format_i18n($total_notifications); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('All', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-6 col-lg-6 col-xl-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                    <i class="la la-bell-slash fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Unread', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger" id="unread-count"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($unread_count) : number_format_i18n($unread_count); ?></h4>
                            <span class="badge bg-danger-transparent rounded-pill fs-11"><?php esc_html_e('Unread', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-6 col-lg-6 col-xl-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Read', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success" id="read-count"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($read_count) : number_format_i18n($read_count); ?></h4>
                            <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('Read', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="card custom-card mb-4 notification-tabs-scroll">
            <div class="card-body">
                <ul class="nav nav-pills mb-0" id="notification-filter-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-filter="all" type="button" role="tab">
                            <i class="la la-list me-1"></i>
                            <?php esc_html_e('All Notifications', 'autopuzzle'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="unread-tab" data-filter="unread" type="button" role="tab">
                            <i class="la la-bell-slash me-1"></i>
                            <?php esc_html_e('Unread', 'autopuzzle'); ?>
                            <span class="badge bg-danger ms-1" id="unread-badge"><?php echo autopuzzle_number_format_persian($unread_count); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="read-tab" data-filter="read" type="button" role="tab">
                            <i class="la la-check-circle me-1"></i>
                            <?php esc_html_e('Read', 'autopuzzle'); ?>
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
                            <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                        </div>
                        <p class="text-muted mt-3"><?php esc_html_e('Loading notifications...', 'autopuzzle'); ?></p>
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
            noNotifications: <?php echo wp_json_encode(esc_html__('No notifications', 'autopuzzle')); ?>,
            noNotificationsDesc: <?php echo wp_json_encode(esc_html__('When you receive new notifications, they will appear here.', 'autopuzzle')); ?>,
            markAsRead: <?php echo wp_json_encode(esc_html__('Mark as Read', 'autopuzzle')); ?>,
            delete: <?php echo wp_json_encode(esc_html__('Delete', 'autopuzzle')); ?>,
            processing: <?php echo wp_json_encode(esc_html__('Processing...', 'autopuzzle')); ?>,
            markAllRead: <?php echo wp_json_encode(esc_html__('Mark All as Read', 'autopuzzle')); ?>,
            loadingError: <?php echo wp_json_encode(esc_html__('Error loading notifications', 'autopuzzle')); ?>,
            serverError: <?php echo wp_json_encode(esc_html__('Server connection error', 'autopuzzle')); ?>,
            operationFailed: <?php echo wp_json_encode(esc_html__('Operation failed', 'autopuzzle')); ?>,
            deleteAllReadConfirm: <?php echo wp_json_encode(esc_html__('Are you sure you want to delete all read notifications?', 'autopuzzle')); ?>,
            deleting: <?php echo wp_json_encode(esc_html__('Deleting...', 'autopuzzle')); ?>,
            deleteRead: <?php echo wp_json_encode(esc_html__('Delete Read', 'autopuzzle')); ?>,
            deleteConfirm: <?php echo wp_json_encode(esc_html__('Are you sure you want to delete this notification?', 'autopuzzle')); ?>,
            loadingShort: <?php echo wp_json_encode(esc_html__('Loading...', 'autopuzzle')); ?>,
            loadingLong: <?php echo wp_json_encode(esc_html__('Loading notifications...', 'autopuzzle')); ?>,
            unreadLabel: <?php echo wp_json_encode(esc_html__('%s unread', 'autopuzzle')); ?>,
            errorPrefix: <?php echo wp_json_encode(esc_html__('Error: ', 'autopuzzle')); ?>
        };

        const shouldUsePersianDigits = <?php echo $use_persian_digits ? 'true' : 'false'; ?>;
        const digitsHelper = window.autopuzzleLocale || window.autopuzzleDigits || {};
        
        // Load notifications
    function loadNotifications(filter) {
        var data = {
            action: 'autopuzzle_get_notifications',
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
            '<p class="text-muted mt-2 mb-0">' + notifTranslations.loadingShort + '</p>' +
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
            html += '<div class="notification-icon ' + bgClass + ' rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 autopuzzle-notification-icon">';
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
                action: 'autopuzzle_get_unread_count',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var unread = response.data.count || 0;
                    var total = parseInt($('#notifications-container .notification-item').length) || 0;
                    var read = total - parseInt($('#notifications-container .notification-item.unread-notification').length) || 0;
                    var unreadText = notifTranslations.unreadLabel.replace('%s', formatNotificationNumber(unread));
                    var unreadZeroText = notifTranslations.unreadLabel.replace('%s', formatNotificationNumber(0));
                    
                    $('#unread-count').text(formatNotificationNumber(unread));
                    $('#unread-badge').text(formatNotificationNumber(unread));
                    $('#read-count').text(formatNotificationNumber(read));
                    
                    if (unread === 0) {
                        $('#unread-badge').hide();
                    } else {
                        $('#unread-badge').show();
                    }
                    
                    // Update header badge if exists
                    var $headerBadge = $('.header-icon-pulse');
                    if ($headerBadge.length) {
                        if (unread > 0) {
                            $headerBadge
                                .text('')
                                .removeClass('autopuzzle-initially-hidden')
                                .show();
                        } else {
                            $headerBadge
                                .text('')
                                .addClass('autopuzzle-initially-hidden')
                                .hide();
                        }
                    }
                    
                    // Update header notification count badge if exists
                    var $headerCountBadge = $('#notifiation-data');
                    if ($headerCountBadge.length && unread > 0) {
                        $headerCountBadge.text(unreadText);
                    } else if ($headerCountBadge.length) {
                        $headerCountBadge.text(unreadZeroText);
                    }
                }
            }
        });
    }
    
    // Format localized number
    function formatNotificationNumber(num) {
        if (digitsHelper && typeof digitsHelper.ensureDigits === 'function') {
            return digitsHelper.ensureDigits(num, shouldUsePersianDigits ? 'fa' : 'en');
        }
        if (!shouldUsePersianDigits) {
            return String(num);
        }
        var persianDigitsFallback = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/\d/g, function(w) {
            return persianDigitsFallback[parseInt(w, 10)];
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
                action: 'autopuzzle_mark_all_notifications_read',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(currentFilter);
                    updateCounts();
                    // Reload header notifications if function exists
                    if (typeof autopuzzleNotifications !== 'undefined') {
                        if (autopuzzleNotifications.loadHeaderNotifications) {
                            autopuzzleNotifications.loadHeaderNotifications();
                        }
                        if (autopuzzleNotifications.loadUnreadCount) {
                            autopuzzleNotifications.loadUnreadCount();
                        }
                    }
                } else {
                    alert(notifTranslations.errorPrefix + (response.data.message || notifTranslations.operationFailed));
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
                action: 'autopuzzle_delete_all_read_notifications',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(currentFilter);
                    updateCounts();
                } else {
                    alert(notifTranslations.errorPrefix + (response.data.message || notifTranslations.operationFailed));
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
                action: 'autopuzzle_mark_notification_read',
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
                    if (typeof autopuzzleNotifications !== 'undefined') {
                        if (autopuzzleNotifications.loadHeaderNotifications) {
                            autopuzzleNotifications.loadHeaderNotifications();
                        }
                        if (autopuzzleNotifications.loadUnreadCount) {
                            autopuzzleNotifications.loadUnreadCount();
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
                action: 'autopuzzle_delete_notification',
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
                    if (typeof autopuzzleNotifications !== 'undefined') {
                        if (autopuzzleNotifications.loadHeaderNotifications) {
                            autopuzzleNotifications.loadHeaderNotifications();
                        }
                        if (autopuzzleNotifications.loadUnreadCount) {
                            autopuzzleNotifications.loadUnreadCount();
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

