<?php
/**
 * Notification Center Page
 * مرکز اطلاع‌رسانی - مدیریت تمام اطلاع‌رسانی‌ها
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';

// Get filters
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;

// Get notification logs
$logs = Maneli_Database::get_notification_logs([
    'type' => $type_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => ($paged - 1) * $per_page,
]);

$total_logs = Maneli_Database::get_notification_logs_count([
    'type' => $type_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
]);

$total_pages = ceil($total_logs / $per_page);

// Get statistics
$stats = Maneli_Database::get_notification_stats([
    'date_from' => $date_from ?: date('Y-m-d', strtotime('-30 days')),
    'date_to' => $date_to ?: date('Y-m-d'),
]);

// Helper function for Persian numbers
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        
        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Notification Center', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title mb-0"><?php esc_html_e('Notification Center', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->
        
        <!-- Statistics Cards -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 mb-4">
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="text-muted mb-1"><?php esc_html_e('Total Sent', 'maneli-car-inquiry'); ?></p>
                                <h4 class="mb-0"><?php echo persian_numbers(number_format($stats->sent ?? 0)); ?></h4>
                            </div>
                            <div class="avatar avatar-md avatar-rounded bg-success-transparent">
                                <i class="ri-check-line fs-20"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="text-muted mb-1"><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></p>
                                <h4 class="mb-0 text-danger"><?php echo persian_numbers(number_format($stats->failed ?? 0)); ?></h4>
                            </div>
                            <div class="avatar avatar-md avatar-rounded bg-danger-transparent">
                                <i class="ri-close-line fs-20"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="text-muted mb-1"><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></p>
                                <h4 class="mb-0 text-warning"><?php echo persian_numbers(number_format($stats->pending ?? 0)); ?></h4>
                            </div>
                            <div class="avatar avatar-md avatar-rounded bg-warning-transparent">
                                <i class="ri-time-line fs-20"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="text-muted mb-1"><?php esc_html_e('Total', 'maneli-car-inquiry'); ?></p>
                                <h4 class="mb-0"><?php echo persian_numbers(number_format($stats->total ?? 0)); ?></h4>
                            </div>
                            <div class="avatar avatar-md avatar-rounded bg-info-transparent">
                                <i class="ri-bar-chart-line fs-20"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters and Actions -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0"><?php esc_html_e('Filters', 'maneli-car-inquiry'); ?></h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#sendBulkModal">
                            <i class="ri-send-plane-line me-1"></i>
                            <?php esc_html_e('Send Bulk', 'maneli-car-inquiry'); ?>
                        </button>
                        <button type="button" class="btn btn-info btn-wave" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="ri-time-line me-1"></i>
                            <?php esc_html_e('Schedule', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></label>
                        <select name="type" class="form-control form-select">
                            <option value=""><?php esc_html_e('All Types', 'maneli-car-inquiry'); ?></option>
                            <option value="sms" <?php selected($type_filter, 'sms'); ?>><?php esc_html_e('SMS', 'maneli-car-inquiry'); ?></option>
                            <option value="telegram" <?php selected($type_filter, 'telegram'); ?>><?php esc_html_e('Telegram', 'maneli-car-inquiry'); ?></option>
                            <option value="email" <?php selected($type_filter, 'email'); ?>><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></option>
                            <option value="notification" <?php selected($type_filter, 'notification'); ?>><?php esc_html_e('In-App Notification', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></label>
                        <select name="status" class="form-control form-select">
                            <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                            <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php esc_html_e('Sent', 'maneli-car-inquiry'); ?></option>
                            <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php esc_html_e('Date From', 'maneli-car-inquiry'); ?></label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo esc_attr($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php esc_html_e('Date To', 'maneli-car-inquiry'); ?></label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php esc_html_e('Search', 'maneli-car-inquiry'); ?></label>
                        <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'maneli-car-inquiry'); ?>" value="<?php echo esc_attr($search); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="ri-search-line me-1"></i>
                            <?php esc_html_e('Filter', 'maneli-car-inquiry'); ?>
                        </button>
                        <a href="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>" class="btn btn-secondary btn-wave">
                            <?php esc_html_e('Reset', 'maneli-car-inquiry'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card custom-card">
            <div class="card-header">
                <h5 class="mb-0"><?php esc_html_e('Notification Logs', 'maneli-car-inquiry'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Created', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Sent', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <p class="text-muted mb-0"><?php esc_html_e('No notifications found.', 'maneli-car-inquiry'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc_html($log->id); ?></td>
                                        <td>
                                            <?php
                                            $type_labels = [
                                                'sms' => esc_html__('SMS', 'maneli-car-inquiry'),
                                                'telegram' => esc_html__('Telegram', 'maneli-car-inquiry'),
                                                'email' => esc_html__('Email', 'maneli-car-inquiry'),
                                                'notification' => esc_html__('Notification', 'maneli-car-inquiry'),
                                            ];
                                            $type_icons = [
                                                'sms' => 'ri-message-2-line',
                                                'telegram' => 'ri-telegram-line',
                                                'email' => 'ri-mail-line',
                                                'notification' => 'ri-notification-line',
                                            ];
                                            echo '<i class="' . esc_attr($type_icons[$log->type] ?? 'ri-question-line') . ' me-1"></i>';
                                            echo esc_html($type_labels[$log->type] ?? $log->type);
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($log->recipient); ?></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo esc_attr($log->message); ?>">
                                                <?php echo esc_html(wp_trim_words($log->message, 10)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'sent' => 'success',
                                                'failed' => 'danger',
                                                'pending' => 'warning',
                                            ];
                                            $status_class = $status_classes[$log->status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo esc_attr($status_class); ?>-transparent">
                                                <?php echo esc_html(ucfirst($log->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($log->created_at))); ?></td>
                                        <td>
                                            <?php if ($log->sent_at): ?>
                                                <?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($log->sent_at))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->status === 'failed'): ?>
                                                <button type="button" class="btn btn-sm btn-primary btn-wave retry-notification" data-log-id="<?php echo esc_attr($log->id); ?>">
                                                    <i class="ri-refresh-line"></i>
                                                    <?php esc_html_e('Retry', 'maneli-car-inquiry'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($log->error_message)): ?>
                                                <button type="button" class="btn btn-sm btn-info btn-wave" data-bs-toggle="tooltip" title="<?php echo esc_attr($log->error_message); ?>">
                                                    <i class="ri-error-warning-line"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php
                            $base_url = add_query_arg([
                                'type' => $type_filter,
                                'status' => $status_filter,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                                'search' => $search,
                            ], home_url('/dashboard/notifications-center'));
                            
                            // Previous
                            if ($paged > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged - 1, $base_url)); ?>">
                                        <?php esc_html_e('Previous', 'maneli-car-inquiry'); ?>
                                    </a>
                                </li>
                            <?php endif;
                            
                            // Page numbers
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $paged - 2 && $i <= $paged + 2)): ?>
                                    <li class="page-item <?php echo ($i == $paged) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>">
                                            <?php echo persian_numbers($i); ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $paged - 3 || $i == $paged + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif;
                            endfor;
                            
                            // Next
                            if ($paged < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged + 1, $base_url)); ?>">
                                        <?php esc_html_e('Next', 'maneli-car-inquiry'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Send Bulk Modal -->
<div class="modal fade" id="sendBulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Send Bulk Notification', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Channels', 'maneli-car-inquiry'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="bulkSMS">
                            <label class="form-check-label" for="bulkSMS"><?php esc_html_e('SMS', 'maneli-car-inquiry'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="telegram" id="bulkTelegram">
                            <label class="form-check-label" for="bulkTelegram"><?php esc_html_e('Telegram', 'maneli-car-inquiry'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="bulkEmail">
                            <label class="form-check-label" for="bulkEmail"><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="notification" id="bulkNotification">
                            <label class="form-check-label" for="bulkNotification"><?php esc_html_e('In-App Notification', 'maneli-car-inquiry'); ?></label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipients', 'maneli-car-inquiry'); ?></label>
                        <select name="recipient_type" class="form-select mb-2">
                            <option value="all"><?php esc_html_e('All Users', 'maneli-car-inquiry'); ?></option>
                            <option value="customers"><?php esc_html_e('Customers Only', 'maneli-car-inquiry'); ?></option>
                            <option value="experts"><?php esc_html_e('Experts Only', 'maneli-car-inquiry'); ?></option>
                            <option value="admins"><?php esc_html_e('Admins Only', 'maneli-car-inquiry'); ?></option>
                        </select>
                        <textarea name="custom_recipients" class="form-control" rows="3" placeholder="<?php esc_attr_e('Or enter custom recipients (phone numbers, emails, or user IDs - one per line)', 'maneli-car-inquiry'); ?>"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" id="sendBulkBtn"><?php esc_html_e('Send', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Schedule Notification Dialog', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Channel', 'maneli-car-inquiry'); ?></label>
                        <select name="channel" class="form-select" required>
                            <option value="sms"><?php esc_html_e('SMS', 'maneli-car-inquiry'); ?></option>
                            <option value="telegram"><?php esc_html_e('Telegram', 'maneli-car-inquiry'); ?></option>
                            <option value="email"><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></option>
                            <option value="notification"><?php esc_html_e('In-App Notification', 'maneli-car-inquiry'); ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?></label>
                        <input type="text" name="recipient" class="form-control" required placeholder="<?php esc_attr_e('Phone, email, chat ID, or user ID', 'maneli-car-inquiry'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Date & Time', 'maneli-car-inquiry'); ?></label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" id="scheduleBtn"><?php esc_html_e('Schedule', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Send Bulk
    $('#sendBulkBtn').on('click', function() {
        var formData = $('#bulkNotificationForm').serialize();
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_send_bulk_notification',
                nonce: maneli_ajax.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Notifications sent successfully!', 'maneli-car-inquiry')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<?php echo esc_js(__('Send', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    // Schedule
    $('#scheduleBtn').on('click', function() {
        var formData = $('#scheduleNotificationForm').serialize();
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_schedule_notification',
                nonce: maneli_ajax.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Notification Scheduled Successfully', 'maneli-car-inquiry')); ?>');
                    $('#scheduleModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<?php echo esc_js(__('Schedule', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    // Retry failed notification
    $('.retry-notification').on('click', function() {
        var logId = $(this).data('log-id');
        var btn = $(this);
        
        if (!confirm('<?php echo esc_js(__('Retry sending this notification?', 'maneli-car-inquiry')); ?>')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_retry_notification',
                nonce: maneli_ajax.nonce,
                log_id: logId
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Notification retried successfully!', 'maneli-car-inquiry')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>

